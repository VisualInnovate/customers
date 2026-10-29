<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { Link } from "@inertiajs/vue3";
import { router, useForm } from "@inertiajs/vue3";
const props = defineProps({
    channel: {
        type: String,
        default: 'all' // Default value for channel
    },
    ChannelsCount: {
        type: Number,
        default: 0 // Default value for ChannelsCount
    }
});

const emit = defineEmits(['update:channel']); // Emit event to update the channel

const isOpen = ref(false);
const selectedChannel = ref(props.channel); // Internal state for the selected channel

const toggleDropdown = () => {
    isOpen.value = !isOpen.value;
};

const handleClickOutside = (event) => {
    if (isOpen.value && !event.target.closest('.channel-dd')) {
        isOpen.value = false;
    }
};

const selectChannel = (channel) => {
    selectedChannel.value = channel; // Update the internal state
    emit('update:channel', channel);
    isOpen.value = false;
    if( selectedChannel.value == 'instagram'){
        router.visit('/chats?channel=instagram');
    }else if(selectedChannel.value == 'whatsApp'){
        router.visit('/chats?channel=whatsApp');
    }else{
        router.visit('/chats');  
    }
  
};

onMounted(() => {
    document.body.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    
    document.body.removeEventListener('click', handleClickOutside);
});
</script>

<template>
    <div class="relative text-sm">
        <div @click="toggleDropdown()" class="channel-dd">
            <div class="cursor-pointer flex items-center space-x-1 bg-slate-50 w-[fit-content] h-[fit-content] rounded-md p-1 px-2 text-sm">
                <span class="capitalize">{{ selectedChannel }}</span>
                
                <span>
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path fill="currentColor" fill-rule="evenodd" d="M16.53 8.97a.75.75 0 0 1 0 1.06l-4 4a.75.75 0 0 1-1.06 0l-4-4a.75.75 0 1 1 1.06-1.06L12 12.44l3.47-3.47a.75.75 0 0 1 1.06 0" clip-rule="evenodd"/></svg>
                </span>
            </div>
        </div>
        <div v-if="isOpen" class="absolute bg-white z-10 px-1 py-2 mt-2 shadow w-fit rounded-md text-black">
            <div>
                <div @click="selectChannel('whatsApp')" class="block px-2 py-1 cursor-pointer hover:bg-slate-100 rounded-md">
                    {{ $t('whatsApp') }}
                </div>
                <div @click="selectChannel('instagram')" class="block px-2 py-1 cursor-pointer hover:bg-slate-100 rounded-md">
                    {{ $t('Instagram') }}
                </div>
                <div @click="selectChannel('all')" class="block px-2 py-1 cursor-pointer hover:bg-slate-100 rounded-md">
                    {{ $t('All') }}
                </div>
            </div>
        </div>
    </div>
</template>