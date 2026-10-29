<script setup>
    import { ref } from 'vue';
    import { TransitionRoot, TransitionChild, Dialog, DialogPanel, DialogTitle } from '@headlessui/vue';

    const props = defineProps({
        label: String,
        isOpen: Boolean,
        closeBtn: Boolean,
        dialogCustomClass : String
    })

    const emit = defineEmits(['close']);

    function closeModal() {
        emit('close', true);
    }
</script>
<template>
    <TransitionRoot appear :show="props.isOpen" as="template">
        <Dialog as="div" class="relative z-10 "  >
            <TransitionChild
                as="template"
                enter="duration-300 ease-out"
                enter-from="opacity-0"
                enter-to="opacity-100"
                leave="duration-200 ease-in"
                leave-from="opacity-100"
                leave-to="opacity-0"
            >
                <div class="fixed inset-0 bg-black bg-opacity-25" />
            </TransitionChild>

            <div class="fixed inset-0 ">
                <div
                class="flex min-h-full items-center justify-center p-4 text-center"
                >
                <TransitionChild
                    as="template"
                    enter="duration-300 ease-out"
                    enter-from="opacity-0 scale-95"
                    enter-to="opacity-100 scale-100"
                    leave="duration-200 ease-in"
                    leave-from="opacity-100 scale-100"
                    leave-to="opacity-0 scale-95"
                    
                >
                    <DialogPanel
                    class="w-full flex flex-col max-w-md transform max-h-[90vh] overflow-hidden rounded-2xl bg-white text-left align-middle shadow-xl transition-all"
                   :class="props.dialogCustomClass"
                    >
                    <div class="flex justify-between  items-center bg-gray-50 px-4 py-3">
                        <DialogTitle as="h3" class="text-lg font-medium leading-6 text-gray-900">
                            {{ props.label }}
                        </DialogTitle>

                        <span @click="closeModal" v-if="closeBtn === true" class="bg-slate-100 rounded-full p-1 hover:shadow cursor-pointer">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path fill="none" stroke="black" stroke-linecap="round" stroke-linejoin="round" d="M17 7L7 17M7 7l10 10"/></svg>
                        </span>
                    </div>
                    <div class="px-4 flex-1 pb-4 overflow-y-auto h-full overflow-x-hidden">
                        <slot />
                    </div>
                    </DialogPanel>
                </TransitionChild>
                </div>
            </div>
        </Dialog>
    </TransitionRoot>
</template>