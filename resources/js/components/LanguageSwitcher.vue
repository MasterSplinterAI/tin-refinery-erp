<template>
  <div class="flex items-center space-x-2">
    <button
      v-for="lang in languages"
      :key="lang"
      @click="switchLanguage(lang)"
      :class="[
        'px-3 py-1 rounded-md text-sm font-medium',
        currentLanguage === lang
          ? 'bg-indigo-600 text-white'
          : 'bg-gray-200 text-gray-700 hover:bg-gray-300'
      ]"
    >
      {{ lang.toUpperCase() }}
    </button>
  </div>
</template>

<script setup lang="ts">
import { useLanguage } from '@/composables/useLanguage';

const { setLanguage, language } = useLanguage();
const languages = ['en', 'es'] as const;
const currentLanguage = language;

const switchLanguage = (lang: 'en' | 'es') => {
  setLanguage(lang);
};
</script> 