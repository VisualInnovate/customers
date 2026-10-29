<template>
  <div>
    <Combobox v-model="selectedValue">
      <p class="text-sm text-slate-900">{{ $t("contacts") }}</p>

      <div class="relative">
        <div
          style="border: 2px solid #DCDFE4;"
          class="relative w-full cursor-default overflow-hidden rounded-lg bg-white text-left sm:text-sm"
        >
          <!-- Display the selected label in the Combobox -->
          <ComboboxInput
            @change="handleChange"
            class="w-full border-none py-2 pl-3 pr-10 text-sm leading-5 focus:ring-0"
            :displayValue="(value) => getLabel(value) || ''"
            v-model="typedValue"
          />
          <ComboboxButton @click="handleSearch" class="absolute inset-y-0 right-0 flex items-center pr-2">
            <ChevronUpDownIcon class="h-5 w-5 text-gray-400" aria-hidden="true" />
          </ComboboxButton>
        </div>
        <TransitionRoot
          leave="transition ease-in duration-100"
          leaveFrom="opacity-100"
          leaveTo="opacity-0"
        >
          <ComboboxOptions
            class="absolute z-50 mt-1 max-h-60 w-full overflow-auto rounded-md bg-white py-1 text-base shadow-lg ring-1 ring-black/5 focus:outline-none sm:text-sm"
          >
            <div
              v-if="filteredOptions.length === 0 && typedValue !== ''"
              class="relative cursor-default select-none px-4 py-2 text-gray-700"
            >
              Nothing found.
            </div>

            <ComboboxOption
              v-for="(option, index) in filteredOptions"
              :key="index"
              :value="option.value"
              v-slot="{ selected, active }"
            >
              <li
                class="relative cursor-default select-none py-2 pl-10 pr-4"
                :class="{
                  'bg-teal-600 text-white': active,
                  'text-gray-900': !active,
                }"
              >
                <span
                  class="block truncate"
                  :class="{ 'font-medium': selected, 'font-normal': !selected }"
                >
                  {{ option.label }}
                </span>
                <span
                  v-if="selected"
                  class="absolute inset-y-0 left-0 flex items-center pl-3"
                  :class="{ 'text-white': active, 'text-teal-600': !active }"
                >
                  <CheckIcon class="h-5 w-5" aria-hidden="true" />
                </span>
              </li>
            </ComboboxOption>
          </ComboboxOptions>
        </TransitionRoot>
      </div>
    </Combobox>
  </div>
</template>

<script setup>
import { ref, watch, defineProps, defineEmits } from 'vue'
import axios from "axios";
import {
  Combobox,
  ComboboxInput,
  ComboboxButton,
  ComboboxOptions,
  ComboboxOption,
  TransitionRoot,
} from '@headlessui/vue'
import { CheckIcon, ChevronUpDownIcon } from '@heroicons/vue/20/solid'

// Props
const props = defineProps({
  options: {
    type: Array,
    required: true,
  },
  modelValue: {
    type: [String, Number, null],
    default: null,
  },
})

// Emits
const emit = defineEmits(['update:modelValue'])

// State
const selectedValue = ref(props.modelValue)
const typedValue = ref('') // Store the input value

// Helper function to get label from selected value
const getLabel = (value) => {
  const option = filteredOptions.value.find((opt) => opt.value === value)
  return option ? option.label : ''
}

// Computed filtered options
const filteredOptions = ref([])

// Watch for changes to selectedValue
watch(selectedValue, (newValue) => {
  console.log(newValue)
  emit('update:modelValue', newValue)
  typedValue.value = getLabel(newValue) // Update input field with label
}, { immediate: true }) // Immediate to update on load

// Handle API search
const handleChange = (e) => {
  axios.get(`contacts/search?search=${e.target.value}`).then((res) => {
    filteredOptions.value = res.data.data.map((option) => ({
      value: option.uuid,
      label: option.full_name,
    }));
  });
}

// Handle direct selection from dropdown
const handleSearch = () => {
  const foundOption = props.options.find(option => option.label.toLowerCase() === typedValue.value.toLowerCase());
  if (foundOption) {
    selectedValue.value = foundOption.value; // Update selected value
  } else {
    typedValue.value = getLabel(selectedValue.value); // Reset to last valid selection
  }
}
</script>
