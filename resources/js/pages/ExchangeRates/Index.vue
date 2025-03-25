<template>
  <AuthenticatedLayout>
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        Exchange Rates
      </h2>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6 bg-white border-b border-gray-200">
            <!-- Current Rate Card -->
            <div v-if="latestRate" class="mb-8 p-4 bg-blue-50 rounded-lg">
              <h3 class="text-lg font-semibold text-blue-800 mb-2">Current Exchange Rate</h3>
              <p class="text-2xl font-bold text-blue-900">
                1 USD = {{ formattedRate }} COP
              </p>
              <p class="text-sm text-blue-600">
                Last updated: {{ formatDate(latestRate.date) }}
              </p>
            </div>

            <!-- Currency Converter -->
            <div class="mb-8 p-4 bg-gray-50 rounded-lg">
              <h3 class="text-lg font-semibold text-gray-800 mb-4">Currency Converter</h3>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700">Amount</label>
                  <input
                    v-model="converter.amount"
                    type="number"
                    step="0.01"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                  />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700">From</label>
                  <select
                    v-model="converter.fromCurrency"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                  >
                    <option value="USD">USD</option>
                    <option value="COP">COP</option>
                  </select>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700">To</label>
                  <select
                    v-model="converter.toCurrency"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                  >
                    <option value="USD">USD</option>
                    <option value="COP">COP</option>
                  </select>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700">Date (Optional)</label>
                  <input
                    v-model="converter.date"
                    type="date"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                  />
                </div>
              </div>
              <button
                @click="convertCurrency"
                class="mt-4 px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
              >
                Convert
              </button>
              <div v-if="conversionResult" class="mt-4 p-4 bg-white rounded-md shadow">
                <p class="text-lg font-semibold">
                  {{ converter.amount }} {{ converter.fromCurrency }} =
                  <span class="text-blue-600">{{ conversionResult.result.toFixed(2) }} {{ converter.toCurrency }}</span>
                </p>
                <p class="text-sm text-gray-600">
                  Using rate: {{ conversionResult.rate_used.toFixed(4) }}
                </p>
              </div>
            </div>

            <!-- Add New Rate Form -->
            <div class="mb-8 p-4 bg-gray-50 rounded-lg">
              <h3 class="text-lg font-semibold text-gray-800 mb-4">Add New Exchange Rate</h3>
              <form @submit.prevent="addRate" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label class="block text-sm font-medium text-gray-700">Rate (COP per USD)</label>
                    <input
                      v-model="newRate.rate"
                      type="number"
                      step="0.0001"
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    />
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700">Date</label>
                    <input
                      v-model="newRate.date"
                      type="date"
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    />
                  </div>
                </div>
                <button
                  type="submit"
                  class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500"
                >
                  Add Rate
                </button>
              </form>
            </div>

            <!-- Historical Rates Table -->
            <div>
              <h3 class="text-lg font-semibold text-gray-800 mb-4">Historical Rates</h3>
              <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                  <thead class="bg-gray-50">
                    <tr>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rate</th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Source</th>
                    </tr>
                  </thead>
                  <tbody class="bg-white divide-y divide-gray-200">
                    <tr v-for="rate in rates.data" :key="rate.id">
                      <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ formatDate(rate.date) }}
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ Number(rate.rate).toFixed(4) }}
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ rate.source }}
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup lang="ts">
import { ref, watch, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

interface ConversionResponse {
  result: number;
  rate_used: number;
}

const props = defineProps<{
  rates: {
    data: Array<{
      id: number;
      rate: number;
      date: string;
      source: string;
      api_provider?: string;
      metadata?: Record<string, any>;
      created_at: string;
      updated_at: string;
    }>;
    current_page: number;
    last_page: number;
    total: number;
  };
  latestRate: {
    id: number;
    rate: number;
    date: string;
    source: string;
    api_provider?: string;
    metadata?: Record<string, any>;
    created_at: string;
    updated_at: string;
  } | null;
  flash?: {
    conversion?: ConversionResponse;
  };
}>();

const converter = ref({
  amount: 0,
  fromCurrency: 'USD',
  toCurrency: 'COP',
  date: ''
});

const newRate = ref({
  rate: 0,
  date: new Date().toISOString().split('T')[0]
});

const conversionResult = ref<ConversionResponse | null>(null);

// Add computed property for formatted rate
const formattedRate = computed(() => {
  if (!props.latestRate?.rate) return '0.0000';
  return Number(props.latestRate.rate).toFixed(4);
});

watch(() => props.flash?.conversion, (newValue) => {
  if (newValue) {
    conversionResult.value = newValue;
  }
}, { immediate: true });

const formatDate = (date: string) => {
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  });
};

const convertCurrency = () => {
  router.post('/exchange-rates/convert', converter.value, {
    preserveScroll: true
  });
};

const addRate = () => {
  router.post('/exchange-rates', newRate.value, {
    onSuccess: () => {
      newRate.value = {
        rate: 0,
        date: new Date().toISOString().split('T')[0]
      };
    }
  });
};
</script> 