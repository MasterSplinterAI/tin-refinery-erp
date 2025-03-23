<template>
  <input
    ref="input"
    type="text"
    :value="modelValue"
    @input="handleInput"
    @blur="handleBlur"
    class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md"
  />
</template>

<script setup lang="ts">
import { ref } from 'vue';

const props = defineProps<{
  modelValue: number;
  max?: number;
  min?: number;
}>();

const emit = defineEmits<{
  (e: 'update:modelValue', value: number): void;
}>();

const input = ref<HTMLInputElement | null>(null);

const handleInput = (event: Event) => {
  const target = event.target as HTMLInputElement;
  let value = target.value.replace(',', '.');
  
  // Allow empty or partial input
  if (!value || value === '-' || value === '.') {
    return;
  }

  // Remove any non-numeric characters except decimal point and minus
  value = value.replace(/[^\d.-]/g, '');
  
  // Ensure only one decimal point
  const parts = value.split('.');
  if (parts.length > 2) {
    value = parts[0] + '.' + parts.slice(1).join('');
  }
  
  const numValue = parseFloat(value);
  
  if (!isNaN(numValue)) {
    if (props.max !== undefined && numValue > props.max) {
      emit('update:modelValue', props.max);
      target.value = props.max.toString();
    } else if (props.min !== undefined && numValue < props.min) {
      emit('update:modelValue', props.min);
      target.value = props.min.toString();
    } else {
      emit('update:modelValue', numValue);
    }
  }
};

const handleBlur = (event: Event) => {
  const target = event.target as HTMLInputElement;
  const value = parseFloat(target.value);
  
  if (isNaN(value)) {
    emit('update:modelValue', 0);
    target.value = '0';
  } else {
    target.value = value.toString();
  }
};
</script> 