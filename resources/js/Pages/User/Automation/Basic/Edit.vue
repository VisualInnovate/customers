<template>
    <AppLayout>
        <div class="bg-white md:bg-inherit pt-10 px-4 md:pt-8 md:p-8 rounded-[5px] text-[#000] h-full md:overflow-y-auto">
            <div class="flex justify-between">
                <div>
                    <h2 class="text-xl mb-1">{{ $t('Update automation') }}</h2>
                    <p class="mb-6 flex items-center text-sm leading-6 text-gray-600">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11v5m0 5a9 9 0 1 1 0-18a9 9 0 0 1 0 18Zm.05-13v.1h-.1V8h.1Z"/></svg>
                        <span class="ml-1 mt-1">{{ $t('Update your setup') }}</span>
                    </p>
                </div>
                <div>
                    <Link href="/automation/basic" class="flex items-center space-x-4 rounded-md bg-primary px-3 py-2 text-sm text-white shadow-sm hover:bg-primaryLight focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16"><path fill="currentColor" fill-rule="evenodd" d="M5.841 5.28a.75.75 0 0 0-1.06-1.06L1.53 7.47L1 8l.53.53l3.25 3.25a.75.75 0 0 0 1.061-1.06l-1.97-1.97H24.25a.75.75 0 0 0 0-1.5H3.871l1.97-1.97Z" clip-rule="evenodd"/></svg>
                        {{ $t('Back') }}
                    </Link>
                </div>
            </div>
            <form @submit.prevent="submitForm()" class="bg-white border py-5 px-5 rounded-[0.5rem]">
                <div class="flex border-b py-5">
                    <div class="w-[40%] mb-1">
                        <h2 class="text-sm text-gray-500 tracking-[0px]">{{ $t('Name') }}</h2>
                    </div>
                    <div class="w-[60%] flex space-x-6">
                        <div class="w-[80%]">
                            <FormInput v-model="form.name" :type="'text'" :error="form.errors.name" :class="'w-full'" :labelClass="'mb-0'"/>
                        </div>
                    </div>
                </div>
                <div class="flex border-b py-5">
                    <div class="w-[40%] mb-1">
                        <h2 class="text-sm text-gray-500 tracking-[0px]">{{ $t('Trigger') }}</h2>
                        <span class="flex text-xs mt-2">
                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24"><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11v5m0 5a9 9 0 1 1 0-18a9 9 0 0 1 0 18Zm.05-13v.1h-.1V8h.1Z"/></svg>
                            {{ $t('Add the string of text responsible for triggering the response') }}
                        </span>
                    </div>
                    <div class="w-[60%] flex space-x-6">
                        <div class="w-[80%]">
                            <FormTextArea v-model="form.trigger" :type="'text'" :error="form.errors.trigger" :textAreaRows="3" :class="'sm:col-span-6 mb-10'"/>
                        </div>
                    </div>
                </div>
                <div class="flex border-b py-5">
                    <div class="w-[40%] mb-1">
                        <h2 class="text-sm text-gray-500 tracking-[0px]">{{ $t('Match criteria') }}</h2>
                        <span class="flex text-xs mt-2">
                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24"><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11v5m0 5a9 9 0 1 1 0-18a9 9 0 0 1 0 18Zm.05-13v.1h-.1V8h.1Z"/></svg>
                            {{ $t('Select the criteria for matching the trigger text above') }}
                        </span>
                    </div>
                    <div class="w-[60%] flex space-x-6">
                        <div class="w-[80%]">
                            <FormSelect v-model="form.match_criteria" :options="criteriaOptions" :error="form.errors.match_criteria" :class="'sm:col-span-6'" :placeholder="$t('Select criteria')"/>
                        </div>
                    </div>
                </div>
                <div class="flex border-b py-5">
                    <div class="w-[40%] mb-1">
                        <h2 class="text-sm text-gray-500 tracking-[0px]">{{ $t('Response type') }}</h2>
                    </div>
                    <div class="w-[60%] flex space-x-6">
                        <div class="w-[80%]">
                            <FormSelect v-model="form.response_type" @update:modelValue="clearResponse" :options="responseOptions" :error="form.errors.response_type" :class="'sm:col-span-6'" :placeholder="'Select Type'"/>
                        </div>
                    </div>
                </div>
                <div v-if="form.response_type === 'text'" class="flex py-5">
                    <div class="w-[40%] mb-1">
                        <h2 class="text-sm text-gray-500 tracking-[0px]">{{ $t('Text response') }}</h2>
                    </div>
                    <div class="w-[60%] flex space-x-6">
                        <div class="w-[80%]">
                            <div>
                                <div :class="'sm:col-span-6 mb-2'">
                                    <div class="mt-2">
                                        <textarea 
                                            class="block w-full rounded-md border-0 py-1.5 px-4 text-gray-900 shadow-sm outline-none ring-1 ring-inset placeholder:text-gray-400 sm:text-sm sm:leading-6"
                                            :class="form.errors.response ? 'ring-[#b91c1c]' : 'ring-gray-300'"
                                            @input="updateTextAreaValue"
                                            :rows="'3'"
                                            ref="textareaRef"
                                        >{{ form.response }}</textarea>
                                    </div>
                                    <div v-if="form.errors.response" class="form-error text-[#b91c1c] text-xs">{{ form.errors.response }}</div>
                                </div>
                                <button type="button" @click="isModalOpen = true" class="bg-slate-100 px-2 py-1 rounded-md text-sm flex items-center gap-x-1 shadow-sm">
                                    Add Variable
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8.25 15L12 18.75L15.75 15m-7.5-6L12 5.25L15.75 9"/></svg>
                                </button>
                            </div>
                            <button type="submit" class="float-right flex items-center space-x-4 rounded-md bg-black px-3 py-2 text-sm text-white shadow-sm hover:bg-slate-600 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                                {{ $t('Save') }}
                            </button>
                        </div>
                    </div>
                </div>
                <div v-else-if="form.response_type === 'template'" class="flex py-5">
                    <div class="w-[40%] mb-1">
                        <h2 class="text-sm text-gray-500 tracking-[0px]">{{ $t('Template response') }}</h2>
                    </div>
                    <div class="w-[60%] flex space-x-6">
                        <div class="w-[80%]">
                            <FormSelectCombo v-model="form.response" :loadOptions="loadTemplates" :error="form.errors.response" :class="'sm:col-span-6 mb-10'" :placeholder="'Select Template'"/>
                            <button type="submit" class="float-right flex items-center space-x-4 rounded-md bg-black px-3 py-2 text-sm text-white shadow-sm hover:bg-slate-600 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                                {{ $t('Save') }}
                            </button>
                        </div>
                    </div>
                </div>
                <div v-else-if="form.response_type === 'image' || form.response_type === 'audio'" class="flex py-5">
                    <div class="w-[40%] mb-1">
                        <h2 v-if="form.response_type === 'image'" class="text-sm text-gray-500 tracking-[0px]">{{ $t('Image response') }}</h2>
                        <h2 v-else class="text-sm text-gray-500 tracking-[0px]">{{ $t('Audio response') }}</h2>
                    </div>
                    <div class="w-[60%] flex space-x-6">
                        <div class="w-[80%]">
                            <div 
                                :class="form.errors.response ? 'border-[#b91c1c]' : 'border-gray-300'"
                                class="flex justify-center px-6 pt-5 pb-6 border-2 border-dashed rounded-md">
                                <input
                                    type="file"
                                    class="sr-only"
                                    :accept="form.response_type === 'image' ? '.jpg, .png' : '.mp3'"
                                    ref="fileInput"
                                    id="file-upload"
                                    @change="handleFileUpload($event)"
                                />
                                <div class="text-center">
                                    <div>
                                        <div v-if="form.response" class="flex justify-center items-center">
                                            <div class="flex justify-center items-center space-x-3 py-1 border bg-slate-100 rounded-lg mb-2 w-fit px-2">
                                                <div>
                                                    <svg class="mx-auto h-6 w-6 text-gray-400 cursor-pointer" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path fill="currentColor" d="M14 9a1.5 1.5 0 1 1 3 0a1.5 1.5 0 0 1-3 0Z"/><path fill="currentColor" fill-rule="evenodd" d="M7.268 4.658a54.647 54.647 0 0 1 9.465 0l1.51.132a3.138 3.138 0 0 1 2.831 2.66a30.604 30.604 0 0 1 0 9.1a3.138 3.138 0 0 1-2.831 2.66l-1.51.131c-3.15.274-6.316.274-9.465 0l-1.51-.131a3.138 3.138 0 0 1-2.832-2.66a30.601 30.601 0 0 1 0-9.1a3.138 3.138 0 0 1 2.831-2.66l1.51-.132Zm9.335 1.495a53.147 53.147 0 0 0-9.206 0l-1.51.131A1.638 1.638 0 0 0 4.41 7.672a29.101 29.101 0 0 0-.311 5.17L7.97 8.97a.75.75 0 0 1 1.09.032l3.672 4.13l2.53-.844a.75.75 0 0 1 .796.21l3.519 3.91a29.101 29.101 0 0 0 .014-8.736a1.638 1.638 0 0 0-1.478-1.388l-1.51-.131Zm2.017 11.435l-3.349-3.721l-2.534.844a.75.75 0 0 1-.798-.213l-3.471-3.905l-4.244 4.243c.049.498.11.996.185 1.491a1.638 1.638 0 0 0 1.478 1.389l1.51.131c3.063.266 6.143.266 9.206 0l1.51-.131c.178-.016.35-.06.507-.128Z" clip-rule="evenodd"/></svg>
                                                </div>
                                                <div class="flex items-center space-x-2">
                                                    <span class="text-sm">{{ form.response?.name ?? form.response }}</span>
                                                    <button type="button" @click= "clearResponse()">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path fill="currentColor" fill-rule="evenodd" d="M17.707 7.707a1 1 0 0 0-1.414-1.414L12 10.586L7.707 6.293a1 1 0 0 0-1.414 1.414L10.586 12l-4.293 4.293a1 1 0 1 0 1.414 1.414L12 13.414l4.293 4.293a1 1 0 1 0 1.414-1.414L13.414 12l4.293-4.293Z" clip-rule="evenodd"/></svg>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="flex justify-center text-sm text-gray-600">
                                            <label v-if="form.response_type === 'image'" for="file-upload" class="relative cursor-pointer bg-white rounded-md font-medium hover:text-indigo-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-indigo-500">
                                                <p>{{ $t('Click to upload image') }}</p>
                                                <p class="text-xs text-center text-gray-500">{{ $t('PNG or JPG files only') }}</p>
                                            </label>
                                            <label v-else for="file-upload" class="relative cursor-pointer bg-white rounded-md font-medium hover:text-indigo-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-indigo-500">
                                                <p>{{ $t('Click to upload audio') }}</p>
                                                <p class="text-xs text-center text-gray-500">{{ $t('MP3 files only') }}</p>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div v-if="form.errors.response" class="form-error text-[#b91c1c] text-xs">{{ form.errors.response }}</div>
                            <button type="submit" class="mt-10 float-right flex items-center space-x-4 rounded-md bg-black px-3 py-2 text-sm text-white shadow-sm hover:bg-slate-600 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                                {{ $t('Save') }}
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </AppLayout>

    <Modal :label="$t('Select variable')" :isOpen="isModalOpen">
        <div class="flex bg-slate-50 p-2 rounded-md mt-3">
            <span class="font-light text-sm">Select a placeholder to add to your response. The placeholder will replace itself with the actual data.</span>
        </div>
        <div class="mt-2 grid grid-cols-1 gap-x-6">
            <div class="pt-3 grid grid-cols-2 gap-x-2 text-sm gap-y-1">
                <button v-for="item in props.placeholders" @click="addToTextArea(item.value)" class="col-span-1 bg-gray-100 p-2 rounded-md text-left hover:bg-gray-50">{{ $t(item.label) }}</button>
            </div>
            <div class="mt-4 border-t pt-4">
                <button type="button" @click.self="isModalOpen = false" class="inline-flex justify-center rounded-md border border-transparent bg-slate-50 px-4 py-2 text-sm text-slate-500 hover:bg-slate-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2 mr-4">{{ $t('Cancel') }}</button>
            </div>
        </div>
    </Modal>
</template>
<script setup>
    import AppLayout from './../../Layout/App.vue';
    import axios from 'axios';
    import { Link, router, useForm } from "@inertiajs/vue3";
    import { ref } from 'vue';
    import { trans } from 'laravel-vue-i18n';
    import FormInput from '@/Components/FormInput.vue';
    import FormSelect from '@/Components/FormSelect.vue';
    import FormSelectCombo from '@/Components/FormSelectCombo.vue';
    import FormTextArea from '@/Components/FormTextArea.vue';
    import Modal from '@/Components/Modal.vue';

    const props = defineProps(['autoreply', 'placeholders']);
    const isModalOpen = ref(false);
    const textareaRef = ref(null);

    const addToTextArea = (textToAdd) => {
        const textarea = textareaRef.value;
        if (textarea) {
            const startPosition = textarea.selectionStart;
            const endPosition = textarea.selectionEnd;

            textarea.value = `${textarea.value.substring(0, startPosition)}${textToAdd}${textarea.value.substring(endPosition)}`;
        }

        isModalOpen.value = false;
    };

    const updateTextAreaValue = (event) => {
        form.response = event.target.value;
    }

    const type = JSON.parse(props.autoreply.metadata).type;
    const response = () => {
        const data = JSON.parse(props.autoreply.metadata).data;
        if(type === 'text'){
            return data.text;
        } else if(type === 'image' || type === 'audio'){
            return data?.file;
        } else {
            return data.template;
        }
    }

    const form = useForm({
        '_method': "put",
        'name' : props.autoreply.name,
        'trigger' : props.autoreply.trigger,
        'match_criteria' : props.autoreply.match_criteria,
        'response_type' : type,
        'response' : response()
    });

    const criteriaOptions = ref([
        { value: 'exact match', label: trans('When text is an exact match to trigger text') },
        { value: 'contains', label: trans('When text contains trigger text') },
    ])

    const responseOptions = ref([
        { value: 'text', label: trans('Respond with text') },
        { value: 'image', label: trans('Respond with image') },
        { value: 'audio', label: trans('Respond with audio') },
    ])

    const loadTemplates = async(query, setOptions) => {
        try {
            const response = await axios.get("/templates?query=" + query);
            //console.log(response.data[0]);
            setOptions(response.data[0]);
        } catch (error) {
            console.error("Error fetching rows:", error);
        }
    }

    const handleFileUpload = (event) => {
        const file = event.target.files[0];
        const reader = new FileReader();
        reader.onload = (e) => {
            form.response = file;
        };
        reader.readAsDataURL(file);
    }

    const clearResponse = () => {
        form.response = null;
    }

    const submitForm = () => {
        form.post('/automation/basic/' + props.autoreply.uuid);
    }
</script>