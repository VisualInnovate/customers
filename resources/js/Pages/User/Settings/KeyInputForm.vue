```vue
<template>
    <div>
        <!-- Modal -->
        <div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg p-6 w-full max-w-2xl max-h-[85vh] overflow-y-auto">
                <!-- Header with module name and logo -->
                <div class="flex items-center mb-4">
                    <img 
                        :src="`/images/${module.logo}`" 
                        :alt="module.name" 
                        class="w-8 h-8 mr-2"
                        v-if="module.logo"
                    />
                    <h3 class="text-base font-medium text-gray-900">
                        {{ $t('Configure') }} {{ module.name }}
                    </h3>
                </div>
                <p class="text-sm text-gray-600 mb-4" v-html="formattedDescription(module.description)"></p>

                <!-- Step Indicator -->
                <div v-if="steps.length > 1" class="flex justify-between mb-6">
                    <div 
                        v-for="(step, index) in steps" 
                        :key="index" 
                        class="flex-1 text-center"
                    >
                        <div 
                            class="w-6 h-6 mx-auto rounded-full flex items-center justify-center text-sm"
                            :class="[
                                currentStep >= index 
                                    ? 'bg-[#034737] text-white' 
                                    : 'bg-gray-200 text-gray-600'
                            ]"
                        >
                            {{ index + 1 }}
                        </div>
                        <p class="text-xs mt-1">{{ $t('Step') }} {{ index + 1 }}</p>
                    </div>
                </div>

                <!-- Step Content -->
                <div class="mb-6">
                    <!-- Third Step: Template Selection -->
                    <div v-if="currentStep === 2">
                        <h3 class="text-base font-medium text-gray-900 mb-4">{{ $t('Select Template') }}</h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div 
                                v-for="(template, index) in templates" 
                                :key="index" 
                                class="border rounded-lg p-4 shadow-sm hover:shadow-md transition-shadow"
                            >
                                <h4 class="text-sm font-medium text-gray-900">{{ template.name }}</h4>
                                <p class="text-xs text-gray-600 mt-2 whitespace-normal">{{ template.order_message }}</p>
                                <button 
                                    @click="createTemplate(template)"
                                    class="mt-4 px-3 py-1 bg-[#034737] text-white rounded-md hover:bg-[#034737]/90 text-sm"
                                    :disabled="isLoading"
                                >
                                    {{ $t('Create') }}
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Other Steps: Original Form Fields -->
                    <div v-else>
                        <p class="text-sm text-gray-600 mb-4 whitespace-normal" v-html="formattedDescription(currentStepData.description)"></p>
                        <img 
                            v-if="currentStepData.image_url"
                            :src="currentStepData.image_url"
                            :alt="`Step ${currentStep + 1} illustration`"
                            class="w-full max-h-48 object-contain mb-4 rounded-md transition-transform duration-300"
                        />

                        <!-- Form Fields -->
                        <div v-if="currentStepFields.length" class="grid grid-cols-1 gap-4">
                            <div 
                                v-for="field in currentStepFields" 
                                :key="field.name"
                                :class="field.class"
                            >
                                <label 
                                    :for="field.name" 
                                    class="block text-sm font-medium text-gray-700"
                                >
                                    {{ field.label }}
                                </label>
                                <p v-if="field.description" class="text-xs text-gray-500 mt-1 whitespace-normal" v-html="formattedDescription(field.description)"></p>

                                <!-- Input Field -->
                                <input
                                    v-if="field.element === 'input'"
                                    v-model="formData[field.name]"
                                    :type="field.type"
                                    :id="field.name"
                                    class="mt-1 p-1 block w-full rounded-md border border-gray-300 focus:ring-[#034737] focus:border-[#034737]"
                                    :placeholder="$t(`Enter ${field.label.toLowerCase()}`)"
                                />
                                <textarea
                                    v-if="field.element === 'textarea'"
                                    v-model="formData[field.name]"
                                    :id="field.name"
                                    class="mt-1 p-2 block w-full rounded-md border border-gray-300 focus:ring-[#034737] focus:border-[#034737]"
                                    :placeholder="$t(`Enter ${field.label.toLowerCase()}`)"
                                    rows="3"
                                ></textarea>

                                <!-- Toggle Field -->
                                <div v-if="field.element === 'toggle'" class="mt-1">
                                    <input
                                        v-model="formData[field.name]"
                                        type="checkbox"
                                        :id="field.name"
                                        class="h-4 w-4 text-[#034737] border-gray-300 rounded focus:ring-[#034737]"
                                    />
                                </div>

                                <!-- Dropdown Field using Headless UI -->
                                <Menu v-if="field.element === 'dropdown'" as="div" class="relative mt-1">
                                    <MenuButton 
                                        class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-left text-sm shadow-sm focus:border-[#034737] focus:ring focus:ring-[#034737] focus:ring-opacity-50"
                                    >
                                        {{ formData[field.name] ? field.options.find(opt => opt.value === formData[field.name])?.label || $t(`Select ${field.label.toLowerCase()}`) : $t(`Select ${field.label.toLowerCase()}`) }}
                                        <ChevronDownIcon class="absolute right-2 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400" />
                                    </MenuButton>
                                    <MenuItems 
                                        class="absolute z-10 mt-1 w-full rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none"
                                    >
                                        <MenuItem 
                                            v-for="option in field.options" 
                                            :key="option.value"
                                            v-slot="{ active }"
                                        >
                                            <div
                                                @click="formData[field.name] = option.value"
                                                :class="[
                                                    active ? 'bg-blue-100 text-[#034737]' : 'text-gray-700',
                                                    'block px-4 py-2 text-sm cursor-pointer'
                                                ]"
                                            >
                                                {{ option.label }}
                                            </div>
                                        </MenuItem>
                                    </MenuItems>
                                </Menu>
                            </div>
                        </div>
                        <div v-else class="text-sm text-gray-600">
                            {{ $t('No configuration required for this step.') }}
                        </div>
                    </div>
                </div>

                <!-- Navigation Buttons -->
                <div class="flex justify-between">
                    <button 
                        v-if="currentStep > 0"
                        @click="currentStep--"
                        class="px-3 py-1 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 text-sm"
                    >
                        {{ $t('Back') }}
                    </button>
                    <div class="flex-1"></div>
                    <div class="flex space-x-2">
                        <button 
                            @click="closeModal"
                            class="px-3 py-1 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 text-sm"
                        >
                            {{ $t('Cancel') }}
                        </button>
                        <button 
                            v-if="currentStep < steps.length - 1"
                            @click="nextStep"
                            class="px-3 py-1 bg-[#034737] text-white rounded-md hover:bg-[#034737]/90 text-sm"
                            :disabled="!isCurrentStepValid"
                        >
                            {{ $t('Next') }}
                        </button>
                        <button 
                            v-else-if="currentStep !== 2"
                            @click="submit"
                            class="px-3 py-1 bg-[#034737] text-white rounded-md hover:bg-[#034737]/90 text-sm"
                            :disabled="currentStepFields.length && !isFormValid || isLoading"
                        >
                            {{ $t('Save') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Menu, MenuButton, MenuItems, MenuItem } from '@headlessui/vue';
import { ChevronDownIcon } from '@heroicons/vue/20/solid';
import axios from 'axios';
import { toast } from 'vue3-toastify';
import 'vue3-toastify/dist/index.css';

const props = defineProps({
    module: {
        type: Object,
        required: true,
        default: () => ({
            name: '',
            logo: '',
            description: '',
            handle: '',
            metadata: '{}'
        })
    }
});

const emit = defineEmits(['close', 'submit', 'template-created']);

const showModal = ref(true);
const currentStep = ref(0);
const selectedTemplate = ref(null);
const isLoading = ref(false);

// Parse metadata
const metadata = computed(() => {
    try {
        return JSON.parse(props.module.metadata) || {};
    } catch (e) {
        console.error('Invalid metadata JSON:', e);
        return {};
    }
});

const steps = computed(() => metadata.value.steps || [{}]);
const templates = computed(() => {
    const stepWithTemplates = steps.value.find(step => step.templates);
    return stepWithTemplates?.templates || [];
});
const currentStepData = computed(() => steps.value[currentStep.value] || {});
const currentStepFields = computed(() => {
    const input = currentStepData.value.input;
    return input && currentStep.value !== 2 ? [input] : [];
});

// Initialize form data
const formData = ref(
    (metadata.value.input_fields || []).reduce((acc, field) => {
        acc[field.name] = field.element === 'toggle' ? false : 
                         field.element === 'dropdown' ? (field.options?.[0]?.value || '') : 
                         '';
        return acc;
    }, {})
);

// Format descriptions to handle \n as <br>
const formattedDescription = computed(() => {
    return (text) => {
        if (!text) return '';
        return text.replace(/\n/g, '<br>');
    };
});

// Validation
const isCurrentStepValid = computed(() => {
    if (currentStep.value === 2) {
        return true; // No validation needed for template selection step
    }
    return currentStepFields.value.every(field => {
        if (field.element === 'toggle') return true;
        if (field.element === 'dropdown') return formData.value[field.name] !== '';
        return formData.value[field.name];
    });
});

const isFormValid = computed(() => {
    return (metadata.value.input_fields || []).every(field => {
        if (field.element === 'toggle') return true;
        if (field.element === 'dropdown') return formData.value[field.name] !== '';
        return formData.value[field.name];
    });
});

// Navigation
const nextStep = () => {
    if (isCurrentStepValid.value && currentStep.value < steps.value.length - 1) {
        currentStep.value++;
    }
};

// Modal controls
const closeModal = () => {
    showModal.value = false;
    selectedTemplate.value = null;
    currentStep.value = 0;
    formData.value = {};
    emit('close');
};

// Create template function
const createTemplate = async (template) => {
    isLoading.value = true;
    try {
        const response = await axios.post(`/addons/setup/${props.module.handle}/create-template`, {
            name: template.name,
            order_message: template.order_message
        });

        toast.success('Template created successfully!', {
            autoClose: 3000,
        });
        emit('template-created', response.data);
        closeModal();
    } catch (error) {
        console.error('Template creation failed:', error);
        toast.error(error.response?.data?.message || 'Failed to create template', {
            autoClose: 3000,
        });
    } finally {
        isLoading.value = false;
    }
};

// Submit form (for non-template steps)
const submit = async () => {
    if (!currentStepFields.value.length || isFormValid.value) {
        isLoading.value = true;
        try {
            const formPayload = new FormData();
            Object.entries(formData.value).forEach(([key, value]) => {
                formPayload.append(key, value);
            });

            const response = await axios.post(`/addons/setup/${props.module.handle}`, formPayload);

            toast.success('Configuration saved successfully!', {
                autoClose: 3000,
            });
            emit('submit', { formData: formData.value, response: response.data });
            closeModal();
        } catch (error) {
            console.error('Form submission failed:', error);
            toast.error(error.response?.data?.message || 'Failed to save configuration', {
                autoClose: 3000,
            });
            emit('submit', { formData: formData.value, error });
        } finally {
            isLoading.value = false;
        }
    }
};
</script>

<style scoped>
img {
    max-width: 100%;
    height: auto;
    transition: transform 0.3s ease;
}

img:hover {
    transform: scale(1.2);
}

.max-h-[85vh] {
    max-height: 85vh;
}
</style>
```