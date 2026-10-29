<<script setup>
    import axios from 'axios';
    import { ref, watch } from 'vue';
    import debounce from 'lodash/debounce';
    import { onMounted } from 'vue';
    import { Link, router } from "@inertiajs/vue3";
    import Pagination from '@/Components/Pagination.vue';
    import TicketStatusToggle from '@/Components/TicketStatusToggle.vue';
    import SortDirectionToggle from '@/Components/SortDirectionToggle.vue';
    import ChannelToggle from '@/Components/ChannelToggle.vue';
     
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
        },
        channel:{
            type:String,
        },
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
    const handleContactId=(id)=>{
        localStorage.setItem('contact_id', id);
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
    const handleContact = () => {
        localStorage.setItem('contact_id', '');
        router.visit('/template/message/chat', {
            method: 'get',
            data: '',
        })
    } 

    const getContactDisplayName = (metadata) => {
    const parsedMetadata = JSON.parse(metadata);
    
    // Check if the metadata is in the second format
    if (parsedMetadata.from) {
        return parsedMetadata.text?.body; // Extract the text body directly
    }

    // Otherwise, handle the first format
    const contacts = parsedMetadata.contacts;
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

onMounted(() => {
    console.log(router.page?.props?.contact?.uuid)
    if(router.page?.props?.contact?.uuid == undefined){
        localStorage.setItem('contact_id', '');
    }
   
    });
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
        search: props.filters.search,
        status : props.filters.status,
    });

    const search = debounce(() => {
        isSearching.value = true;
        runParams();
    }, 1000);

    const readUnreadToggle = () => {
        params.value.status = props.filters.status === 'unread' ? '' : 'unread';
        runParams();
    }
    const runParams = () => {
        const url = window.location.pathname;

        router.visit(url, {
            method: 'get',
            data: params.value,
        })
    }

    const clearSearch = () => {
        params.value.search = null;
        runParams();
    }

</script>
<template>
    <div class="px-4 py-4 border-b">
        <div class="flex items-center justify-between space-x-1 text-xl">
            <div class="flex space-x-1">
                <h2>{{ $t('Chats') }}</h2>
                <span class="text-slate-500">{{ rowCount }}</span>
            </div>
        </div>
        <div class="bg-slate-50 rounded-md mt-3 flex items-center py-[2px]">
            <div class="pl-3 py-2">
                <svg class="text-slate-600" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10a7 7 0 1 0 14 0a7 7 0 1 0-14 0m18 11l-6-6"/></svg>
            </div>
            <input @input="search" v-model="params.search" class="w-full bg-slate-50 outline-none rounded-xl py-2 pl-2 mr-2 text-sm" type="text" :placeholder="$t('Search name or number...')">
            <button v-if="isSearching === false && params.search" @click="clearSearch" type="button" class="pr-2">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path fill="currentColor" d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10s10-4.5 10-10S17.5 2 12 2zm3.7 12.3c.4.4.4 1 0 1.4c-.4.4-1 .4-1.4 0L12 13.4l-2.3 2.3c-.4.4-1 .4-1.4 0c-.4-.4-.4-1 0-1.4l2.3-2.3l-2.3-2.3c-.4-.4-.4-1 0-1.4c.4-.4 1-.4 1.4 0l2.3 2.3l2.3-2.3c.4-.4 1-.4 1.4 0c.4.4.4 1 0 1.4L13.4 12l2.3 2.3z"/></svg>
            </button>
            <span v-if="isSearching" class="pr-2">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><circle cx="12" cy="3.5" r="1.5" fill="currentColor" opacity="0"><animateTransform attributeName="transform" calcMode="discrete" dur="2.4s" repeatCount="indefinite" type="rotate" values="0 12 12;90 12 12;180 12 12;270 12 12"/><animate attributeName="opacity" dur="0.6s" keyTimes="0;0.5;1" repeatCount="indefinite" values="1;1;0"/></circle><circle cx="12" cy="3.5" r="1.5" fill="currentColor" opacity="0"><animateTransform attributeName="transform" begin="0.2s" calcMode="discrete" dur="2.4s" repeatCount="indefinite" type="rotate" values="30 12 12;120 12 12;210 12 12;300 12 12"/><animate attributeName="opacity" begin="0.2s" dur="0.6s" keyTimes="0;0.5;1" repeatCount="indefinite" values="1;1;0"/></circle><circle cx="12" cy="3.5" r="1.5" fill="currentColor" opacity="0"><animateTransform attributeName="transform" begin="0.4s" calcMode="discrete" dur="2.4s" repeatCount="indefinite" type="rotate" values="60 12 12;150 12 12;240 12 12;330 12 12"/><animate attributeName="opacity" begin="0.4s" dur="0.6s" keyTimes="0;0.5;1" repeatCount="indefinite" values="1;1;0"/></circle></svg>
            </span>
        </div>
        <div  class="grid grid-cols-2 mt-4 items-center w-full">
            <div class="flex ml-auto gap-x-4 z-100">
                <TicketStatusToggle :status="status" :rowCount="rowCount"/>
                <ChannelToggle :channel="filters.channel"  :ChannelsCount="rows.data.length"/>
                
            </div>
            <div class="flex ml-auto gap-x-1">
                <!-- <span class="cursor-pointer hover:bg-slate-50 p-1 rounded-full">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 5.6c0-.56 0-.84-.11-1.054a.998.998 0 0 0-.436-.437C19.24 4 18.96 4 18.4 4H5.6c-.56 0-.84 0-1.054.109a1 1 0 0 0-.437.437C4 4.76 4 5.04 4 5.6v.737c0 .245 0 .367.028.482a1 1 0 0 0 .12.29c.061.1.148.187.32.36l5.063 5.062c.173.173.26.26.321.36c.055.09.096.188.12.29c.028.114.028.235.028.474v4.756c0 .857 0 1.286.18 1.544a1 1 0 0 0 .674.416c.311.046.695-.145 1.461-.529l.8-.4c.322-.16.482-.24.599-.36a1 1 0 0 0 .231-.374c.055-.158.055-.338.055-.697v-4.348c0-.245 0-.367.028-.482a.998.998 0 0 1 .12-.29c.06-.1.147-.186.317-.356l.004-.004l5.063-5.062c.172-.173.258-.26.32-.36a.994.994 0 0 0 .12-.29C20 6.706 20 6.584 20 6.345z"/></svg>
                </span> -->
                <SortDirectionToggle :direction="props.chatSortDirection" :url="'/chats/update-sort-direction'"/>
            </div>
        </div>
    </div>
    <div class="flex justify-end p-2">
        <button @click="handleContact" class="bg-primary rounded-3xl mx-2 text-xs text-white p-2 px-4 text-center">{{ $t('Send Template') }}</button>
        <button :class="!props.filters.status ? 'bg-primaryLight !border-primary' : ''" class=" w-fit rounded-full bg-primary px-3 py-1 text-xs  border-transparent text-white shadow-sm focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 hover:opacity-70" @click="readUnreadToggle" type="button" >
                {{ props.filters.status ? 'All' : 'Unread' }}
    </button>
    </div>
    <div class="flex-grow overflow-y-auto h-[65vh]" ref="scrollContainer">
        <Link @click="handleContactId(contact.uuid)" :href="'/chats/' + contact.uuid + '?page=' + props.rows.meta.current_page" class="block border-b group-hover:pr-0" :class="contact.unread_messages > 0 ? 'bg-green-50' : ''" v-for="(contact, index) in rows.data" :key="index">
            <div class="flex space-x-2 hover:bg-gray-50 cursor-pointer py-5 px-4">
                <div class="w-[15%] relative">
                    <div class="relative">
                        <div v-if="rows.data[index].last_chat.channel =='instagram'" class="absolute bottom-0 left-0">
                           <svg width="10px" height="10px" viewBox="0 0 3364.7 3364.7" xmlns="http://www.w3.org/2000/svg" fill="#000000"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"><defs><radialGradient id="0" cx="217.76" cy="3290.99" r="4271.92" gradientUnits="userSpaceOnUse"><stop offset=".09" stop-color="#fa8f21"></stop><stop offset=".78" stop-color="#d82d7e"></stop></radialGradient><radialGradient id="1" cx="2330.61" cy="3182.95" r="3759.33" gradientUnits="userSpaceOnUse"><stop offset=".64" stop-color="#8c3aaa" stop-opacity="0"></stop><stop offset="1" stop-color="#8c3aaa"></stop></radialGradient></defs><path d="M853.2,3352.8c-200.1-9.1-308.8-42.4-381.1-70.6-95.8-37.3-164.1-81.7-236-153.5S119.7,2988.6,82.6,2892.8c-28.2-72.3-61.5-181-70.6-381.1C2,2295.4,0,2230.5,0,1682.5s2.2-612.8,11.9-829.3C21,653.1,54.5,544.6,82.5,472.1,119.8,376.3,164.3,308,236,236c71.8-71.8,140.1-116.4,236-153.5C544.3,54.3,653,21,853.1,11.9,1069.5,2,1134.5,0,1682.3,0c548,0,612.8,2.2,829.3,11.9,200.1,9.1,308.6,42.6,381.1,70.6,95.8,37.1,164.1,81.7,236,153.5s116.2,140.2,153.5,236c28.2,72.3,61.5,181,70.6,381.1,9.9,216.5,11.9,281.3,11.9,829.3,0,547.8-2,612.8-11.9,829.3-9.1,200.1-42.6,308.8-70.6,381.1-37.3,95.8-81.7,164.1-153.5,235.9s-140.2,116.2-236,153.5c-72.3,28.2-181,61.5-381.1,70.6-216.3,9.9-281.3,11.9-829.3,11.9-547.8,0-612.8-1.9-829.1-11.9" fill="url(#0)"></path><path d="M853.2,3352.8c-200.1-9.1-308.8-42.4-381.1-70.6-95.8-37.3-164.1-81.7-236-153.5S119.7,2988.6,82.6,2892.8c-28.2-72.3-61.5-181-70.6-381.1C2,2295.4,0,2230.5,0,1682.5s2.2-612.8,11.9-829.3C21,653.1,54.5,544.6,82.5,472.1,119.8,376.3,164.3,308,236,236c71.8-71.8,140.1-116.4,236-153.5C544.3,54.3,653,21,853.1,11.9,1069.5,2,1134.5,0,1682.3,0c548,0,612.8,2.2,829.3,11.9,200.1,9.1,308.6,42.6,381.1,70.6,95.8,37.1,164.1,81.7,236,153.5s116.2,140.2,153.5,236c28.2,72.3,61.5,181,70.6,381.1,9.9,216.5,11.9,281.3,11.9,829.3,0,547.8-2,612.8-11.9,829.3-9.1,200.1-42.6,308.8-70.6,381.1-37.3,95.8-81.7,164.1-153.5,235.9s-140.2,116.2-236,153.5c-72.3,28.2-181,61.5-381.1,70.6-216.3,9.9-281.3,11.9-829.3,11.9-547.8,0-612.8-1.9-829.1-11.9" fill="url(#1)"></path><path d="M1269.25,1689.52c0-230.11,186.49-416.7,416.6-416.7s416.7,186.59,416.7,416.7-186.59,416.7-416.7,416.7-416.6-186.59-416.6-416.7m-225.26,0c0,354.5,287.36,641.86,641.86,641.86s641.86-287.36,641.86-641.86-287.36-641.86-641.86-641.86S1044,1335,1044,1689.52m1159.13-667.31a150,150,0,1,0,150.06-149.94h-0.06a150.07,150.07,0,0,0-150,149.94M1180.85,2707c-121.87-5.55-188.11-25.85-232.13-43-58.36-22.72-100-49.78-143.78-93.5s-70.88-85.32-93.5-143.68c-17.16-44-37.46-110.26-43-232.13-6.06-131.76-7.27-171.34-7.27-505.15s1.31-373.28,7.27-505.15c5.55-121.87,26-188,43-232.13,22.72-58.36,49.78-100,93.5-143.78s85.32-70.88,143.78-93.5c44-17.16,110.26-37.46,232.13-43,131.76-6.06,171.34-7.27,505-7.27S2059.13,666,2191,672c121.87,5.55,188,26,232.13,43,58.36,22.62,100,49.78,143.78,93.5s70.78,85.42,93.5,143.78c17.16,44,37.46,110.26,43,232.13,6.06,131.87,7.27,171.34,7.27,505.15s-1.21,373.28-7.27,505.15c-5.55,121.87-25.95,188.11-43,232.13-22.72,58.36-49.78,100-93.5,143.68s-85.42,70.78-143.78,93.5c-44,17.16-110.26,37.46-232.13,43-131.76,6.06-171.34,7.27-505.15,7.27s-373.28-1.21-505-7.27M1170.5,447.09c-133.07,6.06-224,27.16-303.41,58.06-82.19,31.91-151.86,74.72-221.43,144.18S533.39,788.47,501.48,870.76c-30.9,79.46-52,170.34-58.06,303.41-6.16,133.28-7.57,175.89-7.57,515.35s1.41,382.07,7.57,515.35c6.06,133.08,27.16,223.95,58.06,303.41,31.91,82.19,74.62,152,144.18,221.43s139.14,112.18,221.43,144.18c79.56,30.9,170.34,52,303.41,58.06,133.35,6.06,175.89,7.57,515.35,7.57s382.07-1.41,515.35-7.57c133.08-6.06,223.95-27.16,303.41-58.06,82.19-32,151.86-74.72,221.43-144.18s112.18-139.24,144.18-221.43c30.9-79.46,52.1-170.34,58.06-303.41,6.06-133.38,7.47-175.89,7.47-515.35s-1.41-382.07-7.47-515.35c-6.06-133.08-27.16-224-58.06-303.41-32-82.19-74.72-151.86-144.18-221.43S2586.8,537.06,2504.71,505.15c-79.56-30.9-170.44-52.1-303.41-58.06C2068,441,2025.41,439.52,1686,439.52s-382.1,1.41-515.45,7.57" fill="#ffffff"></path></g></svg>
                        </div>
                        <div v-if="rows.data[index].last_chat.channel =='whatsapp'"  class="absolute bottom-0 left-0">
                              <svg width="10px" height="10px" viewBox="0 0 48 48" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" fill="#000000"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <title>Whatsapp-color</title> <desc>Created with Sketch.</desc> <defs> </defs> <g id="Icons" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd"> <g id="Color-" transform="translate(-700.000000, -360.000000)" fill="#67C15E"> <path d="M723.993033,360 C710.762252,360 700,370.765287 700,383.999801 C700,389.248451 701.692661,394.116025 704.570026,398.066947 L701.579605,406.983798 L710.804449,404.035539 C714.598605,406.546975 719.126434,408 724.006967,408 C737.237748,408 748,397.234315 748,384.000199 C748,370.765685 737.237748,360.000398 724.006967,360.000398 L723.993033,360.000398 L723.993033,360 Z M717.29285,372.190836 C716.827488,371.07628 716.474784,371.034071 715.769774,371.005401 C715.529728,370.991464 715.262214,370.977527 714.96564,370.977527 C714.04845,370.977527 713.089462,371.245514 712.511043,371.838033 C711.806033,372.557577 710.056843,374.23638 710.056843,377.679202 C710.056843,381.122023 712.567571,384.451756 712.905944,384.917648 C713.258648,385.382743 717.800808,392.55031 724.853297,395.471492 C730.368379,397.757149 732.00491,397.545307 733.260074,397.27732 C735.093658,396.882308 737.393002,395.527239 737.971421,393.891043 C738.54984,392.25405 738.54984,390.857171 738.380255,390.560912 C738.211068,390.264652 737.745308,390.095816 737.040298,389.742615 C736.335288,389.389811 732.90737,387.696673 732.25849,387.470894 C731.623543,387.231179 731.017259,387.315995 730.537963,387.99333 C729.860819,388.938653 729.198006,389.89831 728.661785,390.476494 C728.238619,390.928051 727.547144,390.984595 726.969123,390.744481 C726.193254,390.420348 724.021298,389.657798 721.340985,387.273388 C719.267356,385.42535 717.856938,383.125756 717.448104,382.434484 C717.038871,381.729275 717.405907,381.319529 717.729948,380.938852 C718.082653,380.501232 718.421026,380.191036 718.77373,379.781688 C719.126434,379.372738 719.323884,379.160897 719.549599,378.681068 C719.789645,378.215575 719.62006,377.735746 719.450874,377.382942 C719.281687,377.030139 717.871269,373.587317 717.29285,372.190836 Z" id="Whatsapp"> </path> </g> </g> </g></svg>
                        </div>
                        <img v-if="contact.avatar" class="rounded-full w-10 h-10" :src="contact.avatar">
                        <div v-else class="rounded-full w-10 h-10 flex items-center justify-center bg-slate-200 capitalize">{{ contact.full_name.substring(0, 1) }}</div>
                    </div>
                    <div class="flex items-center space-x-2 relative  " >
                        <div class="absolute" v-if="contact.platform === 'whatsapp'">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512" class="  w-4 h-4 text-green-500  ">
                                <path d="M380.9 97.1C339 55.1 283.2 32 223.9 32c-122.4 0-222 99.6-222 222 0 39.1 10.2 77.3 29.6 111L0 480l117.7-30.9c32.4 17.7 68.9 27 106.1 27h.1c122.3 0 224.1-99.6 224.1-222 0-59.3-25.2-115-67.1-157zm-157 341.6c-33.2 0-65.7-8.9-94-25.7l-6.7-4-69.8 18.3L72 359.2l-4.4-7c-18.5-29.4-28.2-63.3-28.2-98.2 0-101.7 82.8-184.5 184.6-184.5 49.3 0 95.6 19.2 130.4 54.1 34.8 34.9 56.2 81.2 56.1 130.5 0 101.8-84.9 184.6-186.6 184.6zm101.2-138.2c-5.5-2.8-32.8-16.2-37.9-18-5.1-1.9-8.8-2.8-12.5 2.8-3.7 5.6-14.3 18-17.6 21.8-3.2 3.7-6.5 4.2-12 1.4-32.6-16.3-54-29.1-75.5-66-5.7-9.8 5.7-9.1 16.3-30.3 1.8-3.7 .9-6.9-.5-9.7-1.4-2.8-12.5-30.1-17.1-41.2-4.5-10.8-9.1-9.3-12.5-9.5-3.2-.2-6.9-.2-10.6-.2-3.7 0-9.7 1.4-14.8 6.9-5.1 5.6-19.4 19-19.4 46.3 0 27.3 19.9 53.7 22.6 57.4 2.8 3.7 39.1 59.7 94.8 83.8 35.2 15.2 49 16.5 66.6 13.9 10.7-1.6 32.8-13.4 37.4-26.4 4.6-13 4.6-24.1 3.2-26.4-1.3-2.5-5-3.9-10.5-6.6z"/>
                            </svg>
                        </div>
                        <div class="absolute" v-else-if="contact.platform === 'messenger'" >
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" class="  w-4 h-4 text-green-500 ">
                                <path d="M256.6 8C116.5 8 8 110.3 8 248.6c0 72.3 29.7 134.8 78.1 177.9 8.4 7.5 6.6 11.9 8.1 58.2A19.9 19.9 0 0 0 122 502.3c52.9-23.3 53.6-25.1 62.6-22.7C337.9 521.8 504 423.7 504 248.6 504 110.3 396.6 8 256.6 8zm149.2 185.1l-73 115.6a37.4 37.4 0 0 1 -53.9 9.9l-58.1-43.5a15 15 0 0 0 -18 0l-78.4 59.4c-10.5 7.9-24.2-4.6-17.1-15.7l73-115.6a37.4 37.4 0 0 1 53.9-9.9l58.1 43.5a15 15 0 0 0 18 0l78.4-59.4c10.4-8 24.1 4.5 17.1 15.6z"/>
                            </svg>
                        </div>
                    <div>  
                    </div>
                </div>
            </div>
                <div class="w-[85%]">
                    <div class="flex justify-between">
                        <h3 class="truncate">{{ contact.full_name }}</h3>
                        <span class="self-center text-slate-500 text-xs">{{ formatTime(contact.last_chat.created_at) }}</span>
                    </div>
                    <div v-if="contact.last_chat.deleted_at === null" class="flex justify-between">
                        <p v-if="contentType(contact.last_chat.metadata) ==='text'" class="text-slate-500 text-xs truncate self-end"> {{ content(contact.last_chat.metadata).text?.body }}</p>
                        <p v-if="contentType(contact.last_chat.metadata) ==='button'" class="text-slate-500 text-xs truncate self-end"> {{ content(contact.last_chat.metadata).button.text }}</p>
                        <p v-if="contentType(contact.last_chat.metadata) ==='interactive'" class="text-slate-500 text-xs truncate self-end"> {{ content(contact.last_chat.metadata).interactive.button_reply.title }}</p>
                        <p v-if="contentType(contact.last_chat.metadata) ==='image'" class="text-slate-500 text-xs truncate self-end"> 
                            <div class="flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"><path fill="currentColor" d="M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.9 13.98l2.1 2.53l3.1-3.99c.2-.26.6-.26.8.01l3.51 4.68a.5.5 0 0 1-.4.8H6.02c-.42 0-.65-.48-.39-.81L8.12 14c.19-.26.57-.27.78-.02z"/></svg>
                                <span class="ml-2">{{ $t('Photo') }}</span>
                            </div>
                        </p>
                        <p v-if="contentType(contact.last_chat.metadata) ==='document'" class="text-slate-500 text-xs truncate self-end"> 
                            <div class="flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"><path fill="currentColor" fill-rule="evenodd" d="M14.25 2.5a.25.25 0 0 0-.25-.25H7A2.75 2.75 0 0 0 4.25 5v14A2.75 2.75 0 0 0 7 21.75h10A2.75 2.75 0 0 0 19.75 19V9.147a.25.25 0 0 0-.25-.25H15a.75.75 0 0 1-.75-.75V2.5Zm.75 9.75a.75.75 0 0 1 0 1.5H9a.75.75 0 0 1 0-1.5h6Zm0 4a.75.75 0 0 1 0 1.5H9a.75.75 0 0 1 0-1.5h6Z" clip-rule="evenodd"/><path fill="currentColor" d="M15.75 2.824c0-.184.193-.301.336-.186c.121.098.23.212.323.342l3.013 4.197c.068.096-.006.22-.124.22H16a.25.25 0 0 1-.25-.25V2.824Z"/></svg>
                                <span class="ml-2">{{ getExtension(contact.last_chat.media.type) }} {{ $t('File') }}</span>
                            </div>
                        </p>
                        <p v-if="contentType(contact.last_chat.metadata) ==='video'" class="text-slate-500 text-xs truncate self-end"> 
                            <div class="flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16"><path fill="currentColor" d="M3.5 2.5A2.5 2.5 0 0 0 1 5v6a2.5 2.5 0 0 0 2.5 2.5h5A2.5 2.5 0 0 0 11 11V5a2.5 2.5 0 0 0-2.5-2.5h-5Zm10.684 1.61L12 5.893v4.215l2.184 1.78a.5.5 0 0 0 .816-.389v-7a.5.5 0 0 0-.816-.387Z"/></svg>
                                <span class="ml-2">{{ $t('Video') }}</span>
                            </div>
                        </p>
                        <p v-if="contentType(contact.last_chat.metadata) ==='audio'" class="text-slate-500 text-xs truncate self-end"> 
                            <div class="flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 512 512"><path fill="currentColor" d="M256 80C149.9 80 62.4 159.4 49.6 262c9.4-3.8 19.6-6 30.4-6c26.5 0 48 21.5 48 48v128c0 26.5-21.5 48-48 48c-44.2 0-80-35.8-80-80V288C0 146.6 114.6 32 256 32s256 114.6 256 256v112c0 44.2-35.8 80-80 80c-26.5 0-48-21.5-48-48V304c0-26.5 21.5-48 48-48c10.8 0 21 2.1 30.4 6C449.6 159.4 362.1 80 256 80z"/></svg>
                                <span class="ml-2">{{ $t('Audio') }}</span>
                            </div>
                        </p>
                        <p v-if="contentType(contact.last_chat.metadata) ==='sticker'" class="text-slate-500 text-xs truncate self-end"> 
                            <div class="flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 256 256"><path fill="currentColor" d="M168 32H88a56.06 56.06 0 0 0-56 56v80a56.06 56.06 0 0 0 56 56h48a8.07 8.07 0 0 0 2.53-.41c26.23-8.75 76.31-58.83 85.06-85.06A8.07 8.07 0 0 0 224 136V88a56.06 56.06 0 0 0-56-56Zm-32 175.42V176a40 40 0 0 1 40-40h31.42c-9.26 21.55-49.87 62.16-71.42 71.42Z"/></svg>
                                <span class="ml-2">{{ $t('Sticker') }}</span>
                            </div>
                        </p>
                        <p v-if="contentType(contact.last_chat.metadata) ==='contacts'" class="text-slate-500 text-xs truncate self-end"> 
                            <div class="flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16"><path fill="currentColor" d="M3 14s-1 0-1-1s1-4 6-4s6 3 6 4s-1 1-1 1H3Zm5-6a3 3 0 1 0 0-6a3 3 0 0 0 0 6Z"/></svg>
                                <span class="ml-2">
                                    {{ getContactDisplayName(contact.last_chat.metadata) }}
                                </span>
                            </div>
                        </p>
                        <p v-if="contentType(contact.last_chat.metadata) ==='location'" class="text-slate-500 text-xs truncate self-end"> 
                            <div class="flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16"><path fill="currentColor" d="M9.156 14.544C10.899 13.01 14 9.876 14 7A6 6 0 0 0 2 7c0 2.876 3.1 6.01 4.844 7.544a1.736 1.736 0 0 0 2.312 0ZM6 7a2 2 0 1 1 4 0a2 2 0 0 1-4 0Z"/></svg>
                                <span class="ml-2">{{ $t('Location') }}</span>
                            </div>
                        </p>
                        <span v-if="contact.unread_messages > 0" class="bg-green-600 text-white rounded-md py-[1px] px-[8px] min-w-10 text-[10px] flex items-center justify-center">{{ contact.unread_messages }}</span>
                    </div>
                </div>
            </div>
        </Link>
    </div>
    <div class="px-4 pb-4">
        <Pagination class="mt-3" :pagination="rows.meta"/>
    </div>
</template>
  >