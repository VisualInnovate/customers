<template>
    <SettingLayout>
        <div   >
            <div class="bg-white border md:w-full w-[90vw] border-slate-200 rounded-lg pt-2 text-sm mb-4 px-4 mb-20 ">
                <button @click="isOpenFormModal = true"
                    class="float-right rounded-md bg-primary px-3 py-2 text-sm text-white shadow-sm hover:bg-primaryLight focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 ">{{
                        $t('Add Tags') }}</button>
                <Table  v-if="rows.data.length" :rows="rows">
                    <TableHeader>
                        <TableHeaderRow>
                            <TableHeaderRowItem :position="'first'">{{ $t('Tag') }}</TableHeaderRowItem>
                            <TableHeaderRowItem>{{ $t('Created at') }}</TableHeaderRowItem>
                            <TableHeaderRowItem :position="'last'"></TableHeaderRowItem>
                        </TableHeaderRow>
                    </TableHeader>
                    <TableBody>
                        <TableBodyRow v-for="(item, index) in rows.data" :key="index"
                            :class="!isLastRow(index) ? 'border-b' : ''">
                            <TableBodyRowItem :position="'first'" class="hidden sm:table-cell">
                                <div
                                    class="py-1 px-2 border-primary border text-primary rounded-full w-fit bg-primary-light-2 text-xs">
                                    {{ item.tag }}
                                </div>
                            </TableBodyRowItem>
                            <TableBodyRowItem class="hidden sm:table-cell">{{ new
                                Date(item.updated_at).toLocaleDateString('en-Us') }}</TableBodyRowItem>
                            <TableBodyRowItem :position="'last'">
                                <Dropdown :align="'right'" class="mt-2">
                                    <button
                                        class="inline-flex w-full justify-center rounded-md text-sm font-medium text-black hover:bg-opacity-30 focus:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-opacity-75">
                                        <span class="hover:bg-[#F6F7F9] hover:rounded-full w-[fit-content] p-2">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                                                viewBox="0 0 24 24">
                                                <path fill="currentColor"
                                                    d="M12 16a2 2 0 0 1 2 2a2 2 0 0 1-2 2a2 2 0 0 1-2-2a2 2 0 0 1 2-2m0-6a2 2 0 0 1 2 2a2 2 0 0 1-2 2a2 2 0 0 1-2-2a2 2 0 0 1 2-2m0-6a2 2 0 0 1 2 2a2 2 0 0 1-2 2a2 2 0 0 1-2-2a2 2 0 0 1 2-2Z" />
                                            </svg>
                                        </span>
                                    </button>
                                    <template #items>
                                        <DropdownItemGroup>

                                            <DropdownItem as="button" @click="openAlert(item.id)">{{ $t('Delete') }}
                                            </DropdownItem>
                                        </DropdownItemGroup>
                                    </template>
                                </Dropdown>
                            </TableBodyRowItem>
                        </TableBodyRow>
                    </TableBody>
                </Table>
                <div v-if="rows.data.length == 0">
                    <div class="p-4 py-8 flex justify-center items-center  flex-col ">
                        <div class="mb-4">
                            <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24"><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="m2 2.207l20 19.794M6.562 2.515c2.006-.602 7.754-.723 11.162-.118c.825.147 1.62.679 2.054 1.385c.726 1.183.687 2.56.687 3.942l-.12 8.455M4 4.172c-.632 2.267-.547 6.428-.506 12.57c.006.79.043 1.59.281 2.345c.369 1.17.983 1.88 2.332 2.45c.575.244 1.206.324 1.833.324h4.043c3.796-.092 5.328-.488 7.006-2.678m-8.502 2.678c2.381-1.207 3.608-1.375 3.296-4.411c-.06-.786.39-1.725 1.194-1.977m5.428-3.428c-.243 1.436-.406 1.97-1.375 2.801" color="currentColor"/></svg>
                        </div>
                        <h3 class=" text-center text-lg font-medium ">{{ $t('You don\'t have any Tags to Show') }}</h3>
                        <button @click="isOpenFormModal = true" class="underline text-slate-400" > {{  $t('You can add New Tags here') }}</button>
                    </div>
                </div>
            </div>
        </div>

        <Modal :label="$t('Add New Tag')" :isOpen=isOpenFormModal>
            <div class="mt-5 grid grid-cols-1 gap-x-6 gap-y-4">
                <form @submit.prevent="submitForm()" class="gap-y-4">
                    <div>
                        <FormInput v-model="form.tag" :name="$t('Tag')" :error="form.errors.tag" :type="'text'" />
                    </div>
                    <div class="mt-4 flex ">
                        <button type="button" @click.self="isOpenFormModal = false"
                            class="inline-flex justify-center rounded-md border border-transparent bg-slate-50 px-4 py-2 text-sm text-slate-500 hover:bg-slate-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2 mr-4">{{
                                $t('Cancel') }}</button>
                        <button
                            :class="['inline-flex justify-center rounded-md border border-transparent bg-primary px-4 py-2 text-sm text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2', { 'opacity-50': isLoading }]"
                            :disabled="isLoading">
                            <svg v-if="isLoading" xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                                viewBox="0 0 24 24">
                                <path fill="currentColor"
                                    d="M12 2A10 10 0 1 0 22 12A10 10 0 0 0 12 2Zm0 18a8 8 0 1 1 8-8A8 8 0 0 1 12 20Z"
                                    opacity=".5" />
                                <path fill="currentColor" d="M20 12h2A10 10 0 0 0 12 2V4A8 8 0 0 1 20 12Z">
                                    <animateTransform attributeName="transform" dur="1s" from="0 12 12"
                                        repeatCount="indefinite" to="360 12 12" type="rotate" />
                                </path>
                            </svg>
                            <span v-else>{{ $t('Save') }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </Modal>
        <AlertModal v-model="isOpenAlert" @confirm="() => confirmAlert(deleteAction)" :label="$t('Delete row')"
            :description="$t('Are you sure you want to delete this row? This action can not be undone')" />
    </SettingLayout>
</template>
<script setup>
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
    import SettingLayout from "./Layout.vue";
    import axios from "axios";
    import { onMounted, ref, watch } from 'vue';
    import FormInput from '@/Components/FormInput.vue';
    import AlertModal from '@/Components/AlertModal.vue';
    import { useAlertModal } from '@/Composables/useAlertModal';
    import Modal from '@/Components/Modal.vue';
    import { useForm } from "@inertiajs/vue3";

    const props = defineProps({ rows: Object });

    const form = useForm({
        'tag': null,
    });
    const { isOpenAlert, openAlert, confirmAlert } = useAlertModal();
    const isOpenFormModal = ref(false);

      const isLastRow = (index) => {
        return index === props.rows.data.length - 1;
    }

    const submitForm = async () => {
        form.setError('tag', null);
        axios.post('/tags', {
            tag: form.tag
        }).then((response) => {
            
            toast(response.data.message, {
                autoClose: 3000,
            });
            isOpenFormModal.value = false;
            props.rows.data.push(response.data.data);
        }).catch((error) => {
            toast(error.response?.data?.message, {
                autoClose: 3000,
            });

        });

    }
      const deleteAction = async (id) => {
        try {
            const response = await axios.delete(`/tags/${id}`);
            if (response.status === 200 && response.data.success) {
                const idx = props.rows.data.findIndex((i) => i.id === id);
                props.rows.data.splice(idx, 1);
            }
            toast(response.data.message, {
                autoClose: 3000,
            });
        } catch (error) {
            //console.log(error);
        }
    };


</script>