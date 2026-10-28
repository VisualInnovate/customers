<template>
    <AppLayout>
        <div class="bg-white md:bg-inherit md:flex md:flex-grow md:overflow-y-hidden">
            <div class="md:w-[60%] m-8">
                <Menu v-if="webhookModule" />
                <div class="flex justify-between mt-8">
                    <div>
                        <h3 class="text-md mb-1">{{ $t('Access Tokens') }}</h3>
                        <p class="mb-6 flex items-center text-sm leading-6 text-gray-600">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24">
                                <path fill="none" stroke="currentColor" stroke-linecap="round"
                                    stroke-linejoin="round" stroke-width="2"
                                    d="M12 11v5m0 5a9 9 0 1 1 0-18a9 9 0 0 1 0 18Zm.05-13v.1h-.1V8h.1Z" />
                            </svg>
                            <span class="ml-1 mt-1">{{ $t('Create and manage your API keys') }}</span>
                        </p>
                    </div>
                    <div>
                        <button @click="openModal" type="button" :disabled="loadIcon"
                            class="rounded-md bg-primary px-3 py-2 text-sm text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                            <span v-if="!loadIcon">{{ $t('Generate API key') }}</span>
                            <span v-else>
                                <svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em"
                                    viewBox="0 0 24 24">
                                    <path fill="none" stroke="currentColor" stroke-dasharray="15"
                                        stroke-dashoffset="15" stroke-linecap="round" stroke-width="2"
                                        d="M12 3C16.9706 3 21 7.02944 21 12">
                                        <animate fill="freeze" attributeName="stroke-dashoffset" dur="0.3s"
                                            values="15;0" />
                                        <animateTransform attributeName="transform" dur="1.5s"
                                            repeatCount="indefinite" type="rotate" values="0 12 12;360 12 12" />
                                    </path>
                                </svg>
                            </span>
                        </button>
                    </div>
                </div>
                <!-- Table Component -->
                <TokenTable :rows="props.rows" />
            </div>
            <div class="md:w-[40%] border-l bg-black h-screen hidden md:block">
                <Documentation :apirequests="apirequests" />
            </div>
        </div>

        <!-- Modal for generating API key -->
        <TransitionRoot as="template" :show="isModalOpen">
            <Dialog as="div" class="relative z-50" @close="closeModal">
                <TransitionChild
                    as="template"
                    enter="ease-out duration-300"
                    enter-from="opacity-0"
                    enter-to="opacity-100"
                    leave="ease-in duration-200"
                    leave-from="opacity-100"
                    leave-to="opacity-0"
                >
                    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" />
                </TransitionChild>

                <div class="fixed inset-0 z-50 overflow-y-auto">
                    <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                        <TransitionChild
                            as="template"
                            enter="ease-out duration-300"
                            enter-from="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                            enter-to="opacity-100 translate-y-0 sm:scale-100"
                            leave="ease-in duration-200"
                            leave-from="opacity-100 translate-y-0 sm:scale-100"
                            leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                        >
                            <DialogPanel
                                class="relative transform overflow-hidden rounded-lg bg-white px-4 pb-4 pt-5 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:p-6"
                            >
                                <div>
                                    <div class="mt-3 text-center sm:mt-5">
                                        <DialogTitle as="h3" class="text-lg font-medium leading-6 text-gray-900">
                                            {{ $t('Generate New API Key') }}
                                        </DialogTitle>
                                    </div>
                                    <div class="mt-2">
                                        <form @submit.prevent="generateToken">
                                            <div class="mb-4">
                                                <label for="name" class="block text-sm font-medium text-gray-700">
                                                    {{ $t('API Key Name') }}
                                                    <span class="text-red-500">*</span>
                                                </label>
                                                <input
                                                    v-model="form.name"
                                                    type="text"
                                                    id="name"
                                                    required
                                                    class="w-full px-2 rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-primary sm:text-sm sm:leading-6"
                                                    :class="{ 'border-red-500': form.errors.name }"
                                                />
                                                <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">
                                                    {{ form.errors.name }}
                                                </p>
                                            </div>
                                            <div class="mb-4">
                                                <label for="limit" class="block text-sm font-medium text-gray-700">
                                                    {{ $t('Request Limit') }}
                                                </label>
                                                <input
                                                    v-model.number="form.limit"
                                                    type="number"
                                                    id="limit"
                                                    min="1"
                                                    class="w-full px-2 rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-primary sm:text-sm sm:leading-6"
                                                    :class="{ 'border-red-500': form.errors.limit }"
                                                />
                                                <p v-if="form.errors.limit" class="mt-1 text-sm text-red-600">
                                                    {{ form.errors.limit }}
                                                </p>
                                            </div>
                                            <div class="mb-4">
                                                <label for="expire_at" class="block text-sm font-medium text-gray-700">
                                                    {{ $t('Expiration Date') }}
                                                </label>
                                                <input
                                                    v-model="form.expire_at"
                                                    type="date"
                                                    id="expire_at"
                                                    class="w-full px-2 rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-primary sm:text-sm sm:leading-6"
                                                    :class="{ 'border-red-500': form.errors.expire_at }"
                                                />
                                                <p v-if="form.errors.expire_at" class="mt-1 text-sm text-red-600">
                                                    {{ form.errors.expire_at }}
                                                </p>
                                            </div>
                                            <div class="mt-5 sm:mt-6 sm:flex sm:flex-row-reverse">
                                                <button
                                                    type="submit"
                                                    :disabled="loadIcon"
                                                    class="inline-flex w-full justify-center rounded-md bg-primary px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 sm:ml-3 sm:w-auto"
                                                >
                                                    <span v-if="!loadIcon">{{ $t('Generate') }}</span>
                                                    <span v-else>
                                                        <svg
                                                            xmlns="http://www.w3.org/2000/svg"
                                                            width="1em"
                                                            height="1em"
                                                            viewBox="0 0 24 24"
                                                            class="inline-block"
                                                        >
                                                            <path
                                                                fill="none"
                                                                stroke="currentColor"
                                                                stroke-dasharray="15"
                                                                stroke-dashoffset="15"
                                                                stroke-linecap="round"
                                                                stroke-width="2"
                                                                d="M12 3C16.9706 3 21 7.02944 21 12"
                                                            >
                                                                <animate
                                                                    fill="freeze"
                                                                    attributeName="stroke-dashoffset"
                                                                    dur="0.3s"
                                                                    values="15;0"
                                                                />
                                                                <animateTransform
                                                                    attributeName="transform"
                                                                    dur="1.5s"
                                                                    repeatCount="indefinite"
                                                                    type="rotate"
                                                                    values="0 12 12;360 12 12"
                                                                />
                                                            </path>
                                                        </svg>
                                                    </span>
                                                </button>
                                                <button
                                                    type="button"
                                                    class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:mt-0 sm:w-auto"
                                                    @click="closeModal"
                                                >
                                                    {{ $t('Cancel') }}
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </DialogPanel>
                        </TransitionChild>
                    </div>
                </div>
            </Dialog>
        </TransitionRoot>
    </AppLayout>
</template>

<script setup>
import AppLayout from "./../Layout/App.vue";
import Documentation from "./Documentation.vue";
import Menu from "./Menu.vue";
import { Link, useForm } from "@inertiajs/vue3";
import TokenTable from '@/Components/Tables/TokenTable.vue';
import { ref } from 'vue';
import 'vue3-toastify/dist/index.css';

import { TabGroup, TabList, Tab, TabPanels, TabPanel } from '@headlessui/vue';
import { Dialog, DialogPanel, DialogTitle, TransitionRoot, TransitionChild } from '@headlessui/vue';
import VCodeBlock from '@wdns/vue-code-block';

const props = defineProps(['rows', 'url', 'apirequests', 'webhookModule']);
const tab = ref(null);
const code = ref(`const foo = 'bar';`);
const loadIcon = ref(false);
const isModalOpen = ref(false);

const form = useForm({
    name: null,
    limit: null,
    expire_at: null,
});

const changeTab = (id) => {
    tab.value = id;
};

const openModal = () => {
    isModalOpen.value = true;
};

const closeModal = () => {
    isModalOpen.value = false;
    form.reset();
};

const generateToken = () => {
    loadIcon.value = true;

    form.post('/developer-tools/access-tokens', {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            isModalOpen.value = false;
        },
        onError: () => {
            loadIcon.value = false;
        },
        onFinish: () => {
            loadIcon.value = false;
        },
    });
};
</script>