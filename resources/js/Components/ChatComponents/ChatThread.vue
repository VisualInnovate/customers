<<script setup>
    import ChatBubble from '@/Components/ChatComponents/ChatBubble.vue';
    import axios from 'axios';
    import ChatPinned from './ChatPinned.vue';
    import { onMounted, ref } from 'vue';
    import moment from 'moment';
import { json } from 'body-parser';
import { join } from 'lodash';
const props = defineProps(['rows','statusTracker']);
 
    const emits = defineEmits(['replay']);
    const pinnedChats = ref([])

    const hasDateChanged = (currentIndex) => {
    if (!props.rows || currentIndex === 0 || !props.rows[currentIndex - 1]) return true;
    const currentDate = moment(props.rows[currentIndex]?.[0]?.value?.created_at).format("MM/DD/YYYY");
    const previousDate = moment(props.rows[currentIndex - 1]?.[0]?.value?.created_at).format("MM/DD/YYYY");
    return currentDate !== previousDate;
        };
    const pinMessage = async (values) => {
        await updateMessageStatus(values)
        await getPinnedMessages(values.chat?.contact_id)
    }
    const getPinnedMessages = async (id) => {
        const { data } = await axios.get(`/pinned/chats?contact_id=${id}`);
        pinnedChats.value = data
    }
    const getReplayMessage = (id) => {
    if (!id || !props.rows) return null;
    const row = props.rows.find((row) => row?.[0]?.value?.id === id);
    return row ? row[0].value : null;
};

    const updateMessageStatus = async (values) => {

        const formData = new FormData();

        formData.append('chat_id', values.chat.id);
        formData.append('action', values.type);
        try {
            const response = await axios.post('/toggle/chats', formData);

        } catch (error) {
            // Handle the error
            // console.error('Error:', error);
        }
    }
    onMounted(async () => {
        getPinnedMessages(props.rows[0][0]?.value?.contact_id)
    });

</script>
<template>
    <div>
        <Transition name="slide-down" mode="out-in">
            <ChatPinned v-if="pinnedChats.length" :content="pinnedChats" />
        </Transition>

       
        <div v-if="rows && rows.length > 0" class="py-4 md:py-4 relative px-6 md:px-10">
            <div v-for="(chat, index) in rows" :key="index" class="flex flex-grow flex-col"
                :class="chat[0]?.type === 'ticket' ? 'justify-center' : 'justify-end'">
                <p v-if="hasDateChanged(index)" class="w-full text-center text-slate-700">
                    {{ moment(chat[0]?.value?.created_at).format("DD-MM-YYYY") }}
                </p>
             
                <ChatBubble v-if="chat[0]?.type === 'chat'"
                    :pinned-messages="pinnedChats"
                    :replay-message="getReplayMessage(chat[0]?.value?.old_message_id)" :status="statusTracker"
                    :content="chat[0].value" :type="chat[0]?.value?.type"
                    @replay="(value) => emits('replay', value)" @pin="pinMessage" @star="updateMessageStatus" />
               
                <div v-if="chat[0]?.type === 'notes'"
                    class="py-2 bg-orange-100 my-2 rounded-lg p-2 w-[fit-content] ml-auto">
                    <div class="text-right font-light text-sm">
                        <div>{{ chat[0].value.content }}</div>
                        <div class="flex items-center justify-between mt-2 space-x-4">
                            <p class="text-gray-500 text-xs text-right leading-none">{{ chat[0]?.value?.created_at }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style>

.slide-down-enter-active,
.slide-down-leave-active {
  transition: transform 0.3s ease, opacity 0.3s ease;
}

.slide-down-enter-from {
  transform: translateY(-3px);
  opacity: 0;
}

.slide-down-enter-to {
  transform: translateY(0);
  opacity: 1;
}

.slide-down-leave-from {
  transform: translateY(0);
  opacity: 1;
}

.slide-down-leave-to {
  transform: translateY(-3px);
  opacity: 0;
}

</style>>