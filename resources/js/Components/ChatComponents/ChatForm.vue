<script setup lang="ts">
import axios from 'axios';
import { ref, watch, watchEffect, computed, onMounted, onBeforeUnmount, nextTick } from 'vue';
import { useI18n } from 'vue-i18n';
import { usePage } from '@inertiajs/vue3';
import EmojiPicker from 'vue3-emoji-picker';
import 'vue3-emoji-picker/css';
import MicRecorder from 'mic-recorder-to-mp3-fixed';
import ChatReply from './ChatReply.vue';
import { emitter } from './eventBus';

// Types
interface Contact {
  uuid: string;
  last_inbound_chat?: {
    created_at: string;
  };
}

interface ReplayedMessage {
  id?: string;
  wam_id?: string;
  [key: string]: any;
}

interface SavedReply {
  id: number;
  reply: string;
  path?: string;
}

interface FormData {
  uuid: string | null;
  message: string | null;
  type: string | null;
  file: File | null;
}

// Reactive state
const recorder = ref<MicRecorder | null>(null);
const processingForm = ref(false);
const processingAISuggestions = ref(false);
const formTextInput = ref<string | null>(null);

const isRecording = ref(false);
const mediaRecorder = ref<MediaRecorder | null>(null);
const audioChunks = ref<Blob[]>([]);
const recordingTime = ref(0);
const timerInterval = ref<NodeJS.Timeout | null>(null);
const playbackTime = ref(0);
const playbackInterval = ref<NodeJS.Timeout | null>(null);
const audioDuration = ref(0);
const audioPreviewUrl = ref<string | null>(null);
const isPlaying = ref(false);
const audioPlayer = ref<HTMLAudioElement | null>(null);
const isAudioRecording = ref(false);
const emojiPicker = ref(false);
const emojiPickerRef = ref<HTMLElement | null>(null);

// locale helper
const { t } = useI18n();

// current organization id from page props
const orgId = computed(() => usePage().props.organizationId);
const savedReplies = ref<SavedReply[]>([]);
const showSavedReplies = ref(false);
const selectedSavedReply = ref<SavedReply | null>(null);
const savedRepliesRef = ref<HTMLElement | null>(null);
const textInputRef = ref<HTMLTextAreaElement | null>(null);
const lastSentMessage = ref<string | null>(null);

// Form data
const form = ref<FormData>({
  uuid: null,
  message: null,
  type: null,
  file: null,
});

const form2 = ref<FormData>({
  uuid: null,
  message: null,
  type: null,
  file: null,
});

// Reactive state for replayed message
const replayedMessage = ref<ReplayedMessage | null>(null);
const includeReply = ref<boolean>(true);

// Props
const props = defineProps<{
  contact: Contact;
  chatLimitReached: boolean;
  simpleForm: boolean;
  replayedMessage: ReplayedMessage | null;
}>();

// Emits
const emit = defineEmits<{
  (e: 'response', data: any): void;
  (e: 'viewTemplate', value: boolean): void;
  (e: 'removeReplay'): void;
}>();

// Sync UUIDs with contact prop
watchEffect(() => {
  form.value.uuid = props.contact.uuid;
  form2.value.uuid = props.contact.uuid;
});

// Handle update-data event to set replayed message
const handleUpdateData = (payload: { data: ReplayedMessage }) => {
  replayedMessage.value = payload.data;
  includeReply.value = true;
  emit('response', payload.data);
};

// Handle remove message event from ChatReply
const handleRemoveMessage = () => {
  replayedMessage.value = null;
  includeReply.value = false;
};

// Save current message to saved replies
const saveToSavedReplies = async () => {
  if (!formTextInput.value || formTextInput.value.trim() === '') {
    return;
  }

  try {
    await axios.post('/saved-replies', {
      reply: formTextInput.value,
      type: 'text',
      organization_id: orgId.value,
    });
    
    // Refresh saved replies list
    await loadSavedReplies();
    
    
    // Optional: Show temporary success indicator
    const button = document.querySelector('.save-reply-btn');
    if (button) {
      button.classList.add('text-green-500');
      setTimeout(() => {
        button.classList.remove('text-green-500');
      }, 2000);
    }
  } catch (error) {
    console.error('Error saving reply:', error);
  }
};

onMounted(() => {
  document.addEventListener('click', handleClickOutside);
  recorder.value = new MicRecorder({ bitRate: 128 });
  emitter.on('update-data', handleUpdateData);

  // pre-fetch saved replies so the list is immediately available
  loadSavedReplies();
});

onBeforeUnmount(() => {
  document.removeEventListener('click', handleClickOutside);
  stopTimer();
  stopPlaybackTimer();
  if (audioPreviewUrl.value) {
    URL.revokeObjectURL(audioPreviewUrl.value);
  }
  emitter.off('update-data', handleUpdateData);
});

// Emit viewTemplate event
const viewTemplate = () => {
  emit('viewTemplate', true);
};

// Send message (text, file, or reply)
const sendMessage = async () => {
  if (processingForm.value || (formTextInput.value && formTextInput.value === lastSentMessage.value)) {
    return;
  } 

  form.value.message = formTextInput.value;
  processingForm.value = true;

  if (form.value.message || form.value.file) {
    const formData = new FormData();
    formData.append('message', form.value.message || '');
    formData.append('type', form.value.type || 'text');
    formData.append('uuid', form.value.uuid || '');
    if (includeReply.value && replayedMessage.value?.wam_id) {
      formData.append('reply_to', replayedMessage.value.wam_id);
    }
    if (form.value.file) {
      formData.append('file', form.value.file);
    }

    try {
      await axios.post('/chats', formData);
      lastSentMessage.value = form.value.message;
      if (isAudioRecording.value) {
        await sendAudioMessage();
      }
      form.value.message = null;
      formTextInput.value = null;
      form.value.file = null;
      replayedMessage.value = null;
      selectedSavedReply.value = null;
      emit('removeReplay');
      if (textInputRef.value) {
        textInputRef.value.value = '';
        adjustTextareaHeight();
      }
    } catch (error) {
      console.error('Error:', error);
    } finally {
      processingForm.value = false;
    }
  } else if (isAudioRecording.value) {
    await sendAudioMessage();
    processingForm.value = false;
  } else {
    processingForm.value = false;
  }
};

// Send audio message
const sendAudioMessage = async () => {
  const formData = new FormData();
  formData.append('type', form2.value.type || 'audio');
  formData.append('uuid', form2.value.uuid || '');
  if (includeReply.value && replayedMessage.value?.id) {
    formData.append('reply_to', replayedMessage.value.id);
  }
  if (form2.value.file) {
    formData.append('file', form2.value.file);
  }

  try {
    await axios.post('/chats', formData);
    if (isAudioRecording.value) {
      deleteRecording();
    }
    replayedMessage.value = null;
    emit('removeReplay');
  } catch (error) {
    console.error('Error:', error);
  }
};

// Adjust textarea height dynamically
const adjustTextareaHeight = () => {
  if (textInputRef.value) {
    textInputRef.value.style.height = 'auto';
    textInputRef.value.style.height = `${textInputRef.value.scrollHeight}px`;
  }
};

// Handle Enter key - Send message on Enter, New line on Shift+Enter
const handleEnterKey = (event: KeyboardEvent) => {
  if (event.shiftKey) {
    // Shift + Enter: Add new line
    // Allow default behavior (new line will be added automatically)
    return;
  } else {
    // Enter only: Send message
    event.preventDefault();
    if (formTextInput.value && formTextInput.value.trim() !== '') {
      sendMessage();
    }
  }
};

// Check if inbound chat is within 24 hours
const isInboundChatWithin24Hours = computed(() => {
  if (props.contact.last_inbound_chat?.created_at) {
    const lastInboundChatTime = new Date(props.contact.last_inbound_chat.created_at);
    const currentTime = new Date();
    const timeDifference = currentTime.getTime() - lastInboundChatTime.getTime();
    return timeDifference < 24 * 60 * 60 * 1000;
  }
  return false;
});

// Handle file upload
const handleFileUpload = (event: Event) => {
  const target = event.target as HTMLInputElement;
  const file = target.files?.[0];
  if (file) {
    form.value.file = file;
    sendMessage();
  }
};

// Get accepted file types based on message type
const getAcceptedFileTypes = () => {
  switch (form.value.type) {
    case 'image':
      return '.jpg, .png';
    case 'document':
      return '.txt, .pdf, .ppt, .doc, .xls, .docx, .pptx, .xlsx';
    case 'audio':
      return '.mp3, .ogg';
    case 'video':
      return '.mp4';
    default:
      return '';
  }
};

// Emoji picker toggle
const toggleEmojiPicker = (e: Event) => {
  e.stopPropagation();
  emojiPicker.value = !emojiPicker.value;
};

// Close emoji picker
const closeEmojiPicker = () => {
  emojiPicker.value = false;
};

// Add emoji to textarea
const addEmoji = (emoji: { i: string }) => {
  if (!textInputRef.value) return;
  
  const currentValue = formTextInput.value || '';
  const start = textInputRef.value.selectionStart || 0;
  const end = textInputRef.value.selectionEnd || 0;

  formTextInput.value = currentValue.substring(0, start) + emoji.i + currentValue.substring(end);

  setTimeout(() => {
    if (textInputRef.value) {
      textInputRef.value.focus();
      textInputRef.value.setSelectionRange(start + emoji.i.length, start + emoji.i.length);
    }
  }, 0);
};

// Handle click outside to close emoji picker and saved replies
const handleClickOutside = (event: MouseEvent) => {
  if (
    emojiPickerRef.value &&
    !emojiPickerRef.value.contains(event.target as Node) &&
    textInputRef.value &&
    !textInputRef.value.contains(event.target as Node)
  ) {
    closeEmojiPicker();
  }
  
  // close saved replies panel when clicking outside
  if (showSavedReplies.value && savedRepliesRef.value && !savedRepliesRef.value.contains(event.target as Node)) {
    showSavedReplies.value = false;
  }
  
};

const loadSavedReplies = async () => {
  try {
    const res = await axios.get('/saved-replies/chats');
    if (res?.data?.data) {
      savedReplies.value = res.data.data;
    }
  } catch (e) {
    console.error('Failed to load saved replies', e);
  }
};

const toggleSavedReplies = async () => {
  showSavedReplies.value = !showSavedReplies.value;
  if (showSavedReplies.value && savedReplies.value.length === 0) {
    await loadSavedReplies();
  }
};


const selectSavedReply = (item: SavedReply) => {
  form.value.type = 'text';
  formTextInput.value = item.reply || '';
  selectedSavedReply.value = item;
  // focus textarea after selecting
  nextTick(() => {
    if (textInputRef.value) {
      textInputRef.value.focus();
      adjustTextareaHeight();
    }
  });
  showSavedReplies.value = false;
};

// Delete a saved reply via API and update list
const deleteSavedReply = async (item: SavedReply) => {
  if (!item.id) return;

  try {
    await axios.delete(`/saved-replies/${item.id}`);
    // remove from local array
    savedReplies.value = savedReplies.value.filter(r => r.id !== item.id);

    // clear selection if the deleted item was selected
    if (selectedSavedReply.value?.id === item.id) {
      selectedSavedReply.value = null;
      formTextInput.value = '';
    }
  } catch (err) {
    console.error('Error deleting saved reply:', err);
  }
};

// Handle audio icon click - start or stop recording
const handleAudioClick = async () => {
  if (isRecording.value) {
    await stopRecording();
  } else {
    await startRecording();
  }
};

// Start audio recording
const startRecording = async () => {
  try {
    isAudioRecording.value = true;
    if (recorder.value) {
      await recorder.value.start();
    }
    isRecording.value = true;
    startTimer();
  } catch (error) {
    console.error('Error accessing microphone:', error);
    alert('Unable to access microphone. Please check your permissions.');
  }
};

// Stop audio recording
const stopRecording = async () => {
  if (isRecording.value && recorder.value) {
    try {
      const [buffer, blob] = await recorder.value.stop().getMp3();
      const audioFile = new File(buffer, 'audio-message.mp3', {
        type: blob.type,
        lastModified: Date.now(),
      });

      audioPreviewUrl.value = URL.createObjectURL(blob);
      form2.value.type = 'audio';
      form2.value.file = audioFile;
      stopTimer();

      // Load audio duration
      const audio = new Audio(audioPreviewUrl.value);
      audio.addEventListener('loadedmetadata', () => {
        audioDuration.value = Math.floor(audio.duration);
      });
    } catch (error) {
      console.error('Error stopping recording:', error);
      isRecording.value = false;
      stopTimer();
    }
  }
};

// Delete audio recording
const deleteRecording = () => {
  if (audioPreviewUrl.value) {
    URL.revokeObjectURL(audioPreviewUrl.value);
  }
  audioPreviewUrl.value = null;
  recordingTime.value = 0;
  playbackTime.value = 0;
  audioDuration.value = 0;
  audioChunks.value = [];
  form2.value.type = null;
  form2.value.file = null;
  isAudioRecording.value = false;
  stopPlaybackTimer();
};

// Toggle audio playback
const togglePlayback = () => {
  if (!audioPlayer.value) return;

  if (isPlaying.value) {
    audioPlayer.value.pause();
    stopPlaybackTimer();
  } else {
    audioPlayer.value.play();
    startPlaybackTimer();
  }
  isPlaying.value = !isPlaying.value;
};

// Start recording timer
const startTimer = () => {
  recordingTime.value = 0;
  timerInterval.value = setInterval(() => {
    recordingTime.value++;
  }, 1000);
};

// Stop recording timer
const stopTimer = () => {
  if (timerInterval.value) {
    clearInterval(timerInterval.value);
    timerInterval.value = null;
  }
};

// Start playback timer
const startPlaybackTimer = () => {
  playbackTime.value = 0;
  playbackInterval.value = setInterval(() => {
    if (audioPlayer.value) {
      playbackTime.value = Math.floor(audioPlayer.value.currentTime);
    }
  }, 100);
};

// Stop playback timer
const stopPlaybackTimer = () => {
  if (playbackInterval.value) {
    clearInterval(playbackInterval.value);
    playbackInterval.value = null;
  }
};

// Format time for display
const formatTime = (seconds: number) => {
  const minutes = Math.floor(seconds / 60);
  const remainingSeconds = seconds % 60;
  return `${minutes.toString().padStart(2, '0')}:${remainingSeconds.toString().padStart(2, '0')}`;
};

// Handle audio metadata loaded
const handleAudioLoaded = () => {
  if (audioPlayer.value) {
    audioDuration.value = Math.floor(audioPlayer.value.duration);
  }
};

// Handle audio ended
const handleAudioEnded = () => {
  isPlaying.value = false;
  playbackTime.value = 0;
  stopPlaybackTimer();
};

// Get AI suggestion
const getSuggestion = async () => {
  processingAISuggestions.value = true;
  try {
    const response = await axios.get('/automation/chat/suggestion', {
      params: { contact: props.contact.uuid },
    });

    if (response.data.success) {
      form.value.type = 'text';
      formTextInput.value = response.data.data.text;
    } else {
      console.error('Failed to get suggestion:', response.data.message);
    }
  } catch (error) {
    console.error('Error getting suggestion:', error);
  } finally {
    processingAISuggestions.value = false;
  }
};
</script>

<template>
  <div v-if="props.chatLimitReached" class="flex justify-center items-center w-full px-6 md:px-4">
    <div class="flex items-start space-x-4 bg-orange-100 rounded-lg p-2 mb-2 px-4">
      <span class="text-red-700">
        <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 36 36">
          <path fill="currentColor" d="M18 21.32a1.3 1.3 0 0 0 1.3-1.3V14a1.3 1.3 0 1 0-2.6 0v6a1.3 1.3 0 0 0 1.3 1.32Z" class="clr-i-outline clr-i-outline-path-1"/>
          <circle cx="17.95" cy="24.27" r="1.5" fill="currentColor" class="clr-i-outline clr-i-outline-path-2"/>
          <path fill="currentColor" d="M30.33 25.54L20.59 7.6a3 3 0 0 0-5.27 0L5.57 25.54A3 3 0 0 0 8.21 30h19.48a3 3 0 0 0 2.64-4.43Zm-1.78 1.94a1 1 0 0 1-.86.49H8.21a1 1 0 0 1-.88-1.48l9.74-17.94a1 1 0 0 1 1.76 0l9.74 17.94a1 1 0 0 1-.02.99Z" class="clr-i-outline clr-i-outline-path-3"/>
          <path fill="none" d="M0 0h36v36H0z"/>
        </svg>
      </span>
      <div>
        <div class="text-sm">{{ $t('Maximum chat limit reached') }}</div>
        <div class="text-sm">{{ $t('You have reached the maximum chat limit for your subscription! Please upgrade to send/receive more messages') }}</div>
      </div>
    </div>
  </div>
  
  <div v-if="!isInboundChatWithin24Hours && !props.chatLimitReached" class="flex justify-center items-center w-full px-6 md:px-4">
    <div class="flex items-center justify-between space-x-4 bg-orange-100 rounded-lg p-2 mb-2 px-4">
      <div class="flex items-start justify-between space-x-4">
        <span class="text-red-700">
          <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 36 36">
            <path fill="currentColor" d="M18 21.32a1.3 1.3 0 0 0 1.3-1.3V14a1.3 1.3 0 1 0-2.6 0v6a1.3 1.3 0 0 0 1.3 1.32Z" class="clr-i-outline clr-i-outline-path-1"/>
            <circle cx="17.95" cy="24.27" r="1.5" fill="currentColor" class="clr-i-outline clr-i-outline-path-2"/>
            <path fill="currentColor" d="M30.33 25.54L20.59 7.6a3 3 0 0 0-5.27 0L5.57 25.54A3 3 0 0 0 8.21 30h19.48a3 3 0 0 0 2.64-4.43Zm-1.78 1.94a1 1 0 0 1-.86.49H8.21a1 1 0 0 1-.88-1.48l9.74-17.94a1 1 0 0 1 1.76 0l9.74 17.94a1 1 0 0 1-.02.99Z" class="clr-i-outline clr-i-outline-path-3"/>
            <path fill="none" d="M0 0h36v36H0z"/>
          </svg>
        </span>
        <div>
          <div class="text-sm">{{ $t('24 hour limit') }}</div>
          <div class="text-sm">{{ $t('Whatsapp does not allow sending messages 24 hours after they last messaged you. However, you can send them a template message') }}</div>
        </div>
      </div>
      <button @click="viewTemplate()" class="rounded-md bg-primary px-3 py-1 text-sm text-white shadow-sm w-[25%]">Send Template</button>
    </div>
  </div>

  <form  v-if="simpleForm && isInboundChatWithin24Hours && !props.chatLimitReached"   @submit.prevent="sendMessage()" class="flex items-center px-2 md:px-10 space-x-2">
    <div class="relative bg-white w-full rounded-lg">
      <div class="flex items-start justify-between p-2" v-if="replayedMessage">
        <ChatReply class="p-3 flex-1" :show-remove="true" @remove="handleRemoveMessage" :content="replayedMessage" />
      </div>
      
      <!-- Audio Recording Preview -->
      <div v-if="audioPreviewUrl" class="flex items-center justify-between bg-gray-100 p-3 rounded-lg mb-2">
        <div class="flex items-center space-x-3">
          <button type="button" @click="togglePlayback" class="p-2 rounded-full bg-primary text-white hover:bg-primary-dark">
            <svg v-if="!isPlaying" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
              <path d="M8 5v14l11-7z"/>
            </svg>
            <svg v-else xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
              <path d="M6 4h4v16H6V4zm8 0h4v16h-4V4z"/>
            </svg>
          </button>
          <div class="flex-1">
            <div class="text-sm text-gray-600">
              {{ formatTime(isPlaying ? playbackTime : audioDuration) }}
            </div>
          </div>
        </div>
        <button type="button" @click="deleteRecording" class="text-red-500 hover:text-red-700">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
          </svg>
        </button>
        <audio 
          ref="audioPlayer" 
          :src="audioPreviewUrl" 
          @loadedmetadata="handleAudioLoaded"
          @ended="handleAudioEnded"
          class="hidden"
        ></audio>
      </div>

      <!-- Recording in Progress -->
      <div v-if="isRecording && !audioPreviewUrl" class="flex items-center justify-between bg-red-50 p-3 rounded-lg mb-2">
        <div class="flex items-center space-x-3">
          <div class="w-3 h-3 bg-red-500 rounded-full animate-pulse"></div>
          <span class="text-sm text-gray-700">Recording... {{ formatTime(recordingTime) }}</span>
        </div>
        <button type="button" @click="stopRecording" class="px-3 py-1 bg-red-500 text-white rounded-md text-sm hover:bg-red-600">
          Stop
        </button>
      </div>

      <div class="flex items-center w-full rounded-lg py-4 md:py-2 pl-2 pr-2" :class="processingForm ? 'bg-gray-200' : 'bg-white'">
        <div class="absolute">
          <button type="button" @click="toggleEmojiPicker">😀</button>
          <div v-if="emojiPicker" class="absolute left-0 bottom-full" ref="emojiPickerRef">
            <EmojiPicker :native="true" @select="addEmoji" />
          </div>
        </div>
        
        <!-- Saved Replies Dropdown -->
        <div class="absolute left-10">
          <button 
            type="button" 
            @click="toggleSavedReplies" 
            aria-label="Saved replies" 
            class="p-1 hover:text-primary transition-colors"
          >
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
              <path d="M3 7v13l7-3 7 3V7" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M21 5H3" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </button>
          
          <!-- Saved Replies Dropdown Panel -->
          <div v-if="showSavedReplies"  class="absolute left-0 bottom-full mb-2 w-[320px] max-h-80 overflow-auto bg-white border rounded-lg shadow-xl z-50">
            <div class="sticky top-0 bg-gray-50 p-2 border-b">
              <h3 class="text-sm font-semibold text-gray-700">{{ $t('Saved Replies') }}</h3>
            </div>
            <div v-if="savedReplies.length === 0" class="p-4 text-sm text-slate-500 text-center">
              {{ $t('No saved replies') }}
            </div>
            <template v-else>
              <div 
                v-for="(item, idx) in savedReplies"
                :key="item.id"
                class="w-full p-3 hover:bg-slate-50 border-b last:border-b-0 transition-colors flex justify-between items-start"
                :class="{ 'bg-blue-50': selectedSavedReply?.id === item.id }"
              >
                <!-- clicking text selects reply -->
                <div class="flex-1 cursor-pointer" @click="selectSavedReply(item)">
                  <div class="text-sm text-slate-800 font-medium truncate">{{ item.reply }}</div>
                  <div v-if="item.path" class="text-xs text-slate-500 mt-1 truncate">{{ item.path }}</div>
                </div>

                <!-- delete button -->
                <button 
                  type="button"
                  @click.stop="deleteSavedReply(item)"
                  class="ml-2 p-1 text-red-500 hover:text-red-700"
                  aria-label="Delete saved reply"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="3 6 5 6 21 6"></polyline>
                    <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"></path>
                    <path d="M10 11v6"></path>
                    <path d="M14 11v6"></path>
                  </svg>
                </button>
              </div>
            </template>
          </div>
        </div>

        <!-- Save to Saved Replies Icon -->
        <div class="absolute left-20">
          <button 
            type="button" 
            @click="saveToSavedReplies" 
            aria-label="Save to saved replies" 
            class="p-1 hover:text-green-600 transition-colors save-reply-btn"
            :disabled="!formTextInput || formTextInput.trim() === ''"
            :class="{ 'text-slate-300 cursor-not-allowed': !formTextInput || formTextInput.trim() === '', 'text-slate-500': formTextInput && formTextInput.trim() !== '' }"
          >
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
              <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M17 21v-4H7v4" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M12 7v6" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M9 10h6" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </button>
        </div>

        <textarea
          v-if="!isRecording && !audioPreviewUrl"
          ref="textInputRef"
          @focus="form.type = 'text'"
          @keydown.enter="handleEnterKey"
          class="w-full ml-24 outline-none resize-none text-sm md:text-base pl-2"
          :class="processingForm ? 'bg-gray-200' : 'bg-white'"
          v-model="formTextInput"
          @input="adjustTextareaHeight"
          type="text"
          rows="1"
          :placeholder="$t('Type your message...')"
          :disabled="processingForm"
        ></textarea>
        
        <div v-if="isRecording || audioPreviewUrl" class="flex-1"></div>

        <input
          type="file"
          class="sr-only"
          :accept="getAcceptedFileTypes()"
          id="file-upload"
          @change="handleFileUpload($event)"
        />
        
        <label v-if="!isRecording && !audioPreviewUrl" @click="form.type = 'image'" for="file-upload" class="text-slate-500 mr-2 cursor-pointer hover:text-primary transition-colors">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
            <g fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5">
              <path d="M6.5 8a2 2 0 1 0 4 0a2 2 0 0 0-4 0Zm14.427 1.99c-6.61-.908-12.31 4-11.927 10.51"/>
              <path d="M3 13.066c2.78-.385 5.275.958 6.624 3.1"/>
              <path d="M3 12c0-4.243 0-6.364 1.318-7.682C5.636 3 7.758 3 12 3c4.243 0 6.364 0 7.682 1.318C21 5.636 21 7.758 21 12c0 4.243 0 6.364-1.318 7.682C18.364 21 16.242 21 12 21c-4.243 0-6.364 0-7.682-1.318C3 18.364 3 16.242 3 12Z"/>
            </g>
          </svg>
        </label>
        
        <label v-if="!isRecording && !audioPreviewUrl" @click="form.type = 'document'" for="file-upload" class="text-slate-500 mr-1 cursor-pointer hover:text-primary transition-colors">
          <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24">
            <g fill="none" stroke="currentColor" stroke-width="1.5">
              <path d="M3 10c0-3.771 0-5.657 1.172-6.828C5.343 2 7.229 2 11 2h2c3.771 0 5.657 0 6.828 1.172C21 4.343 21 6.229 21 10v4c0 3.771 0 5.657-1.172 6.828C18.657 22 16.771 22 13 22h-2c-3.771 0-5.657 0-6.828-1.172C3 19.657 3 17.771 3 14v-4Z"/>
              <path stroke-linecap="round" d="M8 10h8m-8 4h5"/>
            </g>
          </svg>
        </label>
        
        <button 
          v-if="!isRecording && !audioPreviewUrl" 
          type="button" 
          @click="handleAudioClick" 
          class="text-slate-500 mr-1 cursor-pointer hover:text-primary transition-colors"
        >
          <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 1024 1024">
            <path fill="currentColor" d="M842 454c0-4.4-3.6-8-8-8h-60c-4.4 0-8 3.6-8 8c0 140.3-113.7 254-254 254S258 594.3 258 454c0-4.4-3.6-8-8-8h-60c-4.4 0-8 3.6-8 8c0 168.7 126.6 307.9 290 327.6V884H326.7c-13.7 0-24.7 14.3-24.7 32v36c0 4.4 2.8 8 6.2 8h407.6c3.4 0 6.2-3.6 6.2-8v-36c0-17.7-11-32-24.7-32H548V782.1c165.3-18 294-158 294-328.1M512 624c93.9 0 170-75.2 170-168V232c0-92.8-76.1-168-170-168s-170 75.2-170 168v224c0 92.8 76.1 168 170 168m-94-392c0-50.6 41.9-92 94-92s94 41.4 94 92v224c0 50.6-41.9 92-94 92s-94-41.4-94-92z"/>
          </svg>
        </button>
        
        <label v-if="!isRecording && !audioPreviewUrl" @click="form.type = 'video'" for="file-upload" class="text-slate-500 mr-2 cursor-pointer hover:text-primary transition-colors">
          <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 32 32">
            <path fill="currentColor" d="M6.5 5.5A4.5 4.5 0 0 0 2 10v12a4.5 4.5 0 0 0 4.5 4.5h12A4.5 4.5 0 0 0 23 22v-1.5l4.2 3.15c1.153.865 2.8.042 2.8-1.4V9.75c0-1.442-1.647-2.265-2.8-1.4L23 11.5V10a4.5 4.5 0 0 0-4.5-4.5zM23 14l5-3.75v11.5L23 18zm-2-4v12a2.5 2.5 0 0 1-2.5 2.5h-12A2.5 2.5 0 0 1 4 22V10a2.5 2.5 0 0 1 2.5-2.5h12A2.5 2.5 0 0 1 21 10"/>
          </svg>
        </label>
        
        <label v-if="!isRecording && !audioPreviewUrl" @click="viewTemplate()" class="text-slate-500 mr-4 cursor-pointer hover:text-primary transition-colors">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 256 256">
            <path fill="currentColor" d="M216 80h-32V48a16 16 0 0 0-16-16H40a16 16 0 0 0-16 16v128a8 8 0 0 0 13 6.22L72 154v30a16 16 0 0 0 16 16h93.59L219 230.22a8 8 0 0 0 5 1.78a8 8 0 0 0 8-8V96a16 16 0 0 0-16-16M66.55 137.78L40 159.25V48h128v88H71.58a8 8 0 0 0-5.03 1.78M216 207.25l-26.55-21.47a8 8 0 0 0-5-1.78H88v-32h80a16 16 0 0 0 16-16V96h32Z"/>
          </svg>
        </label>
        
        <button 
          v-if="audioPreviewUrl" 
          class="flex items-center" 
          type="submit" 
          :disabled="!form2.file"
        >
          <svg :class="!form2.file ? 'text-slate-300' : 'text-black'" xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 16 16">
            <path fill="currentColor" d="M1.724 1.053a.5.5 0 0 0-.714.545l1.403 4.85a.5.5 0 0 0 .397.354l5.69.953c.268.053.268.437 0 .49l-5.69.953a.5.5 0 0 0-.397.354l-1.403 4.85a.5.5 0 0 0 .714.545l13-6.5a.5.5 0 0 0 0-.894l-13-6.5Z"/>
          </svg>
        </button>
        
        <button 
          v-else-if="!isRecording"
          class="flex items-center" 
          type="submit" 
          :disabled="(formTextInput === null || formTextInput.trim() === '') && !form2.file"
        >
          <svg v-if="!processingForm" :class="(formTextInput === null || formTextInput.trim() === '') && !form2.file ? 'text-slate-300' : 'text-black'" xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 16 16">
            <path fill="currentColor" d="M1.724 1.053a.5.5 0 0 0-.714.545l1.403 4.85a.5.5 0 0 0 .397.354l5.69.953c.268.053.268.437 0 .49l-5.69.953a.5.5 0 0 0-.397.354l-1.403 4.85a.5.5 0 0 0 .714.545l13-6.5a.5.5 0 0 0 0-.894l-13-6.5Z"/>
          </svg>
          <svg v-else xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
            <circle cx="12" cy="2" r="0" fill="currentColor">
              <animate attributeName="r" begin="0" calcMode="spline" dur="1s" keySplines="0.2 0.2 0.4 0.8;0.2 0.2 0.4 0.8;0.2 0.2 0.4 0.8" repeatCount="indefinite" values="0;2;0;0"/>
            </circle>
            <circle cx="12" cy="2" r="0" fill="currentColor" transform="rotate(45 12 12)">
              <animate attributeName="r" begin="0.125s" calcMode="spline" dur="1s" keySplines="0.2 0.2 0.4 0.8;0.2 0.2 0.4 0.8;0.2 0.2 0.4 0.8" repeatCount="indefinite" values="0;2;0;0"/>
            </circle>
            <circle cx="12" cy="2" r="0" fill="currentColor" transform="rotate(90 12 12)">
              <animate attributeName="r" begin="0.25s" calcMode="spline" dur="1s" keySplines="0.2 0.2 0.4 0.8;0.2 0.2 0.4 0.8;0.2 0.2 0.4 0.8" repeatCount="indefinite" values="0;2;0;0"/>
            </circle>
            <circle cx="12" cy="2" r="0" fill="currentColor" transform="rotate(135 12 12)">
              <animate attributeName="r" begin="0.375s" calcMode="spline" dur="1s" keySplines="0.2 0.2 0.4 0.8;0.2 0.2 0.4 0.8;0.2 0.2 0.4 0.8" repeatCount="indefinite" values="0;2;0;0"/>
            </circle>
            <circle cx="12" cy="2" r="0" fill="currentColor" transform="rotate(180 12 12)">
              <animate attributeName="r" begin="0.5s" calcMode="spline" dur="1s" keySplines="0.2 0.2 0.4 0.8;0.2 0.2 0.4 0.8;0.2 0.2 0.4 0.8" repeatCount="indefinite" values="0;2;0;0"/>
            </circle>
            <circle cx="12" cy="2" r="0" fill="currentColor" transform="rotate(225 12 12)">
              <animate attributeName="r" begin="0.625s" calcMode="spline" dur="1s" keySplines="0.2 0.2 0.4 0.8;0.2 0.2 0.4 0.8;0.2 0.2 0.4 0.8" repeatCount="indefinite" values="0;2;0;0"/>
            </circle>
            <circle cx="12" cy="2" r="0" fill="currentColor" transform="rotate(270 12 12)">
              <animate attributeName="r" begin="0.75s" calcMode="spline" dur="1s" keySplines="0.2 0.2 0.4 0.8;0.2 0.2 0.4 0.8;0.2 0.2 0.4 0.8" repeatCount="indefinite" values="0;2;0;0"/>
            </circle>
            <circle cx="12" cy="2" r="0" fill="currentColor" transform="rotate(315 12 12)">
              <animate attributeName="r" begin="0.875s" calcMode="spline" dur="1s" keySplines="0.2 0.2 0.4 0.8;0.2 0.2 0.4 0.8;0.2 0.2 0.4 0.8" repeatCount="indefinite" values="0;2;0;0"/>
            </circle>
          </svg>
        </button>
      </div>
    </div>
  </form>
</template>

<style scoped>
.save-reply-btn.text-green-500 {
  transition: color 0.3s ease;
}
</style>