<template>
    <AppLayout>
        <div class="md:bg-inherit bg-white md:flex md:flex-grow capitalize">
            <div  class=" flex-col h-full bg-white border-r border-l md:flex transition-all" :class="[$page.url === '/contacts/add' || contact ? 'hidden' : '' , contact && !editContact ? 'md:w-[60%]  lg:w-[70%]' : '  md:w-[40%] lg:w-[50%]' ]  ">
                <div class="px-4 pt-4">
                    <div class="flex justify-between mt-2">
                        <div class="flex space-x-1 text-xl">
                            <h2>{{ $t('Contacts') }}</h2>
                            <span class="text-slate-500">{{ props.rowCount }}</span>
                        </div>
                        <div class="flex space-x-2 items-center">
                            <Link href="/contacts/add" title="Add Contact">
                                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24"><g fill="currentColor" fill-rule="evenodd" clip-rule="evenodd"><path d="M2 12C2 6.477 6.477 2 12 2s10 4.477 10 10s-4.477 10-10 10S2 17.523 2 12Zm10-8a8 8 0 1 0 0 16a8 8 0 0 0 0-16Z"/><path d="M13 7a1 1 0 1 0-2 0v4H7a1 1 0 1 0 0 2h4v4a1 1 0 1 0 2 0v-4h4a1 1 0 1 0 0-2h-4V7Z"/></g></svg>
                            </Link>
                        </div>
                    </div>
                </div>
                <ContactTable :rows="props.rows" :filters="props.filters" :type="'contact'" @callback="handleContact"/>
            </div>
            <div :class="contact && !editContact ? 'md:w-[40%] lg:w-[30%]' : '  md:w-[60%] lg:w-[50%]'" class="transition-all bg-cover md:h-[100vh] md:overflow-y-hidden">
                <div v-if="contact">
                    <ContactInfo v-if="!editContact" class="pt-20" :contact="contact" :fields="props.fields" :locationSettings="locationSettings"/>
                    <ContactForm v-else :contactGroups="props.contactGroups" :contact="props.contact" :fields="props.fields" :locationSettings="locationSettings" :countries="props.countries"/>
                </div>
                <div v-else>
                    <div v-if="$page.url === '/contacts/add'">
                        <ContactForm :contactGroups="props.contactGroups" :contact="props.contact" :fields="props.fields" :locationSettings="locationSettings" :countries="props.countries"/>
                    </div>
                    <div v-else>
                        <div class="md:flex px-2 justify-center pt-20 hidden">
                            <div class="border  w-full py-10 px-5  rounded-xl bg-white">
                                <h2 class="text-center text-xl text-slate-500 mb-6">{{ $t('Select contact') }}</h2>
                                <div class="flex justify-center">
                                    <div class="border-r border-slate-500 h-10"></div>
                                </div>
                                <h2 class="text-center text-slate-600">{{ $t('Or') }}</h2>
                                <div class="flex justify-center">
                                    <div class="border-r border-slate-500 h-10"></div>
                                </div>
                                <div class="flex justify-center space-x-4 mt-6">
                                    <Link href="/contacts/add" class="bg-primary rounded-lg text-xs text-white p-2 px-4 text-center">{{ $t('Add contact') }}</Link>
                                    <button @click="isOpenModal = true" class="bg-primary rounded-lg text-xs text-white p-2 px-4 text-center">{{ $t('Bulk upload') }}</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>

    <ContactImportModal :type="'contact'" v-model:modelValue="isOpenModal"/>
</template>
<script setup>
    import AppLayout from "./../Layout/App.vue";
    import { ref } from 'vue';
    import { Link } from "@inertiajs/vue3";
    import ContactForm from '@/Components/ContactComponents/CreateForm.vue';
    import ContactImportModal from '@/Components/ContactImportModal.vue';
    import ContactInfo from '@/Components/ContactInfo.vue';
    import ContactTable from '@/Components/Tables/ContactTable.vue';
    import { router } from '@inertiajs/vue3';

    const props = defineProps({ rows: Object, filters: Object, rowCount: Number, contactGroups: Object, contact: Object, editContact: Boolean, fields: Object, locationSettings: String, flash: Object, countries: Array });
    const isOpenModal = ref(false);

    const handleContact = (value) => {
        router.visit('/contacts', {
            method: 'get',
            data: value,
        })
    } 
</script>