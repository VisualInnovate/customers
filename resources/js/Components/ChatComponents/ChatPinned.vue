<script setup>
    import { set } from 'lodash';
    import { ref } from 'vue';

    const props = defineProps({
        content: Array,
    });

    // State to track the active step
    const activeStep = ref(0);

    // Function to set the active step
    const setActiveStep = () => {
        const section = document.getElementById(`bubble-id-${props.content[activeStep.value].id}`);
        const container = document.getElementById('chats-container');
        container.scrollTo({
            top: section.offsetTop - 10,
            behavior: "smooth"
        });
        setTimeout(() => {
            section.classList.add('fade-in');
        }, 200);
        setTimeout(() => {
            section.classList.remove('fade-in');
        }, 400)

        activeStep.value === props.content.length - 1 ? (activeStep.value = 0) : activeStep.value++;
    };
</script>

<template>
    <Teleport to='#pinned-container'>
        <div @click="setActiveStep"
            class="w-full cursor-pointer py-2 h-14 relative px-6 md:px-10 sticky top-0 left-0 z-[9] bg-slate-100">
            <div class="flex gap-3 items-center">
                <div class=" shrink-0 rounded-full flex items-center justify-center bg-slate-200 w-10 h-10">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 56 56">
                        <path fill="gray"
                            d="M14.254 35.969h11.695v12.047c0 3.562 1.477 6.562 2.063 6.562s2.062-3 2.062-6.562V35.969h11.672c1.617 0 2.79-1.032 2.79-2.602c0-.984-.306-1.687-.985-2.414l-7.172-7.781c-.492-.516-.797-.89-.68-1.852l1.196-8.554c.07-.492.117-.774.539-1.078l5.765-4.172c1.29-.938 1.852-2.086 1.852-3.141c0-1.57-1.266-2.953-3.07-2.953H14.02c-1.805 0-3.07 1.383-3.07 2.953c0 1.055.562 2.203 1.827 3.14l5.766 4.173c.445.304.492.585.562 1.078l1.196 8.554c.117.961-.188 1.336-.68 1.852l-7.172 7.781c-.68.727-.984 1.43-.984 2.414c0 1.57 1.172 2.602 2.789 2.602" />
                    </svg>
                </div>
                <div
                    class="flex-1 hover:opacity-50 transition  content w-full items-center flex justify-between text-slate-600">
                    <Transition name="slide-down" mode="out-in">
                        <div v-if="content.length" :key="activeStep" class="w-full">
                            <p v-if="JSON.parse(content[activeStep].metadata).type === 'text'"
                                class="w-[90%] overflow-hidden truncate text-sm">
                                {{ JSON.parse(content[activeStep].metadata)?.text?.body }}
                            </p>
                            <div v-else-if="JSON.parse(content[activeStep].metadata).type === 'image'">
                                <div class="flex gap-1 text-sm items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24">
                                        <path fill="currentColor"
                                            d="M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.9 13.98l2.1 2.53l3.1-3.99c.2-.26.6-.26.8.01l3.51 4.68a.5.5 0 0 1-.4.8H6.02c-.42 0-.65-.48-.39-.81L8.12 14c.19-.26.57-.27.78-.02z" />
                                    </svg>
                                    <div v-if="JSON.parse(content[activeStep].metadata).image?.caption"
                                        class="w-[90%] overflow-hidden truncate">
                                        {{ JSON.parse(content[activeStep].metadata).image?.caption }}
                                    </div>
                                    <span v-else class="ml-2">{{ $t('Photo') }}</span>
                                </div>
                            </div>
                        </div>
                    </Transition>
                    <div v-if="content?.length > 1">
                        <div
                            class="absolute top-1/2 transform -translate-y-1/2 right-4 rounded-full flex items-center text-xs justify-center bg-slate-200 w-5 h-5">
                            {{ activeStep + 1 }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<style>

    .slide-down-enter-active,
    .slide-down-leave-active {
        transition: transform 0.3s ease, opacity 0.3s ease;
    }

    .slide-down-enter-from {
        transform: translateY(3px);
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
        transform: translateY(3px);
        opacity: 0;
    }

    .fade-in {
        filter: brightness(0.7) contrast(1.5);

    }


</style>
