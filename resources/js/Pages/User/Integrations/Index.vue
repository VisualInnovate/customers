<template>
    <AppLayout>
        <div class="bg-white md:bg-inherit pt-10 px-4 md:pt-8 md:p-8 rounded-[5px] text-[#000] h-full md:overflow-y-auto">
            <div class="flex justify-between">
                <div>
                    <h1 class="text-xl mb-1">{{ $t('Integrations') }}</h1>
                    <p class="mb-6 flex items-center text-sm leading-6 text-gray-600 max-w-2xl">
                      
                        <span class="ml-1 mt-1">{{ $t('Our integrations make it quick and easy to get the most out of Delighted – from automating sending to customers, to following up within your help desk, or sharing feedback across your team.') }}</span>
                    </p>
                </div>
            </div>
        
         
            <!-- Table Component-->
            <IntegrationsTable :open-ai="settings.ai" :integrations="props.rows"  />
        </div>
    </AppLayout>
</template>

<script setup>  
    import { ref, onMounted ,watch} from 'vue';
    import AppLayout from "./../Layout/App.vue";
    import { toast } from 'vue3-toastify';
    import IntegrationsTable from '@/Components/Tables/IntegrationsTable.vue';
    import { router } from '@inertiajs/vue3';
    const props = defineProps(['rows', 'aimodule', 'settings', 'organization_integrations','flash']);
    const config = ref(props.settings.metadata);

    onMounted(() => {
      
        if(props.flash.status != null){
            toast("Integration instagrame Successfully", {
            autoClose: 3000,
        });
        }
  
       
    });
</script>