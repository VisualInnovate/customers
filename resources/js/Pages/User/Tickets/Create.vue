<template>
    <AppLayout>
        <div class="bg-white md:bg-inherit pt-0 px-4 md:pt-8 md:p-8 rounded-[5px] text-[#000] overflow-y-scroll h-screen flex flex-col">
            <div class="md:flex justify-between hidden">
                <div>
                    <h2 class="text-xl mb-1">{{ $t('Create ticket') }}</h2>
                    <p class="mb-6 flex items-center text-sm leading-6 text-gray-600">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11v5m0 5a9 9 0 1 1 0-18a9 9 0 0 1 0 18Zm.05-13v.1h-.1V8h.1Z"/></svg>
                        <span class="ml-1 mt-1">{{ $t('Have an issue? Describe it in detail below') }}</span>
                    </p>
                </div>
                <div>
                    <Link href="/tickets/index" class="flex items-center space-x-4 rounded-md bg-primary px-3 py-2 text-sm text-white shadow-sm hover:bg-primaryLight focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16"><path fill="currentColor" fill-rule="evenodd" d="M5.841 5.28a.75.75 0 0 0-1.06-1.06L1.53 7.47L1 8l.53.53l3.25 3.25a.75.75 0 0 0 1.061-1.06l-1.97-1.97H24.25a.75.75 0 0 0 0-1.5H3.871l1.97-1.97Z" clip-rule="evenodd"/></svg>
                        {{ $t('Back') }}
                    </Link>
                </div>
            </div>
            <!-- Form Wrapper -->
            <div class="flex-grow">
                <form @submit.prevent="submitForm()" class="bg-white md:border py-5 md:px-5 rounded-[0.5rem] h-full">
                    <div class="mt-5 mb-5 grid grid-cols-1 gap-1 ">
                        <!-- Form Fields -->
                      
                        <div>
                            <FormInput v-model="form.contact" placeholder="contact" :name="$t('Contact')" :type="'email'" :error="form.errors.contact" :class="'sm:col-span-6'" :labelClass="'mb-0'"/>
                        </div>
                        <div>
                            <FormInput v-model="form.subject" placeholder="Subject" :name="$t('Subject')" :type="'text'" :error="form.errors.subject" :class="'sm:col-span-6'" :labelClass="'mb-0'"/>
                        </div>
                        <div>
                            <FormInput v-model="form.service" placeholder="service" :name="$t('Service')" :type="'text'" :error="form.errors.service" :class="'sm:col-span-6'" :labelClass="'mb-0'"/>
                        </div>
                        <div>
                            <FormSelect :name="$t('Selecte Type')" :required="true" :error="form.errors.type" v-model="form.type" :options="Types" :class="'sm:col-span-6'"/>
                        </div>
                        <div>
                            <FormSelect :name="$t('Selecte Status')" :required="true" :error="form.errors.status" v-model="form.status" :options="StatusOptions" :class="'sm:col-span-6'"/>
                        </div>
                        <div>
                            <FormSelect :name="$t('Selecte Periority')" :required="true" :error="form.errors.priority" v-model="form.priority" :options="Periorities" :class="'sm:col-span-6'"/>
                        </div>
                        <div>
                            <FormSelect :name="$t('Selecte Agent')" :required="true" :error="form.errors.agent" v-model="form.agent" :options="Agents" :class="'sm:col-span-6 '"/>
                        </div>
                     
                       
                        <div>
                            <p class="text-sm text-slate-900">{{ $t("Description") }}</p>
                            <QuillEditor :name="$t('Description')" v-model:content="form.description" style="max-height: 200px;" :toolbar="[['image'], [{ list: 'bullet' }],[{ list: 'ordered' }], ['bold', 'italic'],,['customFeature']]" theme="snow"  />
                        </div>
                        <div>
                            <FormSelect :name="$t('Selecte Agent')" :required="true" :error="form.errors.name" v-model="form.name" :options="Agents" :class="'sm:col-span-6 invisible'"/>
                        </div>
                    </div>
                    <div class="flex justify-end">
                <button type="submit" class=" flex items-center space-x-4 rounded-md bg-black px-3 py-2 text-sm text-white shadow-sm hover:bg-slate-600 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                    <span v-if="!isLoading">{{ $t('Create ticket') }}</span>
                    <svg v-else xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path fill="currentColor" d="M12 2A10 10 0 1 0 22 12A10 10 0 0 0 12 2Zm0 18a8 8 0 1 1 8-8A8 8 0 0 1 12 20Z" opacity=".5"/><path fill="currentColor" d="M20 12h2A10 10 0 0 0 12 2V4A8 8 0 0 1 20 12Z"><animateTransform attributeName="transform" dur="1s" from="0 12 12" repeatCount="indefinite" to="360 12 12" type="rotate"/></path></svg>
                </button>
            </div>
                </form>
            </div>
            <!-- Button -->
          {{ errors }}
        </div>
    </AppLayout>
</template>

<script setup>
 import '@vueup/vue-quill/dist/vue-quill.snow.css';
    import AppLayout from './../Layout/App.vue';
    import { Link, useForm } from "@inertiajs/vue3";
    import { ref, onMounted } from 'vue';
    import FormInput from '@/Components/FormInput.vue';
    import FormSelect from '@/Components/FormSelect.vue';
    import Combobox from '@/Components/Select.vue'
    import FormTextArea from '@/Components/FormTextArea.vue';
    import { trans } from 'laravel-vue-i18n';
     import { QuillEditor } from '@vueup/vue-quill'

    const props = defineProps(['categories','agents']);
    
    const form = useForm({
        contact:null,
        subject:null,
        type:null,
        status:null,
        priority:null,
        agent:null,
        description:null,
        service:null,
    });
    const categoryOptions = ref([]);
    const Periorities = ref([
     
        { value: 'high', label: trans('High') },
        { value: 'medium', label: trans('Medium') },
        { value: 'low', label: trans('Low') },
    ]);
    const StatusOptions = ref([
        { value: 'open', label: trans('Open') },
        { value: 'closed', label: trans('Closed') },
        { value: 'pending', label: trans('Pending') },
        { value: 'resolved', label: trans('Resolved') },
        
    ]);
    const Contact = ref([
    'sales@mamasfirst.com'
       
       
    ]);
    const Types = ref([
        { value: 'Question', label: trans('Question') },
        { value: 'Incident', label: trans('Incident') },
        { value: 'Refund', label: trans('Refund') },
        { value: 'product malfunction', label: trans('product malfunction') },
        
    ]);
    const Agents = ref([]);
    const transformCategories = (categories) => {
        return categories.map((category) => ({
            value: category.id,
            label: category.name,
        }));
    };

    const submitForm = () => {
        form.post('/tickets/store');
        
    }

    onMounted(() => {
        Agents.value=props.agents.map((option) => ({
            value: option.user.full_name,
            label: option.user.full_name,
            
        }));
        categoryOptions.value = transformCategories(props.categories);
    });
</script>