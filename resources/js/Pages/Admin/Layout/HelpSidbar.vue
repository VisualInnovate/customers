<template>
    <aside class="md:flex flex-col w-[25%] h-full bg-white border-r border-l hidden">
        <div class="flex-grow space-y-3 px-2 overflow-y-auto">
            <div class="flex-1">
                <!-- <div>
                    <p>{{ $page.url }}</p> 
                </div> -->
                <h2 class="px-4 pt-10 text-xl">{{ $t('KnowLedge Base') }}</h2>
                <ul class="pt-5 pb-4 space-y-1 text-sm text-gray-700">
                    <li class="hover:bg-primary hover:text-white rounded-[5px] px-2 truncate" :class="$page.url.startsWith('/admin/knowledge/categories') ? 'bg-primary text-white' : ''">
                        <Link rel="noopener noreferrer" href="/admin/knowledge/categories" class="flex items-center p-2 space-x-3 rounded-md">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24">
                                <path fill="currentColor" d="M10 4L2 8v12h20V8l-8-4h-4zm8 14H4V9.236L12 5.5l8 3.736V18z"/>
                            </svg>
                            <span>{{ $t('Categories') }}</span>
                        </Link>
                    </li>
                    <li class="hover:bg-primary hover:text-white rounded-[5px] px-2 truncate" :class="$page.url.startsWith('/admin/knowledge/sub-categories') ? 'bg-primary text-white' : ''">
                        <Link rel="noopener noreferrer" href="/admin/knowledge/sub-categories" class="flex items-center p-2 space-x-3 rounded-md">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24">
                                <path fill="currentColor" d="M20 14h-6v-4h6v4zm0-6h-6V4h6v4zm-8 6H4v-4h8v4zm0-6H4V4h8v4zm-8 6H4v4h8v-4z"/>
                            </svg>
                            <span>{{ $t('SubCategories') }}</span>
                        </Link>
                    </li>
                    <li class="hover:bg-primary hover:text-white rounded-[5px] px-2 truncate" :class="$page.url.startsWith('/admin/knowledge/articles') ? 'bg-primary text-white' : ''">
                        <Link rel="noopener noreferrer" href="/admin/knowledge/articles" class="flex items-center p-2 space-x-3 rounded-md">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24">
                                <path fill="currentColor" d="M4 3h12l4 4v14a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2zm0 2v14h16V9h-5a1 1 0 0 1-1-1V3H4zm4 5h8v2H8V8zm0 4h8v2H8v-2zm0 4h6v2H8v-2z"/>
                            </svg>
                            <span>{{ $t('Articles') }}</span>
                        </Link>
                    </li>
                </ul>
            </div>
        </div>
        <div class="mx-4 my-2 px-4 py-2 border rounded-md">
            <span class="text-sm font-light text-gray-500">v2.1 • Released Sept 11, 2024</span>
        </div>
    </aside>
</template>
<script setup>
    import { Link } from "@inertiajs/vue3";
    import { ref } from "vue";
    
</script>
