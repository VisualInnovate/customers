```vue
<script setup>
import { ref, computed } from 'vue';
import { GoogleMap, Marker } from 'vue3-google-map';

const props = defineProps({
    content: {
        type: Object,
        required: true,
        default: () => ({}),
    },
    showRemove: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['remove', 'error']);

const baseUrl = import.meta.env.VITE_MEDIA_BASE_URL || 'https://app.customercloudhq.com/media/';
const googleMapsApiKey = import.meta.env.VITE_GOOGLE_MAPS_API_KEY || '';

const handleSrc = (path) => {
    if (!path || typeof path !== 'string' || path.includes('javascript:')) {
        emit('error', 'معاينة المحتوى غير متاحة');
        return '';
    }
    return path.includes(baseUrl) ? path : `${baseUrl}${path}`;
};

const parsedMetadata = computed(() => {
    if (!props.content?.metadata) return {};
    try {
        return JSON.parse(props.content.metadata);
    } catch (e) {
        console.error('Failed to parse content metadata:', e);
        emit('error', 'فشل في تحليل بيانات الرسالة');
        return {};
    }
});

const senderDisplayName = computed(() => {
    if (!props.content) return '';
    if (props.content.type === 'outbound' && props.content.contact?.full_name) {
        return props.content.senderName;
    }
    return props.content.senderName || '';
});

const location = computed(() => {
    const item = parsedMetadata.value;
    if (!item.location?.latitude || !item.location?.longitude) {
        return null;
    }
     return { lat: item.location?.latitude || 0, lng: item.location?.longitude || 0 };
});

const handleImageError = () => {
    emit('error', 'فشل في تحميل الصورة');
};

const handleRemove = (e) => {
    e.stopPropagation();
    emit('remove');
};
</script>

<template>
    <div class="relative w-full mb-1 bg-black bg-opacity-5 p-2 rounded-md border-l-primary border-l-[5px]">
        <p v-if="senderDisplayName" class="text-xs font-semibold text-gray-600 mb-1">{{ senderDisplayName }}</p>
        <p
            v-if="parsedMetadata.type === 'text' && parsedMetadata.text?.body"
            class="normal-case text-sm max-h-[100px] overflow-y-auto"
        >
            {{ parsedMetadata.text.body }}
        </p>
        <div v-else-if="parsedMetadata.type === 'image'">
            <img
                v-if="props.content.media?.path"
                :src="props.content.media.path"
                :alt="parsedMetadata.image?.caption || 'صورة الرد'"
                class="mb-2 max-w-[300px] max-h-[150px] object-contain"
                loading="lazy"
                @error="handleImageError"
            />
            <div
                v-else
                class="text-slate-500 flex justify-center items-center space-x-4"
            >
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    width="24"
                    height="24"
                    viewBox="0 0 24 24"
                    aria-hidden="true"
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
            <div v-if="parsedMetadata.image?.caption" class="max-w-[300px] text-sm">
                {{ parsedMetadata.image.caption }}
            </div>
        </div>
        <div v-else-if="parsedMetadata.type === 'location'">
            <div v-if="location && googleMapsApiKey" class="relative">
                <GoogleMap
                    api-key=""
                    class="w-full max-w-[300px] h-[200px]"
                    :center="location"
                    :zoom="15"
                    aria-label="Map showing location"
                >
                    <Marker :options="{ position: location }" :aria-label="`Marker at latitude ${location.lat}, longitude ${location.lng}`" />
                </GoogleMap>
                <div v-if="parsedMetadata.location?.name" class="mt-2 text-sm text-gray-600">
                    {{ parsedMetadata.location.name }}
                </div>
                <div v-if="parsedMetadata.location?.address" class="text-sm text-gray-600">
                    {{ parsedMetadata.location.address }}
                </div>
            </div>
            <div v-else class="text-slate-500 flex justify-center items-center space-x-4">
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    width="24"
                    height="24"
                    viewBox="0 0 24 24"
                    aria-hidden="true"
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
                {{ $t('Location not available') }}
            </div>
        </div>
        <div v-else-if="parsedMetadata.type === 'audio'" class="flex items-center space-x-4">
            <svg
                xmlns="http://www.w3.org/2000/svg"
                width="24"
                height="24"
                viewBox="0 0 24 24"
                aria-hidden="true"
            >
                <path
                    fill="currentColor"
                    d="M842 454c0-4.4-3.6-8-8-8h-60c-4.4 0-8 3.6-8 8c0 140.3-113.7 254-254 254S258 594.3 258 454c0-4.4-3.6-8-8-8h-60c-4.4 0-8 3.6-8 8c0 168.7 126.6 307.9 290 327.6V884H326.7c-13.7 0-24.7 14.3-24.7 32v36c0 4.4 2.8 8 6.2 8h407.6c3.4 0 6.2-3.6 6.2-8v-36c0-17.7-11-32-24.7-32H548V782.1c165.3-18 294-158 294-328.1M512 624c93.9 0 170-75.2 170-168V232c0-92.8-76.1-168-170-168s-170 75.2-170 168v224c0 92.8 76.1 168 170 168m-94-392c0-50.6 41.9-92 94-92s94 41.4 94 92v224c0 50.6-41.9 92-94 92s-94-41.4-94-92z"
                />
            </svg>
            <span class="text-sm">{{ $t('Audio message') }}</span>
        </div>
        <div v-else-if="parsedMetadata.type === 'video'" class="flex items-center space-x-4">
            <svg
                xmlns="http://www.w3.org/2000/svg"
                width="24"
                height="24"
                viewBox="0 0 24 24"
                aria-hidden="true"
            >
                <path
                    fill="currentColor"
                    d="M8 5v14l11-7z"
                />
            </svg>
            <span class="text-sm">{{ $t('Video message') }}</span>
        </div>
        <div v-else-if="parsedMetadata.type === 'document'" class="flex items-center space-x-4">
            <svg
                xmlns="http://www.w3.org/2000/svg"
                width="24"
                height="24"
                viewBox="0 0 24 24"
                aria-hidden="true"
            >
                <path
                    fill="currentColor"
                    d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6zm4 18H6V4h7v5h5v11z"
                />
            </svg>
            <span class="text-sm">{{ $t('Document') }}</span>
        </div>
        <button
            v-if="showRemove"
            class="absolute top-[50%] translate-y-[-50%] right-2"
            @click="handleRemove"
            aria-label="إزالة الرد"
            title="إزالة الرد"
        >
            <svg
                xmlns="http://www.w3.org/2000/svg"
                width="25"
                height="25"
                viewBox="0 0 24 24"
                aria-hidden="true"
            >
                <path
                    fill="#c4c4c4"
                    d="M19.1 4.9C15.2 1 8.8 1 4.9 4.9S1 15.2 4.9 19.1s10.2 3.9 14.1 0s4-10.3.1-14.2m-4.3 11.3L12 13.4l-2.8 2.8l-1.4-1.4l2.8-2.8l-2.8-2.8l1.4-1.4l2.8 2.8l2.8-2.8l1.4 1.4l-2.8 2.8l2.8 2.8z"
                />
            </svg>
        </button>
    </div>
</template>
```