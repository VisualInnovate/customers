import { reactive } from 'vue';

// Shared state to ensure only one media element plays at a time across all ChatBubble instances.
export const sharedMediaState = reactive({
    currentMedia: null,
}); 