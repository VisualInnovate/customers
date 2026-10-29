<script setup>
import { usePage } from "@inertiajs/vue3";
import { ref, computed, watch, reactive, onMounted } from 'vue';
import { GoogleMap, Marker } from "vue3-google-map";
import ChatReply from "./ChatReply.vue";
import Dropdown from '@/Components/Dropdown.vue';
import DropdownItemGroup from '@/Components/DropdownItemGroup.vue';
import DropdownItem from '@/Components/DropdownItem.vue';
import moment from 'moment';
import ChatPinnedLimitError from "./Modals/ChatPinnedLimitError.vue";
import { sharedMediaState } from './mediaStore.js';

const props = defineProps({
    content: Object,
    type: String,
    status: Object,
    replayMessage: Object,
    pinnedMessages: Object,
    activeRecordId: [Number, String], // Track currently active record
    setActiveRecord: Function // Function to update active record
});

const mapLink = ref('');
const emits = defineEmits(['replay', 'pin', 'star']);
const downloading = ref(false);
const statusMap = reactive([]);
const isPinnedLimitError = ref(false);
const videoDuration = ref(null);

// Track if this record is expanded (for documents, videos, etc.)
const isExpanded = ref(false);

// Watch for status changes
watch(() => props.status, (newStatus) => {
    if (!newStatus) return;
    const existingItem = statusMap.find(item => item.chat_id === newStatus.chat_id);
    if (existingItem) {
        existingItem.status = newStatus.status;
    } else {
        statusMap.push(newStatus);
    }
});

// Watch for active record changes
watch(() => props.activeRecordId, (newId) => {
    // If another record becomes active, close this one
    if (newId !== props.content.id) {
        isExpanded.value = false;
    }
});

// Handle record expansion
const toggleExpand = () => {
    if (isExpanded.value) {
        isExpanded.value = false;
        props.setActiveRecord(null);
    } else {
        isExpanded.value = true;
        props.setActiveRecord(props.content.id);
    }
};

// Handle download click
const downloadClicked = () => {
    downloading.value = true;
    setTimeout(() => {
        downloading.value = false;
    }, 2000);
};

// Get file extension
const getExtension = (fileFormat) => {
    const formatMap = {
        'application/pdf': 'PDF',
        'text/plain': 'TXT',
        'application/powerpoint': 'PPT',
        'application/vnd.ms-powerpoint': 'PPT',
        'application/msword': 'DOC',
        'application/vnd.ms-excel': 'XLS',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document': 'DOCX',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation': 'PPTX',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet': 'XLSX',
    };
    return formatMap[fileFormat] || 'Unknown';
};

// Check if file type is viewable in iframe
const isViewableDocument = (fileFormat) => {
    const viewableTypes = [
        'application/pdf',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    ];
    return viewableTypes.includes(fileFormat);
};

// Emit replay event
const replay = () => {
    emits('replay', props.content);
};

// Handle pin action
const pin = () => {
    if (!props.content.is_pinned && props.pinnedMessages.length === 3) {
        isPinnedLimitError.value = true;
        return;
    }
    props.content.is_pinned = !props.content.is_pinned;
    emits('pin', { chat: props.content, type: 'is_pinned' });
};

// Handle star action
const star = () => {
    props.content.is_starred = !props.content.is_starred;
    emits('star', { chat: props.content, type: 'is_starred' });
};

// Format file size
const formatFileSize = (sizeInBytes) => {
    if (sizeInBytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
    const i = parseInt(Math.floor(Math.log(sizeInBytes) / Math.log(k)));
    return Math.round((sizeInBytes / Math.pow(k, i)) * 100) / 100 + ' ' + sizes[i];
};

// Get contact display name
const getContactDisplayName = (metadata) => {
    const parsedMetadata = JSON.parse(metadata);
    if (parsedMetadata.from) {
        return parsedMetadata?.text?.body;
    }
    const contacts = parsedMetadata.contacts;
    if (contacts.length === 1) {
        const contact = contacts[0];
        return contact.name.formatted_name || `${contact.name.first_name} ${contact.name.last_name}`;
    } else if (contacts.length > 1) {
        const firstName = contacts[0].name.first_name;
        const otherContactsCount = contacts.length - 1;
        return `${firstName} and ${otherContactsCount} other contacts`;
    } else {
        return 'No contacts available';
    }
};

// Get location URL
const location = (metadata) => {
    return `https://www.google.com/maps?q=${metadata.location.latitude},${metadata.location.longitude}&hl=es;z=14&output=embed`;
};

// Handle media source
const handleSrc = (content) => {
    if (content.media?.path && content.channel === 'whatsapp') {
        const baseUrl = "https://app.customercloudhq.com/media/";
        if (content?.media.path.includes(baseUrl)) return content.media.path;
        else return baseUrl + content.media.path;
    }
    if (content.media?.path && content.channel === 'instagram' && content.type === 'outbound') {
        const baseUrl = "https://app.customercloudhq.com/media/";
        if (content?.media?.path.includes(baseUrl)) return content.media.path;
        else return baseUrl + content.media.path;
    }
    if (content.media?.path && content.channel === 'instagram' && content.type === 'inbound') {
        return content.media.path;
    }
    return content.media?.path || '';
};

// Handle status icon
const handleStatusIcon = computed(() => {
    const status = statusMap?.find(item => item.chat_id === props.content.id)?.status || props.content.status;
    switch (status) {
        case 'sent':
            return '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16"><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="m2.75 8.75l3.5 3.5l7-7.5"/></svg>';
        case 'delivered':
            return '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16"><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="m1.75 9.75l2.5 2.5m3.5-4l2.5-2.5m-4.5 4l2.5 2.5l6-6.5"/></svg>';
        case 'failed':
            return '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"><path fill="currentColor" d="M12 22C6.477 22 2 17.523 2 12S6.477 2 12 2s10 4.477 10 10s-4.477 10-10 10m0-2a8 8 0 1 0 0-16a8 8 0 0 0 0 16m-1-5h2v2h-2zm0-8h2v6h-2z"/></svg>';
        case 'read':
            return '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16"><path fill="none" stroke="#52a3ff" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="m1.75 9.75l2.5 2.5m3.5-4l2.5-2.5m-4.5 4l2.5 2.5l6-6.5"/></svg>';
    }
});

// Handle media play (audio or video)
const handleMediaPlay = (event) => {
    if (sharedMediaState.currentMedia && sharedMediaState.currentMedia !== event.target) {
        sharedMediaState.currentMedia.pause();
        sharedMediaState.currentMedia.currentTime = 0; // Reset to start
    }
    sharedMediaState.currentMedia = event.target;
};

// Reset current media when it ends
const handleMediaEnded = () => {
    sharedMediaState.currentMedia = null;
};

const onVideoLoaded = (event) => {
    videoDuration.value = event.target.duration;
};

const formatDuration = (seconds) => {
    if (seconds === null || isNaN(seconds) || seconds < 0) {
        return null;
    }
    const minutes = Math.floor(seconds / 60);
    const remainingSeconds = Math.floor(seconds % 60);
    return `${String(minutes).padStart(2, '0')}:${String(remainingSeconds).padStart(2, '0')}`;
};
</script>

<template>
    <div
        :id="`bubble-id-${content?.id}`"
        class="group transition transition-delay-500 rounded-lg my-1 p-2 text-sm flex flex-col relative"
        :class="[
            props.type === 'outbound'
                ? 'ml-auto rounded-tr-none bg-[#d8fad4] speech-bubble-right'
                : 'mr-auto rounded-tl-none bg-white speech-bubble-left',
            content?.channel == 'instagram' ? 'bg-[rgb(239,239,239)]' : 'bg-white',
        ]"
    >
        <Dropdown :align="'right'" custom-class="max-w-[100px] mt-5">
            <button
                class="z-[5] absolute flex items-center justify-center opacity-0 bg-white shadow-md w-[17px] h-[17px] rounded-md right-0 transition group-hover:opacity-100"
                id="replay"
            >
                <button>
                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24">
                        <path fill="gray" d="m12 15.4l-6-6L7.4 8l4.6 4.6L16.6 8L18 9.4z" />
                    </svg>
                </button>
            </button>
            <template #items>
                <DropdownItemGroup>
                    <DropdownItem class="!text-xs px-1 py-1" as="button" @click="replay">{{ $t('Reply') }}</DropdownItem>
                    <DropdownItem class="!text-xs px-1 py-1" as="button" @click="pin">{{
                        content.is_pinned ? $t('Unpin') : $t('Pin')
                    }}</DropdownItem>
                    <DropdownItem class="!text-xs px-1 py-1" as="button" @click="star">{{
                        content.is_starred ? $t('Unstar') : $t('Star')
                    }}</DropdownItem>
                </DropdownItemGroup>
            </template>
        </Dropdown>

        <div>
            <!-- Replay message -->
            <ChatReply v-if="replayMessage" :content="replayMessage" />

            <!-- Text message formatting -->
            <div v-if="!(JSON.parse(content?.metadata).type)" class="max-w-[300px] break-words">
                <p class="normal-case whitespace-pre-wrap">
                    {{ JSON.parse(content?.metadata).text }}
                </p>
                <div
                    v-if="JSON.parse(content?.metadata)?.buttons"
                    class="mr-auto text-sm text-[#00a5f4] flex flex-col relative max-w-[25em]"
                >
                    <div
                        v-for="(item, index) in JSON.parse(content?.metadata)?.buttons"
                        :key="index"
                        class="flex justify-center items-center space-x-2 rounded-lg bg-white h-10 my-[0.1em]"
                    >
                        <span>
                            <!-- Button icons... -->
                        </span>
                        <span>{{ item.text }}</span>
                    </div>
                </div>
            </div>

            <div
                v-if="JSON.parse(content?.metadata).reaction && JSON.parse(content?.metadata)?.reactions"
                class="bottom-[-2px] right-2 absolute"
            >
                <p>{{ JSON.parse(content?.metadata).reactions.emoji }}</p>
            </div>

            <div v-if="JSON.parse(content?.metadata).type === 'text'" class="max-w-[300px] break-words">
                <p class="normal-case whitespace-pre-wrap">
                    {{ JSON.parse(content.metadata).from ? JSON.parse(content.metadata)?.text?.body : JSON.parse(content.metadata)?.text?.body }}
                </p>
                <img
                    v-if="content?.media != null"
                    :src="handleSrc(content)"
                    alt="Image"
                    class="my-2 max-w-[100px]"
                />
                <div
                    v-if="JSON.parse(content?.metadata)?.buttons"
                    class="mr-auto text-sm text-[#00a5f4] flex flex-col relative max-w-[25em]"
                >
                    <div
                        v-for="(item, index) in JSON.parse(content?.metadata)?.buttons"
                        :key="index"
                        class="flex justify-center items-center space-x-2 rounded-lg bg-white h-10 my-[0.1em]"
                    >
                        <span>
                            <!-- Button.icons... -->
                        </span>
                        <span>{{ item.text }}</span>
                    </div>
                </div>
            </div>

            <!-- Reply button formatting -->
            <div v-if="JSON.parse(content?.metadata).type === 'button'" class="max-w-[300px] break-words">
                <p class="normal-case whitespace-pre-wrap">{{ JSON.parse(content.metadata).button.text }}</p>
            </div>

            <!-- Interactive button formatting -->
            <div v-if="JSON.parse(content?.metadata).type === 'interactive'" class="max-w-[300px] break-words">
                <p class="normal-case whitespace-pre-wrap">
                    {{ JSON.parse(content?.metadata).interactive.button_reply.title }}
                </p>
            </div>

            <!-- Image formatting -->
            <div v-else-if="JSON.parse(content?.metadata).type === 'image'">
                <img
                    v-if="content.media != null"
                    :src="handleSrc(content)"
                    alt="Image"
                    class="mb-2 max-w-[300px]"
                />
                <div v-else class="text-slate-500 flex justify-center items-center space-x-4">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
                        <g fill="none">
                            <path
                                d="M24 0v24H0V0h24ZM12.593 23.258l-.011.002l-.071.035l-.02.004l-.014-.004l-.071-.035c-.01-.004-.019-.001-.024.005l-.004.01l-.017.428l.005.02l.01.013l.104.074l.015.004l.012-.004l.104-.074l.012-.016l.004-.017l-.017-.427c-.002-.01-.009-.017-.017-.018Zm.265-.113l-.013.002l-.185.093l-.01.01l-.003.011l.018.43l.005.012l.008.007l.201.093c.012.004.023 0 .029-.008l.004-.014l-.034-.614c-.003-.012-.01-.02-.20-.022Zm-.715.002a.023.023 0 0 0-.027.006l-.006.014l-.034.614c0 .012.007.02.017.024l.015-.002l.201-.093l.01-.008l.004-.011l.017-.43l-.003-.012l-.01-.01l-.184-.092Z"
                            />
                            <path
                                fill="currentColor"
                                d="m13.299 3.148l8.634 14.954a1.5 1.5 0 0 1-1.299 2.25H3.366a1.5 1.5 0 0 1-1.299-2.25l8.634-14.954c.577-1 2.02-1 2.598 0ZM12 4.898L4.232 18.352h15.536L12 4.898ZM12 15a1 1 0 1 1 0 2a1 1 0 0 1 0-2Zm0-7a1 1 0 0 1 1 1v4a1 1 0 1 1-2 0V9a1 1 0 0 1 1-1Z"
                            />
                        </g>
                    </svg>
                    {{ $t('Content not available') }}
                </div>
                <div v-if="JSON.parse(content?.metadata).image?.caption" class="max-w-[300px] break-words">
                    {{ JSON.parse(content.metadata).image?.caption }}
                </div>
                <div
                    v-if="JSON.parse(content?.metadata)?.buttons"
                    class="mr-auto text-sm text-[#00a5f4] flex flex-col relative max-w-[25em]"
                >
                    <div
                        v-for="(item, index) in JSON.parse(content?.metadata)?.buttons"
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
                            <img v-else-if="item.type === 'URL'" :src="'/images/icons/link.png'" class="h-4">
                            <img v-else :src="'/images/icons/reply.png'" class="h-4">
                        </span>
                        <span>{{ item.text }}</span>
                    </div>
                </div>
            </div>

            <!-- Reaction formatting -->
            <div v-if="JSON.parse(content?.metadata).type == 'reaction'">
                {{ JSON.parse(content?.metadata)?.reaction?.emoji }}
            </div>

            <!-- Document formatting -->
            <div v-else-if="JSON.parse(content?.metadata).type === 'document'">
                <div class="relative w-[300px]">
                    <!-- Document viewer for supported types -->
                    <div v-if="content.media != null && isViewableDocument(content.media.type) && isExpanded">
                        <iframe
                            :src="handleSrc(content)"
                            class="w-full h-[350px] border-0"
                            frameborder="0"
                            allowfullscreen
                        ></iframe>
                    </div>
                    <!-- Document preview (collapsed state) -->
                    <div v-else class="flex space-x-2 w-full bg-white opacity-90 pt-2">
                        <div>
                            <svg
                                v-if="getExtension(content?.media?.type) === 'PDF'"
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
                                v-if="getExtension(content?.media?.type) === 'XLSX' || getExtension(content?.media?.type) === 'XLS'"
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
                                v-if="getExtension(content?.media?.type) === 'DOC' || getExtension(content?.media?.type) === 'DOCX'"
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
                                <path fill="#2b7cd3" d="M30 9.5H8.512V16l11.069 1.95L30 16Z" />
                                <path fill="#185abd" d="M8.512 16v6.5l10.418 1.3L30 22.5V16Z" />
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
                                v-if="getExtension(content?.media?.type) === 'PPT' || getExtension(content?.media?.type) === 'PPTX'"
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
                                    d="M17.628 9.389V23.26a1.2 1.2 0 0 1-.742 1.1a1.16 1.16 0 0 1-.45.091H7.027a10.08 10.08 0 0 1-.521-.65a12.735 12.735 0 0 1-2.553-7.657v-.286A12.705 12.705 0 0 1 6.05 8.85a8.82 8.82 0 0 1 .456-.65h9.93a1.2 1.2 0 0 1 1.192 1.189Z"
                                    opacity=".1"
                                />
                                <path
                                    d="M16.977 10.04v13.871a1.15 1.15 0 0 1-.091.448a1.2 1.2 0 0 1-1.1.741H7.62q-.309-.314-.593-.65a10.08 10.08 0 0 1-.521-.65a12.735 12.735 0 0 1-2.553-7.657v-.286A12.705 12.705 0 0 1 6.05 8.85h9.735a1.2 1.2 0 0 1 1.192 1.19Z"
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
                                    d="M9.293 12.028a3.287 3.287 0 0 1 2.174.636a2.27 2.27 0 0 1 .756 1.841a2.555 2.555 0 0 1-.373 1.376a2.49 2.49 0 0 1-1.059.935a3.607 3.607 0 0 1-1.591.334H7.687v2.8H6.141v-7.922ZM7.686 15.94h1.331a1.735 1.735 0 0 0 1.177-.351a1.3 1.3 0 0 0 .4-1.025q0-1.309-1.525-1.31H7.686v2.686Z"
                                />
                            </svg>
                        </div>
                        <div>
                            <div>
                                <p class="overflow-ellipsis overflow-hidden line-clamp-2 w-[200px]">
                                    {{ content?.media?.name }}
                                </p>
                            </div>
                            <div class="flex items-center text-slate-500 text-[11px]">
                                <span>{{ getExtension(content?.media?.type) }}</span>
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
                                <span>{{ formatFileSize(content?.media?.size) }}</span>
                            </div>
                        </div>
                        <div class="flex justify-end w-full items-start">
                            <button @click="toggleExpand" class="text-blue-500 text-sm">
                                {{ isExpanded ? $t('Collapse') : $t('View') }}
                            </button>
                        </div>
                    </div>
                    <!-- Fallback for non-viewable documents or missing media -->
                    <div v-if="content.media == null || !isViewableDocument(content.media.type)" class="text-slate-500 flex justify-center items-center space-x-4">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
                            <g fill="none">
                                <path
                                    d="M24 0v24H0V0h24ZM12.593 23.258l-.011.002l-.071.035l-.02.004l-.014-.004l-.071-.035c-.01-.004-.019-.001-.024.005l-.004.01l-.017.428l.005.02l.01.013l.104.074l.015.004l.012-.004l.104-.074l.012-.016l.004-.017l-.017-.427c-.002-.01-.009-.017-.017-.018Zm.265-.113l-.013.002l-.185.093l-.01.01l-.003.011l.018.43l.005.012l.008.007l.201.093c.012.004.023 0 .029-.008l.004-.014l-.034-.614c-.003-.012-.01-.02-.20-.022Zm-.715.002a.023.023 0 0 0-.027.006l-.006.014l-.034.614c0 .012.007.02.017.024l.015-.002l.201-.093l.01-.008l.004-.011l.017-.43l-.003-.012l-.01-.01l-.184-.092Z"
                                />
                                <path
                                    fill="currentColor"
                                    d="m13.299 3.148l8.634 14.954a1.5 1.5 0 0 1-1.299 2.25H3.366a1.5 1.5 0 0 1-1.299-2.25l8.634-14.954c.577-1 2.02-1 2.598 0ZM12 4.898L4.232 18.352h15.536L12 4.898ZM12 15a1 1 0 1 1 0 2a1 1 0 0 1 0-2Zm0-7a1 1 0 0 1 1 1v4a1 1 0 1 1-2 0V9a1 1 0 0 1 1-1Z"
                                />
                            </g>
                        </svg>
                        {{ $t('Document not viewable in browser') }}
                    </div>
                    <a
                        :href="content?.media?.path"
                        :download="content?.media?.name"
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
                                d="M4.025 11.975q0 2.825 1.725 5t4.4 2.825q.375.1.6.425t.225.725q0 .4-.275.663t-.625.187q-3.5-.7-5.775-3.437t-2.275-6.388q0-3.65 2.288-6.387T10.1 2.15q.35-.075.625.188T11 3q0 .4-.225.725t-.6.425q-2.7.65-4.425 2.825t-1.725 5Zm8.95 1.15l1.85-1.85q.3-.3.725-.3t.725.3q.3.3.288.725t-.313.725L12.675 16.3q-.3.3-.7.3t-.7-.3L7.65 12.65q-.3-.3-.288-.713t.313-.712q.3-.3.713-.3t.712.3l1.875 1.9V8q0-.425.288-.713T11.975 7q.425 0 .713.288t.287.712v5.125Zm3.05 5.75q.375-.225.813-.2t.762.35q.3.3.25.713t-.4.637q-.8.5-1.675.863t-1.8.562q-.4.075-.7-.175t-.3-.675q0-.425.263-.75t.687-.45q.55-.15 1.075-.375t1.025-.5ZM13.975 4.2q-.425-.125-.7-.45T13 3q0-.425.288-.675t.687-.175q.95.2 1.838.562T17.5 3.6q.35.225.375.625t-.25.7q-.3.325-.738.35t-.812-.2q-.5-.275-1.025-.5T13.975 4.2Zm5.75 9.75q.125-.425.463-.7t.762-.275q.425 0 .688.325t.162.725q-.2.95-.575 1.812t-.9 1.663q-.225.35-.625.35t-.675-.275q-.3-.3-.337-.725t.187-.8q.275-.5.488-1.012t.362-1.088Zm-.85-6.075q-.225-.375-.188-.788t.338-.712q.275-.275.675-.263t.625.338q.55.8.925 1.688t.575 1.837q.075.4-.187.7t-.688.3q-.425 0-.762-.287t-.463-.713q-.15-.55-.363-1.075t-.487-1.025Z"
                            />
                        </svg>
                        <svg
                            v-else
                            xmlns="http://www.w3.org/2000/svg"
                            width="30"
                            height="30"
                            viewBox="0 0 24 24"
                        >
                            <g fill="none" stroke="currentColor" stroke-linecap="round" stroke-width="2">
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
                                <path stroke-dasharray="10" stroke-dashoffset="10" d="M12 8v7.5">
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
                <div
                    v-if="JSON.parse(content?.metadata)?.buttons"
                    class="mr-auto text-sm text-[#00a5f4] flex flex-col relative max-w-[25em]"
                >
                    <div
                        v-for="(item, index) in JSON.parse(content?.metadata)?.buttons"
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
                            <img v-else-if="item.type === 'URL'" :src="'/images/icons/link.png'" class="h-4">
                            <img v-else :src="'/images/icons/reply.png'" class="h-4">
                        </span>
                        <span>{{ item.text }}</span>
                    </div>
                </div>
            </div>

            <!-- Location formatting -->
            <div v-else-if="JSON.parse(content.metadata).type == 'location'">
                <iframe
                    v-if="isExpanded"
                    class="w-full h-[250px]"
                    :src="location(JSON.parse(content.metadata))"
                    style="border:0;"
                    allowfullscreen=""
                    loading="lazy"
                    referrerpolicy="no-referrer-when-downgrade"
                ></iframe>
                <div v-else class="flex items-center space-x-2 cursor-pointer" @click="toggleExpand">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
                        <path fill="currentColor" d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5a2.5 2.5 0 0 1 0-5a2.5 2.5 0 0 1 0 5z"/>
                    </svg>
                    <span>View Location</span>
                </div>
            </div>

            <!-- Sticker formatting -->
            <div v-else-if="JSON.parse(content?.metadata).type == 'sticker'">
                <img
                    v-if="content?.media != null"
                    :src="handleSrc(content)"
                    alt="Image"
                    class="mb-2 max-w-[100px]"
                />
                <div v-else class="text-slate-500 flex justify-center items-center space-x-4">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
                        <g fill="none">
                            <path
                                d="M24 0v24H0V0h24ZM12.593 23.258l-.011.002l-.071.035l-.02.004l-.014-.004l-.071-.035c-.01-.004-.019-.001-.024.005l-.004.01l-.017.428l.005.02l.01.013l.104.074l.015.004l.012-.004l.104-.074l.012-.016l.004-.017l-.017-.427c-.002-.01-.009-.017-.017-.018Zm.265-.113l-.013.002l-.185.093l-.01.01l-.003.011l.018.43l.005.012l.008.007l.201.093c.012.004.023 0 .029-.008l.004-.014l-.034-.614c-.003-.012-.01-.02-.20-.022Zm-.715.002a.023.023 0 0 0-.027.006l-.006.014l-.034.614c0 .012.007.02.017.024l.015-.002l.201-.093l.01-.008l.004-.011l.017-.43l-.003-.012l-.01-.01l-.184-.092Z"
                            />
                            <path
                                fill="currentColor"
                                d="m13.299 3.148l8.634 14.954a1.5 1.5 0 0 1-1.299 2.25H3.366a1.5 1.5 0 0 1-1.299-2.25l8.634-14.954c.577-1 2.02-1 2.598 0ZM12 4.898L4.232 18.352h15.536L12 4.898ZM12 15a1 1 0 1 1 0 2a1 1 0 0 1 0-2Zm0-7a1 1 0 0 1 1 1v4a1 1 0 1 1-2 0V9a1 1 0 0 1 1-1Z"
                            />
                        </g>
                    </svg>
                    {{ $t('Content not available') }}
                </div>
            </div>

            <!-- Contacts formatting -->
            <div v-else-if="JSON.parse(content.metadata).type === 'contacts'">
                <div class="flex space-x-3 w-[300px] items-center">
                    <div class="rounded-full p-3 bg-slate-200">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 512 512">
                            <path
                                fill="currentColor"
                                d="M332.64 64.58C313.18 43.57 286 32 256 32c-30.16 0-57.43 11.5-76.8 32.38c-19.58 21.11-29.12 49.8-26.88 80.78C156.76 206.28 203.27 256 256 256s99.16-49.71 103.67-110.82c2.27-30.7-7.33-59.33-27.03-80.6ZM432 480H80a31 31 0 0 1-24.2-11.13c-6.5-7.77-9.12-18.38-7.18-29.11C57.06 392.94 83.4 353.61 124.8 326c36.78-24.51 83.37-38 131.2-38s94.42 13.5 131.2 38c41.4 27.6 67.74 66.93 76.18 113.75c1.94 10.73-.68 21.34-7.18 29.11A31 31 0 0 1 432 480Z"
                            />
                        </svg>
                    </div>
                    <div>
                        {{ getContactDisplayName(content?.metadata) }}
                    </div>
                </div>
            </div>

            <!-- Audio formatting -->
            <div v-else-if="JSON.parse(content?.metadata).type === 'audio'">
                <audio
                    v-if="content.media != null"
                    controls
                    @play="handleMediaPlay"
                    @ended="handleMediaEnded"
                >
                    <source :src="handleSrc(content)">
                    {{ $t('Your browser does not support the audio element') }}
                </audio>
                <div v-else class="text-slate-500 flex justify-center items-center space-x-4">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
                        <g fill="none">
                            <path
                                d="M24 0v24H0V0h24ZM12.593 23.258l-.011.002l-.071.035l-.02.004l-.014-.004l-.071-.035c-.01-.004-.019-.001-.024.005l-.004.01l-.017.428l.005.02l.01.013l.104.074l.015.004l.012-.004l.104-.074l.012-.016l.004-.017l-.017-.427c-.002-.01-.009-.017-.017-.018Zm.265-.113l-.013.002l-.185.093l-.01.01l-.003.011l.018.43l.005.012l.008.007l.201.093c.012.004.023 0 .029-.008l.004-.014l-.034-.614c-.003-.012-.01-.02-.20-.022Zm-.715.002a.023.023 0 0 0-.027.006l-.006.014l-.034.614c0 .012.007.02.017.024l.015-.002l.201-.093l.01-.008l.004-.011l.017-.43l-.003-.012l-.01-.01l-.184-.092Z"
                            />
                            <path
                                fill="currentColor"
                                d="m13.299 3.148l8.634 14.954a1.5 1.5 0 0 1-1.299 2.25H3.366a1.5 1.5 0 0 1-1.299-2.25l8.634-14.954c.577-1 2.02-1 2.598 0ZM12 4.898L4.232 18.352h15.536L12 4.898ZM12 15a1 1 0 1 1 0 2a1 1 0 0 1 0-2Zm0-7a1 1 0 0 1 1 1v4a1 1 0 1 1-2 0V9a1 1 0 0 1 1-1Z"
                            />
                        </g>
                    </svg>
                    {{ $t('Content not available') }}
                </div>
            </div>

            <!-- Video formatting -->
            <div v-else-if="JSON.parse(content?.metadata).type === 'video'">
                <div class="relative">
                    <video
                        v-if="content.media != null && handleSrc(content)"
                        controls
                        class="w-full max-w-[300px] rounded"
                        @play="handleMediaPlay"
                        @ended="handleMediaEnded"
                        @loadedmetadata="onVideoLoaded"
                        @error="e => console.error('Video error:', e)"
                    >
                        <source :src="handleSrc(content)" :type="content.media?.type || 'video/mp4'">
                        {{ $t('Your browser does not support the video element') }}
                    </video>
                    <div
                        v-if="formatDuration(videoDuration)"
                        class="absolute bottom-2 right-2 bg-black bg-opacity-50 text-white text-xs px-1 py-0.5 rounded"
                        >
                        {{ formatDuration(videoDuration) }}
                    </div>
                </div>
                <div v-if="JSON.parse(content?.metadata).video?.caption" class="max-w-[300px] break-words mt-1">
                    {{ JSON.parse(content.metadata).video?.caption }}
                </div>
                <div v-if="!content.media || !handleSrc(content)" class="text-slate-500 flex justify-center items-center space-x-4">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
                        <g fill="none">
                            <path
                                d="M24 0v24H0V0h24ZM12.593 23.258l-.011.002l-.071.035l-.02.004l-.014-.004l-.071-.035c-.01-.004-.019-.001-.024.005l-.004.01l-.017.428l.005.02l.01.013l.104.074l.015.004l.012-.004l.104-.074l.012-.016l.004-.017l-.017-.427c-.002-.01-.009-.017-.017-.018Zm.265-.113l-.013.002l-.185.093l-.01.01l-.003.011l.018.43l.005.012l.008.007l.201.093c.012.004.023 0 .029-.008l.004-.014l-.034-.614c-.003-.012-.01-.02-.20-.022Zm-.715.002a.023.023 0 0 0-.027.006l-.006.014l-.034.614c0 .012.007.02.017.024l.015-.002l.201-.093l.01-.008l.004-.011l.017-.43l-.003-.012l-.01-.01l-.184-.092Z"
                            />
                            <path
                                fill="currentColor"
                                d="m13.299 3.148l8.634 14.954a1.5 1.5 0 0 1-1.299 2.25H3.366a1.5 1.5 0 0 1-1.299-2.25l8.634-14.954c.577-1 2.02-1 2.598 0ZM12 4.898L4.232 18.352h15.536L12 4.898ZM12 15a1 1 0 1 1 0 2a1 1 0 0 1 0-2Zm0-7a1 1 0 0 1 1 1v4a1 1 0 1 1-2 0V9a1 1 0 0 1 1-1Z"
                            />
                        </g>
                    </svg>
                    {{ $t('Content not available') }}
                </div>
            </div>

            <!-- Timestamp -->
            <div class="flex items-center justify-between mt-2 space-x-4">
                <span
                    v-if="props.type === 'outbound' && content.user"
                    class="text-gray-500 text-xs text-right leading-none"
                >
                    Sent By: {{ content.user?.first_name + ' ' + content.user?.last_name }}
                </span>
                <p class="text-gray-500 text-xs text-right leading-none">
                    {{ moment(content.created_at).format("hh:mm A") }}
                </p>
                <div class="flex items-center">
                    <svg
                        v-if="content.is_starred"
                        xmlns="http://www.w3.org/2000/svg"
                        width="17"
                        height="17"
                        viewBox="0 0 24 24"
                    >
                        <path
                            fill="gray"
                            d="m7.325 18.923l1.24-5.313l-4.123-3.572l5.431-.47L12 4.557l2.127 5.01l5.43.47l-4.123 3.572l1.241 5.313L12 16.102z"
                        />
                    </svg>
                    <svg
                        class="mr-[2px]"
                        v-if="content.is_pinned"
                        xmlns="http://www.w3.org/2000/svg"
                        width="12"
                        height="12"
                        viewBox="0 0 56 56"
                    >
                        <path
                            fill="gray"
                            d="M14.254 35.969h11.695v12.047c0 3.562 1.477 6.562 2.063 6.562s2.062-3 2.062-6.562V35.969h11.672c1.617 0 2.79-1.032 2.79-2.602c0-.984-.306-1.687-.985-2.414l-7.172-7.781c-.492-.516-.797-.89-.68-1.852l1.196-8.554c.07-.492.117-.774.539-1.078l5.765-4.172c1.29-.938 1.852-2.086 1.852-3.141c0-1.57-1.266-2.953-3.07-2.953H14.02c-1.805 0-3.07 1.383-3.07 2.953c0 1.055.562 2.203 1.827 3.14l5.766 4.173c.445.304.492.585.562 1.078l1.196 8.554c.117.961-.188 1.336-.68 1.852l-7.172 7.781c-.68.727-.984 1.43-.984 2.414c0 1.57 1.172 2.602 2.789 2.602"
                        />
                    </svg>
                    <p
                        class="text-xs text-gray-500"
                        v-if="props.type === 'outbound'"
                        v-html="handleStatusIcon"
                    ></p>
                </div>
            </div>

            <div
                v-if="JSON.parse(content?.metadata).type === 'contacts'"
                class="cursor-pointer text-center border-t mt-2 pt-2"
                @click="toggleExpand"
            >
                {{ isExpanded ? $t('Collapse') : $t('View') }}
            </div>
        </div>
    </div>
    <ChatPinnedLimitError v-model="isPinnedLimitError" />
</template>