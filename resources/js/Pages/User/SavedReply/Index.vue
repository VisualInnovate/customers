<template>
    <AppLayout>
        <div class="bg-white md:bg-inherit pt-0 px-4 md:pt-8 md:p-8 rounded-[5px] text-[#000] overflow-y-scroll capitalize h-full">
            <div class="flex justify-between flex-wrap mt-8 md:mt-0">
                <div>
                    <h2 class="text-xl mb-1">{{ $t('Saved Replies') }}</h2>
                    <p class="mb-6 flex items-center text-sm gap-2  text-gray-600">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11v5m0 5a9 9 0 1 1 0-18a9 9 0 0 1 0 18Zm.05-13v.1h-.1V8h.1Z"/></svg>
                        <span >{{ $t('Effortlessly create, manage, and access predefined responses to save time and ensure consistent messaging.') }}</span>
                    </p>
                </div>
                <div class="space-x-2 flex items-center">
                   
                    <Link href="/saved-replies/create" class="md:block hidden rounded-md bg-primary px-3 py-2 text-sm text-white shadow-sm hover:bg-primaryLight focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">{{ $t('Create Saved Reply') }}</Link>
                </div>
            </div>
         
            <!-- Table Component-->
            <SavedRepliesTable :rows="props.rows"/>
        </div>
    </AppLayout>
</template>
<script setup>
    import AppLayout from "./../Layout/App.vue";
    import {onMounted, ref} from 'vue';
    import SavedRepliesTable from "../../../Components/Tables/SavedRepliesTable.vue";
    import { Link } from "@inertiajs/vue3";
    import axios from 'axios';
    const props = defineProps({ rows: Object });


   
</script>