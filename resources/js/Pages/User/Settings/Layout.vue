<template>
    <AppLayout>
        <div class="bg-white md:bg-inherit pt-10 px-4 md:pt-8 md:py-8 md:pl-8 md:pr-0 rounded-[5px] text-[#000] overflow-y-hidden">
            <div class="flex justify-between">
                <div>
                    <h2 class="text-xl mb-1">{{ $t('General settings') }}</h2>
                    <p class="mb-6 flex items-center text-sm leading-6">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11v5m0 5a9 9 0 1 1 0-18a9 9 0 0 1 0 18Zm.05-13v.1h-.1V8h.1Z"/></svg>
                        <span class="ml-1 mt-1">{{ $t('Update your organization settings') }}</span>
                    </p>
                </div>
            </div>
        
            <div class="flex mt-4">
                <div class="md:w-1/5 bg-white border border-slate-200 h-[fit-content] rounded-lg pb-10 md:block hidden">
                    <ul class="pt-2 space-y-1 text-sm mb-2 px-2">
                        <li class="hover:bg-slate-50 hover:text-black rounded-[5px] px-1 truncate cursor-pointer" :class="$page.url === '/settings' ? 'bg-slate-50 text-black' : ''">
                            <Link rel="noopener noreferrer" href="/settings" class="flex items-center p-2 space-x-3 rounded-md">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><g fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"><path d="M12 15a3 3 0 1 0 0-6a3 3 0 0 0 0 6"/><path d="m19.622 10.395l-1.097-2.65L20 6l-2-2l-1.735 1.483l-2.707-1.113L12.935 2h-1.954l-.632 2.401l-2.645 1.115L6 4L4 6l1.453 1.789l-1.08 2.657L2 11v2l2.401.656L5.516 16.3L4 18l2 2l1.791-1.46l2.606 1.072L11 22h2l.604-2.387l2.651-1.098C16.697 18.832 18 20 18 20l2-2l-1.484-1.75l1.098-2.652l2.386-.62V11z"/></g></svg>
                                <span>{{ $t('General settings') }}</span>
                            </Link>
                        </li>
                        <li class="hover:bg-slate-50 hover:text-black rounded-[5px] px-1 truncate cursor-pointer" :class="$page.url.startsWith('/settings/whatsapp') ? 'bg-slate-50 text-black' : ''">
                            <Link rel="noopener noreferrer" href="/settings/whatsapp" class="flex items-center p-2 space-x-3 rounded-md">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path fill="currentColor" d="M19.05 4.91A9.816 9.816 0 0 0 12.04 2c-5.46 0-9.91 4.45-9.91 9.91c0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38c1.45.79 3.08 1.21 4.74 1.21c5.46 0 9.91-4.45 9.91-9.91c0-2.65-1.03-5.14-2.9-7.01m-7.01 15.24c-1.48 0-2.93-.4-4.2-1.15l-.3-.18l-3.12.82l.83-3.04l-.2-.31a8.264 8.264 0 0 1-1.26-4.38c0-4.54 3.7-8.24 8.24-8.24c2.2 0 4.27.86 5.82 2.42a8.183 8.183 0 0 1 2.41 5.83c.02 4.54-3.68 8.23-8.22 8.23m4.52-6.16c-.25-.12-1.47-.72-1.69-.81c-.23-.08-.39-.12-.56.12c-.17.25-.64.81-.78.97c-.14.17-.29.19-.54.06c-.25-.12-1.05-.39-1.99-1.23c-.74-.66-1.23-1.47-1.38-1.72c-.14-.25-.02-.38.11-.51c.11-.11.25-.29.37-.43s.17-.25.25-.41c.08-.17.04-.31-.02-.43s-.56-1.34-.76-1.84c-.2-.48-.41-.42-.56-.43h-.48c-.17 0-.43.06-.66.31c-.22.25-.86.85-.86 2.07c0 1.22.89 2.4 1.01 2.56c.12.17 1.75 2.67 4.23 3.74c.59.26 1.05.41 1.41.52c.59.19 1.13.16 1.56.1c.48-.07 1.47-.6 1.67-1.18c.21-.58.21-1.07.14-1.18s-.22-.16-.47-.28"/></svg>
                                <div class="overflow-hidden text-ellipsis">{{ $t('Whatsapp settings') }}</div>
                            </Link>
                        </li>
                        <li class="hover:bg-slate-50 hover:text-black rounded-[5px] px-1 truncate cursor-pointer" :class="$page.url.startsWith('/settings/contacts') ? 'bg-slate-50 text-black' : ''">
                            <Link rel="noopener noreferrer" href="/settings/contacts" class="flex items-center p-2 space-x-3 rounded-md">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 20c0-1.742-1.67-3.223-4-3.773M15 20c0-2.21-2.686-4-6-4s-6 1.79-6 4m12-7a4 4 0 0 0 0-8m-6 8a4 4 0 1 1 0-8a4 4 0 0 1 0 8"/></svg>
                                <span>{{ $t('Contact fields') }}</span>
                            </Link>
                        </li>
                        <li class="hover:bg-slate-50 hover:text-black rounded-[5px] px-1 truncate cursor-pointer" :class="$page.url.startsWith('/settings/tickets') ? 'bg-slate-50 text-black' : ''">
                            <Link rel="noopener noreferrer" href="/settings/tickets" class="flex items-center p-2 space-x-3 rounded-md">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path fill="currentColor" d="M4 4a2 2 0 0 0-2 2v4a2 2 0 0 1 2 2a2 2 0 0 1-2 2v4a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-4a2 2 0 0 1-2-2a2 2 0 0 1 2-2V6a2 2 0 0 0-2-2zm0 2h16v2.54c-1.24.71-2 2.03-2 3.46s.76 2.75 2 3.46V18H4v-2.54c1.24-.71 2-2.03 2-3.46s-.76-2.75-2-3.46z"/></svg>
                                <span>{{ $t('Ticket settings') }}</span>
                            </Link>
                        </li>
                        <li class="hover:bg-slate-50 hover:text-black rounded-[5px] px-1 truncate cursor-pointer" :class="$page.url.startsWith('/settings/tags') ? 'bg-slate-50 text-black' : ''">
                            <Link rel="noopener noreferrer" href="/settings/tags" class="flex items-center p-2 space-x-3 rounded-md">
                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 20 20"><path fill="currentColor" d="M.205 9.166A.65.65 0 0 1 0 8.696V3.358c-.01-.334.076-.639.27-.89C.5 2.172.848 2.024 1.299 2h5.518c.18 0 .353.07.48.194l9.015 8.798c.186.22.273.491.24.774c-.027.234-.124.45-.322.673l-5.443 5.236a1.12 1.12 0 0 1-.782.319c-.275 0-.532-.098-.818-.318zm1.15-.747l8.628 8.18l5.122-4.925l-8.57-8.36h-5.18zM9.976 3.32h-2.99a.667.667 0 0 1-.677-.657c0-.363.304-.658.678-.658h3.271c.18 0 .353.07.48.194l9.015 8.798c.187.22.273.492.24.775c-.027.234-.124.449-.322.673l-5.442 5.236a1.12 1.12 0 0 1-.782.318c-.276 0-.532-.097-.819-.318l-1.341-1.271a.643.643 0 0 1-.012-.93a.69.69 0 0 1 .958-.01l1.191 1.134l5.123-4.924zm-5.48 4.845c-1.122 0-2.032-.882-2.032-1.97c0-1.09.91-1.972 2.032-1.972s2.031.882 2.031 1.971s-.91 1.971-2.031 1.971m0-1.314a.667.667 0 0 0 .677-.657a.667.667 0 0 0-.677-.657a.667.667 0 0 0-.678.657c0 .363.304.657.678.657"/></svg>
                                <span>{{ $t('Contacts Tags') }}</span>
                            </Link>
                        </li>
                    </ul>
                </div>

                <div class="md:w-4/5 bg-white md:bg-inherit md:px-8 rounded-[5px] text-[#000] h-full md:overflow-y-auto">
                    <slot/>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
<script setup>
    import AppLayout from "./../Layout/App.vue";
    import { Link } from "@inertiajs/vue3";
    import { ref } from 'vue';
    import FormModal from '@/Components/FormModal.vue';
    import { trans } from 'laravel-vue-i18n';
</script>