<script setup>
    import SettingLayout from "./../Layout.vue";
    import { toast } from 'vue3-toastify';
    import 'vue3-toastify/dist/index.css';
    import axios from "axios";
    import { useForm } from '@inertiajs/vue3';
    import { onMounted, ref } from 'vue';
    import FormTextArea from '@/Components/FormTextArea.vue';
    import FormToggleSwitch from '@/Components/FormToggleSwitch.vue';
    import { useI18n } from 'vue-i18n';

    const props = defineProps({
        rows: Object,
    })
  
    const form = useForm({
        'message': null,
        'active': false
    });
    if(props.rows){ 
        form.message = props.rows.data.message || null;
        form.active = props.rows.data.active || false;
    }   

    const isLoading = ref(false);
    const {t} = useI18n();

    const submitForm = async () => {
        isLoading.value = true;
        await axios.post('/auto-reply/store', {
            message: form.message,
            active: form.active || false
        }, {
            headers: {
                'Content-Type': 'multipart/form-data',
            },
        })
        isLoading.value = false;
          toast(t('Successfully Updated'), {
                autoClose: 3000,
            });
    };


    const emit = defineEmits(['update:modelValue', 'callback']);

    function onClose() {
        emit('update:modelValue', false);
    }
    
   
</script>
<template>
    <SettingLayout>
    <div class="mt-5 grid grid-cols-1 gap-x-6 gap-y-4 py-5">
        
        <form @submit.prevent="submitForm()" class="">

            <div class="border-b pb-8 max-w-2xl">
                <FormTextArea v-model="form.message" :error="form.errors.message" name="Greeting Message"
                    :type="'text'" />
            <div class="flex mt-3 gap-2">

                <FormToggleSwitch v-model="form.active" /> 
                <p class="text-sm">{{ $t('Active') }}</p>
            </div>
            </div>

            <div class="mt-4 flex ">
                <button type="button" @click.self="onClose"
                    class="inline-flex justify-center rounded-md border border-transparent bg-slate-50 px-4 py-2 text-sm text-slate-500 hover:bg-slate-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2 mr-4">{{
                    $t('Cancel') }}</button>
                <button
                    :class="['inline-flex justify-center rounded-md border border-transparent bg-primary px-4 py-2 text-sm text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2', { 'opacity-50': isLoading }]"
                    :disabled="isLoading">
                    <svg v-if="isLoading" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24">
                        <path fill="currentColor"
                            d="M12 2A10 10 0 1 0 22 12A10 10 0 0 0 12 2Zm0 18a8 8 0 1 1 8-8A8 8 0 0 1 12 20Z"
                            opacity=".5" />
                        <path fill="currentColor" d="M20 12h2A10 10 0 0 0 12 2V4A8 8 0 0 1 20 12Z">
                            <animateTransform attributeName="transform" dur="1s" from="0 12 12" repeatCount="indefinite"
                                to="360 12 12" type="rotate" />
                        </path>
                    </svg>
                    <span v-else>{{ $t('Save') }}</span>
                </button>
            </div>
        </form>
    </div>
    </SettingLayout>
</template>