<script setup>
import { ref,onMounted } from 'vue';
import ShopifyModal from '../Modals/ShopifyModal.vue';
import SetupAiModal from '../Modals/SetupAiModal.vue';
import LiveChat from '../Modals/LiveChat.vue';
import axios from "axios";
import { toast } from 'vue3-toastify';

import { router } from '@inertiajs/vue3';
import InstaEmbeded from '@/Components/InstaEmbeded.vue';
import { useI18n } from 'vue-i18n';
const props = defineProps(['integrations','settings']);

const emit = defineEmits(['edit', 'delete']);
const {t} = useI18n();
const isopenlivechate=ref(false)
const active=ref(false)
const isShopifyModalOpen = ref(false);
const openAi=ref('')
const chatlivedata=ref('')
const livechate=()=>{
    isopenlivechate.value=true
}
const isAiModalOpen = ref(false);
const handleShopifyModal = () => {
    isShopifyModalOpen.value =  true 
}
const handleAiModal = () => {
    isAiModalOpen.value =  true
}
const launchInstagramSignup = async () => {
    axios.get('/instagram/auth').then(res=>{
        window.open(res.data.redirectUrl, '_blank')
    }).catch(err=>{
        alert(err)
    })

};


const deleteIntegration = async () => {
    axios.get('/instagram/delete/integration?name=instagram').then(res=>{
        
        toast("delete Integration  Successfully", {
            autoClose: 3000,
        });
        router.reload();

    }).catch(err=>{
        alert(err)
    })

};





const integrations = [ 
    { id : 1 , name: 'Shopify' , logo: '<svg xmlns="http://www.w3.org/2000/svg" width="28.06" height="32" viewBox="0 0 256 292"><path fill="#95BF46" d="M223.774 57.34c-.201-1.46-1.48-2.268-2.537-2.357a19614 19614 0 0 0-23.383-1.743s-15.507-15.395-17.209-17.099c-1.703-1.703-5.029-1.185-6.32-.805c-.19.056-3.388 1.043-8.678 2.68c-5.18-14.906-14.322-28.604-30.405-28.604c-.444 0-.901.018-1.358.044C129.31 3.407 123.644.779 118.75.779c-37.465 0-55.364 46.835-60.976 70.635c-14.558 4.511-24.9 7.718-26.221 8.133c-8.126 2.549-8.383 2.805-9.45 10.462C21.3 95.806.038 260.235.038 260.235l165.678 31.042l89.77-19.42S223.973 58.8 223.775 57.34M156.49 40.848l-14.019 4.339c.005-.988.01-1.96.01-3.023c0-9.264-1.286-16.723-3.349-22.636c8.287 1.04 13.806 10.469 17.358 21.32m-27.638-19.483c2.304 5.773 3.802 14.058 3.802 25.238c0 .572-.005 1.095-.01 1.624c-9.117 2.824-19.024 5.89-28.953 8.966c5.575-21.516 16.025-31.908 25.161-35.828m-11.131-10.537c1.617 0 3.246.549 4.805 1.622c-12.007 5.65-24.877 19.88-30.312 48.297l-22.886 7.088C75.694 46.16 90.81 10.828 117.72 10.828"/><path fill="#5E8E3E" d="M221.237 54.983a19614 19614 0 0 0-23.383-1.743s-15.507-15.395-17.209-17.099c-.637-.634-1.496-.959-2.394-1.099l-12.527 256.233l89.762-19.418S223.972 58.8 223.774 57.34c-.201-1.46-1.48-2.268-2.537-2.357"/><path fill="#FFF" d="m135.242 104.585l-11.069 32.926s-9.698-5.176-21.586-5.176c-17.428 0-18.305 10.937-18.305 13.693c0 15.038 39.2 20.8 39.2 56.024c0 27.713-17.577 45.558-41.277 45.558c-28.44 0-42.984-17.7-42.984-17.7l7.615-25.16s14.95 12.835 27.565 12.835c8.243 0 11.596-6.49 11.596-11.232c0-19.616-32.16-20.491-32.16-52.724c0-27.129 19.472-53.382 58.778-53.382c15.145 0 22.627 4.338 22.627 4.338"/></svg>'  ,description : "Connects your website to Shopify's powerful e-commerce tools for product management, checkout, and scaling online sales.", action :{label : t('Setup') , value : handleShopifyModal} },
    { id : 2 , name: 'Odoo' , logo: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" width="35" height="35"><circle cx="50" cy="50" r="40" fill="#a24589" /><circle cx="50" cy="50" r="20" fill="#FFFFFF" /></svg>'  ,description : "Seamlessly integrates business operations like ERP, CRM, and inventory management into your website for streamlined workflows", action : {label : t('Soon') , value : 'disabled'} },
    { id : 2 , name: 'Tableau' , logo: '<svg xmlns="http://www.w3.org/2000/svg" width="32.64" height="32" viewBox="0 0 256 251"><path fill="#7099A6" d="M123.929 11.593v11.641h-20.82v7.571h20.82v23.186h8.139V30.805h21.341v-7.571h-21.341V0h-8.139z"/><path fill="#EB912C" d="M55.884 41.121v16.893H24.369v10.599h31.515v34.307h11.688V68.613H99.56V58.014H67.572V24.228H55.884z"/><path fill="#59879B" d="M187.953 41.121v16.893h-31.515v11.12h31.515v33.786h12.161V69.134h31.514v-11.12h-31.514V24.228h-12.161z"/><path fill="#E8762C" d="M120.901 98.661v18.928H85.837v14.101h35.064v37.855h14.196V131.69h35.063v-14.101h-35.063V79.733h-14.196z"/><path fill="#5B6591" d="M224.01 108.219v11.878h-21.341v9.605h21.341v23.707h10.647v-23.707h21.341v-9.605h-21.341V96.39H224.01z"/><path fill="#7099A6" d="M20.821 109.26v11.357H0v8.044h20.821v22.714h8.138v-22.714l21.341-.757v-7.287H28.959V97.904h-8.138z"/><path fill="#C72035" d="M55.884 162.731v16.893H24.369v11.12h31.515v33.786h12.161v-33.786H99.56v-11.12H68.045v-33.786H55.884z"/><path fill="#1F447E" d="M187.953 162.731v16.893h-31.515v10.6h31.515v34.306h12.161v-34.306h31.514v-10.6h-31.514v-33.786h-12.161z"/><path fill="#5B6591" d="M122.936 205.65v11.83h-21.341v9.606h21.341v23.707h10.647v-23.707h21.341v-9.606h-21.341v-23.707h-10.647z"/></svg>'  ,description : "Integrates advanced data visualization tools to display interactive dashboards and insights on your website.", action : { label: t('Soon') , value : 'disabled'} },
    { id : 2 , name: 'Zoom' , logo: '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 256 256"><defs><linearGradient id="logosZoomIcon0" x1="23.666%" x2="76.334%" y1="95.612%" y2="4.388%"><stop offset="0%" stop-color="#0845BF"/><stop offset="19.11%" stop-color="#0950DE"/><stop offset="38.23%" stop-color="#0B59F6"/><stop offset="50%" stop-color="#0B5CFF"/><stop offset="67.32%" stop-color="#0E5EFE"/><stop offset="77.74%" stop-color="#1665FC"/><stop offset="86.33%" stop-color="#246FF9"/><stop offset="93.88%" stop-color="#387FF4"/><stop offset="100%" stop-color="#4F90EE"/></linearGradient></defs><path fill="url(#logosZoomIcon0)" d="M256 128c0 13.568-1.024 27.136-3.328 40.192c-6.912 43.264-41.216 77.568-84.48 84.48C155.136 254.976 141.568 256 128 256s-27.136-1.024-40.192-3.328c-43.264-6.912-77.568-41.216-84.48-84.48C1.024 155.136 0 141.568 0 128s1.024-27.136 3.328-40.192c6.912-43.264 41.216-77.568 84.48-84.48C100.864 1.024 114.432 0 128 0s27.136 1.024 40.192 3.328c43.264 6.912 77.568 41.216 84.48 84.48C254.976 100.864 256 114.432 256 128"/><path fill="#FFF" d="M204.032 207.872H75.008c-8.448 0-16.64-4.608-20.48-12.032c-4.608-8.704-2.816-19.2 4.096-26.112l89.856-89.856H83.968c-17.664 0-32-14.336-32-32h118.784c8.448 0 16.64 4.608 20.48 12.032c4.608 8.704 2.816 19.2-4.096 26.112l-89.6 90.112h74.496c17.664 0 32 14.08 32 31.744"/></svg>'  ,description : "Embeds video conferencing functionality for seamless virtual meetings and webinars within your website.", action : {label : t('Soon') , value : 'disabled'} },
    { id : 2 , name: 'OpenAI' , logo: '<svg xmlns="http://www.w3.org/2000/svg" width="31.51" height="32" viewBox="0 0 256 260"><path d="M239.184 106.203a64.72 64.72 0 0 0-5.576-53.103C219.452 28.459 191 15.784 163.213 21.74A65.586 65.586 0 0 0 52.096 45.22a64.72 64.72 0 0 0-43.23 31.36c-14.31 24.602-11.061 55.634 8.033 76.74a64.67 64.67 0 0 0 5.525 53.102c14.174 24.65 42.644 37.324 70.446 31.36a64.72 64.72 0 0 0 48.754 21.744c28.481.025 53.714-18.361 62.414-45.481a64.77 64.77 0 0 0 43.229-31.36c14.137-24.558 10.875-55.423-8.083-76.483m-97.56 136.338a48.4 48.4 0 0 1-31.105-11.255l1.535-.87l51.67-29.825a8.6 8.6 0 0 0 4.247-7.367v-72.85l21.845 12.636c.218.111.37.32.409.563v60.367c-.056 26.818-21.783 48.545-48.601 48.601M37.158 197.93a48.35 48.35 0 0 1-5.781-32.589l1.534.921l51.722 29.826a8.34 8.34 0 0 0 8.441 0l63.181-36.425v25.221a.87.87 0 0 1-.358.665l-52.335 30.184c-23.257 13.398-52.97 5.431-66.404-17.803M23.549 85.38a48.5 48.5 0 0 1 25.58-21.333v61.39a8.29 8.29 0 0 0 4.195 7.316l62.874 36.272l-21.845 12.636a.82.82 0 0 1-.767 0L41.353 151.53c-23.211-13.454-31.171-43.144-17.804-66.405zm179.466 41.695l-63.08-36.63L161.73 77.86a.82.82 0 0 1 .768 0l52.233 30.184a48.6 48.6 0 0 1-7.316 87.635v-61.391a8.54 8.54 0 0 0-4.4-7.213m21.742-32.69l-1.535-.922l-51.619-30.081a8.39 8.39 0 0 0-8.492 0L99.98 99.808V74.587a.72.72 0 0 1 .307-.665l52.233-30.133a48.652 48.652 0 0 1 72.236 50.391zM88.061 139.097l-21.845-12.585a.87.87 0 0 1-.41-.614V65.685a48.652 48.652 0 0 1 79.757-37.346l-1.535.87l-51.67 29.825a8.6 8.6 0 0 0-4.246 7.367zm11.868-25.58L128.067 97.3l28.188 16.218v32.434l-28.086 16.218l-28.188-16.218z"/></svg>' ,description: "Enhances user interaction by integrating AI-driven conversational capabilities directly into your platform" , action : {label : openAi  ? t('Update') : t('Setup') , value : handleAiModal} },
    { id : 2 , name: 'Live chat' , logo: '<svg height="34px" width="34px" version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 502.664 502.664" xml:space="preserve" fill="#000000"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <g> <g> <g> <path style="fill:#010002;" d="M378.74,181.184c0-76.576-84.708-138.743-189.305-138.743C84.73,42.441,0,104.608,0,181.184 c0,47.154,32.291,88.591,81.343,113.7l-47.024,89.389l101.987-70.515c16.955,3.645,34.6,6.234,53.129,6.234 C294.053,319.992,378.74,257.846,378.74,181.184z M129.942,196.24H89.95v-40.014h39.992V196.24z M251.3,156.226h39.992v40.014 H251.3V156.226z M170.625,156.226h39.971v40.014h-39.971V156.226z"></path> <path style="fill:#010002;" d="M502.664,268.481c0-50.325-38.763-93.984-95.602-115.943c2.804,10.332,4.314,21.053,4.314,32.097 c0,90.77-100.304,164.412-224.25,164.412c-1.532,0-2.955-0.324-4.465-0.324c32.68,30.868,83.695,50.799,141.138,50.799 c17.515,0,34.147-2.438,50.152-5.846l96.378,66.546l-44.457-84.363C472.206,352.111,502.664,312.981,502.664,268.481z"></path> </g> </g> <g> </g> <g> </g> <g> </g> <g> </g> <g> </g> <g> </g> <g> </g> <g> </g> <g> </g> <g> </g> <g> </g> <g> </g> <g> </g> <g> </g> <g> </g> </g> </g></svg>' ,description: "Live chat offers instant communication, allowing users to connect with support or services in real-time for quick resolutions" , action : {label : chatlivedata  ? t('Update') : t('Setup') , value : livechate} },
]
onMounted(() => {
    
      props.integrations.forEach((item, index) => {
        if(item.name=='instagram'&&item.active == 1){
            active.value=!(active.value)
        }
          if(item.name=='AI Settings'){
            openAi.value=item.ai

          }
          if(item.name=='LiveChat'){
            chatlivedata.value=item.metadata
            
          }
     
         
  });
    });

</script>
<template>
    <div class="grid md:grid-cols-3 gap-x-5 gap-y-4">
    <div v-for="integration in integrations" :key="integration.id" class="bg-white rounded-[0.5rem] shadow-md text-sm space-y-3 flex flex-col">
            <div class="flex items-center gap-x-3 pb-2 bg-slate-50 px-4 pt-4 rounded-tr-[0.5rem] rounded-tl-[0.5rem]">
                <div>
                    <div v-html ="integration.logo"></div>
                </div>
                <div class="text-[16px]">{{ $t(integration.name) }}</div>
            </div>
            <div class="flex-1 text-slate-500 border-b px-4 text-xs pt-2 pb-4">{{ integration.description }}</div>
            <div class="flex justify-end items-center px-4 pb-4 text-xs">
                <div>
                    <button  @click="integration.action.value" :disabled="integration.action.value == 'disabled'"
                        class="rounded-full border-2 w-full px-10 py-2 disabled:opacity-50 enabled:hover:border-secondary enabled:hover:bg-secondary enabled:hover:text-white text-secondary">{{integration.action.label }}</button>
            </div>
        </div> 
    </div>
    <div class="bg-white rounded-[0.5rem] shadow-md text-sm space-y-3 flex flex-col">
        <div class="flex items-center gap-x-3 pb-2 bg-slate-50 px-4 pt-4 rounded-tr-[0.5rem] rounded-tl-[0.5rem]">
        <div>
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="32" height="32"><path fill="url(#grad1)" d="M12 2.163c3.213 0 3.589.012 4.85.07 1.156.057 2.19.228 3.19.746a5.896 5.896 0 0 1 2.139 2.139c.518 1 .688 2.034.746 3.19.058 1.26.07 1.637.07 4.85s-.012 3.589-.07 4.85c-.057 1.156-.228 2.19-.746 3.19a5.896 5.896 0 0 1-2.139 2.139c-1 .518-2.034.688-3.19.746-1.26.058-1.637.07-4.85.07s-3.589-.012-4.85-.07c-1.156-.058-2.19-.228-3.19-.746a5.896 5.896 0 0 1-2.139-2.139c-.518-1-.688-2.034-.746-3.19-.058-1.26-.07-1.637-.07-4.85s.012-3.589.07-4.85c.057-1.156.228-2.19.746-3.19a5.896 5.896 0 0 1 2.139-2.139c1-.518 2.034-.688 3.19-.746 1.26-.058 1.637-.07 4.85-.07zM12 0c-3.292 0-3.69.013-4.974.072-1.184.059-2.264.231-3.182.689a7.866 7.866 0 0 0-2.868 2.868c-.458.918-.63 1.998-.689 3.182C.013 8.31 0 8.708 0 12c0 3.292.013 3.69.072 4.974.059 1.184.231 2.264.689 3.182a7.866 7.866 0 0 0 2.868 2.868c.918.458 1.998.63 3.182.689 1.284.059 1.682.072 4.974.072 3.292 0 3.69-.013 4.974-.072 1.184-.059 2.264-.231 3.182-.689a7.866 7.866 0 0 0 2.868-2.868c.458-.918.63-1.998.689-3.182.059-1.284.072-1.682.072-4.974 0-3.292-.013-3.69-.072-4.974-.059-1.184-.231-2.264-.689-3.182a7.866 7.866 0 0 0-2.868-2.868c-.918-.458-1.998-.63-3.182-.689-1.284-.059-1.682-.072-4.974-.072zm0 5.838a6.146 6.146 0 1 0 0 12.292 6.146 6.146 0 0 0 0-12.292zm0 9.553a3.407 3.407 0 1 1 0-6.813 3.407 3.407 0 0 1 0 6.813zm6.406-9.56a.981.981 0 1 1-.001 1.963.981.981 0 0 1 .001-1.963z"/><defs><linearGradient id="grad1" x1="0%" y1="0%" x2="100%" y2="100%"><stop offset="0%" style="stop-color:#F58529;stop-opacity:1" /> <stop offset="50%" style="stop-color:#F77737;stop-opacity:1" /><stop offset="100%" style="stop-color:#D83A8C;stop-opacity:1" /></linearGradient></defs></svg>
        </div>
        <div class="text-[16px]">Instagram</div>
        </div>
        <div class="text-slate-500 border-b px-4 text-xs pt-2 pb-4">
        Share your moments and connect with your followers directly from your platform.
        </div>
        <div class="flex justify-end items-center px-4 pb-4 text-xs">
        <button v-if="!active" @click="launchInstagramSignup" 
                class="rounded-full border-2 px-10 py-2 disabled:opacity-50 hover:border-secondary hover:bg-secondary hover:text-white text-secondary">
            Connect
        </button>
        <button @click="deleteIntegration"  v-if="active"
                class="rounded-full border-2 px-10 py-2 disabled:opacity-50 hover:border-secondary hover:bg-secondary hover:text-white text-secondary">
            Delete
        </button>
        </div>
  </div>
    </div>

    <ShopifyModal v-if="isShopifyModalOpen" v-model="isShopifyModalOpen" @closeModal="isShopifyModalOpen = false"/>
    <SetupAiModal v-if="isAiModalOpen" v-model="isAiModalOpen" :data="openAi"  />
    <LiveChat v-if="isopenlivechate" v-model="isopenlivechate" :data="chatlivedata"  />

   
</template>