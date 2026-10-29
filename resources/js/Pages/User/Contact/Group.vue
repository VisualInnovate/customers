<template>
    <AppLayout>
        <div class="capitalize bg-white md:bg-inherit md:flex md:flex-grow">
            <div class="md:w-[30%] md:flex flex-col h-full bg-white border-r border-l" :class="[group ? 'hidden' : '' , group  ? 'md:w-[60%]  lg:w-[70%]' : '  md:w-[40%] lg:w-[50%]']">
                <div class="px-4 pt-4">
                    <div class="flex justify-between mt-2">
                        <div class="flex space-x-1 text-xl">
                            <h2>{{ $t('Groups') }}</h2>
                            <span class="text-slate-500 ">{{ props.rowCount }}</span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <span @click="openModal()" class="cursor-pointer" title="Add Contact">
                                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24"><g fill="currentColor" fill-rule="evenodd" clip-rule="evenodd"><path d="M2 12C2 6.477 6.477 2 12 2s10 4.477 10 10s-4.477 10-10 10S2 17.523 2 12Zm10-8a8 8 0 1 0 0 16a8 8 0 0 0 0-16Z"/><path d="M13 7a1 1 0 1 0-2 0v4H7a1 1 0 1 0 0 2h4v4a1 1 0 1 0 2 0v-4h4a1 1 0 1 0 0-2h-4V7Z"/></g></svg>
                            </span>
                        </div>
                    </div>
                </div>
                <ContactTable :rows="props.rows" :filters="props.filters" :type="'group'" @callback="handleGroup"/>
            </div>
            <div :class="group ? 'md:w-[40%] lg:w-[30%]' : '  md:w-[60%] lg:w-[50%]'" class="transition-all bg-cover md:h-[100vh] md:overflow-y-hidden" >
                <div v-if="group">
                    <ContactGroupInfo :group="group" />
                </div>
                <div v-else class="hidden px-2 pt-20 md:block">
                    <div class="w-full px-5 py-10 bg-white border rounded-xl">
                        <h2 class="mb-6 text-xl text-center text-slate-500">{{ $t('Select group') }}</h2>
                        <div class="flex justify-center">
                            <div class="h-10 border-r border-slate-500"></div>
                        </div>
                        <h2 class="text-center text-slate-600">{{ $t('Or') }}</h2>
                        <div class="flex justify-center">
                            <div class="h-10 border-r border-slate-500"></div>
                        </div>
                        <div class="flex justify-center mt-6 space-x-4">
                            <button @click="openModal()" class="p-2 px-4 text-xs text-center text-white capitalize rounded-lg bg-primary">{{ $t('Add group') }}</button>
                            <button @click="isOpenModal = true" class="p-2 px-4 text-xs text-center text-white rounded-lg bg-primary">{{ $t('Bulk upload') }}</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
    <!-- Form Modal Component-->
    <FormModal 
        v-model="isOpenFormModal" 
        :label="$t('Add group')" 
        :url="formUrl" 
        :form="form" 
        :formInputs="formInputs"
        @callback="handleCallback"
    />

    <ContactImportModal :type="'group'" v-model:modelValue="isOpenModal"/>
</template>
<script setup>
    import AppLayout from "./../Layout/App.vue";
    import { ref } from 'vue';
    import ContactGroupInfo from '@/Components/ContactGroupInfo.vue';
    import ContactImportModal from '@/Components/ContactImportModal.vue';
    import ContactTable from '@/Components/Tables/ContactTable.vue';
    import Dropdown from '@/Components/Dropdown.vue';
    import DropdownItemGroup from '@/Components/DropdownItemGroup.vue';
    import DropdownItem from '@/Components/DropdownItem.vue';
    import FormModal from '@/Components/FormModal.vue';
    import { router } from '@inertiajs/vue3';
    import { trans } from 'laravel-vue-i18n';
    import Select from '@/Components/Select.vue';

    const props = defineProps({ rows: Object, filters: Object, rowCount: Number, group: Object, contacts: Array });
    const isOpenModal = ref(false);
    const currentUrl = window.location.href;
    const isOpenFormModal = ref(false);
    const formUrl = ref(currentUrl);
    const form = ref({
        name: '',
        contacts: null,
    });

    const contactOptions = ref([]);

    const initialFormInputs = [
        {
            inputType: 'FormInput',
            name: 'name',
            label: trans('name'),
            type: 'text',
            className: 'sm:col-span-6',
        },
        {
            inputType: 'Select',
            name: 'contacts',
            label: trans('Select Contacts'),
            className: 'sm:col-span-6',
            options: contactOptions,
            placeholder: trans('Select contacts'),
            multiple: true,
        },
    ];

    const formInputs = initialFormInputs;

    const openModal = () => {
        isOpenFormModal.value = true;
        form.value = {
            name: '',
            contacts: null,
        };
        // Transform contacts into options format
        contactOptions.value = props.contacts.map((contact) => ({
            value: contact.uuid,
            label: contact.full_name || `${contact.first_name} ${contact.last_name}`.trim(),
        }));
    }

    const handleGroup = (value) => {
        router.visit('/contact-groups', {
            method: 'get',
            data: value,
        })
    }

    const handleCallback = (res) => {
        group.value = res.data;
        form.value = {
            name: res.data.name,
            contacts: res.data.contacts?.map(contact => contact.uuid) || null,
        };
    }
</script>