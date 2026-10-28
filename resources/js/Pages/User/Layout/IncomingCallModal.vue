<template>
  <div :class="rtlClass">
    <!-- Modal for active call -->
    <div v-if="showModal && activeCall && !isMinimized" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
      <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md">
        <h2 class="text-xl font-semibold mb-4">Incoming Call</h2>
        <div class="mb-4">
          <p class="text-gray-700"><strong>Contact Name:</strong> {{ activeCall.contact.full_name }}</p>
          <p class="text-gray-700"><strong>Phone Number:</strong> {{ activeCall.contact.phone }}</p>
          <p class="text-gray-700"><strong>Call Time:</strong> {{ formattedTime(activeCall) }}</p>
          <p v-if="callDurations[activeCall.id]" class="text-gray-700"><strong>Call Duration:</strong> {{ callDurations[activeCall.id] }}</p>
        </div>
        <div class="flex justify-between space-x-2">
          <button @click="acceptCall(activeCall)" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">
            Accept
          </button>
          <button @click="terminateCall(activeCall)" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600">
            Terminate
          </button>
          <button @click="minimizeModal" class="bg-gray-300 text-gray-700 px-4 py-2 rounded hover:bg-gray-400">
            Minimize
          </button>
        </div>
      </div>
    </div>

    <!-- Minimized floating UI for active call -->
    <div v-if="showModal && activeCall && isMinimized" class="fixed bottom-4 right-4 bg-white rounded-lg shadow-lg p-4 flex items-center space-x-2 z-50">
      <div>
        <p class="text-sm font-semibold">{{ activeCall.contact.full_name }}</p>
        <p class="text-xs text-gray-500">{{ activeCall.contact.phone }}</p>
        <p v-if="callDurations[activeCall.id]" class="text-xs text-gray-500">Duration: {{ callDurations[activeCall.id] }}</p>
      </div>
      <button @click="restoreModal" class="text-blue-500 hover:text-blue-700">
        Restore
      </button>
      <button @click="terminateCall(activeCall)" class="text-red-500 hover:text-red-700">
        Terminate
      </button>
    </div>

    <!-- Waiting calls list -->
    <div v-if="waitingCalls.length > 0" class="fixed top-4 right-4 w-64 space-y-2 z-50">
      <div v-for="call in waitingCalls" :key="call.id" class="bg-white rounded-lg shadow-lg p-4 flex items-center space-x-2">
        <div>
          <p class="text-sm font-semibold">{{ call.contact.full_name }}</p>
          <p class="text-xs text-gray-500">{{ call.contact.phone }}</p>
          <p class="text-xs text-gray-500">Waiting</p>
        </div>
        <button @click="setActiveCall(call)" class="text-blue-500 hover:text-blue-700">
          Answer
        </button>
        <button @click="terminateCall(call)" class="text-red-500 hover:text-red-700">
          Terminate
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, defineProps } from 'vue';
import { usePage, router } from '@inertiajs/vue3';
import { toast } from 'vue3-toastify';
import { useRtl } from '@/Composables/useRtl';
import { getEchoInstance } from '../../../echo';

// Props
const props = defineProps({
  playSound: {
    type: Function,
    default: null,
  },
});

// RTL support
const { rtlClass } = useRtl();

// Reactive state
const showModal = ref(false);
const isMinimized = ref(false);
const activeCall = ref(null);
const waitingCalls = ref([]);
const callDurations = ref({});
const callStartTimes = ref({});
let durationIntervals = {};

// Computed property for formatted time
const formattedTime = computed(() => (call) => {
  if (!call.time) return '';
  return new Date(call.time).toLocaleString();
});

// Computed properties from Inertia page props
const config = computed(() => usePage().props.config);
const organization = computed(() => usePage().props.organization);

// Utility to get config value by key
const getValueByKey = (key) => {
  const found = config.value.find(item => item.key === key);
  return found ? found.value : '';
};

// Format duration in MM:SS
const formatDuration = (seconds) => {
  const minutes = Math.floor(seconds / 60);
  const secs = Math.floor(seconds % 60);
  return `${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
};

// Start call duration timer
const startCallDuration = (callId) => {
  callStartTimes.value[callId] = Date.now();
  durationIntervals[callId] = setInterval(() => {
    const elapsedSeconds = Math.floor((Date.now() - callStartTimes.value[callId]) / 1000);
    callDurations.value[callId] = formatDuration(elapsedSeconds);
  }, 1000);
};

// Stop call duration timer
const stopCallDuration = (callId) => {
  if (durationIntervals[callId]) {
    clearInterval(durationIntervals[callId]);
    delete durationIntervals[callId];
  }
  delete callDurations.value[callId];
  delete callStartTimes.value[callId];
};

// Create a new WebRTC SDP answer compatible with Meta's WhatsApp API
const createSdpAnswer = async (remoteSdp) => {
  const peerConnection = new RTCPeerConnection({
    iceServers: [{ urls: 'stun:stun.l.google.com:19302' }],
  });

  // Audio element to play the incoming stream
  const remoteAudio = document.createElement('audio');
  remoteAudio.autoplay = true;
  document.body.appendChild(remoteAudio);

  try {
    // Capture microphone audio
    const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
    stream.getTracks().forEach(track => peerConnection.addTrack(track, stream));

    // When remote track arrives, play it
    peerConnection.addEventListener('track', event => {
      remoteAudio.srcObject = event.streams[0];
    });

    // Apply Meta's offer SDP
    await peerConnection.setRemoteDescription({
      type: 'offer',
      sdp: remoteSdp,
    });

    // Create our SDP answer
    const answer = await peerConnection.createAnswer();
    await peerConnection.setLocalDescription(answer);

    // Wait for ICE gathering
    await new Promise(resolve => {
      if (peerConnection.iceGatheringState === 'complete') return resolve();
      peerConnection.addEventListener('icegatheringstatechange', () => {
        if (peerConnection.iceGatheringState === 'complete') resolve();
      });
    });

    // Keep connection open (don’t close it)
    return { sdp: peerConnection.localDescription.sdp, peerConnection, remoteAudio };
  } catch (error) {
    console.error('Error creating SDP answer:', error);
    throw error;
  }
};

// Handle incoming call event
const handleIncomingCall = (event) => {
  console.log('Incoming call event:', event);
  const newCall = {
    id: event.call_id,
    contact: event.contact,
    session: event.session,
    time: new Date(),
  };

  // Check if call already exists
  if (waitingCalls.value.some(call => call.id === newCall.id) || (activeCall.value && activeCall.value.id === newCall.id)) {
    return;
  }

  if (!activeCall.value) {
    // No active call, set as active
    activeCall.value = newCall;
    showModal.value = true;
    isMinimized.value = false;
  } else {
    // Add to waiting list
    waitingCalls.value.push(newCall);
  }
  if (props.playSound) props.playSound();
};

// Handle call status update event
const handleCallUpdate = (event) => {
  console.log('Update call event:', event);
  if (activeCall.value && event.call_id === activeCall.value.id) {
    if (event.status === 'accepted') {
      toast.success('Call accepted successfully', { autoClose: 3000 });
      startCallDuration(event.call_id);
    } else if (['rejected', 'terminated', 'completed'].includes(event.status)) {
      toast.info('Call terminated successfully', { autoClose: 3000 });
      stopCallDuration(event.call_id);
      closeCall(activeCall.value);
    }
  } else if (waitingCalls.value.some(call => call.id === event.call_id)) {
    if (['rejected', 'terminated', 'completed'].includes(event.status)) {
      toast.info('Waiting call terminated', { autoClose: 3000 });
      waitingCalls.value = waitingCalls.value.filter(call => call.id !== event.call_id);
    }
  }
};

// Accept call
const acceptCall = async (call) => {
  try {
    const remoteSdp = call.session.sdp.sdp;
    if (!remoteSdp) throw new Error('No remote SDP found in call data');

    const { sdp, peerConnection, remoteAudio } = await createSdpAnswer(remoteSdp);

    // Send the SDP answer to backend
    router.post('/settings/whatsapp/calls/accept', {
      call_id: call.id,
      sdp,
      type: 'answer',
    }, {
      onSuccess: () => {
        // Success toast is handled by UpdateCallEvent
        peerConnection.addEventListener('connectionstatechange', () => {
          console.log('Call connection state:', peerConnection.connectionState);
          if (peerConnection.connectionState === 'connected') {
            console.log('Voice channel active');
          }
          if (['disconnected', 'failed'].includes(peerConnection.connectionState)) {
            remoteAudio.remove();
            peerConnection.close();
            stopCallDuration(call.id);
            closeCall(call);
          }
        });
      },
      onError: (errors) => {
        toast.error('Failed to accept call: ' + (errors.message || 'Unknown error'), { autoClose: 3000 });
        peerConnection.close();
        remoteAudio.remove();
        stopCallDuration(call.id);
        closeCall(call);
      },
    });
  } catch (error) {
    toast.error('Failed to accept call: ' + error.message, { autoClose: 3000 });
    stopCallDuration(call.id);
    closeCall(call);
  }
};

// Terminate call
const terminateCall = async (call) => {
  try {
    router.post('/settings/whatsapp/calls/terminate', {
      call_id: call.id,
    }, {
      onSuccess: () => {
        // Success toast is handled by UpdateCallEvent
        stopCallDuration(call.id);
        closeCall(call);
      },
      onError: (errors) => {
        toast.error('Failed to terminate call: ' + (errors.message || 'Unknown error'), { autoClose: 3000 });
        stopCallDuration(call.id);
        closeCall(call);
      },
    });
  } catch (error) {
    toast.error('Failed to terminate call: ' + error.message, { autoClose: 3000 });
    stopCallDuration(call.id);
    closeCall(call);
  }
};

// Set active call from waiting list and initiate connection
const setActiveCall = async (call) => {
  if (activeCall.value) {
    // Terminate the current active call
    await terminateCall(activeCall.value);
  }
  // Set the selected call as active
  activeCall.value = call;
  waitingCalls.value = waitingCalls.value.filter(c => c.id !== call.id);
  showModal.value = true;
  isMinimized.value = false;
  // Automatically accept the call to initiate WebRTC connection
  await acceptCall(call);
};

// Minimize modal
const minimizeModal = () => {
  isMinimized.value = true;
};

// Restore modal
const restoreModal = () => {
  isMinimized.value = false;
};

// Close call
const closeCall = (call) => {
  if (activeCall.value && activeCall.value.id === call.id) {
    activeCall.value = null;
    showModal.value = false;
    isMinimized.value = false;
    // Promote next waiting call to active
    if (waitingCalls.value.length > 0) {
      activeCall.value = waitingCalls.value[0];
      waitingCalls.value = waitingCalls.value.slice(1);
      showModal.value = true;
      isMinimized.value = false;
      // Automatically accept the promoted call
      acceptCall(activeCall.value);
    }
  } else {
    waitingCalls.value = waitingCalls.value.filter(c => c.id !== call.id);
  }
};

// Setup Echo listeners
onMounted(() => {
  const echo = getEchoInstance(
    getValueByKey('pusher_app_key'),
    getValueByKey('pusher_app_cluster')
  );

  const channel = echo.channel(`calls.ch${organization.value.id}`);
  channel.listen('IncomingCallEvent', handleIncomingCall);
  channel.listen('UpdateCallEvent', handleCallUpdate);
});

// Cleanup Echo listeners and duration timers
onUnmounted(() => {
  const echo = getEchoInstance(
    getValueByKey('pusher_app_key'),
    getValueByKey('pusher_app_cluster')
  );
  echo.leave(`calls.ch${organization.value.id}`);
  Object.keys(durationIntervals).forEach(callId => stopCallDuration(callId));
});
</script>

<style scoped>
/* Tailwind is used inline, but scoped styles can be added here if needed */
</style>