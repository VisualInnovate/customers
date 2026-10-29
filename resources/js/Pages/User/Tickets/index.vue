<template>
    <AppLayout>
        <div class="bg-white md:bg-inherit pt-0 px-4 md:p-8 rounded-[5px] text-[#000] overflow-y-scroll capitalize">
            <div class="flex justify-between mt-8 md:mt-0">
                <div>
                    <h2 class="text-xl mb-1">{{ $t('Tickets') }}</h2>
                    <div class="flex">
                        <p class="mb-6 flex items-center text-sm leading-6 text-gray-600">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11v5m0 5a9 9 0 1 1 0-18a9 9 0 0 1 0 18Zm.05-13v.1h-.1V8h.1Z"/></svg>
                            <span class="ml-1 mt-1">{{ $t('Add Tickets') }}</span>
                        </p>
                        <div class="mx-2">
                        <Link href="/tickets/resolved" class="rounded-md bg-primary px-3 py-2 text-sm text-white shadow-sm hover:bg-primaryLight focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">{{ $t('Resolved') }}</Link>
                      </div>
                    </div>
                </div>
                <div class="my-auto">
                    <Link href="/tickets/create" class="rounded-md bg-primary px-3 py-2 text-sm text-white shadow-sm hover:bg-primaryLight focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">{{ $t('Create Ticket') }}</Link>
                </div>
            </div>
           
            <!-- Table Component-->
            <TicketTable :rows="props.tickets"/>
        </div>
    </AppLayout>
</template>
<script setup>
    import AppLayout from "./../Layout/App.vue";
    import { router } from '@inertiajs/vue3';
    import { Link } from "@inertiajs/vue3";
    import TicketTable from '@/Components/Tables/TicketTable.vue';
    import axios from 'axios';
    import { ref } from 'vue';

    const props = defineProps({ rows: Object,tickets:Array });
    const isSyncActive = ref(false);
    const syncTemplates = () => {
        isSyncActive.value = true;
        axios.get('/templates/sync')
        .then(function (response) {
            router.reload();
            setTimeout(() => {
                isSyncActive.value = false;
            }, 1500);
        })
        .catch(function (error) {
            isSyncActive.value = false;
        });
    }
</script>