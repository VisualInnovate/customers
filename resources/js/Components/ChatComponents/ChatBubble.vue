<script setup>
import { usePage } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import { GoogleMap, Marker } from 'vue3-google-map';
import Modal from './Modal.vue';
import ChatReply from './ChatReply.vue';
import { sharedMediaState } from './mediaStore.js';
import { emitter } from './eventBus';

// Props
const props = defineProps({
  replyTo: Object,
  content: Object,
  type: String,
  activeRecordId: [String, Number, null],
  setActiveRecord: Function,
  referencedMessage: Object, // For reactions
});

// Define emits for reply and send-audio events
const emit = defineEmits(['reply', 'send-audio']);

// Reactive state
const downloading = ref(false);
const isModalOpen = ref(false);
const isImageModalOpen = ref(false);
const copiedRef = ref(null);
const isExpanded = ref(false);
const showMenu = ref(false);
const isRecording = ref(false);
const mediaRecorder = ref(null);
const audioChunks = ref([]);
const audioBlob = ref(null);
const audioUrl = ref(null);

// Memoize parsed metadata
const parsedMetadata = computed(() => {
  try {
    return JSON.parse(props.content.metadata);
  } catch (error) {
    console.error('Error parsing metadata:', error);
    return {};
  }
});

// Check if this message has a reaction
const hasReaction = computed(() => {
  return props.referencedMessage && props.referencedMessage.metadata && JSON.parse(props.referencedMessage.metadata).type === 'reaction' && JSON.parse(props.referencedMessage.metadata).reaction?.message_id === props.content.id;
});

// Get reaction emoji if present (handles Unicode escape sequences)
const reactionEmoji = computed(() => {
  if (hasReaction.value) {
    try {
      const metadata = JSON.parse(props.referencedMessage.metadata);
      let emoji = metadata.reaction?.emoji || '';
      
      // Handle Unicode escape sequences like \ud83d\ude02
      if (emoji.includes('\\u')) {
        emoji = JSON.parse(`"${emoji}"`);
      }
      
      return emoji;
    } catch (error) {
      console.error('Error parsing reaction emoji:', error);
      return '';
    }
  }
  return '';
});

// Handle media play
const handleMediaPlay = (event) => {
  if (sharedMediaState.currentMedia && sharedMediaState.currentMedia !== event.target) {
    sharedMediaState.currentMedia.pause();
    sharedMediaState.currentMedia.currentTime = 0;
  }
  sharedMediaState.currentMedia = event.target;
};

// Handle expansion toggle
const toggleExpand = () => {
  if (isExpanded.value) {
    isExpanded.value = false;
    props.setActiveRecord(null);
  } else {
    isExpanded.value = true;
    props.setActiveRecord(props.content.id);
  }
};

// Sync isExpanded with activeRecordId
watch(() => props.activeRecordId, (newId) => {
  isExpanded.value = newId === props.content.id;
});

// Download handling
const downloadClicked = () => {
  downloading.value = true;
  setTimeout(() => {
    downloading.value = false;
  }, 2000);
};

// File extension mapping
const getExtension = (fileFormat) => {
  const formatMap = {
    'text/plain': 'TXT',
    'application/pdf': 'PDF',
    'application/powerpoint': 'PPT',
    'application/vnd.ms-powerpoint': 'PPT',
    'application/msword': 'DOC',
    'application/vnd.ms-excel': 'XLS',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document': 'DOCX',
    'application/vnd.openxmlformats-officedocument.presentationml.presentation': 'PPTX',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet': 'XLSX',
    'audio/mpeg': 'MP3',
    'audio/wav': 'WAV',
  };
  return formatMap[fileFormat] || 'Unknown';
};

// Format file size
const formatFileSize = (sizeInBytes) => {
  if (sizeInBytes === 0) return '0 Bytes';
  const k = 1024;
  const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
  const i = parseInt(Math.floor(Math.log(sizeInBytes) / Math.log(k)));
  return Math.round((sizeInBytes / Math.pow(k, i)) * 100) / 100 + ' ' + sizes[i];
};

// Format contact display name
const getContactDisplayName = (metadata) => {
  const contacts = parsedMetadata.value.contacts || [];
  if (contacts.length === 1) {
    const contact = contacts[0];
    return contact.name.formatted_name || `${contact.name.first_name} ${contact.name.last_name}`;
  } else if (contacts.length > 1) {
    const firstName = contacts[0].name.first_name;
    const otherContactsCount = contacts.length - 1;
    return `${firstName} and ${otherContactsCount} other contacts`;
  }
  return 'No contacts available';
};

// Generate location coordinates
const location = () => {
  const item = parsedMetadata.value;
  return { lat: item.location?.latitude || 0, lng: item.location?.longitude || 0 };
};

// Generate a Google Maps search link for the location
const locationLink = computed(() => {
  const item = parsedMetadata.value;
  const lat = item.location?.latitude;
  const lng = item.location?.longitude;
  if (lat != null && lng != null) {
    return `https://www.google.com/maps/search/?api=1&query=${lat},${lng}`;
  }
  return '';
});

// Copy any text to clipboard (reuse existing logic)
const copyLink = async (text) => {
  if (!text) return;
  await copyItem(text);
};

// Get config value by key
const getValueByKey = (key) => {
  const config = computed(() => usePage().props.config);
  const found = config.value.find((item) => item.key === key);
  return found ? found.value : '';
};

// Determine chat status
const chatStatus = (logs) => {
  let status = 'sent';
  logs.forEach((log) => {
    try {
      const metadata = JSON.parse(log.metadata);
      const logStatus = metadata.status;
      if (logStatus === 'failed') {
        status = 'failed';
        return;
      } else if (logStatus === 'read') {
        status = 'read';
      } else if (logStatus === 'delivered' && status !== 'read') {
        status = 'delivered';
      }
    } catch (error) {
      console.error('Error parsing log metadata:', error);
    }
  });
  return status;
};

// Get errors from logs
const getErrors = (logIndex = 0) => {
  try {
    if (!props.content?.logs?.[logIndex]?.metadata) {
      return [];
    }
    const metadata = JSON.parse(props.content.logs[logIndex].metadata);
    return metadata.errors || [];
  } catch (error) {
    console.error('Error parsing metadata:', error);
    return [];
  }
};

// Copy item to clipboard
const copyItem = async (token) => {
  copiedRef.value = token;
  const tempInput = document.createElement('textarea');
  tempInput.value = token;
  document.body.appendChild(tempInput);
  tempInput.select();
  document.execCommand('copy');
  document.body.removeChild(tempInput);
  setTimeout(() => {
    copiedRef.value = null;
  }, 2000);
};

// Open image modal
const openImageModal = () => {
  isImageModalOpen.value = true;
};

// Close image modal
const closeImageModal = () => {
  isImageModalOpen.value = false;
};

// Handle reply action
const handleReply = () => {
  emitter.emit('update-data', { data: props.content });
  showMenu.value = false;
};

// Audio recording functionality
const startRecording = async () => {
  try {
    const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
    mediaRecorder.value = new MediaRecorder(stream);
    audioChunks.value = [];

    mediaRecorder.value.ondataavailable = (event) => {
      if (event.data.size > 0) {
        audioChunks.value.push(event.data);
      }
    };

    mediaRecorder.value.onstop = () => {
      audioBlob.value = new Blob(audioChunks.value, { type: 'audio/mp3' });
      audioUrl.value = URL.createObjectURL(audioBlob.value);
      // Emit the audio data to parent component
      emit('send-audio', {
        media: {
          path: audioUrl.value,
          type: 'audio/mp3',
          name: `recording-${new Date().toISOString()}.mp3`,
          size: audioBlob.value.size,
        },
        metadata: JSON.stringify({
          type: 'audio',
        }),
      });
      // Stop all tracks to release the microphone
      stream.getTracks().forEach(track => track.stop());
    };

    mediaRecorder.value.start();
    isRecording.value = true;
  } catch (error) {
    console.error('Error starting audio recording:', error);
    isRecording.value = false;
  }
};

const stopRecording = () => {
  if (mediaRecorder.value && mediaRecorder.value.state === 'recording') {
    mediaRecorder.value.stop();
    isRecording.value = false;
  }
};

const toggleRecording = () => {
  if (isRecording.value) {
    stopRecording();
  } else {
    startRecording();
  }
};

const errors = getErrors();
</script>

<template>
  <div class="flex flex-col">
    <!-- Message Display -->
    <div
      class="flex relative flex-col p-2 pt-4 my-1 text-sm rounded-lg"
      :class="props.type === 'outbound' ? 'ml-auto rounded-tr-none bg-[#d8fad4] speech-bubble-right' : 'mr-auto rounded-tl-none bg-white speech-bubble-left'"
    >
      <!-- Menu Button for Message Options -->
      <button
        class="absolute z-50 top-[-1px] right-[-5px] text-gray-500 hover:text-gray-700"
        aria-label="Message options"
        @click="showMenu = !showMenu"
      >
        <svg
          xmlns="http://www.w3.org/2000/svg"
          width="20"
          height="20"
          viewBox="0 0 24 24"
        >
          <path
            fill="currentColor"
            d="M12 16a2 2 0 0 1 2 2a2 2 0 0 1-2 2a2 2 0 0 1-2-2a2 2 0 0 1 2-2m0-6a2 2 0 0 1 2 2a2 2 0 0 1-2 2a2 2 0 0 1-2-2a2 2 0 0 1 2-2m0-6a2 2 0 0 1 2 2a2 2 0 0 1-2 2a2 2 0 0 1-2-2a2 2 0 0 1 2-2Z"
          />
        </svg>
      </button>

      <!-- Dropdown Menu -->
      <div
        v-if="showMenu"
        class="absolute top-4 right-0 bg-white shadow-lg rounded-md py-1 z-50 min-w-[150px]"
      >
        <ul>
          <li
            class="flex items-center px-4 py-2 hover:bg-gray-100 cursor-pointer"
            @click="handleReply"
          >
            <svg
              xmlns="http://www.w3.org/2000/svg"
              width="20"
              height="20"
              viewBox="0 0 24 24"
              class="mr-2"
            >
              <path
                fill="currentColor"
                d="M10 9V5l-7 7l7 7v-4.1c5 0 8.5 1.6 11 5.1c-2-6.5-7-11-11-11"
              />
            </svg>
            {{ $t('Reply') }}
          </li>
        </ul>
      </div>

      <div class="relative">
        <!-- Display Reply Message if context exists -->
        <ChatReply
          v-if="props.replyTo"
          :content="props.replyTo"
          :show-remove="false"
        />

        <!-- Reaction formatting (only if this is a reaction message) -->
        <div v-if="parsedMetadata.type === 'reaction'" class="max-w-[300px]">
          <span class="text-2xl">{{ parsedMetadata.reaction?.emoji }}</span>
        </div>

        <!-- Text message formatting with header -->
        <div v-else-if="parsedMetadata.type === 'text'" class="max-w-[300px]">
          <p v-if="parsedMetadata.header?.text" class="font-semibold normal-case whitespace-pre-wrap">
            {{ parsedMetadata.header.text }}
          </p>
          <p class="normal-case whitespace-pre-wrap">
            {{ parsedMetadata.text?.body }}
          </p>
          <div
            v-if="parsedMetadata?.buttons"
            class="mr-auto text-sm text-[#00a5f4] flex flex-col relative max-w-[25em]"
          >
            <div
              v-for="(item, index) in parsedMetadata?.buttons"
              :key="index"
              class="flex justify-center items-center space-x-2 rounded-lg bg-white h-10 my-[0.1em]"
            >
              <span>
                <svg
                  v-if="item.type === 'COPY_CODE'"
                  xmlns="http://www.w3.org/2000/svg"
                  width="18"
                  height="18"
                  viewBox="0 0 24 24"
                >
                  <path
                    fill="currentColor"
                    d="M19 21H8V7h11m0-2H8a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h11a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2m-3-4H4a2 2 0 0 0-2 2v14h2V3h12V1Z"
                  />
                </svg>
                <svg
                  v-else-if="item.type === 'PHONE_NUMBER'"
                  xmlns="http://www.w3.org/2000/svg"
                  width="18"
                  height="18"
                  viewBox="0 0 24 24"
                >
                  <g fill="none">
                    <path
                      fill="currentColor"
                      d="M20 16v4c-2.758 0-5.07-.495-7-1.325c-3.841-1.652-6.176-4.63-7.5-7.675C4.4 8.472 4 5.898 4 4h4l1 4l-3.5 3c1.324 3.045 3.659 6.023 7.5 7.675L16 15l4 1z"
                    />
                    <path
                      stroke="currentColor"
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M13 18.675c1.93.83 4.242 1.325 7 1.325v-4l-4-1l-3 3.675zm0 0C9.159 17.023 6.824 14.045 5.5 11m0 0C4.4 8.472 4 5.898 4 4h4l1 4l-3.5 3z"
                    />
                  </g>
                </svg>
              </span>
              <span>{{ item.text }}</span>
            </div>
          </div>
        </div>

        <!-- Unsupported Messages -->
        <div v-else-if="parsedMetadata.type === 'unsupported'" class="max-w-[300px]">
          <span class="text-red-500">
            <svg
              xmlns="http://www.w3.org/2000/svg"
              width="24"
              height="24"
              viewBox="0 0 24 24"
            >
              <path
                fill="none"
                stroke="currentColor"
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3"
              />
            </svg>
          </span>
          <p class="normal-case whitespace-pre-wrap">
            {{ $t('Error code') }} : {{ parsedMetadata.errors?.[0]?.code }}
          </p>
          <p class="normal-case whitespace-pre-wrap">
            {{ parsedMetadata.errors?.[0]?.error_data?.details }}
          </p>
        </div>

        <!-- Button formatting -->
        <div v-else-if="parsedMetadata.type === 'button'" class="max-w-[300px]">
          <p class="normal-case whitespace-pre-wrap">
            {{ parsedMetadata.button?.text }}
          </p>
        </div>

        <!-- Interactive button formatting -->
        <div v-else-if="parsedMetadata.type === 'interactive'" class="max-w-[300px]">
          <div v-if="parsedMetadata.interactive?.type === 'button_reply'">
            <p class="normal-case whitespace-pre-wrap">
              {{ parsedMetadata.interactive?.button_reply?.title }}
            </p>
          </div>
          <div v-else-if="parsedMetadata.interactive?.type === 'list_reply'">
            <p class="normal-case whitespace-pre-wrap">
              {{ parsedMetadata.interactive?.list_reply?.title }}
            </p>
            <p class="normal-case whitespace-pre-wrap">
              {{ parsedMetadata.interactive?.list_reply?.description }}
            </p>
          </div>
          <div v-else-if="parsedMetadata.interactive?.type === 'call_permission_reply'">
            <p class="normal-case whitespace-pre-wrap">
              Call Permission: {{ parsedMetadata.interactive?.call_permission_reply?.response }}
            </p>
            <p class="normal-case whitespace-pre-wrap text-slate-500 text-[12px]">
              Expires: {{ new Date(parsedMetadata.interactive?.call_permission_reply?.expiration_timestamp * 1000).toLocaleString() }}
            </p>
            <p class="normal-case whitespace-pre-wrap text-slate-500 text-[12px]">
              Source: {{ parsedMetadata.interactive?.call_permission_reply?.response_source }}
            </p>
          </div>
        </div>

        <!-- Image formatting with zoom on click -->
        <div v-else-if="parsedMetadata.type === 'image'">
          <img
            v-if="props.content.media != null"
            :src="props.content?.media?.path"
            alt="Image"
            class="mb-2 max-w-[300px] cursor-pointer"
            @click="openImageModal"
          />
          <div
            v-else
            class="flex justify-center items-center space-x-4 text-slate-500"
          >
            <svg
              xmlns="http://www.w3.org/2000/svg"
              width="24"
              height="24"
              viewBox="0 0 24 24"
            >
              <g fill="none">
                <path
                  d="M24 0v24H0V0h24ZM12.593 23.258l-.011.002l-.071.035l-.02.004l-.014-.004l-.071-.035c-.01-.004-.019-.001-.024.005l-.004.01l-.017.428l.005.02l.01.013l.104.074l.015.004l.012-.004l.104-.074l.012-.016l.004-.017l-.017-.427c-.002-.01-.009-.017-.017-.018Zm.265-.113l-.013.002l-.185.093l-.01.01l-.003.011l.018.43l.005.012l.008.007l.201.093c.012.004.023 0 .029-.008l.004-.014l-.034-.614c-.003-.012-.01-.02-.20-.022Zm-.715.002a.023.023 0 0 0-.027.006l-.006.014l-.034.614c0 .012.007.02.017.024l.015-.002l.201-.093l.01-.008l.004-.011l.017-.43l-.003-.012l-.01-.01l-.184-.092Z"
                />
                <path
                  fill="currentColor"
                  d="m13.299 3.148l8.634 14.954a1.5 1.5 0 0 1-1.299 2.25H3.366a1.5 1.5 0 0 1-1.299-2.25l8.634-14.954c.577-1 2.02-1 2.598 0ZM12 4.898L4.232 18.352h15.536L12 4.898ZM12 15a1 1 0 1 1 0 2a1 1 0 0 1 0-2Zm0-7a1 1 0 0 1 1 1v4a1 1 0 0 1-2 0V9a1 1 0 0 1 1-1Z"
                />
              </g>
            </svg>
            {{ $t('Content not available') }}
          </div>
          <div v-if="parsedMetadata.image?.caption" class="max-w-[300px]">
            {{ parsedMetadata.image?.caption }}
          </div>
          <div
            v-if="parsedMetadata?.buttons"
            class="mr-auto text-sm text-[#00a5f4] flex flex-col relative max-w-[25em]"
          >
            <div
              v-for="(item, index) in parsedMetadata?.buttons"
              :key="index"
              class="flex justify-center items-center space-x-2 rounded-lg bg-white h-10 my-[0.1em]"
            >
              <span>
                <svg
                  v-if="item.type === 'COPY_CODE'"
                  xmlns="http://www.w3.org/2000/svg"
                  width="18"
                  height="18"
                  viewBox="0 0 24 24"
                >
                  <path
                    fill="currentColor"
                    d="M19 21H8V7h11m0-2H8a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h11a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2m-3-4H4a2 2 0 0 0-2 2v14h2V3h12V1Z"
                  />
                </svg>
                <svg
                  v-else-if="item.type === 'PHONE_NUMBER'"
                  xmlns="http://www.w3.org/2000/svg"
                  width="18"
                  height="18"
                  viewBox="0 0 24 24"
                >
                  <g fill="none">
                    <path
                      fill="currentColor"
                      d="M20 16v4c-2.758 0-5.07-.495-7-1.325c-3.841-1.652-6.176-4.63-7.5-7.675C4.4 8.472 4 5.898 4 4h4l1 4l-3.5 3c1.324 3.045 3.659 6.023 7.5 7.675L16 15l4 1z"
                    />
                    <path
                      stroke="currentColor"
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M13 18.675c1.93.83 4.242 1.325 7 1.325v-4l-4-1l-3 3.675zm0 0C9.159 17.023 6.824 14.045 5.5 11m0 0C4.4 8.472 4 5.898 4 4h4l1 4l-3.5 3z"
                    />
                  </g>
                </svg>
                <img
                  v-else-if="item.type === 'URL'"
                  :src="'/images/icons/link.png'"
                  class="h-4"
                />
                <img
                  v-else
                  :src="'/images/icons/reply.png'"
                  class="h-4"
                />
              </span>
              <span>{{ item.text }}</span>
            </div>
          </div>
        </div>

        <!-- Document formatting -->
        <div v-else-if="parsedMetadata.type === 'document'">
          <div class="relative w-[300px]">
            <div class="flex pt-2 space-x-2 w-full h-1/3 bg-white opacity-90">
              <div>
                <svg
                  v-if="getExtension(props.content?.media?.type) === 'PDF'"
                  xmlns="http://www.w3.org/2000/svg"
                  width="36"
                  height="36"
                  viewBox="0 0 1024 1024"
                >
                  <path
                    fill="red"
                    d="m531.3 574.4l.3-1.4c5.8-23.9 13.1-53.7 7.4-80.7c-3.8-21.3-19.5-29.6-32.9-30.2c-15.8-.7-29.9 8.3-33.4 21.4c-6.6 24-.7 56.8 10.1 98.6c-13.6 32.4-35.3 79.5-51.2 107.5c-29.6 15.3-69.3 38.9-75.2 68.7c-1.2 5.5.2 12.5 3.5 18.8c3.7 7 9.6 12.4 16.5 15c3 1.1 6.6 2 10.8 2c17.6 0 46.1-14.2 84.1-79.4c5.8-1.9 11.8-3.9 17.6-5.9c27.2-9.2 55.4-18.8 80.9-23.1c28.2 15.1 60.3 24.8 82.1 24.8c21.6 0 30.1-12.8 33.3-20.5c5.6-13.5 2.9-30.5-6.2-39.6c-13.2-13-45.3-16.4-95.3-10.2c-24.6-15-40.7-35.4-52.4-65.8zM421.6 726.3c-13.9 20.2-24.4 30.3-30.1 34.7c6.7-12.3 19.8-25.3 30.1-34.7zm87.6-235.5c5.2 8.9 4.5 35.8.5 49.4c-4.9-19.9-5.6-48.1-2.7-51.4c.8.1 1.5.7 2.2 2zm-1.6 120.5c10.7 18.5 24.2 34.4 39.1 46.2c-21.6 4.9-41.3 13-58.9 20.2c-4.2 1.7-8.3 3.4-12.3 5c13.3-24.1 24.4-51.4 32.1-71.4zm155.6 65.5c.1.2.2.5-.4.9h-.2l-.2.3c-.8.5-9 5.3-44.3-8.6c40.6-1.9 45 7.3 45.1 7.4zm191.4-388.2L639.4 73.4c-6-6-14.1-9.4-22.6-9.4H192c-17.7 0-32 14.3-32 32v832c0 17.7 14.3 32 32 32h640c17.7 0 32-14.3 32-32V311.3c0-8.5-3.4-16.7-9.4-22.7zM790.2 326H602V137.8L790.2 326zm1.8 562H232V136h302v216a42 42 0 0 0 42 42h216v494z"
                  />
                </svg>
                <svg
                  v-if="getExtension(props.content?.media?.type) === 'XLSX' || getExtension(props.content?.media?.type) === 'XLS'"
                  xmlns="http://www.w3.org/2000/svg"
                  width="36"
                  height="36"
                  viewBox="0 0 32 32"
                >
                  <defs>
                    <linearGradient
                      id="vscodeIconsFileTypeExcel0"
                      x1="4.494"
                      x2="13.832"
                      y1="-2092.086"
                      y2="-2075.914"
                      gradientTransform="translate(0 2100)"
                      gradientUnits="userSpaceOnUse"
                    >
                      <stop offset="0" stop-color="#18884f" />
                      <stop offset=".5" stop-color="#117e43" />
                      <stop offset="1" stop-color="#0b6631" />
                    </linearGradient>
                  </defs>
                  <path
                    fill="#185c37"
                    d="M19.581 15.35L8.512 13.4v14.409A1.192 1.192 0 0 0 9.705 29h19.1A1.192 1.192 0 0 0 30 27.809V22.5Z"
                  />
                  <path
                    fill="#21a366"
                    d="M19.581 3H9.705a1.192 1.192 0 0 0-1.193 1.191V9.5L19.581 16l5.861 1.95L30 16V9.5Z"
                  />
                  <path fill="#107c41" d="M8.512 9.5h11.069V16H8.512Z" />
                  <path
                    d="M16.434 8.2H8.512v16.25h7.922a1.2 1.2 0 0 0 1.194-1.191V9.391A1.2 1.2 0 0 0 16.434 8.2Z"
                    opacity=".1"
                  />
                  <path
                    d="M15.783 8.85H8.512V25.1h7.271a1.2 1.2 0 0 0 1.194-1.191V10.041a1.2 1.2 0 0 0-1.194-1.191Z"
                    opacity=".2"
                  />
                  <path
                    d="M15.783 8.85H8.512V23.8h7.271a1.2 1.2 0 0 0 1.194-1.191V10.041a1.2 1.2 0 0 0-1.194-1.191Z"
                    opacity=".2"
                  />
                  <path
                    d="M15.132 8.85h-6.62V23.8h6.62a1.2 1.2 0 0 0 1.194-1.191V10.041a1.2 1.2 0 0 0-1.194-1.191Z"
                    opacity=".2"
                  />
                  <path
                    fill="url(#vscodeIconsFileTypeExcel0)"
                    d="M3.194 8.85h11.938a1.193 1.193 0 0 1 1.194 1.191v11.918a1.193 1.193 0 0 1-1.194 1.191H3.194A1.192 1.192 0 0 1 2 21.959V10.041A1.192 1.192 0 0 1 3.194 8.85Z"
                  />
                  <path
                    fill="#fff"
                    d="m5.7 19.873l2.511-3.884l-2.3-3.862h1.847L9.013 14.6c.116.234.2.408.238.524h.017c.082-.188.169-.369.26-.546l1.342-2.447h1.7l-2.359 3.84l2.419 3.905h-1.809l-1.45-2.711A2.355 2.355 0 0 1 9.2 16.8h-.024a1.688 1.688 0 0 1-.168.351l-1.493 2.722Z"
                  />
                  <path
                    fill="#33c481"
                    d="M28.806 3h-9.225v6.5H30V4.191A1.192 1.192 0 0 0 28.806 3Z"
                  />
                  <path fill="#107c41" d="M19.581 16H30v6.5H19.581Z" />
                </svg>
                <svg
                  v-if="getExtension(props.content?.media?.type) === 'DOC' || getExtension(props.content?.media?.type) === 'DOCX'"
                  xmlns="http://www.w3.org/2000/svg"
                  width="36"
                  height="36"
                  viewBox="0 0 32 32"
                >
                  <defs>
                    <linearGradient
                      id="vscodeIconsFileTypeWord0"
                      x1="4.494"
                      x2="13.832"
                      y1="-1712.086"
                      y2="-1695.914"
                      gradientTransform="translate(0 1720)"
                      gradientUnits="userSpaceOnUse"
                    >
                      <stop offset="0" stop-color="#2368c4" />
                      <stop offset=".5" stop-color="#1a5dbe" />
                      <stop offset="1" stop-color="#1146ac" />
                    </linearGradient>
                  </defs>
                  <path
                    fill="#41a5ee"
                    d="M28.806 3H9.705a1.192 1.192 0 0 0-1.193 1.191V9.5l11.069 3.25L30 9.5V4.191A1.192 1.192 0 0 0 28.806 3Z"
                  />
                  <path
                    fill="#2b7cd3"
                    d="M30 9.5H8.512V16l11.069 1.95L30 16Z"
                  />
                  <path
                    fill="#185abd"
                    d="M8.512 16v6.5l10.418 1.3L30 22.5V16Z"
                  />
                  <path
                    fill="#103f91"
                    d="M9.705 29h19.1A1.192 1.192 0 0 0 30 27.809V22.5H8.512v5.309A1.192 1.192 0 0 0 9.705 29Z"
                  />
                  <path
                    d="M16.434 8.2H8.512v16.25h7.922a1.2 1.2 0 0 0 1.194-1.191V9.391A1.2 1.2 0 0 0 16.434 8.2Z"
                    opacity=".1"
                  />
                  <path
                    d="M15.783 8.85H8.512V25.1h7.271a1.2 1.2 0 0 0 1.194-1.191V10.041a1.2 1.2 0 0 0-1.194-1.191Z"
                    opacity=".2"
                  />
                  <path
                    d="M15.783 8.85H8.512V23.8h7.271a1.2 1.2 0 0 0 1.194-1.191V10.041a1.2 1.2 0 0 0-1.194-1.191Z"
                    opacity=".2"
                  />
                  <path
                    d="M15.132 8.85h-6.62V23.8h6.62a1.2 1.2 0 0 0 1.194-1.191V10.041a1.2 1.2 0 0 0-1.194-1.191Z"
                    opacity=".2"
                  />
                  <path
                    fill="url(#vscodeIconsFileTypeWord0)"
                    d="M3.194 8.85h11.938a1.193 1.193 0 0 1 1.194 1.191v11.918a1.193 1.193 0 0 1-1.194 1.191H3.194A1.192 1.192 0 0 1 2 21.959V10.041A1.192 1.192 0 0 1 3.194 8.85Z"
                  />
                  <path
                    fill="#fff"
                    d="M6.9 17.988c.023.184.039.344.046.481h.028c.01-.13.032-.287.065-.47s.062-.338.089-.465l1.255-5.407h1.624l1.3 5.326a7.761 7.761 0 0 1 .162 1h.022a7.6 7.6 0 0 1 .135-.975l1.039-5.358h1.477l-1.824 7.748h-1.727l-1.237-5.126q-.054-.222-.122-.578t-.084-.52h-.021q-.021.189-.084.561c-.042.249-.075.432-.1.552L7.78 19.871H6.024L4.19 12.127h1.5l1.131 5.418a4.469 4.469 0 0 1 .079.443Z"
                  />
                </svg>
                <svg
                  v-if="getExtension(props.content?.media?.type) === 'PPT' || getExtension(props.content?.media?.type) === 'PPTX'"
                  xmlns="http://www.w3.org/2000/svg"
                  width="36"
                  height="36"
                  viewBox="0 0 32 32"
                >
                  <defs>
                    <linearGradient
                      id="vscodeIconsFileTypePowerpoint0"
                      x1="4.494"
                      x2="13.832"
                      y1="-1748.086"
                      y2="-1731.914"
                      gradientTransform="translate(0 1756)"
                      gradientUnits="userSpaceOnUse"
                    >
                      <stop offset="0" stop-color="#ca4c28" />
                      <stop offset=".5" stop-color="#c5401e" />
                      <stop offset="1" stop-color="#b62f14" />
                    </linearGradient>
                  </defs>
                  <path
                    fill="#ed6c47"
                    d="M18.93 17.3L16.977 3h-.146A12.9 12.9 0 0 0 3.953 15.854V16Z"
                  />
                  <path
                    fill="#ff8f6b"
                    d="M17.123 3h-.146v13l6.511 2.6L30 16v-.146A12.9 12.9 0 0 0 17.123 3Z"
                  />
                  <path
                    fill="#d35230"
                    d="M30 16v.143A12.905 12.905 0 0 1 17.12 29h-.287a12.907 12.907 0 0 1-12.88-12.857V16Z"
                  />
                  <path
                    d="M17.628 9.389V23.26a1.2 1.2 0 0 1-.742 1.1a1.16 1.16 0 0 1-.45.091H7.027a10.08 10.08 0 0 1-.521-.650a12.735 12.735 0 0 1-2.553-7.657v-.286A12.705 12.705 0 0 1 6.05 8.85a8.82 8.82 0 0 1 .456-.650h9.93a1.2 1.2 0 0 1 1.192 1.189Z"
                    opacity=".1"
                  />
                  <path
                    d="M16.977 10.04v13.871a1.15 1.15 0 0 1-.091.448a1.2 1.2 0 0 1-1.1.741H7.62q-.309-.314-.593-.650a10.08 10.08 0 0 1-.521-.650a12.735 12.735 0 0 1-2.553-7.657v-.286A12.705 12.705 0 0 1 6.05 8.85h9.735a1.2 1.2 0 0 1 1.192 1.19Z"
                    opacity=".2"
                  />
                  <path
                    d="M16.977 10.04v12.571a1.2 1.2 0 0 1-1.192 1.189H6.506a12.735 12.735 0 0 1-2.553-7.657v-.286A12.705 12.705 0 0 1 6.05 8.85h9.735a1.2 1.2 0 0 1 1.192 1.19Z"
                    opacity=".2"
                  />
                  <path
                    d="M16.326 10.04v12.571a1.2 1.2 0 0 1-1.192 1.189H6.506a12.735 12.735 0 0 1-2.553-7.657v-.286A12.705 12.705 0 0 1 6.05 8.85h9.084a1.2 1.2 0 0 1 1.192 1.19Z"
                    opacity=".2"
                  />
                  <path
                    fill="url(#vscodeIconsFileTypePowerpoint0)"
                    d="M3.194 8.85h11.938a1.193 1.193 0 0 1 1.194 1.191v11.918a1.193 1.193 0 0 1-1.194 1.191H3.194A1.192 1.192 0 0 1 2 21.959V10.041A1.192 1.192 0 0 1 3.194 8.85Z"
                  />
                  <path
                    fill="#fff"
                    d="M9.293 12.028a3.287 3.287 0 0 1 2.174.636a2.27 2.27 0 0 1 .756 1.841a2.555 2.555 0 0 1-.373 1.376a2.49 2.49 0 0 1-1.059.935a3.607 3.607 0 0 1-1.591.334H7.687v2.8H6.141v-7.922ZM7.686 15.94h1.331a1.735 1.735 0 0 0 1.177-.351a1.3 1.3 0 0 0 .4-1.025q0-1.309-1.525-1.310H7.686v2.686Z"
                  />
                </svg>
              </div>
              <div>
                <p class="overflow-ellipsis overflow-hidden line-clamp-2 w-[200px]">
                  {{ props.content?.media?.name }}
                </p>
                <div class="flex items-center text-slate-500 text-[11px]">
                  <span>{{ getExtension(props.content?.media?.type) }}</span>
                  <span>
                    <svg
                      xmlns="http://www.w3.org/2000/svg"
                      width="12"
                      height="12"
                      viewBox="0 0 24 24"
                    >
                      <circle
                        cx="12.1"
                        cy="12.1"
                        r="1"
                        fill="none"
                        stroke="currentColor"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                      />
                    </svg>
                  </span>
                  <span>{{ formatFileSize(props.content?.media?.size) }}</span>
                </div>
              </div>
              <a
                :href="props.content?.media?.path"
                :download="props.content?.media?.name"
                target="_blank"
                @click="downloadClicked"
                class="flex justify-end w-full"
              >
                <svg
                  v-if="!downloading"
                  xmlns="http://www.w3.org/2000/svg"
                  width="30"
                  height="30"
                  viewBox="0 0 24 24"
                >
                  <path
                    fill="currentColor"
                    d="M4.025 11.975q0 2.825 1.725 5t4.4 2.825q.375.1.6.425t.225.725q0 .4-.275.663t-.625.187q-3.5-.7-5.775-3.437t-2.275-6.388q0-3.65 2.288-6.387T10.1 2.15q.35-.075.625.188T11 3q0 .4-.225.725t-.6.425q-2.7.65-4.425 2.825t-1.725 5Zm8.95 1.15l1.85-1.85q.3-.3.725-.3t.725.3q.3.3.288.725t-.313.725L12.675 16.3q-.3.3-.7.3t-.7-.3L7.65 12.65q-.3-.3-.288-.713t.313-.712q.3-.3.713-.3t.712.3l1.875 1.9V8q0-.425.288-.713T11.975 7q.425 0 .713.288t.287.712v5.125Zm3.05 5.75q.375-.225.813-.2t.762.35q.3.3.25.713t-.4.637q-.8.5-1.675.863t-1.8.562q-.4.075-.7-.175t-.3-.675q0-.425.263-.75t.687-.45q.55-.15 1.075-.375t1.025-.5ZM13.975 4.2q-.425-.125-.7-.45T13 3q0-.425.288-.675t.687-.175q.95.2 1.838.562T17.5 3.6q.35.225.375.625t-.25.7q-.3.325-.738.350t-.812-.2q-.5-.275-1.025-.5T13.975 4.2Zm5.75 9.75q.125-.425.463-.7t.762-.275q.425 0 .688.325t.162.725q-.2.95-.575 1.812t-.9 1.663q-.225.35-.625.35t-.675-.275q-.3-.3-.337-.725t.187-.8q.275-.5.488-1.012t.362-1.088Zm-.85-6.075q-.225-.375-.188-.788t.338-.712q.275-.275.675-.263t.625.338q.55.8.925 1.688t.575 1.837q.075.4-.187.7t-.688.3q-.425 0-.762-.287t-.463-.713q-.15-.55-.363-1.075t-.487-1.025Z"
                  />
                </svg>
                <svg
                  v-else
                  xmlns="http://www.w3.org/2000/svg"
                  width="30"
                  height="30"
                  viewBox="0 0 24 24"
                >
                  <g
                    fill="none"
                    stroke="currentColor"
                    stroke-linecap="round"
                    stroke-width="2"
                  >
                    <path
                      stroke-dasharray="2 4"
                      stroke-dashoffset="6"
                      d="M12 3C16.9706 3 21 7.02944 21 12C21 16.9706 16.9706 21 12 21"
                    >
                      <animate
                        attributeName="stroke-dashoffset"
                        dur="0.6s"
                        repeatCount="indefinite"
                        values="6;0"
                      />
                    </path>
                    <path
                      stroke-dasharray="30"
                      stroke-dashoffset="30"
                      d="M12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3"
                    >
                      <animate
                        fill="freeze"
                        attributeName="stroke-dashoffset"
                        begin="0.1s"
                        dur="0.3s"
                        values="30;0"
                      />
                    </path>
                    <path
                      stroke-dasharray="10"
                      stroke-dashoffset="10"
                      d="M12 8v7.5"
                    >
                      <animate
                        fill="freeze"
                        attributeName="stroke-dashoffset"
                        begin="0.5s"
                        dur="0.2s"
                        values="10;0"
                      />
                    </path>
                    <path
                      stroke-dasharray="6"
                      stroke-dashoffset="6"
                      d="M12 15.5l3.5 -3.5M12 15.5l-3.5 -3.5"
                    >
                      <animate
                        fill="freeze"
                        attributeName="stroke-dashoffset"
                        begin="0.7s"
                        dur="0.2s"
                        values="6;0"
                      />
                    </path>
                  </g>
                </svg>
              </a>
            </div>
            <!-- Document preview when expanded -->
            <div
              v-if="isExpanded && ['PDF', 'DOCX', 'PPTX', 'XLSX'].includes(getExtension(props.content?.media?.type))"
            >
              <iframe
                :src="props.content?.media?.path"
                width="300"
                height="400"
                class="mt-2"
              ></iframe>
            </div>
          </div>
          <div
            v-if="parsedMetadata?.buttons"
            class="mr-auto text-sm text-[#00a5f4] flex flex-col relative max-w-[25em]"
          >
            <div
              v-for="(item, index) in parsedMetadata?.buttons"
              :key="index"
              class="flex justify-center items-center space-x-2 rounded-lg bg-white h-10 my-[0.1em]"
            >
              <span>
                <svg
                  v-if="item.type === 'COPY_CODE'"
                  xmlns="http://www.w3.org/2000/svg"
                  width="18"
                  height="18"
                  viewBox="0 0 24 24"
                >
                  <path
                    fill="currentColor"
                    d="M19 21H8V7h11m0-2H8a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h11a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2m-3-4H4a2 2 0 0 0-2 2v14h2V3h12V1Z"
                  />
                </svg>
                <svg
                  v-else-if="item.type === 'PHONE_NUMBER'"
                  xmlns="http://www.w3.org/2000/svg"
                  width="18"
                  height="18"
                  viewBox="0 0 24 24"
                >
                  <g fill="none">
                    <path
                      fill="currentColor"
                      d="M20 16v4c-2.758 0-5.07-.495-7-1.325c-3.841-1.652-6.176-4.63-7.5-7.675C4.4 8.472 4 5.898 4 4h4l1 4l-3.5 3c1.324 3.045 3.659 6.023 7.5 7.675L16 15l4 1z"
                    />
                    <path
                      stroke="currentColor"
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M13 18.675c1.93.83 4.242 1.325 7 1.325v-4l-4-1l-3 3.675zm0 0C9.159 17.023 6.824 14.045 5.5 11m0 0C4.4 8.472 4 5.898 4 4h4l1 4l-3.5 3z"
                    />
                  </g>
                </svg>
                <img
                  v-else-if="item.type === 'URL'"
                  :src="'/images/icons/link.png'"
                  class="h-4"
                />
                <img
                  v-else
                  :src="'/images/icons/reply.png'"
                  class="h-4"
                />
              </span>
              <span>{{ item.text }}</span>
            </div>
          </div>
          <!-- Expand/Collapse button for documents -->
          <div
            v-if="['PDF', 'DOCX', 'PPTX', 'XLSX'].includes(getExtension(props.content?.media?.type))"
            class="pt-2 mt-2 text-center border-t cursor-pointer"
            @click="toggleExpand"
          >
            {{ isExpanded ? $t('Collapse') : $t('View') }}
          </div>
        </div>

        <!-- Location formatting -->
        <div v-else-if="parsedMetadata.type === 'location'">
          <div v-if="!isExpanded">
            <div
              class="pt-2 mt-2 text-center border-t cursor-pointer"
              @click="toggleExpand"
            >
              {{ $t('View Location') }}
            </div>
          </div>
          <div v-else>
            <div class="mb-2">
              <p class="font-semibold">{{ parsedMetadata.location?.name }}</p>
              <p class="text-sm text-gray-600">{{ parsedMetadata.location?.address }}</p>
            </div>
            <!-- link and copy functionality -->
            <div class="flex items-center space-x-2 mb-2">
              <a
                v-if="locationLink"
                :href="locationLink"
                target="_blank"
                class="text-blue-500 underline text-sm"
              >
                {{ $t('Open in Maps') }}
              </a>
              <button
                v-if="locationLink"
                @click="copyLink(locationLink)"
                class="text-gray-500 hover:text-gray-700"
                aria-label="Copy link"
              >
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M8 17H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v2"/>
                  <rect x="8" y="8" width="12" height="12" rx="2" ry="2"/>
                </svg>
              </button>
            </div>
            <GoogleMap
              :api-key="getValueByKey('google_maps_api_key')"
              style="width: 300px; height: 200px"
              :center="location()"
              :zoom="15"
            >
              <Marker :options="{ position: location() }" />
            </GoogleMap>
            <div
              class="pt-2 mt-2 text-center border-t cursor-pointer"
              @click="toggleExpand"
            >
              {{ $t('Collapse') }}
            </div>
          </div>
        </div>

        <!-- Sticker formatting -->
        <div v-else-if="parsedMetadata.type === 'sticker'">
          <img
            v-if="props.content.media != null"
            :src="props.content?.media?.path"
            alt="Image"
            class="mb-2 max-w-[100px]"
          />
          <div
            v-else
            class="flex justify-center items-center space-x-4 text-slate-500"
          >
            <svg
              xmlns="http://www.w3.org/2000/svg"
              width="24"
              height="24"
              viewBox="0 0 24 24"
            >
              <g fill="none">
                <path
                  d="M24 0v24H0V0h24ZM12.593 23.258l-.011.002l-.071.035l-.02.004l-.014-.004l-.071-.035c-.01-.004-.019-.001-.024.005l-.004.01l-.017.428l.005.02l.01.013l.104.074l.015.004l.012-.004l.104-.074l.012-.016l.004-.017l-.017-.427c-.002-.01-.009-.017-.017-.018Zm.265-.113l-.013.002l-.185.093l-.01.01l-.003.011l.018.43l.005.012l.008.007l.201.093c.012.004.023 0 .029-.008l.004-.014l-.034-.614c-.003-.012-.01-.02-.20-.022Zm-.715.002a.023.023 0 0 0-.027.006l-.006.014l-.034.614c0 .012.007.02.017.024l.015-.002l.201-.093l.01-.008l.004-.011l.017-.43l-.003-.012l-.01-.01l-.184-.092Z"
                />
                <path
                  fill="currentColor"
                  d="m13.299 3.148l8.634 14.954a1.5 1.5 0 0 1-1.299 2.25H3.366a1.5 1.5 0 0 1-1.299-2.25l8.634-14.954c.577-1 2.02-1 2.598 0ZM12 4.898L4.232 18.352h15.536L12 4.898ZM12 15a1 1 0 1 1 0 2a1 1 0 0 1 0-2Zm0-7a1 1 0 0 1 1 1v4a1 1 0 0 1-2 0V9a1 1 0 0 1 1-1Z"
                />
              </g>
            </svg>
            {{ $t('Content not available') }}
          </div>
        </div>

        <!-- Contacts formatting -->
        <div v-else-if="parsedMetadata.type === 'contacts'">
          <div class="flex space-x-3 w-[300px] items-center">
            <div class="p-3 rounded-full bg-slate-200">
              <svg
                xmlns="http://www.w3.org/2000/svg"
                width="24"
                height="24"
                viewBox="0 0 512 512"
              >
                <path
                  fill="currentColor"
                  d="M332.64 64.58C313.18 43.57 286 32 256 32c-30.16 0-57.43 11.5-76.8 32.38c-19.58 21.11-29.12 49.8-26.88 80.78C156.76 206.28 203.27 256 256 256s99.16-49.71 103.67-110.82c2.27-30.7-7.33-59.33-27.03-80.6ZM432 480H80a31 31 0 0 1-24.2-11.13c-6.5-7.77-9.12-18.38-7.18-29.11C57.06 392.94 83.4 353.61 124.8 326c36.78-24.51 83.37-38 131.2-38s94.42 13.5 131.2 38c41.4 27.6 67.74 66.93 76.18 113.75c1.94 10.73-.68 21.34-7.18 29.11A31 31 0 0 1 432 480Z"
                />
              </svg>
            </div>
            <div>
              {{ getContactDisplayName(props.content.metadata) }}
            </div>
          </div>
          <!-- Expand/Collapse for contacts -->
          <div
            class="pt-2 mt-2 text-center border-t cursor-pointer"
            @click="toggleExpand"
          >
            {{ isExpanded ? $t('Collapse') : $t('View') }}
          </div>
          <!-- Expanded contact details -->
          <div v-if="isExpanded">
            <div
              v-for="(contact, index) in parsedMetadata.contacts"
              :key="index"
              class="mt-2"
            >
              <p>
                <strong>Name:</strong>
                {{
                  contact.name.formatted_name ||
                  `${contact.name.first_name} ${contact.name.last_name}`
                }}
              </p>
              <p v-if="contact.phones">
                <strong>Phone:</strong> {{ contact.phones[0]?.phone }}
              </p>
              <p v-if="contact.emails">
                <strong>Email:</strong> {{ contact.emails[0]?.email }}
              </p>
            </div>
          </div>
        </div>

        <!-- Audio formatting -->
        <div v-else-if="parsedMetadata.type === 'audio'">
          <audio
            v-if="props.content.media != null"
            controls
            @play="handleMediaPlay"
          >
            <source :src="props.content?.media?.path" :type="props.content?.media?.type" />
            {{ $t('Your browser does not support the audio element') }}
          </audio>
          <div
            v-else
            class="text-slate-500 flex justify-center items-center space-x-4"
          >
            <svg
              xmlns="http://www.w3.org/2000/svg"
              width="24"
              height="24"
              viewBox="0 0 24 24"
            >
              <g fill="none">
                <path
                  d="M24 0v24H0V0h24ZM12.593 23.258l-.011.002l-.071.035l-.02.004l-.014-.004l-.071-.035c-.01-.004-.019-.001-.024.005l-.004.01l-.017.428l.005.02l.01.013l.104.074l.015.004l.012-.004l.104-.074l.012-.016l.004-.017l-.017-.427c-.002-.01-.009-.017-.017-.018Zm.265-.113l-.013.002l-.185.093l-.01.01l-.003.011l.018.43l.005.012l.008.007l.201.093c.012.004.023 0 .029-.008l.004-.014l-.034-.614c-.003-.012-.01-.02-.20-.022Zm-.715.002a.023.023 0 0 0-.027.006l-.006.014l-.034.614c0 .012.007.02.017.024l.015-.002l.201-.093l.01-.008l.004-.011l.017-.43l-.003-.012l-.01-.01l-.184-.092Z"
                />
                <path
                  fill="currentColor"
                  d="m13.299 3.148l8.634 14.954a1.5 1.5 0 0 1-1.299 2.25H3.366a1.5 1.5 0 0 1-1.299-2.25l8.634-14.954c.577-1 2.02-1 2.598 0ZM12 4.898L4.232 18.352h15.536L12 4.898ZM12 15a1 1 0 1 1 0 2a1 1 0 0 1 0-2Zm0-7a1 1 0 0 1 1 1v4a1 1 0 0 1-2 0V9a1 1 0 0 1 1-1Z"
                />
              </g>
            </svg>
            {{ $t('Content not available') }}
          </div>
        </div>

        <!-- Video formatting -->
        <div v-else-if="parsedMetadata.type === 'video'">
          <video
            v-if="props.content.media != null"
            controls
            width="300"
            class="max-h-[350px]"
          >
            <source :src="props.content?.media?.path" type="video/mp4" />
            {{ $t('Your browser does not support the video element') }}
          </video>
          <div
            v-else
            class="flex justify-center items-center space-x-4 text-slate-500"
          >
            <svg
              xmlns="http://www.w3.org/2000/svg"
              width="24"
              height="24"
              viewBox="0 0 24 24"
            >
              <g fill="none">
                <path
                  d="M24 0v24H0V0h24ZM12.593 23.258l-.011.002l-.071.035l-.02.004l-.014-.004l-.071-.035c-.01-.004-.019-.001-.024.005l-.004.01l-.017.428l.005.02l.01.013l.104.074l.015.004l.012-.004l.104-.074l.012-.016l.004-.017l-.017-.427c-.002-.01-.009-.017-.017-.018Zm.265-.113l-.013.002l-.185.093l-.01.01l-.003.011l.018.43l.005.012l.008.007l.201.093c.012.004.023 0 .029-.008l.004-.014l-.034-.614c-.003-.012-.01-.02-.20-.022Zm-.715.002a.023.023 0 0 0-.027.006l-.006.014l-.034.614c0 .012.007.02.017.024l.015-.002l.201-.093l.01-.008l.004-.011l.017-.43l-.003-.012l-.01-.01l-.184-.092Z"
                />
                <path
                  fill="currentColor"
                  d="m13.299 3.148l8.634 14.954a1.5 1.5 0 0 1-1.299 2.25H3.366a1.5 1.5 0 0 1-1.299-2.25l8.634-14.954c.577-1 2.02-1 2.598 0ZM12 4.898L4.232 18.352h15.536L12 4.898ZM12 15a1 1 0 1 1 0 2a1 1 0 0 1 0-2Zm0-7a1 1 0 0 1 1 1v4a1 1 0 0 1-2 0V9a1 1 0 0 1 1-1Z"
                />
              </g>
            </svg>
            {{ $t('Content not available') }}
          </div>
        </div>

        <!-- Reaction Emoji Display (for non-reaction messages) -->
        <div
          v-if="hasReaction && parsedMetadata.type !== 'reaction'"
          class="absolute bottom-0 right-0 transform translate-x-1/2 translate-y-1/2 bg-white rounded-full p-1 shadow-md text-sm"
        >
          {{ reactionEmoji }}
        </div>

        <!-- Image Zoom Modal -->
        <Modal :show="isImageModalOpen" @close="closeImageModal">
          <div class="p-4 bg-white rounded-lg max-w-[90vw] max-h-[90vh] mx-auto">
            <div class="flex justify-between items-center mb-4">
              <h2 class="text-lg font-semibold">{{ $t('Image Preview') }}</h2>
              <button @click="closeImageModal" class="text-gray-500 hover:text-gray-700">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
                  <path fill="currentColor" d="M6.4 19L5 17.6l5.6-5.6L5 6.4L6.4 5l5.6 5.6L17.6 5L19 6.4L13.4 12l5.6 5.6l-1.4 1.4l-5.6-5.6L6.4 19Z"/>
                </svg>
              </button>
            </div>
            <img
              v-if="props.content.media != null"
              :src="props.content?.media?.path"
              alt="Large Image"
              class="max-w-[80vw] max-h-[80vh] object-contain"
            />
            <div v-if="parsedMetadata.image?.caption" class="mt-2 text-sm text-gray-600">
              {{ parsedMetadata.image?.caption }}
            </div>
          </div>
        </Modal>
      </div>
</div>
    <!-- Timestamp and Status -->
    <div class="flex items-center justify-end space-x-2 text-slate-500 text-[11px]">
      <span>{{ new Date(props.content.created_at).toLocaleString() }}</span>
      <span v-if="props.type === 'outbound' && chatStatus(props.content.logs) === 'read'">
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24"><path fill="none" stroke="#00a5f4" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m3 12l7 7l11-11m-16.5 5l6 6l8.5-8.5"/></svg>
      </span>
      <span v-else-if="props.type === 'outbound' && chatStatus(props.content.logs) === 'delivered'">
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24"><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m3 12l7 7L21 8m-9-1l2.5 2.5L21 3"/></svg>
      </span>
      <span v-else-if="props.type === 'outbound' && chatStatus(props.content.logs) === 'sent'">
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24"><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m5 12l7 7l7-7"/></svg>
      </span>
      <span v-if="props.type === 'outbound' && chatStatus(props.content.logs) === 'failed'" class="cursor-pointer" @click="isModalOpen = true">
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24"><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3Z"/></svg>
      </span>
    </div>

    <!-- Error Modal -->
    <Modal :show="isModalOpen" @close="isModalOpen = false">
      <div class="p-4">
        <div class="flex justify-between items-center">
          <h2 class="text-lg font-semibold">{{ $t('Errors') }}</h2>
          <button @click="isModalOpen = false" class="text-gray-500 hover:text-gray-700">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
              <path fill="currentColor" d="M6.4 19L5 17.6l5.6-5.6L5 6.4L6.4 5l5.6 5.6L17.6 5L19 6.4L13.4 12l5.6 5.6l-1.4 1.4l-5.6-5.6L6.4 19Z"/>
            </svg>
          </button>
        </div>
        <div class="mt-4">
          <ul>
            <li v-for="(error, index) in errors" :key="index">
              <span class="text-red-500 font-semibold">{{ $t('Error code') }} {{ error.code }}</span>
              <p>{{ error.error_data?.details || error.title }}</p>
            </li>
          </ul>
        </div>
      </div>
    </Modal>
  </div>
</template>