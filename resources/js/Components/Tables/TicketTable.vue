<script setup>
    import axios from 'axios';
    import AlertModal from '@/Components/AlertModal.vue';
    import { Link, router } from "@inertiajs/vue3";
    import { useAlertModal } from '@/Composables/useAlertModal';
    import { toast } from 'vue3-toastify';
    import 'vue3-toastify/dist/index.css';
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

    const props = defineProps({
        rows: {
            type: Object,
            required: true,
        },
    });

    const { isOpenAlert, openAlert, confirmAlert } = useAlertModal();

    const deleteAction = async (key) => {

        axios.get(`/tickets/destroy/${key}`).then(()=>{
            toast('Ticket delete Successfully', {
                autoClose: 3000,
            });
            router.visit('/tickets/index') 
        })

      
    };
    
    const emit = defineEmits(['delete']);

    function deleteItem(id) {
        emit('delete', id);
    }

    const isLastRow = (index) => {
        return index === props.rows.data.length - 1;
    }

    const capitalizeFirstLetter = (string) => {
        return string.charAt(0).toUpperCase() + string.slice(1).toLowerCase();
    }

    const findBodyText = (item) => {
        const metadataArray = JSON.parse(item);
        const bodyArray = metadataArray.components.find(element => element.type === "BODY");

        if (bodyArray) {
            return bodyArray.text;
        }

        return 'N/A';
    }
</script>
<template>
    <Table :rows="rows">
        <TableHeader>
            <TableHeaderRow>
                <TableHeaderRowItem :position="'first'">{{ $t('Ticket number') }}</TableHeaderRowItem>
                <TableHeaderRowItem>{{ $t('Subject') }}</TableHeaderRowItem>
                <TableHeaderRowItem>{{ $t('Type') }}</TableHeaderRowItem>

                <TableHeaderRowItem>{{ $t('Contact') }}</TableHeaderRowItem>
                <TableHeaderRowItem>{{ $t('Agent') }}</TableHeaderRowItem>
             
                
            </TableHeaderRow>
        </TableHeader>
        <TableBody>
            <TableBodyRow v-for="(item, index) in rows.data" :key="index" :class="!isLastRow(index) ? 'border-b' : ''">
                <TableBodyRowItem :position="'first'">{{ item.reference }}</TableBodyRowItem>
                <TableBodyRowItem :position="'first'">{{ item.subject }}</TableBodyRowItem>
                <TableBodyRowItem :position="'first'">{{ item.type }}</TableBodyRowItem>
                <TableBodyRowItem :position="'first'">{{ item.contact }}</TableBodyRowItem>
                <TableBodyRowItem :position="'first'">{{ item.agent }}</TableBodyRowItem>
        
                <TableBodyRowItem class="hidden sm:table-cell">
                    <div class="py-1 px-2 bg-gray-50 rounded-[5px] border border-dashed w-[20em] truncate text-xs">
                        {{ findBodyText(item.metadata) }}
                    </div>
                </TableBodyRowItem>
             
               
                <TableBodyRowItem :position="'last'">
                    <Dropdown :align="'right'" class="mt-2">
                        <button class="inline-flex w-full justify-center rounded-md text-sm font-medium text-black hover:bg-opacity-30 focus:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-opacity-75">
                            <span class="hover:bg-[#F6F7F9] hover:rounded-full w-[fit-content] p-2">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24">
                                    <path fill="currentColor" d="M12 16a2 2 0 0 1 2 2a2 2 0 0 1-2 2a2 2 0 0 1-2-2a2 2 0 0 1 2-2m0-6a2 2 0 0 1 2 2a2 2 0 0 1-2 2a2 2 0 0 1-2-2a2 2 0 0 1 2-2m0-6a2 2 0 0 1 2 2a2 2 0 0 1-2 2a2 2 0 0 1-2-2a2 2 0 0 1 2-2Z"/>
                                </svg>
                            </span>
                        </button>
                        <template #items>
                            <DropdownItemGroup>
                                <DropdownItem :href="'/tickets/edit/' + item.id">{{ $t('edit') }}</DropdownItem>
                                 <DropdownItem as="button" @click="openAlert(item.id)">{{ $t('Delete') }}</DropdownItem>
                            </DropdownItemGroup>
                        </template>
                    </Dropdown>
                </TableBodyRowItem>
            </TableBodyRow>
        </TableBody>
    </Table>
    <div v-if="rows.data.length == 0" class="bg-white rounded-xl">
        <div class="p-4 py-8">
            <div class="flex justify-center mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" width="72" height="72" viewBox="0 0 32 32"><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-width="2" d="M12 15h8m-8 4h8m8 5V11c0-1.105-.892-2-1.997-2H17c-2 0-2-3-5-3H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h20a2 2 0 0 0 2-2Z"></path></svg>
            </div>
            <h3 class="text-center text-lg font-medium mb-4">{{ $t('You don\'t have any Tickets') }}</h3>
            <div class="flex justify-center">
                <Link href="/tickets/create" class="rounded-md px-3 py-2 text-sm hover:shadow-md text-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 bg-primary" :disabled="isLoading">
                    <span v-if="!isLoading">{{ $t('Create Tickets') }}</span>
                </Link>
            </div>
        </div>
    </div>

    <!-- Alert Modal Component-->
    <TransitionRoot as="template" :show="isOpenAlert">
        <Dialog as="div" class="relative z-10" @close="open = false">
            <TransitionChild as="template" enter="ease-out duration-300" enter-from="opacity-0" enter-to="opacity-100" leave="ease-in duration-200" leave-from="opacity-100" leave-to="opacity-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" />
            </TransitionChild>

            <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
                <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                    <TransitionChild as="template" enter="ease-out duration-300" enter-from="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" enter-to="opacity-100 translate-y-0 sm:scale-100" leave="ease-in duration-200" leave-from="opacity-100 translate-y-0 sm:scale-100" leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
                        <DialogPanel class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg">
                            <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                                <div class="sm:flex sm:items-start">
                                    <div class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                                        <ExclamationTriangleIcon class="h-6 w-6 text-red-600" aria-hidden="true" />
                                    </div>
                                    <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left">
                                        <DialogTitle as="h3" class="text-base font-semibold leading-6 text-gray-900">{{ props.label }}</DialogTitle>
                                        <div class="mt-2">
                                            <p class="text-sm text-gray-500">{{ props.description }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                                <button v-if="!isLoading" type="button" class="inline-flex w-full justify-center rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500 sm:ml-3 sm:w-auto" @click.stop="confirm()">{{ $t('Delete') }}</button>
                                <button v-else type="button" class="inline-flex w-full justify-center rounded-md bg-gray-400 px-3 py-2 text-sm font-semibold text-white sm:ml-3 sm:w-auto">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"><path fill="currentColor" d="M12 2A10 10 0 1 0 22 12A10 10 0 0 0 12 2Zm0 18a8 8 0 1 1 8-8A8 8 0 0 1 12 20Z" opacity=".5"/><path fill="currentColor" d="M20 12h2A10 10 0 0 0 12 2V4A8 8 0 0 1 20 12Z"><animateTransform attributeName="transform" dur="1s" from="0 12 12" repeatCount="indefinite" to="360 12 12" type="rotate"/></path></svg>
                                </button>
                                <button type="button" class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:mt-0 sm:w-auto" @click.self="onClose" ref="cancelButtonRef">{{ $t('Cancel') }}</button>
                            </div>
                        </DialogPanel>
                    </TransitionChild>
                </div>
            </div>
        </Dialog>
    </TransitionRoot>




    <AlertModal 
        v-model="isOpenAlert" 
        @confirm="() => confirmAlert(deleteAction)"
        :label = "$t('Delete row')" 
        :description = "$t('Are you sure you want to delete this row? This action can not be undone')"
    />
</template>
  