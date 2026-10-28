```vue
<script setup>
import AppLayout from "./../Layout/App.vue";
import Documentation from "./Documentation.vue";
import Menu from "./Menu.vue";
import { Link, useForm, router, usePage } from "@inertiajs/vue3";
import { ref, watchEffect, onMounted, onUpdated } from 'vue';
import { TabGroup, TabList, Tab, TabPanels, TabPanel } from '@headlessui/vue';
import { Dialog, DialogPanel, DialogTitle, TransitionRoot, TransitionChild } from '@headlessui/vue';
import Table from '@/Components/Table.vue';
import TableHeader from '@/Components/TableHeader.vue';
import TableHeaderRow from '@/Components/TableHeaderRow.vue';
import TableHeaderRowItem from '@/Components/TableHeaderRowItem.vue';
import TableBody from '@/Components/TableBody.vue';
import TableBodyRow from '@/Components/TableBodyRow.vue';
import TableBodyRowItem from '@/Components/TableBodyRowItem.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownItemGroup from '@/Components/DropdownItemGroup.vue';
import DropdownItem from '@/Components/DropdownItem.vue';
import Pagination from '@/Components/Pagination.vue';
import AlertModal from '@/Components/AlertModal.vue';
import { useAlertModal } from '@/Composables/useAlertModal';
import debounce from 'lodash/debounce';
import { trans } from 'laravel-vue-i18n';
import 'vue3-toastify/dist/index.css';

const props = defineProps({
    rows: {
        type: Object,
        required: true,
        validator: (rows) => Array.isArray(rows.data)
    },
    url: String,
    apirequests: {
        type: Object,
        default: () => ({ search: '', page: null })
    },
    webhookModule: Object,
});

const tab = ref(0);
const code = ref(`const foo = 'bar';`);
const loadIcon = ref(false);
const isSearching = ref(false);
const bulkCheckbox = ref(false);
const selectedCount = ref(0);
const checkedTokens = ref([]);
const isModalOpen = ref(false);
const params = ref({
    search: props.apirequests?.search || null,
    page: props.apirequests?.page || null,
});

const { isOpenAlert, openAlert, confirmAlert } = useAlertModal();

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
        onError: (errors) => {
            console.error('Token generation failed:', errors);
        },
        onFinish: () => {
            loadIcon.value = false;
        },
    });
};

const search = debounce(() => {
    params.value.page = null;
    isSearching.value = true;
    runSearch();
}, 1000);

const clearSearch = () => {
    params.value.search = null;
    runSearch();
};

const runSearch = () => {
    const filteredParams = Object.fromEntries(
        Object.entries(params.value).filter(([_, value]) => value !== null)
    );
    router.visit('/developer-tools/access-tokens', {
        method: 'get',
        data: filteredParams,
        preserveState: true,
        onFinish: () => {
            isSearching.value = false;
        },
    });
};

function saveCheckedItems() {
    localStorage.setItem('checkedTokens', JSON.stringify(checkedTokens.value));
}

function loadCheckedItems() {
    const savedItems = localStorage.getItem('checkedTokens');
    checkedTokens.value = savedItems ? JSON.parse(savedItems) : [];
}

function updateCheckedItems(id, isChecked) {
    const index = checkedTokens.value.indexOf(id);
    if (isChecked && index === -1) {
        checkedTokens.value.push(id);
    } else if (!isChecked && index !== -1) {
        checkedTokens.value.splice(index, 1);
    }
    saveCheckedItems();
}

function toggleCheckbox(tokenId) {
    const token = props.rows.data.find(token => token.id === tokenId);
    token.isChecked = !token.isChecked;
    updateCheckedItems(tokenId, token.isChecked);
    updateBulkCheckboxState();
    updateSelectedCount();
}

function toggleAllCheckboxes() {
    bulkCheckbox.value = !bulkCheckbox.value;
    props.rows.data.forEach(row => {
        row.isChecked = bulkCheckbox.value;
        updateCheckedItems(row.id, bulkCheckbox.value);
    });
    updateSelectedCount();
}

function applyCheckedState() {
    props.rows.data.forEach(row => {
        row.isChecked = checkedTokens.value.includes(row.id);
    });
    updateBulkCheckboxState();
    updateSelectedCount();
}

function updateBulkCheckboxState() {
    bulkCheckbox.value = props.rows.data.length > 0 && props.rows.data.every(row => row.isChecked);
}

function updateSelectedCount() {
    selectedCount.value = checkedTokens.value.length;
}

function deleteItems(value) {
    router.visit('/developer-tools/access-tokens', {
        method: 'delete',
        data: { 'ids': value === 'all' ? [] : checkedTokens.value },
        preserveState: true,
        onSuccess: () => {
            localStorage.removeItem('checkedTokens');
            checkedTokens.value = [];
        },
        onError: (errors) => {
            console.error('Bulk delete failed:', errors);
        },
    });
}

const deleteAction = (id) => {
    form.delete(`/developer-tools/access-tokens/${id}`, {
        preserveState: true,
        onSuccess: () => {
            localStorage.removeItem('checkedTokens');
            checkedTokens.value = checkedTokens.value.filter(tokenId => tokenId !== id);
        },
        onError: (errors) => {
            console.error('Delete failed:', errors);
        },
    });
};

const isLastRow = (index) => {
    return index === props.rows.data.length - 1;
};

onMounted(() => {
    loadCheckedItems();
    applyCheckedState();
});

onUpdated(() => {
    applyCheckedState();
});

watchEffect(() => {
    params.value.page = props.apirequests?.page;
    applyCheckedState();
});
</script>

<template>
    <AppLayout :title="props.title">
        <div class="bg-[#f0f2f5] md:bg-white flex items-center md:border md:border-primary md:shadow-sm h-12 md:h-10 w-full md:w-80 rounded-xl mb-6 text-sm mt-6">
            <span class="pl-3">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24">
                    <path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m15 15l6 6m-11-4a7 7 0 1 1 0-14a7 7 0 0 1 0 14Z"/>
                </svg>
            </span>
            <input
                @input="search"
                v-model="params.search"
                type="text"
                class="outline-none px-4 w-full bg-inherit"
                :placeholder="trans('Search API logs')"
                aria-label="Search API logs"
            >
            <button v-if="isSearching === false && params.search" @click="clearSearch" type="button" class="pr-2">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
                    <path fill="currentColor" d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10s10-4.5 10-10S17.5 2 12 2zm3.7 12.3c.4.4.4 1 0 1.4c-.4.4-1 .4-1.4 0L12 13.4l-2.3 2.3c-.4.4-1 .4-1.4 0c-.4-.4-.4-1 0-1.4l2.3-2.3l-2.3-2.3c-.4-.4-.4-1 0-1.4c.4-.4 1-.4 1.4 0l2.3 2.3l2.3-2.3c.4-.4 1-.4 1.4 0c.4.4.4 1 0 1.4L13.4 12l2.3 2.3z"/>
                </svg>
            </button>
            <span v-if="isSearching" class="pr-2">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
                    <circle cx="12" cy="3.5" r="1.5" fill="currentColor" opacity="0">
                        <animateTransform attributeName="transform" calcMode="discrete" dur="2.4s" repeatCount="indefinite" type="rotate" values="0 12 12;90 12 12;180 12 12;270 12 12"/>
                        <animate attributeName="opacity" dur="0.6s" keyTimes="0;0.5;1" repeatCount="indefinite" values="1;1;0"/>
                    </circle>
                    <circle cx="12" cy="3.5" r="1.5" fill="currentColor" opacity="0">
                        <animateTransform attributeName="transform" begin="0.2s" calcMode="discrete" dur="2.4s" repeatCount="indefinite" type="rotate" values="30 12 12;120 12 12;210 12 12;300 12 12"/>
                        <animate attributeName="opacity" begin="0.2s" dur="0.6s" keyTimes="0;0.5;1" repeatCount="indefinite" values="1;1;0"/>
                    </circle>
                    <circle cx="12" cy="3.5" r="1.5" fill="currentColor" opacity="0">
                        <animateTransform attributeName="transform" begin="0.4s" calcMode="discrete" dur="2.4s" repeatCount="indefinite" type="rotate" values="60 12 12;150 12 12;240 12 12;330 12 12"/>
                        <animate attributeName="opacity" begin="0.4s" dur="0.6s" keyTimes="0;0.5;1" repeatCount="indefinite" values="1;1;0"/>
                    </circle>
                </svg>
            </span>
        </div>

        <div class="mb-4 flex justify-between items-center">
            <div>
                <span v-if="selectedCount > 0" class="text-sm text-gray-600">
                    {{ trans(':count selected', { count: selectedCount }) }}
                </span>
            </div>
            <button
                v-if="selectedCount > 0"
                @click="openAlert('bulk')"
                class="rounded-md px-3 py-2 text-sm text-white bg-red-600 hover:bg-red-700"
            >
                {{ trans('Delete selected') }}
            </button>
        </div>

        <Table :rows="rows">
            <TableHeader>
                <TableHeaderRow>
                    <TableHeaderRowItem :position="'first'">
                        <input
                            type="checkbox"
                            v-model="bulkCheckbox"
                            @change="toggleAllCheckboxes"
                            aria-label="Select all API logs"
                        >
                    </TableHeaderRowItem>
                    <TableHeaderRowItem>{{ trans('ID') }}</TableHeaderRowItem>
                    <TableHeaderRowItem>{{ trans('Endpoint') }}</TableHeaderRowItem>
                    <TableHeaderRowItem>{{ trans('IP Address') }}</TableHeaderRowItem>
                    <TableHeaderRowItem>{{ trans('Created At') }}</TableHeaderRowItem>
                    <TableHeaderRowItem :position="'last'"></TableHeaderRowItem>
                </TableHeaderRow>
            </TableHeader>
            <TableBody>
                <TableBodyRow v-for="(item, index) in rows.data" :key="item.id" :class="!isLastRow(index) ? 'border-b' : ''">
                    <TableBodyRowItem :position="'first'">
                        <input
                            type="checkbox"
                            v-model="item.isChecked"
                            @change="toggleCheckbox(item.id)"
                            :aria-label="trans('Select log :id', { id: item.id })"
                        >
                    </TableBodyRowItem>
                    <TableBodyRowItem>{{ item.id }}</TableBodyRowItem>
                    <TableBodyRowItem>{{ item.endpoint }}</TableBodyRowItem>
                    <TableBodyRowItem>{{ item.ip_address }}</TableBodyRowItem>
                    <TableBodyRowItem>{{ item.created_at }}</TableBodyRowItem>
                    <TableBodyRowItem :position="'last'">
                        <Dropdown :align="'right'" class="mt-2">
                            <button
                                class="inline-flex w-full justify-center rounded-md text-sm font-medium text-black hover:bg-opacity-30 focus:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-opacity-75"
                                :aria-label="trans('Actions for log :id', { id: item.id })"
                            >
                                <span class="hover:bg-[#F6F7F9] hover:rounded-full w-[fit-content] p-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24">
                                        <path fill="currentColor" d="M12 16a2 2 0 0 1 2 2a2 2 0 0 1-2 2a2 2 0 0 1-2-2a2 2 0 0 1 2-2m0-6a2 2 0 0 1 2 2a2 2 0 0 1-2 2a2 2 0 0 1-2-2a2 2 0 0 1 2-2m0-6a2 2 0 0 1 2 2a2 2 0 0 1-2 2a2 2 0 0 1-2-2a2 2 0 0 1 2-2Z"/>
                                    </svg>
                                </span>
                            </button>
                            <template #items>
                                <DropdownItemGroup>
                                    <DropdownItem as="button" @click="openAlert(item.id)">{{ trans('Delete') }}</DropdownItem>
                                </DropdownItemGroup>
                            </template>
                        </Dropdown>
                    </TableBodyRowItem>
                </TableBodyRow>
            </TableBody>
        </Table>

        <div v-if="rows.data.length === 0" class="bg-white rounded-xl">
            <div class="p-4 py-8">
                <div class="flex justify-center mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" width="72" height="72" viewBox="0 0 256 256">
                        <path fill="currentColor" d="M216 80h-32V48a16 16 0 0 0-16-16H40a16 16 0 0 0-16 16v128a8 8 0 0 0 13 6.22L72 154v30a16 16 0 0 0 16 16h93.59L219 230.22a8 8 0 0 0 5 1.78a8 8 0 0 0 8-8V96a16 16 0 0 0-16-16M66.55 137.78L40 159.25V48h128v88H71.58a8 8 0 0 0-5.03 1.78M216 207.25l-26.55-21.47a8 8 0 0 0-5-1.78H88v-32h80a16 16 0 0 0 16-16V96h32Z"/>
                    </svg>
                </div>
                <h3 class="text-center text-lg font-medium mb-4">{{ trans('No API logs found') }}</h3>
                <div class="flex justify-center">
                    <button
                        @click="openModal"
                        class="rounded-md px-3 py-2 text-sm hover:shadow-md text-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 bg-primary"
                    >
                        {{ trans('Generate new token') }}
                    </button>
                </div>
            </div>
        </div>


        <TransitionRoot appear :show="isModalOpen" as="template">
            <Dialog as="div" @close="closeModal" class="relative z-10">
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
                <div class="fixed inset-0 overflow-y-auto">
                    <div class="flex min-h-full items-center justify-center p-4 text-center">
                        <TransitionChild
                            as="template"
                            enter="duration-300 ease-out"
                            enter-from="opacity-0 scale-95"
                            enter-to="opacity-100 scale-100"
                            leave="duration-200 ease-in"
                            leave-from="opacity-100 scale-100"
                            leave-to="opacity-0 scale-95"
                        >
                            <DialogPanel class="w-full max-w-md transform overflow-hidden rounded-2xl bg-white p-6 text-left align-middle shadow-xl transition-all">
                                <DialogTitle as="h3" class="text-lg font-medium leading-6 text-gray-900">
                                    {{ trans('Generate New Token') }}
                                </DialogTitle>
                                <div class="mt-2">
                                    <div class="mb-4">
                                        <label for="name" class="block text-sm font-medium text-gray-700">{{ trans('Name') }}</label>
                                        <input
                                            id="name"
                                            v-model="form.name"
                                            type="text"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                            :placeholder="trans('Token name')"
                                        >
                                        <p v-if="form.errors.name" class="text-red-500 text-xs mt-1">{{ form.errors.name }}</p>
                                    </div>
                                    <div class="mb-4">
                                        <label for="limit" class="block text-sm font-medium text-gray-700">{{ trans('Limit') }}</label>
                                        <input
                                            id="limit"
                                            v-model="form.limit"
                                            type="number"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                            :placeholder="trans('Request limit')"
                                        >
                                        <p v-if="form.errors.limit" class="text-red-500 text-xs mt-1">{{ form.errors.limit }}</p>
                                    </div>
                                    <div class="mb-4">
                                        <label for="expire_at" class="block text-sm font-medium text-gray-700">{{ trans('Expires At') }}</label>
                                        <input
                                            id="expire_at"
                                            v-model="form.expire_at"
                                            type="datetime-local"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                        >
                                        <p v-if="form.errors.expire_at" class="text-red-500 text-xs mt-1">{{ form.errors.expire_at }}</p>
                                    </div>
                                </div>
                                <div class="mt-4 flex justify-end">
                                    <button
                                        type="button"
                                        class="inline-flex justify-center rounded-md border border-transparent bg-gray-100 px-4 py-2 text-sm font-medium text-gray-900 hover:bg-gray-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-gray-500 focus-visible:ring-offset-2"
                                        @click="closeModal"
                                    >
                                        {{ trans('Cancel') }}
                                    </button>
                                    <button
                                        type="button"
                                        class="ml-2 inline-flex justify-center rounded-md border border-transparent bg-primary px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2"
                                        @click="generateToken"
                                        :disabled="loadIcon"
                                    >
                                        <span v-if="!loadIcon">{{ trans('Generate') }}</span>
                                        <span v-else class="flex items-center">
                                            <svg class="animate-spin h-5 w-5 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                            {{ trans('Generating...') }}
                                        </span>
                                    </button>
                                </div>
                            </DialogPanel>
                        </TransitionChild>
                    </div>
                </div>
            </Dialog>
        </TransitionRoot>

        <AlertModal
            v-model="isOpenAlert"
            @confirm="() => confirmAlert(isOpenAlert.value === 'bulk' ? () => deleteItems('selected') : () => deleteAction(isOpenAlert.value))"
            :label="trans('Delete API log')"
            :description="trans('Are you sure you want to delete this API log? This action cannot be undone.')"
        />
    </AppLayout>
</template>
```