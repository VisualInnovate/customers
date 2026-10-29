<script setup>
    import axios from 'axios';
    import { onMounted, ref, watchEffect } from 'vue';
    import { Link, router, useForm } from '@inertiajs/vue3';
    import Dropdown from '@/Components/Dropdown.vue';
    import DropdownItemGroup from '@/Components/DropdownItemGroup.vue';
    import DropdownItem from '@/Components/DropdownItem.vue';
    import { trans } from 'laravel-vue-i18n';
    import MediaContactsModal from './Modals/MediaContactsModal.vue';

    const props = defineProps(['contact', 'fields', 'locationSettings']);
    const contact = ref(props.contact);
    const metadata = (props.contact.metadata !== null && props.contact.metadata !== '') ? JSON.parse(props.contact.metadata) : {};
    const showMediaModal = ref(false);
    const media = ref([])
    watchEffect(() => {
        contact.value = props.contact;
    });

    const favorite = async () => {
        contact.value.is_favorite = !contact.value.is_favorite;
        router.put('/contacts/favorite/' + contact.value.uuid, { favorite: contact.value.is_favorite });
    }
    const handelChat=(id)=>{
        router.visit(`/chats/${id}`, {
            method: 'get',
            data: '',
        })
    }
    const handleContact = () => {
        router.visit('/template/message/chat', {
            method: 'get',
            data: '',
        })
    } 
    const getMedia = async(type='image') => {
        
        const {data} =  await axios.get(`/contacts/media/${contact.value.uuid}?type=${type}`)
        media.value = data
    }

    const handleContactMedia = async() => {
        showMediaModal.value = true;
        await getMedia();
    }
  
    const deleteRow = async () => {
        router.visit('/contacts', {
            method: 'delete',
            data: { 'uuids': [contact.value.uuid] },
            preserveState: true
        })
    }

    const getAddressDetail = (value, key) => {
        const address = JSON.parse(value);
        return address?.[key] && address?.[key] != 'Not Set' ? address?.[key] : trans('not set');
    }
</script>
<template>
    <div class="px-3 overflow-y-scroll h-screen">
        <div class=" pb-6 flex flex-col justify-center items-center  border-gray-300 border-b">
            <div>
                <div class="rounded-full p-1">
                    <img v-if="contact.avatar" class="rounded-full w-32 h-32" :src="contact.avatar">
                    <div v-else class="rounded-full w-32 h-32">
                        <svg class="text-gray-300" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd"
                                d="M18.685 19.097A9.723 9.723 0 0021.75 12c0-5.385-4.365-9.75-9.75-9.75S2.25 6.615 2.25 12a9.723 9.723 0 003.065 7.097A9.716 9.716 0 0012 21.75a9.716 9.716 0 006.685-2.653zm-12.54-1.285A7.486 7.486 0 0112 15a7.486 7.486 0 015.855 2.812A8.224 8.224 0 0112 20.25a8.224 8.224 0 01-5.855-2.438zM15.75 9a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z"
                                clip-rule="evenodd" />
                        </svg>

                    </div>
                </div>
            </div>
            <div>
                <h1 class="text-2xl text-center">{{ contact.full_name }}</h1>
                <div class="text-slate-500l text-center">
                    <span class="text-sm text-center">{{ contact.formatted_phone_number }}</span>
                </div>
                <div class="flex space-x-3 mt-4">
                    <button class="bg-gray-200 h-9 px-4 rounded-md flex items-center">
                        <Link :href="'/contacts/' + contact.uuid + '?edit=true'" class="text-[14px]">{{ $t('Edit') }}
                        </Link>
                    </button>
                    <button @click="favorite()" class="bg-gray-200 h-9 px-4 rounded-md">
                        <svg v-if="contact.is_favorite === 0 || contact.is_favorite === false"
                            xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24">
                            <path fill="none" stroke="currentColor" stroke-width="1.5"
                                d="M9.153 5.408C10.42 3.136 11.053 2 12 2c.947 0 1.58 1.136 2.847 3.408l.328.588c.36.646.54.969.82 1.182c.28.213.63.292 1.33.45l.636.144c2.46.557 3.689.835 3.982 1.776c.292.94-.546 1.921-2.223 3.882l-.434.507c-.476.557-.715.836-.822 1.18c-.107.345-.071.717.001 1.46l.066.677c.253 2.617.38 3.925-.386 4.506c-.766.582-1.918.051-4.22-1.009l-.597-.274c-.654-.302-.981-.452-1.328-.452c-.347 0-.674.15-1.329.452l-.595.274c-2.303 1.06-3.455 1.59-4.22 1.01c-.767-.582-.64-1.89-.387-4.507l.066-.676c.072-.744.108-1.116 0-1.46c-.106-.345-.345-.624-.821-1.18l-.434-.508c-1.677-1.96-2.515-2.941-2.223-3.882c.293-.941 1.523-1.22 3.983-1.776l.636-.144c.699-.158 1.048-.237 1.329-.45c.28-.213.46-.536.82-1.182l.328-.588Z" />
                        </svg>
                        <svg v-else xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24">
                            <path fill="#031b4d" stroke="#031b4d" stroke-width="1.5"
                                d="M9.153 5.408C10.42 3.136 11.053 2 12 2c.947 0 1.58 1.136 2.847 3.408l.328.588c.36.646.54.969.82 1.182c.28.213.63.292 1.33.45l.636.144c2.46.557 3.689.835 3.982 1.776c.292.94-.546 1.921-2.223 3.882l-.434.507c-.476.557-.715.836-.822 1.18c-.107.345-.071.717.001 1.46l.066.677c.253 2.617.38 3.925-.386 4.506c-.766.582-1.918.051-4.22-1.009l-.597-.274c-.654-.302-.981-.452-1.328-.452c-.347 0-.674.15-1.329.452l-.595.274c-2.303 1.06-3.455 1.59-4.22 1.01c-.767-.582-.64-1.89-.387-4.507l.066-.676c.072-.744.108-1.116 0-1.46c-.106-.345-.345-.624-.821-1.18l-.434-.508c-1.677-1.96-2.515-2.941-2.223-3.882c.293-.941 1.523-1.22 3.983-1.776l.636-.144c.699-.158 1.048-.237 1.329-.45c.28-.213.46-.536.82-1.182l.328-.588Z" />
                        </svg>
                    </button>
                    <Dropdown class="h-100" :align="'right'">
                        <button class="h-9 bg-gray-200 py-2 px-3 rounded-md flex items-center">
                            <span class="">
                                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 16 16">
                                    <path fill="currentColor"
                                        d="M4 8a2 2 0 1 1-4 0a2 2 0 0 1 4 0Zm6 0a2 2 0 1 1-4 0a2 2 0 0 1 4 0Zm4 2a2 2 0 1 0 0-4a2 2 0 0 0 0 4Z" />
                                </svg>
                            </span>
                        </button>
                        <template #items>
                            <DropdownItemGroup>
                                <!--<DropdownItem as="button" @click="openModal('edit')">Edit Contact</DropdownItem>-->
                                <!-- <DropdownItem as="button" @click="deleteRow()">{{ $t('Delete') }}</DropdownItem>-->
                                <DropdownItem as="button" @click="handleContact()">{{ $t('Send Template') }}</DropdownItem>
                                <DropdownItem as="button" @click="handelChat(contact.uuid)">{{ $t('show Chat') }}</DropdownItem>


                            </DropdownItemGroup>
                        </template>
                    </Dropdown>
                </div>
            </div>
        </div>
        <div class=" border-gray-300 px-5 border-b py-4">
            <div class="grid grid-cols-2  text-[14px]">
                <div class="text-start text-slate-500 pb-2">
                    <span>{{ $t('Full name') }}</span>
                </div>
                <div>
                    <span>{{ contact.full_name }}</span>
                </div>
                <div class="text-start text-slate-500 pb-2">
                    <span>{{ $t('Group') }}</span>
                </div>
                <div>
                    <span class="p-1 bg-gray-50 text-xs rounded-lg text-gray-600">{{ contact.contact_group?.name ??$t('not set') }}</span>
                </div>
                <div class="text-start text-slate-500 pb-2">
                    <span>{{ $t('Email') }}</span>
                </div>
                <div>
                    <span>{{ contact.email ?? $t('not set') }}</span>
                </div>
                <div class="text-start text-slate-500 pb-2">
                    <span>{{ $t('Contact') }}</span>
                </div>
                <div>
                    <span>{{ contact.formatted_phone_number }}</span>
                </div>
            </div>
        </div>
        <button class="px-5 border-gray-300 border-b py-4 flex justify-between block w-full hover:opacity-50 cursor-pointer transition-all" @click="handleContactMedia">
           <div class="flex items-center gap-2">
         <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"><path fill="currentColor" d="M4 20q-.825 0-1.412-.587T2 18V6q0-.825.588-1.412T4 4h5.175q.4 0 .763.15t.637.425L12 6h9q.425 0 .713.288T22 7t-.288.713T21 8h-9.825l-2-2H4v12l1.975-6.575q.2-.65.738-1.037T7.9 10h12.9q1.025 0 1.613.813t.312 1.762l-1.8 6q-.2.65-.737 1.038T19 20zm2.1-2H19l1.8-6H7.9zM4 11.45V6zM6.1 18l1.8-6z"/></svg>
            <p class="text-sm">{{ $t('Media (Images, Audios , Videos)') }}</p>
            </div>
            <svg class="text-slate-500" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 48 48"><path fill="currentColor" d="M12.25 8.5a3.75 3.75 0 0 0-3.75 3.75v23.5a3.75 3.75 0 0 0 3.75 3.75h23.5a3.75 3.75 0 0 0 3.75-3.75v-8.5a1.25 1.25 0 0 1 2.5 0v8.5A6.25 6.25 0 0 1 35.75 42h-23.5A6.25 6.25 0 0 1 6 35.75v-23.5A6.25 6.25 0 0 1 12.25 6h8.5a1.25 1.25 0 0 1 0 2.5zM27 7.25c0-.69.56-1.25 1.25-1.25h12.5c.69 0 1.25.56 1.25 1.25v12.5a1.25 1.25 0 1 1-2.5 0v-9.482L29.134 20.634a1.25 1.25 0 0 1-1.768-1.768L37.732 8.5H28.25c-.69 0-1.25-.56-1.25-1.25"/></svg>
        </button>
        <div v-if="locationSettings === 'before' && fields.length > 0" class=" border-gray-300 border-b py-4">
            <div v-for="(input, index) in props.fields" class="grid grid-cols-2 space-x-8 text-[14px]">
                <div class="text-start text-slate-500 pb-2">
                    <span>{{ $t(input.name) }}</span>
                </div>
                <div>
                    <span v-if="metadata && metadata[input.name] != null">{{ metadata[input.name] }}</span>
                    <span v-else class="p-1 bg-slate-100 text-xs rounded-md text-gray-600">{{ $t('not set') }}</span>
                </div>
            </div>
        </div>
        <div class="px-5 py-4">
            <div class="grid grid-cols-2 text-[14px]">
                <div class="text-start text-slate-500 pb-2">
                    <span>{{ $t('Street') }}</span>
                </div>
                <div>
                    <span>{{ getAddressDetail(contact.address, 'street') }}</span>
                </div>
                <div class="text-start text-slate-500 pb-2">
                    <span>{{ $t('City') }}</span>
                </div>
                <div>
                    <span>{{ getAddressDetail(contact.address, 'city') }}</span>
                </div>
                <div class="text-start text-slate-500 pb-2">
                    <span>{{ $t('State') }}</span>
                </div>
                <div>
                    <span>{{ getAddressDetail(contact.address, 'state') }}</span>
                </div>
                <div class="text-start text-slate-500 pb-2">
                    <span>{{ $t('Zip code') }}</span>
                </div>
                <div>
                    <span>{{ getAddressDetail(contact.address, 'zip') }}</span>
                </div>
                <div class="text-start text-slate-500 pb-2">
                    <span>{{ $t('Country') }}</span>
                </div>
                <div>
                    <span>{{ getAddressDetail(contact.address, 'country') }}</span>
                </div>
            </div>
        </div>
        <div v-if="locationSettings === 'after' && fields.length > 0" class=" border-gray-300 border-t py-4">
            <div v-for="(input, index) in props.fields" class="grid grid-cols-2 space-x-8 text-[14px]">
                <div class="text-start text-slate-500 pb-2">
                    <span>{{ $t(input.name) }}</span>
                </div>
                <div>
                    <span v-if="metadata && metadata[input.name] != null">{{ metadata[input.name] }}</span>
                    <span v-else class="p-1 bg-slate-100 text-xs rounded-md text-gray-600">{{ $t('not set') }}</span>
                </div>
            </div>
        </div>
        <div v-if="contact.tags.length" class=" border-gray-300 border-t py-4 ">
            <div class="flex gap-2 text-slate-500 text-sm">
                <p> {{ $t('Tags') }}</p>
                <div class="flex-1 flex gap-1">
                <div v-for="(item, index) in contact.tags" class="py-1 px-2 border-primary border text-primary rounded-full w-fit bg-primary-light-2 text-xs">
                    {{ item.tag }}
                </div>
                </div>
            </div>
        </div>
    </div>
    <MediaContactsModal v-model="showMediaModal"  :data="media" @onClose="showMediaModal = false" @on-change="e => getMedia(e)" />
</template>