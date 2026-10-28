<script setup>

    import { onMounted, ref, defineModel } from 'vue';
    import Modal from '@/Components/Modal.vue';
    import axios from 'axios';

    const emit = defineEmits(['on-select']);
    const modelValue = defineModel({
        type: Boolean,
    });

    const savedReplies = ref([]);

    const getData = async () => {
        const { data } = await axios.get('/saved-replies/chats');
        savedReplies.value = data.data;
    }
    onMounted(async () => await getData());
</script>
<template>
    <Modal :label="$t('Saved Replies')" :isOpen=modelValue>

        <p class="text-sm text-slate-500 mb-2">{{ $t('Select a saved reply to use') }}</p>
        <ul class="overflow-y-scroll max-h-[50vh]">
            <li v-for="item in savedReplies" @click="emit('on-select', item)"
                class="flex items-center cursor-pointer hover:opacity-60 transition gap-2 py-1 w-full mb-2 px-2 bg-gray-50 rounded-[5px] border border-dashed  text-sm">
                <!-- <img v-if="item.path" class="h-20 w-20 border p-2" :src="item.path" alt=""> -->
                <p class="truncate w-full">
                    {{ item.reply }}
                </p>
            </li>
        </ul>
        <button type="button" @click.self="modelValue = false"
            class="mt-2 block ml-auto justify-center  rounded-md border border-transparent bg-primary px-4 py-2 text-sm text-white hover:opacity-80 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2 mr-4">{{
                $t('Close') }}</button>
    </Modal>
</template>