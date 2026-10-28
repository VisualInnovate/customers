<script setup>

    import { onMounted, ref, defineModel } from 'vue';
    import Modal from '@/Components/Modal.vue';

    const modelValue = defineModel({
        type: Boolean,
    });

</script>
<template>
    <Modal :label="$t('Pinned Messages Limit Reached')" :isOpen=modelValue>
        <p> {{ $t('Maximum number of pinned messages is 3 please unpin some messages then try again') }} </p>
      
        <button type="button" @click.self="modelValue = false"
            class="mt-2 block ml-auto justify-center  rounded-md border border-transparent bg-primary px-4 py-2 text-sm text-white hover:opacity-80 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2 mr-4">{{
                $t('Ok') }}</button>
    </Modal>
</template>