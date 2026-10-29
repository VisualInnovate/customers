<template>
  <AppLayout>
    <div class="flex flex-grow">
      <!-- Sidebar -->
      <HelpSidbar />

      <!-- Main Content -->
      <div class="bg-white md:bg-inherit pt-16 md:pt-8 md:px-16 px-4 w-full py-8 md:overflow-y-scroll h-screen">
        <!-- Page Header -->
        <div class="flex justify-between items-center mb-6">
          <div>
            <h1 class="text-xl font-semibold mb-1">{{ $t('Manage Categories') }}</h1>
            <p class="text-sm leading-6 text-gray-600 flex items-center">
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24">
                <path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11v5m0 5a9 9 0 1 1 0-18a9 9 0 0 1 0 18Zm.05-13v.1h-.1V8h.1Z" />
              </svg>
              <span class="ml-2">{{ $t('View, edit, or delete categories') }}</span>
            </p>
          </div>
          <div>
            <Link
              @click="openModal()" 
              class="rounded-md bg-primary px-3 py-2 text-sm text-white shadow-sm hover:bg-primaryLight focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
            >
              {{ $t('Add Category') }}
            </Link>
          </div>
        </div>
        {{ rows }}
        <!-- Category Table -->
        <CategoryTable />

        <!-- Form Modal Component-->
        <FormModal 
            v-model="isOpenFormModal" 
                :label="label" 
                :url="formUrl" 
                :form="form" 
                :formInputs="formInputs"
                :formMethod="formMethod"
                @closeModal="isOpenFormModal = false"
                @submit="submitForm" 
            />
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
    import AppLayout from "./../../Layout/App.vue";
    import HelpSidbar from "./../../Layout/HelpSidbar.vue";
    import { Link } from "@inertiajs/vue3";
    import CategoryTable from "@/components/Tables/CategoryTable.vue";
    import FormModal from '@/Components/FormModalModified.vue';
    import { trans } from 'laravel-vue-i18n';
    import { ref } from 'vue';


    const props= defineProps({
      rows: Array,
    });

    const isOpenFormModal = ref(true);
    const label = ref(trans('Add New Category'));
    const formUrl = ref('/knowledge/categories/store');
    const formMethod = ref('post');
        
    const form = {
        name: null
    };


    
    const openModal = (key, formData = {}) => {
      console.log('Opening Modal');
      label.value = trans('Add New Category');
      formMethod.value = 'post';
      form.name = formData.name || null;
      isOpenFormModal.value = true;
    }

    const formInputs = [
        {
            inputType: 'FormInput',
            name: 'name',
            label: trans('name'),
            type: 'text',
            className: 'sm:col-span-6',
        }
    ];


    function getRow() {
        axios.post(formUrl.value).then((response) => {
            const { data } = response;
            for (const key in data.item) {
                if (form.hasOwnProperty(key)) {
                    form[key] = data.item[key];
                }
            }
            isOpenFormModal.value = true;
        })
        .catch((error) => {
            // console.error('Error:', error);
        });
    }





</script>



<style>


</style>

