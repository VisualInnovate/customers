<script setup>
    import axios from 'axios';
    import { ref, watch } from 'vue';
    import debounce from 'lodash/debounce';
    import { Link, router } from "@inertiajs/vue3";
    import Pagination from '@/Components/Pagination.vue';
    import TicketStatusToggle from '@/Components/TicketStatusToggle.vue';
    import SortDirectionToggle from '@/Components/SortDirectionToggle.vue';

    import { computed } from 'vue';

    // '', '', 'Mentions'
    const items = ref([
        {
            name: 'All Conversations',
            description: 'All conversations will be listed here',
            link: '/chats',
        },
        {
            name: 'Assigned To Me',
            description: 'Conversations assigned to me by the team leader or another agent',
            link: '/chats/assigned-to-me',
        },
        {
            name: 'Mentions',
            description: 'Any conversation mentions by another agent',
            link: '/chats/mentions',
        },
    ]);

    const repeatedItems = computed(() => {
    return [...items.value]; 
    });

    const props = defineProps({
        rows: {
            type: Object,
            required: true,
        },
        filters: {
            type: Object
        },
        rowCount: {
            type: Number,
            required: true,
        },
        ticketingIsEnabled: {
            type: Boolean
        },
        status: {
            type: String,
        },
        chatSortDirection: {
            type: String
        }
    });

    const isSearching = ref(false);
    const selectedContact = ref(null);
    const emit = defineEmits(['view']);

    function viewChat(contact) {
        selectedContact.value = contact;
        emit('view', contact);
    }

    const contentType = (metadata) => {
        const chatData = JSON.parse(metadata);
        return chatData.type;
    }

    const content = (metadata) => {
        return JSON.parse(metadata);
    }

    const getExtension = (fileFormat) => {
        const formatMap = {
            'text/plain': 'TXT',
            'application/pdf': 'PDF',
            'application/vnd.ms-powerpoint': 'PPT',
            'application/msword': 'DOC',
            'application/vnd.ms-excel': 'XLS',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document': 'DOCX',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation': 'PPTX',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet': 'XLSX',
        };

        return formatMap[fileFormat] || 'Unknown';
    };

    const getContactDisplayName = (metadata) => {
        const contacts = JSON.parse(metadata).contacts;
        if (contacts.length === 1) {
            const contact = contacts[0];
            return contact.name.formatted_name || `${contact.name.first_name} ${contact.name.last_name}`;
        } else if (contacts.length > 1) {
            const firstName = contacts[0].name.first_name;
            const otherContactsCount = contacts.length - 1;
            return `${firstName} and ${otherContactsCount} other contacts`;
        } else {
            return 'No contacts available';
        }
    }

    const formatTime = (time) => {
        const currentTime = new Date();
        const targetTime = new Date(time);
        const timeDiff = currentTime - targetTime;

        // Check if the time is today
        if (
            targetTime.getDate() === currentTime.getDate() &&
            targetTime.getMonth() === currentTime.getMonth() &&
            targetTime.getFullYear() === currentTime.getFullYear()
        ) {
            return formatDate(targetTime, 'h:mm a');
        }

        // Check if the time is yesterday
        const yesterday = new Date();
        yesterday.setDate(currentTime.getDate() - 1);
        if (
            targetTime.getDate() === yesterday.getDate() &&
            targetTime.getMonth() === yesterday.getMonth() &&
            targetTime.getFullYear() === yesterday.getFullYear()
        ) {
            return 'Yesterday';
        }

        // If beyond yesterday, return the time in the format d/m/y
        return formatDate(targetTime, 'd/m/y');
    };

    const formatDate = (date, format) => {
        let options = null;
        if(format === 'h:mm a'){
            options = { hour12: true, hour: 'numeric', minute: 'numeric' };
        } else {
            options = { day: 'numeric', month: 'numeric', year: 'numeric' };
        }
        
        return new Intl.DateTimeFormat('en-US', options).format(date);
    };

    const queryParamsString = window.location.search;

    // Watch for changes in the query parameters
    watch(() => queryParamsString,
        (newQuery, oldQuery) => {
            console.log('Query parameters changed:', newQuery);
            // Perform actions based on query parameter changes
        }
    );

    const params = ref({
        search: props.filters?.search,
    });

    const search = debounce(() => {
        isSearching.value = true;
        runSearch();
    }, 1000);

    const runSearch = () => {
        const url = window.location.pathname;

        router.visit(url, {
            method: 'get',
            data: params.value,
        })
    }

    const clearSearch = () => {
        params.value.search = null;
        runSearch();
    }
</script>
<template>
    <div class="px-4 py-4 border-b">
        <div class="flex items-center justify-between space-x-1 text-xl">
            <div class="flex space-x-1">
                <h2>{{ $t('Chat Folders') }}</h2>
                <span class="text-slate-500">{{ rowCount }}</span>
            </div>
        </div>
        <div class="bg-slate-50 rounded-md mt-3 flex items-center py-[2px]">
            <div class="pl-3 py-2">
                <svg class="text-slate-600" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10a7 7 0 1 0 14 0a7 7 0 1 0-14 0m18 11l-6-6"/></svg>
            </div>
            <input @input="search" v-model="params.search" class="w-full bg-slate-50 outline-none rounded-xl py-2 pl-2 mr-2 text-sm" type="text" :placeholder="$t('Search dddd name or number...')">
            <button v-if="isSearching === false && params.search" @click="clearSearch" type="button" class="pr-2">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path fill="currentColor" d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10s10-4.5 10-10S17.5 2 12 2zm3.7 12.3c.4.4.4 1 0 1.4c-.4.4-1 .4-1.4 0L12 13.4l-2.3 2.3c-.4.4-1 .4-1.4 0c-.4-.4-.4-1 0-1.4l2.3-2.3l-2.3-2.3c-.4-.4-.4-1 0-1.4c.4-.4 1-.4 1.4 0l2.3 2.3l2.3-2.3c.4-.4 1-.4 1.4 0c.4.4.4 1 0 1.4L13.4 12l2.3 2.3z"/></svg>
            </button>
            <span v-if="isSearching" class="pr-2">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><circle cx="12" cy="3.5" r="1.5" fill="currentColor" opacity="0"><animateTransform attributeName="transform" calcMode="discrete" dur="2.4s" repeatCount="indefinite" type="rotate" values="0 12 12;90 12 12;180 12 12;270 12 12"/><animate attributeName="opacity" dur="0.6s" keyTimes="0;0.5;1" repeatCount="indefinite" values="1;1;0"/></circle><circle cx="12" cy="3.5" r="1.5" fill="currentColor" opacity="0"><animateTransform attributeName="transform" begin="0.2s" calcMode="discrete" dur="2.4s" repeatCount="indefinite" type="rotate" values="30 12 12;120 12 12;210 12 12;300 12 12"/><animate attributeName="opacity" begin="0.2s" dur="0.6s" keyTimes="0;0.5;1" repeatCount="indefinite" values="1;1;0"/></circle><circle cx="12" cy="3.5" r="1.5" fill="currentColor" opacity="0"><animateTransform attributeName="transform" begin="0.4s" calcMode="discrete" dur="2.4s" repeatCount="indefinite" type="rotate" values="60 12 12;150 12 12;240 12 12;330 12 12"/><animate attributeName="opacity" begin="0.4s" dur="0.6s" keyTimes="0;0.5;1" repeatCount="indefinite" values="1;1;0"/></circle></svg>
            </span>
        </div>
        <div v-if="ticketingIsEnabled" class="grid grid-cols-2 mt-4 items-center w-full">
            <TicketStatusToggle :status="status" :rowCount="rowCount"/>
            <div class="flex ml-auto gap-x-1">
                <!--<span class="cursor-pointer hover:bg-slate-50 p-1 rounded-full">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 5.6c0-.56 0-.84-.11-1.054a.998.998 0 0 0-.436-.437C19.24 4 18.96 4 18.4 4H5.6c-.56 0-.84 0-1.054.109a1 1 0 0 0-.437.437C4 4.76 4 5.04 4 5.6v.737c0 .245 0 .367.028.482a1 1 0 0 0 .12.29c.061.1.148.187.32.36l5.063 5.062c.173.173.26.26.321.36c.055.09.096.188.12.29c.028.114.028.235.028.474v4.756c0 .857 0 1.286.18 1.544a1 1 0 0 0 .674.416c.311.046.695-.145 1.461-.529l.8-.4c.322-.16.482-.24.599-.36a1 1 0 0 0 .231-.374c.055-.158.055-.338.055-.697v-4.348c0-.245 0-.367.028-.482a.998.998 0 0 1 .12-.29c.06-.1.147-.186.317-.356l.004-.004l5.063-5.062c.172-.173.258-.26.32-.36a.994.994 0 0 0 .12-.29C20 6.706 20 6.584 20 6.345z"/></svg>
                </span>-->
                <SortDirectionToggle :direction="props.chatSortDirection" :url="'/chats/update-sort-direction'"/>
            </div>
        </div>
    </div>
    <div class="flex-grow overflow-y-auto h-[65vh]" ref="scrollContainer">
        <Link :href="item.link" class="block border-b group-hover:pr-0" v-for="(item, index) in repeatedItems" :key="index">

            <div class="flex space-x-2 hover:bg-gray-50 cursor-pointer py-3 px-4">
                <!-- <div class="w-[15%]">
                    <img v-if="contact.avatar" class="rounded-full w-10 h-10" :src="contact.avatar">
                </div> -->
                <div class="w-[85%]">
                    <div class="flex justify-between">
                        <h3 class="truncate">{{ item.name }}</h3>
                    </div>
                    <div class="flex justify-between">
                        <p class="text-slate-500 text-xs truncate self-end"> {{ item.description }}  </p>
                    </div>
                </div>
            </div>
        </Link>
    </div>
</template>
  