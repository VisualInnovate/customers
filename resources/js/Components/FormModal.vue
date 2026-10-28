<script setup>
    import axios from 'axios';
    import { router } from '@inertiajs/vue3';
    import { ref, onMounted, computed, watch } from 'vue';
    import Modal from '@/Components/Modal.vue';
    import FormInput from '@/Components/FormInput.vue';
    import FormImage from '@/Components/FormImage.vue';
    import FormSelect from '@/Components/FormSelect.vue';
    import { toast } from 'vue3-toastify';
    import 'vue3-toastify/dist/index.css';
    import { Listbox, ListboxButton, ListboxOptions, ListboxOption } from '@headlessui/vue';

    const contacts = ref([]);
    const searchQuery = ref('');
    const selectedContacts = ref([]);
    const selectedContactIds = ref(new Set());
    const isLoadingContacts = ref(false);

    const props = defineProps({
        modelValue: Boolean,
        label: String,
        url: String,
        form: Object,
        formInputs: Array,
        SelectGroup: Array,
        selectContact: Array, // New prop for pre-selected contacts
    });

    const form = ref(props.form);
    const isLoading = ref(false);
    const errorMessages = ref({});

    const filteredContacts = computed(() => {
        if (!searchQuery.value) return contacts.value;
        
        return contacts.value.filter(contact => {
            return (
                (contact.full_name && contact.full_name.toLowerCase().includes(searchQuery.value.toLowerCase())) ||
                (contact.email && contact.email.toLowerCase().includes(searchQuery.value.toLowerCase())) ||
                (contact.phone && contact.phone.toLowerCase().includes(searchQuery.value.toLowerCase())) ||
                (contact.formatted_phone_number && contact.formatted_phone_number.toLowerCase().includes(searchQuery.value.toLowerCase()))
            );
        });
    });

    // Fetch contacts with search functionality
    const fetchContacts = async () => {
        isLoadingContacts.value = true;
        try {
            const params = {};
            if (searchQuery.value) {
                params.search = searchQuery.value;
            }
            params.fetch_json = true;
            const response = await axios.get('/contacts', { params });
            contacts.value = response.data.rows;
        } catch (error) {
            console.error('Error fetching contacts:', error);
            toast.error('Failed to load contacts');
        } finally {
            isLoadingContacts.value = false;
        }
    };

    // Debounce search
    let searchTimeout = null;
    watch(searchQuery, (newVal) => {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            fetchContacts();
        }, 300);
    });

    // Watch for changes in selectedContacts and update the IDs set
    watch(selectedContacts, (newVal) => {
        selectedContactIds.value = new Set(newVal.map(contact => contact.id));
    }, { deep: true });

    // Initialize selectedContacts with selectContact prop on mount
    onMounted(() => {
        fetchContacts();
        if (props.selectContact && props.selectContact.length > 0) {
            selectedContacts.value = [...props.selectContact];
        }
    });

    const submitForm = async (event) => {
        isLoading.value = true;
        errorMessages.value = {};

        try {
            // Create a plain object for form data
            const payload = {
                ...form.value,
                contact_ids: selectedContacts.value.length > 0 
                    ? selectedContacts.value.map(contact => contact.id)
                    : [],
            };

            const response = await axios.post(props.url, payload, {
                headers: {
                    'Content-Type': 'application/json',
                },
            });

            if (response.status === 200 && response.data.success) {
                handleSuccessResponse(response);
            } else {
                handleFailedResponse(response);
            }
        } catch (error) {
            handleError(error);
        } finally {
            setTimeout(() => {
                isLoading.value = false;
            }, 1000);
        }
    };

    const handleSuccessResponse = (response) => {
        setTimeout(() => {
            onClose();
            router.reload();
            toast(response.data.message, {
                autoClose: 3000,
            });
            emit('callback', response.data);
        }, 1000);
    };

    const handleFailedResponse = (response) => {
        setTimeout(() => {
            if (response.data.errors) {
                errorMessages.value = response.data.errors;
            }
        }, 1000);
    };

    const handleError = (error) => {
        setTimeout(() => {
            errorMessages.value = error.response?.data?.errors || { general: ['An error occurred'] };
            toast.error('Failed to submit form');
        }, 1000);
    };

    const emit = defineEmits(['update:modelValue', 'callback']);
    
    function onClose() {
        emit('update:modelValue', false);
    }
</script>

<template>
    <Modal :label="label" :isOpen="modelValue">
        <div class="mt-5 grid grid-cols-1 gap-x-6 gap-y-4">
            <form @submit.prevent="submitForm()" class="grid gap-x-6 gap-y-4 sm:grid-cols-6">
                <template v-for="(input, index) in formInputs" :key="index">
                    <FormInput v-if="input.inputType == 'FormInput'" v-model="form[input.name]" :error="errorMessages?.[input.name]?.[0]" :name="input.label" :type="input.type" :class="input.className"/>
                    <FormSelect v-else-if="input.inputType == 'FormSelect'" v-model="form[input.name]" :error="errorMessages?.[input.name]?.[0]" :options="input.options" :name="input.label" :class="input.className" :placeholder="input.placeholder"/>
                    <FormImage v-else-if="input.inputType == 'FormImage'" v-model="form[input.name]" :error="errorMessages?.[input.name]?.[0]" :options="input.options" :label="input.label" :class="input.className"/>
                </template>

                <!-- Multi-select contacts component -->
                <div class="sm:col-span-6">
                    <label class="block text-sm font-medium leading-6 text-gray-900 mb-1">Contacts</label>
                    <Listbox v-model="selectedContacts" multiple>
                        <div class="relative mt-1">
                            <ListboxButton
                                class="relative w-full cursor-default rounded-md bg-white py-2 pl-3 pr-10 text-left text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:outline-none focus:ring-2 focus:ring-primary sm:text-sm sm:leading-6 h-10"
                            >
                                <span class="block truncate">
                                    {{ selectedContacts.length > 0 
                                        ? `${selectedContacts.length} selected` 
                                        : 'Select contacts' }}
                                </span>
                                <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-chevrons-up-down"><path d="m7 15 5 5 5-5"/><path d="m7 9 5-5 5 5"/></svg>
                                </span>
                            </ListboxButton>

                            <transition
                                leave-active-class="transition duration-100 ease-in"
                                leave-from-class="opacity-100"
                                leave-to-class="opacity-0"
                            >
                                <ListboxOptions
                                    class="max-h-[30vh] overflow-scroll z-50 mt-1 w-full rounded-md bg-white py-1 text-base shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none sm:text-sm"
                                >
                                    <div class="sticky top-0 z-10 bg-white p-2">
                                        <input
                                            type="text"
                                            v-model="searchQuery"
                                            placeholder="Search contacts..."
                                            class="w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-primary sm:text-sm sm:leading-6"
                                            @click.stop
                                        />
                                    </div>

                                    <div v-if="isLoadingContacts" class="relative cursor-default select-none py-2 pl-4 pr-4 text-gray-700 flex justify-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" class="animate-spin">
                                            <path fill="currentColor" d="M12 2A10 10 0 1 0 22 12A10 10 0 0 0 12 2Zm0 18a8 8 0 1 1 8-8A8 8 0 0 1 12 20Z" opacity=".5"/>
                                            <path fill="currentColor" d="M20 12h2A10 10 0 0 0 12 2V4A8 8 0 0 1 20 12Z">
                                                <animateTransform attributeName="transform" dur="1s" from="0 12 12" repeatCount="indefinite" to="360 12 12" type="rotate"/>
                                            </path>
                                        </svg>
                                    </div>

                                    <div v-else-if="filteredContacts.length === 0" class="relative cursor-default select-none py-2 pl-4 pr-4 text-gray-700">
                                        No contacts found.
                                    </div>

                                    <ListboxOption
                                        v-for="contact in filteredContacts"
                                        :key="contact.id"
                                        :value="contact"
                                        as="template"
                                        v-slot="{ active, selected }"
                                        :disabled="selectedContactIds.has(contact.id)"
                                    >
                                        <li
                                            :class="[
                                                active ? 'bg-primary text-white' : 'text-gray-900',
                                                selectedContactIds.has(contact.id) ? 'bg-gray-100' : '',
                                                'relative cursor-default select-none py-2 pl-3 pr-9',
                                            ]"
                                        >
                                            <div class="flex items-center">
                                                <span :class="[selected ? 'font-semibold' : 'font-normal', 'block truncate']">
                                                    {{ contact.full_name }}
                                                </span>
                                                <span :class="[active ? 'text-white' : 'text-gray-500', 'ml-2 text-xs']">
                                                    {{ contact.formatted_phone_number || contact.phone }}
                                                </span>
                                            </div>

                                            <span
                                                v-if="selectedContactIds.has(contact.id)"
                                                :class="[
                                                    active ? 'text-white' : 'text-primary',
                                                    'absolute inset-y-0 right-0 flex items-center pr-4',
                                                ]"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-check"><path d="M20 6 9 17l-5-5"/></svg>
                                            </span>
                                        </li>
                                    </ListboxOption>
                                </ListboxOptions>
                            </transition>
                        </div>
                    </Listbox>
                    <p v-if="errorMessages?.contacts" class="mt-1 text-sm text-red-600">{{ errorMessages.contacts[0] }}</p>
                </div>

                <div class="sm:col-span-6 mt-4 flex">
                    <button type="button" @click.self="onClose" class="inline-flex justify-center rounded-md border border-transparent bg-slate-50 px-4 py-2 text-sm text-slate-500 hover:bg-slate-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2 mr-4">{{ $t('Cancel') }}</button>
                    <button 
                        :class="['inline-flex justify-center rounded-md border border-transparent bg-primary px-4 py-2 text-sm text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2', { 'opacity-50': isLoading }]"
                        :disabled="isLoading"
                    >
                        <svg v-if="isLoading" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"><path fill="currentColor" d="M12 2A10 10 0 1 0 22 12A10 10 0 0 0 12 2Zm0 18a8 8 0 1 1 8-8A8 8 0 0 1 12 20Z" opacity=".5"/><path fill="currentColor" d="M20 12h2A10 10 0 0 0 12 2V4A8 8 0 0 1 20 12Z"><animateTransform attributeName="transform" dur="1s" from="0 12 12" repeatCount="indefinite" to="360 12 12" type="rotate"/></path></svg>
                        <span v-else>{{ $t('Save') }}</span>
                    </button>
                </div>
            </form>
        </div>
    </Modal>
</template>