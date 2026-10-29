<template>
    <AppLayout>
        <div class="md:flex md:flex-col bg-white border-l py-4 text-[#000] capitalize overflow-y-hidden">
            <div class="flex justify-between px-8 border-b pb-2">
                <div>
                    <h2 class="text-xl mb-1">{{ $t('Template') }}</h2>
                    <p class="flex items-center text-sm leading-6 text-gray-600">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11v5m0 5a9 9 0 1 1 0-18a9 9 0 0 1 0 18Zm.05-13v.1h-.1V8h.1Z"/></svg>
                        <span class="ml-1 mt-1">{{ $t('Send Template ') }}</span>
                    </p>
                </div>
                <div class="space-x-2 flex items-center">
                    <Link href="/campaigns" class="rounded-md bg-black px-3 py-2 text-sm text-white shadow-sm hover:bg-primaryLight focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">{{ $t('Back') }}</Link>
                </div>
            </div>
            <div class="md:flex md:flex-grow-1">
                <div v-if="settings?.whatsapp" class="md:w-[50%] p-4 md:p-8 overflow-y-auto h-[90vh] p-4">
                    <div class="bg-slate-50 border border-primary shadow rounded-md p-4 py-8">
                        <div class="flex justify-center mb-4">
                            <svg xmlns="http://www.w3.org/2000/svg" width="72" height="72" viewBox="0 0 48 48"><path fill="black" d="M43.634 4.366a1.25 1.25 0 0 1 0 1.768l-4.913 4.913a9.253 9.253 0 0 1-.744 12.244l-3.343 3.343a1.25 1.25 0 0 1-1.768 0l-11.5-11.5a1.25 1.25 0 0 1 0-1.768l3.343-3.343a9.25 9.25 0 0 1 12.244-.743l4.913-4.914a1.25 1.25 0 0 1 1.768 0m-7.611 7.425a6.75 6.75 0 0 0-9.546 0l-2.46 2.459l9.733 9.732l2.46-2.459a6.75 6.75 0 0 0 0-9.546zM9.28 36.953l-4.914 4.913a1.25 1.25 0 0 0 1.768 1.768l4.913-4.913a9.253 9.253 0 0 0 12.244-.744l3.343-3.343a1.25 1.25 0 0 0 0-1.768L25.268 31.5l3.366-3.366a1.25 1.25 0 0 0-1.768-1.768L23.5 29.732L18.268 24.5l3.366-3.366a1.25 1.25 0 0 0-1.768-1.768L16.5 22.732l-1.366-1.366a1.25 1.25 0 0 0-1.768 0l-3.343 3.343a9.25 9.25 0 0 0-.743 12.244m2.51-10.476l2.46-2.46l9.732 9.733l-2.459 2.46a6.75 6.75 0 0 1-9.546 0l-.186-.187a6.75 6.75 0 0 1 0-9.546"/></svg>
                        </div>
                        <h3 class="text-center text-lg font-medium mb-4">{{ $t('Connect your whatsapp account') }}</h3>
                        <h4 class="text-center mb-4">{{ $t('You need to connect your WhatsApp account first before you can send out campaigns.') }}</h4>
                        <div class="flex justify-center">
                            <Link href="/settings/whatsapp" class="rounded-md px-3 py-2 text-sm hover:shadow-md text-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 bg-primary" :disabled="isLoading">
                                <span v-if="!isLoading">{{ $t('Connect Whatsapp account') }}</span>
                            </Link>
                        </div>
                    </div>
                </div>
                <form v-else @submit.prevent="submitForm()" class="md:w-[50%] p-4 md:p-8 overflow-y-auto h-[90vh]">
                    <div class="grid gap-x-6 gap-y-4 sm:grid-cols-2 mb-8">
                        <FormSelect v-model="form.template" @update:modelValue="loadTemplate" :options="templateOptions" :required="true" :error="form.errors.template" :name="$t('select Template')" :class="'sm:col-span-3'" :placeholder="$t('Select template')"/>
                        <div v-if="show_contacts" class="flex sm:col-span-3">
                            <div class="flex-grow">
                                <Select class="w-full" 
                                    :placeholder="$t('Select contacts')"  
                                    :options="contactGroupOptions" 
                                    v-model="form.contacts"
                                    :required="true"
                                    :error="form.errors.contacts"
                                />
                                <p v-if="form.errors.contacts" class="text-red-600 text-xs mt-1">{{ form.errors.contacts }}</p>
                            </div>
                            <button 
                                type="button"
                                @click="showImportModal = true"
                                class="ml-2 px-3 py-2 text-sm text-white bg-primary rounded-md hover:bg-primaryLight focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
                            >
                                {{ $t('Add Group') }}
                            </button>
                        </div>
                    </div>
                    <div>
                        <div v-if="form.header.parameters.length > 0" class="bg-slate-100 p-3 mt-4 text-sm">
                            <h2 class="flex items-center justify-between space-x-2 pb-2 border-b">
                                <div class="flex items-center space-x-2">
                                    <span>{{ $t('Header variables') }}</span>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 1024 1024"><path fill="currentColor" d="M512 64a448 448 0 1 1 0 896.064A448 448 0 0 1 512 64zm67.2 275.072c33.28 0 60.288-23.104 60.288-57.344s-27.072-57.344-60.288-57.344c-33.28 0-60.16 23.104-60.16 57.344s26.88 57.344 60.16 57.344zM590.912 699.2c0-6.848 2.368-24.64 1.024-34.752l-52.608 60.544c-10.88 11.456-24.512 19.392-30.912 17.28a12.992 12.992 0 0 1-8.256-14.72l87.68-276.992c7.168-35.136-12.544-67.2-54.336-71.296c-44.096 0-108.992 44.736-148.48 101.504c0 6.784-1.28 23.68.064 33.792l52.544-60.608c10.88-11.328 23.552-19.328 29.952-17.152a12.8 12.8 0 0 1 7.808 16.128L388.48 728.576c-10.048 32.256 8.96 63.872 55.04 71.04c67.84 0 107.904-43.648 147.456-100.416z"/></svg>
                                </div>
                                <span>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"><path fill="currentColor" d="M6.102 16.98c-1.074 0-1.648-1.264-.94-2.073l5.521-6.31a1.75 1.75 0 0 1 2.634 0l5.522 6.31c.707.809.133 2.073-.94 2.073H6.101Z"/></svg>
                                </span>
                            </h2>
                            <div v-for="(item, index) in form.header.parameters" :key="index" class="mt-2 flex items-center space-x-4">
                                <div v-if="form.header.parameters[index].type === 'text'" class="w-full">
                                    <FormSelect v-model="form.header.parameters[index].selection" :name="$t('Content type')" :options="variableOptions" :class="'sm:col-span-6'"/>
                                </div>
                                <div v-if="form.header.parameters[index].type === 'text'" class="w-full">
                                    <FormInput v-if="form.header.parameters[index].selection === 'static'" :name="$t('Value')" :required="true" :error="form.errors['header.parameters.0.value']" v-model="form.header.parameters[index].value" :type="'text'" :class="'sm:col-span-6'"/>
                                    <FormSelect v-if="form.header.parameters[index].selection === 'dynamic'" :name="$t('Value')" :required="true" :error="form.errors['header.parameters.0.value']" v-model="form.header.parameters[index].value" :options="dynamicOptions" :class="'sm:col-span-6'"/>
                                </div>
                                <div v-if="['IMAGE', 'DOCUMENT', 'VIDEO'].includes(form.header.parameters[index].type)" class="w-full mt-3">
                                    <div>
                                        <div class="flex items-center space-x-4">
                                            <label class="cursor-pointer flex justify-center px-2 py-2 w-[30%] bg-slate-200 shadow-sm rounded-lg border" 
                                                :class="form.errors['header.parameters.0.value'] ? 'border border-red-700' : ''" for="file-upload">
                                                {{ $t('Upload') }} {{ form.header.parameters[index].type.toLowerCase() }}
                                            </label>
                                            <input type="file" class="sr-only" :accept="getFileAcceptAttribute(form.header.parameters[index].type)" ref="fileInput" id="file-upload" @change="handleFileUpload"/>
                                            <div v-if="form.header.parameters[index].value" class="w-[20em] truncate">{{ form.header.parameters[index].selection === 'default' ? form.header.parameters[index].value : form.header.parameters[index].value.name }}</div>
                                            <span v-else>{{ $t('No file chosen') }}</span>
                                        </div>
                                        <p v-if="form.header.parameters[index].type === 'IMAGE'" class="text-left text-xs mt-2">{{ $t('Max file upload size is') }} <b>5MB</b> <br> {{ $t('Supported file extensions') }}: .png, jpg</p>
                                        <p v-if="form.header.parameters[index].type === 'DOCUMENT'" class="text-left text-xs mt-2">{{ $t('Max file upload size is') }} <b>100MB</b> <br> {{ $t('Supported file extensions') }}: .pdf, .txt, .ppt, .doc, .xls, .docx, .pptx, .xlsx</p>
                                        <p v-if="form.header.parameters[index].type === 'VIDEO'" class="text-left text-xs mt-2">{{ $t('Max file upload size is') }} <b>16MB</b> <br> {{ $t('Supported file extensions') }}: .mp4</p>
                                    </div>
                                    <div v-if="form.errors['header.parameters.0.value']" class="form-error text-[#b91c1c] text-xs">{{ form.errors['header.parameters.0.value'] }}</div>
                                </div>
                            </div>
                        </div>
                        <div v-if="form.body.parameters.length > 0" class="bg-slate-100 p-3 mt-1 text-sm">
                            <h2 class="flex items-center justify-between space-x-2 pb-2 border-b">
                                <div class="flex items-center space-x-2">
                                    <span>{{ $t('Body variables') }}</span>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 1024 1024"><path fill="currentColor" d="M512 64a448 448 0 1 1 0 896.064A448 448 0 0 1 512 64zm67.2 275.072c33.28 0 60.288-23.104 60.288-57.344s-27.072-57.344-60.288-57.344c-33.28 0-60.16 23.104-60.16 57.344s26.88 57.344 60.16 57.344zM590.912 699.2c0-6.848 2.368-24.64 1.024-34.752l-52.608 60.544c-10.88 11.456-24.512 19.392-30.912 17.28a12.992 12.992 0 0 1-8.256-14.72l87.68-276.992c7.168-35.136-12.544-67.2-54.336-71.296c-44.096 0-108.992 44.736-148.48 101.504c0 6.784-1.28 23.68.064 33.792l52.544-60.608c10.88-11.328 23.552-19.328 29.952-17.152a12.8 12.8 0 0 1 7.808 16.128L388.48 728.576c-10.048 32.256 8.96 63.872 55.04 71.04c67.84 0 107.904-43.648 147.456-100.416z"/></svg>
                                </div>
                                <span>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"><path fill="currentColor" d="M6.102 16.98c-1.074 0-1.648-1.264-.94-2.073l5.521-6.31a1.75 1.75 0 0 1 2.634 0l5.522 6.31c.707.809.133 2.073-.94 2.073H6.101Z"/></svg>
                                </span>
                            </h2>
                            <div v-for="(item, index) in form.body.parameters" :key="index" class="mt-2 flex items-center space-x-4">
                                <div class="w-[40%]">
                                    <span v-text="'{{' + (index + 1) + '}}'"></span>
                                </div>
                                <div class="w-full">
                                    <FormSelect v-model="form.body.parameters[index].selection" :options="variableOptions" :class="'sm:col-span-6'"/>
                                </div>
                                <div class="w-full">
                                    <FormInput v-if="form.body.parameters[index].selection === 'static'" v-model="form.body.parameters[index].value" :required="true" :error="form.errors['body.parameters.0.value']" :type="'text'" :class="'sm:col-span-6'"/>
                                    <FormSelect v-if="form.body.parameters[index].selection === 'dynamic'" v-model="form.body.parameters[index].value" :required="true" :error="form.errors['body.parameters.0.value']" :options="dynamicOptions" :class="'sm:col-span-6'"/>
                                </div>
                            </div>
                        </div>
                        <div v-if="form.buttons.length > 0 && hasButtonParameters" class="bg-slate-100 p-3 mt-1 text-sm">
                            <h2 class="flex items-center justify-between space-x-2 pb-2 border-b">
                                <div class="flex items-center space-x-2">
                                    <span>{{ $t('Button variables') }}</span>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 1024 1024"><path fill="currentColor" d="M512 64a448 448 0 1 1 0 896.064A448 448 0 0 1 512 64zm67.2 275.072c33.28 0 60.288-23.104 60.288-57.344s-27.072-57.344-60.288-57.344c-33.28 0-60.16 23.104-60.16 57.344s26.88 57.344 60.16 57.344zM590.912 699.2c0-6.848 2.368-24.64 1.024-34.752l-52.608 60.544c-10.88 11.456-24.512 19.392-30.912 17.28a12.992 12.992 0 0 1-8.256-14.72l87.68-276.992c7.168-35.136-12.544-67.2-54.336-71.296c-44.096 0-108.992 44.736-148.48 101.504c0 6.784-1.28 23.68.064 33.792l52.544-60.608c10.88-11.328 23.552-19.328 29.952-17.152a12.8 12.8 0 0 1 7.808 16.128L388.48 728.576c-10.048 32.256 8.96 63.872 55.04 71.04c67.84 0 107.904-43.648 147.456-100.416z"/></svg>
                                </div>
                                <span>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"><path fill="currentColor" d="M6.102 16.98c-1.074 0-1.648-1.264-.94-2.073l5.521-6.31a1.75 1.75 0 0 1 2.634 0l5.522 6.31c.707.809.133 2.073-.94 2.073H6.101Z"/></svg>
                                </span>
                            </h2>
                            <div v-for="(item, index) in form.buttons" :key="index">
                                <div v-if="item.parameters.length > 0" class="mt-4 bg-slate-50 p-3">
                                    <div class="w-[100%] mb-1">
                                        <span>{{ $t('Label') }}: {{ item.text }}</span>
                                    </div>
                                    <div v-for="(value, key) in item.parameters" :key="key" class="flex items-center space-x-4">
                                        <div class="w-full">
                                            <FormSelect v-model="value.type" :name="$t('Button type')" :options="variableOptions" :class="'sm:col-span-6'"/>
                                        </div>
                                        <div class="w-full">
                                            <FormInput v-if="value.type === 'static'" v-model="value.value" :name="$t('Value')" :required="true" :error="form.errors['buttons.'+ index +'.parameters.0.value']" :type="'text'" :class="'sm:col-span-6'"/>
                                            <FormSelect v-if="value.type === 'dynamic'" v-model="value.value" :name="$t('Value')" :required="true" :error="form.errors['buttons.'+ index +'.parameters.0.value']" :options="dynamicOptions" :class="'sm:col-span-6'"/>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center justify-end space-x-3 mt-3">
                            <div>
                                <Link href="/chats" class="block rounded-md px-3 py-2 text-sm text-gray-600 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 bg-slate-200">
                                    {{ $t('Cancel') }}
                                </Link>
                            </div>
                            <div>
                                <button type="submit" class="rounded-md px-3 py-2 text-sm text-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 bg-primary" :disabled="isLoading">
                                    <span v-if="!isLoading">{{ $t('Save') }}</span>
                                    <svg v-else xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path fill="currentColor" d="M12 2A10 10 0 1 0 22 12A10 10 0 0 0 12 2Zm0 18a8 8 0 1 1 8-8A8 8 0 0 1 12 20Z" opacity=".5"/><path fill="currentColor" d="M20 12h2A10 10 0 0 0 12 2V4A8 8 0 0 1 20 12Z"><animateTransform attributeName="transform" dur="1s" from="0 12 12" repeatCount="indefinite" to="360 12 12" type="rotate"/></path></svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
                <div class="md:w-[50%] py-5 px-20 flex justify-center chat-bg overflow-scroll">
                    <div>
                        <WhatsappTemplate :parameters="form" :visible="form.template ? true : false"/>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add Contact Import Modal -->
        <ContactImportModal
            v-model="showImportModal"
            type="contact"
            :contacts="props.contacts"
            @contacts-selected="handleContactsSelected"
        />
    </AppLayout>
</template>

<script setup>
import axios from "axios";
import { toast } from 'vue3-toastify';
import AppLayout from "./../Layout/App.vue";
import FormInput from '@/Components/FormInput.vue';
import FormSelect from '@/Components/FormSelect.vue';
import Select from '@/Components/Select.vue';
import WhatsappTemplate from '@/Components/WhatsappTemplate.vue';
import ContactImportModal from '@/Components/ContactImportModal.vue';
import { ref, computed, onMounted } from 'vue';
import { Link, useForm } from "@inertiajs/vue3";
import 'vue3-toastify/dist/index.css';
import { trans } from 'laravel-vue-i18n';

const props = defineProps(['contacts', 'templates']);
const show_contacts = ref(true);
const isLoading = ref(false);
const contactGroupOptions = ref([]);
const templateOptions = ref([]);
const config = ref(props.settings);
const settings = ref(config.value ? JSON.parse(config.value) : null);
const search_value = ref('');
const variableOptions = ref([
    { value: 'static', label: trans('static') },
    { value: 'dynamic', label: trans('dynamic') }
]);

const dynamicOptions = ref([
    { value: 'first name', label: trans('contact first name') },
    { value: 'last name', label: trans('contact last name') },
    { value: 'name', label: trans('contact full name') },
    { value: 'phone', label: trans('contact phone') },
    { value: 'email', label: trans('contact email') },
]);

const form = useForm({
    template: null,
    contacts: null,
    skip_schedule: false,
    header: {
        format: null,
        text: null,
        parameters: []
    },
    body: {
        text: null,
        parameters: []
    },
    footer: {
        text: null,
    },
    buttons: [],
});

const hasButtonParameters = computed(() =>
    form.buttons.some(button => button.parameters && button.parameters.length > 0)
);

const showImportModal = ref(false);
const selectedGroupContacts = ref([]);

const handleContactsSelected = (contacts) => {
    selectedGroupContacts.value = contacts;
    form.contacts = contacts;
    showImportModal.value = false;
};

const loadTemplate = async () => {
    try {
        const response = await axios.get(`/templates/${form.template}`);
        console.log('API Response:', response.data);

        if (response.data) {
            const metadata = JSON.parse(response.data.metadata);
            console.log('Parsed Metadata:', metadata);

            // Extract HEADER component
            form.header.format = extractComponent(metadata, 'HEADER', 'format');
            form.header.text = extractComponent(metadata, 'HEADER', 'text');
            const headerExamples = extractComponent(metadata, 'HEADER', 'example');
            console.log('Header Examples:', headerExamples);

            if (headerExamples) {
                if (form.header.format === 'TEXT') {
                    form.header.parameters = headerExamples.header_text.map(item => ({
                        type: 'text',
                        selection: 'static',
                        value: item,
                    }));
                } else if (form.header.format === 'IMAGE' || form.header.format === 'DOCUMENT' || form.header.format === 'VIDEO') {
                    form.header.parameters = headerExamples.header_handle.map(item => ({
                        type: form.header.format,
                        selection: 'default',
                        value: null,
                        url: item,
                    }));
                }
            } else {
                form.header.parameters = [];
            }

            console.log('Header:', form.header);

            // Extract BODY component
            form.body.text = extractComponent(metadata, 'BODY', 'text');
            const bodyExamples = extractComponent(metadata, 'BODY', 'example');
            console.log('Body Examples:', bodyExamples);

            if (bodyExamples) {
                form.body.parameters = bodyExamples.body_text[0].map(item => ({
                    type: 'text',
                    selection: 'static',
                    value: item,
                }));
            } else {
                form.body.parameters = [];
            }

            console.log('Body:', form.body);

            // Extract FOOTER component
            form.footer.text = extractComponent(metadata, 'FOOTER', 'text');
            console.log('Footer:', form.footer);

            // Extract BUTTONS component
            const buttons = extractComponent(metadata, 'BUTTONS', 'buttons');
            console.log('Buttons:', buttons);

            if (buttons) {
                form.buttons = buttons.map(item => ({
                    type: item.type,
                    text: item.text,
                    value: item[item.type.toLowerCase()] ?? null,
                    parameters: item.type === 'URL'
                        ? detectUrlParameters(item.url)
                        : item.example
                            ? item.example.map(param => ({ type: 'static', value: param }))
                            : [],
                }));
            } else {
                form.buttons = [];
            }

            console.log('Buttons:', form.buttons);
        }
    } catch (error) {
        console.error('Error fetching template data:', error);
    }
};

function detectUrlParameters(url) {
    const parameterRegex = /\{\{\d+\}\}/g;
    const matches = url.match(parameterRegex);
    
    if (matches) {
        return matches.map((match, index) => ({
            type: 'static',
            value: null
        }));
    }
    
    return [];
}

const handleFileUpload = (event) => {
    const fileSizeLimit = getFileSizeLimit(form.header.parameters[0].type);
    const file = event.target.files[0];

    if (file && file.size > fileSizeLimit) {
        toast.error(trans('file size exceeds the limit. Max allowed size:') + ' ' + fileSizeLimit + 'b');
        event.target.value = null;
    } else {
        const reader = new FileReader();

        reader.onload = (e) => {
            form.header.parameters[0].url = e.target.result;
        };

        form.header.parameters[0].selection = 'upload';
        form.header.parameters[0].value = file;

        reader.readAsDataURL(file);
    }
};

const getFileAcceptAttribute = (fileType) => {
    switch (fileType) {
        case 'IMAGE':
            return '.png, .jpg';
        case 'DOCUMENT':
            return '.pdf, .txt, .ppt, .doc, .xls, .docx, .pptx, .xlsx';
        case 'VIDEO':
            return '.mp4';
        default:
            return '';
    }
};

const getFileSizeLimit = (fileType) => {
    switch (fileType) {
        case 'IMAGE':
            return 5 * 1024 * 1024; // 5MB
        case 'DOCUMENT':
            return 100 * 1024 * 1024; // 100MB
        case 'VIDEO':
            return 16 * 1024 * 1024; // 16MB
        default:
            return Infinity;
    }
};

const extractComponent = (data, type, customProperty) => {
    const component = data.components.find(
        (c) => c.type === type
    );

    return component ? component[customProperty] : null;
};

const transformOptions = (options) => {
    return options.map((option) => ({
        value: option.uuid,
        label: option.language ? option.name + ' [' + option.language + ']' : option.name,
    }));
};

const submitForm = () => {
    if (!form.contacts) {
        toast.error(trans('Please select at least one contact or group'));
        form.errors.contacts = trans('Contacts field is required');
        return;
    }

    isLoading.value = true;
    form.post(`${form?.contacts}`, {
        onSuccess: () => {
            toast.success(trans('Form submitted successfully'));
        },
        onError: (errors) => {
            toast.error(trans('Failed to submit form'));
            console.error('Form submission errors:', errors);
        },
        onFinish: () => {
            isLoading.value = false;
        }
    });
};

const handleSearch = (query) => {
    search_value.value = query;

    if (!query) {
        contactGroupOptions.value = props.contacts.map((option) => ({
            value: option.uuid,
            label: option.full_name,
        }));
        return;
    }

    axios.get(`contacts/search?search=${query}`)
        .then((res) => {
            contactGroupOptions.value = res.data.map((option) => ({
                value: option.uuid,
                label: option.full_name,
            }));
        })
        .catch((error) => {
            console.error("Error fetching contacts:", error);
        });
};

const getMoreContact = () => {
    axios.get(`contacts/search?search=${search_value.value}`).then((res) => {
        contactGroupOptions.value = res.data.map((option) => ({
            value: option.uuid,
            label: option.full_name,
        }));
    });
    form.contacts = search_value.value;
};

onMounted(() => {
    if (localStorage.getItem('contact_id')) {
        show_contacts.value = false;
        form.contacts = localStorage.getItem('contact_id');
    }

    contactGroupOptions.value = props.contacts.map((option) => ({
        value: option.uuid,
        label: option.full_name,
    }));
    templateOptions.value = props.templates.map((option) => ({
        value: option.uuid,
        label: option.name,
    }));
    contactGroupOptions.value = [...contactGroupOptions.value, ...transformOptions(props.contactGroups)];
});
</script>

<style>
    ._7r39 {
        background-color: transparent;
        background-size: cover;
        background-repeat: no-repeat;
        border-radius: 4px;
        box-sizing: border-box;
        min-width: 24em;
        min-height: 15em;
    }
</style>