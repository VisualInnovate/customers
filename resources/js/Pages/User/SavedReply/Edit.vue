<template>
    <AppLayout>
        <div class="px-4 md:px-0 flex flex-col bg-white border-l py-4 text-[#000] md:overflow-y-hidden capitalize">
            <div class="flex justify-between md:px-8 border-b pb-4">
                <div>
                    <h2 class="text-xl mb-1">{{ $t('Update Saved Reply') }}</h2>
                    <p class="flex items-center text-sm leading-6 text-gray-600">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24">
                            <path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                stroke-width="2" d="M12 11v5m0 5a9 9 0 1 1 0-18a9 9 0 0 1 0 18Zm.05-13v.1h-.1V8h.1Z" />
                        </svg>
                        <span class="ml-1 mt-1">{{ $t('Update Saved Reply for review') }}</span>
                    </p>

                </div>
                <div class="space-x-2 flex items-center">
                    <Link href="/saved-replies"
                        class="rounded-md bg-black px-3 py-2 text-sm text-white shadow-sm hover:bg-primaryLight focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary">
                    {{ $t('Back') }}</Link>
                    <button @click="submitForm()" type="button"
                        class="capitalize rounded-md px-3 py-2 float-right text-sm text-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary"
                        :class="isFormValid === true ? 'bg-primary hover:bg-primaryLight shadow-sm' : 'bg-gray-200'"
                        :disabled="!isFormValid || isLoading">
                        <span v-if="!isLoading">{{ $t('Update Saved Reply') }}</span>
                        <svg v-else xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
                            <path fill="currentColor"
                                d="M12 2A10 10 0 1 0 22 12A10 10 0 0 0 12 2Zm0 18a8 8 0 1 1 8-8A8 8 0 0 1 12 20Z"
                                opacity=".5" />
                            <path fill="currentColor" d="M20 12h2A10 10 0 0 0 12 2V4A8 8 0 0 1 20 12Z">
                                <animateTransform attributeName="transform" dur="1s" from="0 12 12"
                                    repeatCount="indefinite" to="360 12 12" type="rotate" />
                            </path>
                        </svg>
                    </button>
                </div>
            </div>
            <div class="md:flex md:flex-grow-1 md:h-[88vh] mt-4 md:mt-0">
                <div class="md:w-[50%] md:p-8 overflow-y-auto">
                    <!-- <div class="mb-8">
                        <div
                            class="flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md">
                            <input type="file" class="sr-only" accept=".jpg, .png" ref="fileInput" id="file-upload"
                                @change="handleFileUpload($event)" />
                            <div class="text-center">
                                <div>
                                    <div v-if="form.media" class="flex justify-center items-center">
                                        <div
                                            class="flex justify-center items-center space-x-3 py-1 border bg-slate-100 rounded-lg mb-2 w-fit px-2">
                                            <div>
                                                <svg class="mx-auto h-6 w-6 text-gray-400 cursor-pointer"
                                                    xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                    viewBox="0 0 24 24">
                                                    <path fill="currentColor"
                                                        d="M14 9a1.5 1.5 0 1 1 3 0a1.5 1.5 0 0 1-3 0Z" />
                                                    <path fill="currentColor" fill-rule="evenodd"
                                                        d="M7.268 4.658a54.647 54.647 0 0 1 9.465 0l1.51.132a3.138 3.138 0 0 1 2.831 2.66a30.604 30.604 0 0 1 0 9.1a3.138 3.138 0 0 1-2.831 2.66l-1.51.131c-3.15.274-6.316.274-9.465 0l-1.51-.131a3.138 3.138 0 0 1-2.832-2.66a30.601 30.601 0 0 1 0-9.1a3.138 3.138 0 0 1 2.831-2.66l1.51-.132Zm9.335 1.495a53.147 53.147 0 0 0-9.206 0l-1.51.131A1.638 1.638 0 0 0 4.41 7.672a29.101 29.101 0 0 0-.311 5.17L7.97 8.97a.75.75 0 0 1 1.09.032l3.672 4.13l2.53-.844a.75.75 0 0 1 .796.21l3.519 3.91a29.101 29.101 0 0 0 .014-8.736a1.638 1.638 0 0 0-1.478-1.388l-1.51-.131Zm2.017 11.435l-3.349-3.721l-2.534.844a.75.75 0 0 1-.798-.213l-3.471-3.905l-4.244 4.243c.049.498.11.996.185 1.491a1.638 1.638 0 0 0 1.478 1.389l1.51.131c3.063.266 6.143.266 9.206 0l1.51-.131c.178-.016.35-.06.507-.128Z"
                                                        clip-rule="evenodd" />
                                                </svg>
                                            </div>
                                            <div class="flex items-center space-x-2">
                                                <span class="text-sm">{{ form.media.name }}</span>
                                                <button @click="removeFile()">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                        viewBox="0 0 24 24">
                                                        <path fill="currentColor" fill-rule="evenodd"
                                                            d="M17.707 7.707a1 1 0 0 0-1.414-1.414L12 10.586L7.707 6.293a1 1 0 0 0-1.414 1.414L10.586 12l-4.293 4.293a1 1 0 1 0 1.414 1.414L12 13.414l4.293 4.293a1 1 0 1 0 1.414-1.414L13.414 12l4.293-4.293Z"
                                                            clip-rule="evenodd" />
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <label for="file-upload">
                                        <svg class="mx-auto h-12 w-12 text-gray-400 cursor-pointer"
                                            xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                            viewBox="0 0 24 24">
                                            <path fill="currentColor" d="M14 9a1.5 1.5 0 1 1 3 0a1.5 1.5 0 0 1-3 0Z" />
                                            <path fill="currentColor" fill-rule="evenodd"
                                                d="M7.268 4.658a54.647 54.647 0 0 1 9.465 0l1.51.132a3.138 3.138 0 0 1 2.831 2.66a30.604 30.604 0 0 1 0 9.1a3.138 3.138 0 0 1-2.831 2.66l-1.51.131c-3.15.274-6.316.274-9.465 0l-1.51-.131a3.138 3.138 0 0 1-2.832-2.66a30.601 30.601 0 0 1 0-9.1a3.138 3.138 0 0 1 2.831-2.66l1.51-.132Zm9.335 1.495a53.147 53.147 0 0 0-9.206 0l-1.51.131A1.638 1.638 0 0 0 4.41 7.672a29.101 29.101 0 0 0-.311 5.17L7.97 8.97a.75.75 0 0 1 1.09.032l3.672 4.13l2.53-.844a.75.75 0 0 1 .796.21l3.519 3.91a29.101 29.101 0 0 0 .014-8.736a1.638 1.638 0 0 0-1.478-1.388l-1.51-.131Zm2.017 11.435l-3.349-3.721l-2.534.844a.75.75 0 0 1-.798-.213l-3.471-3.905l-4.244 4.243c.049.498.11.996.185 1.491a1.638 1.638 0 0 0 1.478 1.389l1.51.131c3.063.266 6.143.266 9.206 0l1.51-.131c.178-.016.35-.06.507-.128Z"
                                                clip-rule="evenodd" />
                                        </svg>
                                    </label>
                                    <div class="text-center text-sm text-gray-600">
                                        <label for="file-upload"
                                            class="relative cursor-pointer  bg-white rounded-md font-medium hover:text-primary focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-primary">
                                            <span>{{ $t('Add an image For your message') }}</span>
                                        </label>
                                    </div>
                                    <p class="text-xs text-gray-500">{{ $t('PNG or JPG files only') }}</p>
                                </div>
                            </div>
                        </div>
                    </div> -->

                    <h2 class="text-slate-600">{{ $t('Body') }} <span class="text-xs">({{ $t('Required')
                            }})</span></h2>
                    <span class="text-slate-600 text-xs">{{ $t('Enter the text for your message') }}</span>
                    <div class="mb-8">
                        <div>
                            <FormTextArea v-model="form.reply" :name="''" :type="'text'" />
                        </div>
                    </div>
                </div>
                <div class="md:w-[50%] py-20 px-4 md:px-20 overflow-y-auto"
                    style="background-image: url('/images/whatsapp-bg-02.png');">
                    <div
                        class="mr-auto rounded-lg rounded-tl-none my-1 p-1  text-sm bg-white flex flex-col relative speech-bubble-left w-[25em]">
                        <div v-if="imageUrl" class="mb-4 bg-[#ccd0d5] flex justify-center py-8 rounded">
                            <img :src="imageUrl">
                        </div>
                        <p class="px-2 normal-case whitespace-pre-wrap" v-html="form.reply"></p>
                        <span class="mt-1 px-2 text-right text-xs leading-none float-right">9:15</span>
                    </div>
                </div>
            </div>
        </div>
        <Modal :label="''" :isOpen=isModalOpen>
            <div class="mt-5 grid grid-cols-1 gap-x-6 gap-y-4">
                <div class="text-center">
                    <div v-if="error != null" class="bg-[#fae6e6] text-[darkred] rounded text-sm p-2 mb-4">
                        <div>{{ $t('Error') }}: </div>
                        <div>{{ error }}</div>
                        <button type="button" @click="closeModal"
                            class="mt-4 inline-flex justify-center rounded-md border border-transparent bg-slate-50 px-4 py-2 text-sm text-slate-500 hover:bg-slate-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2 mr-4">Close</button>
                    </div>
                    <div v-else>
                        <h2 class="text-xl capitalize">{{ $t('Your Saved Reply is being Saved!') }}</h2>
                        <div class="flex justify-center mt-4">
                            <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24">
                                <path fill="none" stroke="currentColor" stroke-dasharray="15" stroke-dashoffset="15"
                                    stroke-linecap="round" stroke-width="2" d="M12 3C16.9706 3 21 7.02944 21 12">
                                    <animate fill="freeze" attributeName="stroke-dashoffset" dur="0.3s" values="15;0" />
                                    <animateTransform attributeName="transform" dur="1.5s" repeatCount="indefinite"
                                        type="rotate" values="0 12 12;360 12 12" />
                                </path>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>
        </Modal>

    </AppLayout>
</template>
<script setup>
    import axios from "axios";
    import AppLayout from "./../Layout/App.vue";
    import FormInput from '@/Components/FormInput.vue';
    import FormTextArea from '@/Components/FormTextArea.vue';
    import { ref, computed, watch } from 'vue';
    import { Link } from "@inertiajs/vue3";
    import Modal from '@/Components/Modal.vue';

    import 'vue3-toastify/dist/index.css';
    import { router } from '@inertiajs/vue3';

    const props = defineProps({ data: Object });

    const isLoading = ref(false);
    const imageUrl = ref(null);
    const isModalOpen = ref(false);
    const error = ref(null);

    const form = ref({
        'reply': null,
        'media': null,
        'path': null
    });

    if (props.data) {
        form.value.reply = props.data.data.reply;
        form.value.path = props.data.data.path || null;
        imageUrl.value = form.value.path;

    }
    const handleFileUpload = (event) => {
        const file = event.target.files[0];
        const reader = new FileReader();
        reader.onload = (e) => {
            imageUrl.value = e.target.result;
            form.value.media = file; // Update header example with the uploaded image
        };
        reader.readAsDataURL(file);
    };

    const isFormValid = computed(() => {
        if (
            form.value.reply === null ||
            form.value.reply.trim() === ""
        ) {
            return false;
        }
        return true;
    });
    const removeFile = () => {
        form.value.media = '';
        imageUrl.value = null;
    };

    const submitForm = () => {
        isLoading.value = true;
        isModalOpen.value = true;

        axios.post(`/saved-replies/update/${props.data.data.id}`, { reply: form.value.reply, ...(form.value.media ? { media: form.value.media } : { path: form.value.path }) }, {
            headers: {
                'Content-Type': 'multipart/form-data',
            },
        })
            .then(response => {
                if (response.data.success === false) {
                    isLoading.value = false;
                    error.value = response.data.message;
                } else {
                    router.visit('/saved-replies', {
                        method: 'get',
                    });
                }
            })
            .catch(error => {
                // Handle any errors that occur during the request
                //console.error('An error occurred:', error);
            });
    }

    const closeModal = () => {
        isModalOpen.value = false;

        setTimeout(() => {
            error.value = null;
        }, 500);
    }

    watch(form, () => {
        isFormValid.value;
    });
</script>