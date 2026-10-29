<script setup>
const props = defineProps({
    content: Object,
    showRemove : Boolean
})
const emit = defineEmits(['remove']);
const handleSrc = (path) => {
    const baseUrl =  "https://app.customercloudhq.com/media/";
    if (path.includes(baseUrl)) return path
    else  return baseUrl + path;

}
</script>
<template>

<div>
        
    <div  class="relative w-full mb-1 bg-black bg-opacity-5 p-2 rounded-md border-l-primary  border-l-[5px] ">
     
        <p v-if="JSON.parse(content.metadata).type === 'text' " class="normal-case truncate overflow-hidden  ">{{ JSON.parse(content.metadata)?.text?.body }}</p>
        <div v-else-if="JSON.parse(content.metadata).type === 'image' ">
        <img v-if="content.media != null" :src="handleSrc(content?.media?.path)" alt="Image" class="mb-2 max-w-[300px] max-h-[50px]" />
        <div v-else class="text-slate-500 flex justify-center items-center space-x-4">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><g fill="none"><path d="M24 0v24H0V0h24ZM12.593 23.258l-.011.002l-.071.035l-.02.004l-.014-.004l-.071-.035c-.01-.004-.019-.001-.024.005l-.004.01l-.017.428l.005.02l.01.013l.104.074l.015.004l.012-.004l.104-.074l.012-.016l.004-.017l-.017-.427c-.002-.01-.009-.017-.017-.018Zm.265-.113l-.013.002l-.185.093l-.01.01l-.003.011l.018.43l.005.012l.008.007l.201.093c.012.004.023 0 .029-.008l.004-.014l-.034-.614c-.003-.012-.01-.02-.02-.022Zm-.715.002a.023.023 0 0 0-.027.006l-.006.014l-.034.614c0 .012.007.02.017.024l.015-.002l.201-.093l.01-.008l.004-.011l.017-.43l-.003-.012l-.01-.01l-.184-.092Z"/><path fill="currentColor" d="m13.299 3.148l8.634 14.954a1.5 1.5 0 0 1-1.299 2.25H3.366a1.5 1.5 0 0 1-1.299-2.25l8.634-14.954c.577-1 2.02-1 2.598 0ZM12 4.898L4.232 18.352h15.536L12 4.898ZM12 15a1 1 0 1 1 0 2a1 1 0 0 1 0-2Zm0-7a1 1 0 0 1 1 1v4a1 1 0 1 1-2 0V9a1 1 0 0 1 1-1Z"/></g></svg>
            {{ $t('Content not available') }}
        </div>
        <div v-if="JSON.parse(content.metadata).image?.caption" class="max-w-[300px]">{{ JSON.parse(content.metadata).image?.caption }}</div>
        </div>
        <button v-if="showRemove" class="absolute top-[50%] translate-y-[-50%] right-2" @click="emit('remove')" >
           <svg xmlns="http://www.w3.org/2000/svg" width="25" height="25" viewBox="0 0 24 24"><path fill="#c4c4c4" d="M19.1 4.9C15.2 1 8.8 1 4.9 4.9S1 15.2 4.9 19.1s10.2 3.9 14.1 0s4-10.3.1-14.2m-4.3 11.3L12 13.4l-2.8 2.8l-1.4-1.4l2.8-2.8l-2.8-2.8l1.4-1.4l2.8 2.8l2.8-2.8l1.4 1.4l-2.8 2.8l2.8 2.8z"/></svg>
        </button>
    </div>
        
</div>
</template>

<style>

</style>