<template>
  <AuthenticatedLayout>
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        Currency Exchanges
      </h2>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6 bg-white border-b border-gray-200">
            <!-- Notification area -->
            <div v-if="$page.props.flash?.success" class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
              <p>{{ $page.props.flash.success }}</p>
            </div>
            <div v-if="$page.props.flash?.error" class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
              <p>{{ $page.props.flash.error }}</p>
            </div>

            <!-- Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
              <div class="bg-blue-50 p-4 rounded-lg">
                <h3 class="text-sm font-medium text-blue-800">Total USD Exchanged</h3>
                <p class="text-2xl font-bold text-blue-900">${{ formatNumber(summary.total_usd_exchanged) }}</p>
              </div>
              <div class="bg-green-50 p-4 rounded-lg">
                <h3 class="text-sm font-medium text-green-800">Total COP Received</h3>
                <p class="text-2xl font-bold text-green-900">${{ formatNumber(summary.total_cop_received) }}</p>
              </div>
              <div class="bg-purple-50 p-4 rounded-lg">
                <h3 class="text-sm font-medium text-purple-800">Average Rate</h3>
                <p class="text-2xl font-bold text-purple-900">${{ formatNumber(summary.average_rate) }}</p>
              </div>
            </div>

            <!-- New Exchange Form -->
            <div class="mb-8 p-4 bg-gray-50 rounded-lg">
              <h2 class="text-lg font-medium mb-4">Record New Exchange</h2>
              <form @submit.prevent="submitForm" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label class="block text-sm font-medium text-gray-700">Exchange Date</label>
                    <input
                      type="date"
                      v-model="form.exchange_date"
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                      required
                    />
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700">Exchange Rate</label>
                    <input
                      type="number"
                      step="0.0001"
                      v-model="form.exchange_rate"
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                      required
                    />
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700">USD Amount</label>
                    <input
                      type="number"
                      step="0.01"
                      v-model="form.usd_amount"
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                      required
                    />
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700">COP Amount</label>
                    <input
                      type="number"
                      step="0.01"
                      v-model="form.cop_amount"
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                      required
                    />
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700">Bank Fee (USD)</label>
                    <input
                      type="number"
                      step="0.01"
                      v-model="form.bank_fee_usd"
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    />
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700">Bank Fee (COP)</label>
                    <input
                      type="number"
                      step="0.01"
                      v-model="form.bank_fee_cop"
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    />
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700">Bank Name</label>
                    <input
                      type="text"
                      v-model="form.bank_name"
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    />
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700">Bank Reference</label>
                    <input
                      type="text"
                      v-model="form.bank_reference"
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    />
                  </div>
                  <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700">Notes</label>
                    <textarea
                      v-model="form.notes"
                      rows="3"
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    ></textarea>
                  </div>
                </div>
                <div class="flex justify-end">
                  <button
                    type="submit"
                    class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                  >
                    Record Exchange
                  </button>
                </div>
              </form>
            </div>

            <!-- Exchange History -->
            <div>
              <div class="flex justify-between items-center mb-4">
                <h2 class="text-lg font-medium">Exchange History</h2>
                <div class="flex space-x-2">
                  <button
                    @click="checkXeroConnection"
                    class="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                    :disabled="checkingConnection"
                  >
                    <span v-if="checkingConnection">Checking...</span>
                    <span v-else>Check Xero Connection</span>
                  </button>
                  <button
                    v-if="hasFailedSyncs"
                    @click="retryFailedSyncs"
                    class="bg-yellow-500 text-white px-4 py-2 rounded-md hover:bg-yellow-600 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:ring-offset-2"
                  >
                    Retry Failed Xero Syncs
                  </button>
                </div>
              </div>
              <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                  <thead class="bg-gray-50">
                    <tr>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">USD Amount</th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">COP Amount</th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rate</th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Bank Fees</th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reference</th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Xero Status</th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                  </thead>
                  <tbody class="bg-white divide-y divide-gray-200">
                    <tr v-for="exchange in exchanges.data" :key="exchange.id">
                      <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ formatDate(exchange.exchange_date) }}
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        ${{ formatNumber(exchange.usd_amount) }}
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        ${{ formatNumber(exchange.cop_amount) }}
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        ${{ formatNumber(exchange.exchange_rate) }}
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        <span v-if="exchange.bank_fee_usd || exchange.bank_fee_cop">
                          USD: ${{ formatNumber(exchange.bank_fee_usd) }}<br>
                          COP: ${{ formatNumber(exchange.bank_fee_cop) }}
                        </span>
                        <span v-else>-</span>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ exchange.bank_reference || '-' }}
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap text-sm">
                        <div class="flex items-center">
                          <span
                            :class="{
                              'text-green-600': exchange.xero_synced,
                              'text-yellow-600': !exchange.xero_synced && !exchange.xero_sync_error,
                              'text-red-600': !exchange.xero_synced && exchange.xero_sync_error
                            }"
                          >
                            {{ xeroSyncStatus(exchange) }}
                          </span>
                          <span 
                            v-if="exchange.xero_reference" 
                            class="ml-2 bg-green-100 text-green-800 text-xs px-2 py-1 rounded-full"
                            title="Xero Reference ID"
                          >
                            ID: {{ exchange.xero_reference.substring(0, 8) }}...
                          </span>
                        </div>
                        <div v-if="exchange.xero_sync_error" class="text-xs text-red-500 mt-1">
                          {{ exchange.xero_sync_error }}
                        </div>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        <button
                          v-if="!exchange.xero_synced"
                          @click="syncWithXero(exchange.id)"
                          class="bg-blue-500 text-white px-2 py-1 rounded text-xs hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                          :disabled="syncingExchange === exchange.id"
                        >
                          <span v-if="syncingExchange === exchange.id">Syncing...</span>
                          <span v-else>Sync to Xero</span>
                        </button>
                        <span v-else class="text-sm text-gray-500">
                          Synced {{ formatDate(exchange.xero_sync_date) }}
                        </span>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="mt-4">
                <Pagination :links="exchanges.links" />
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Pagination from '@/components/Pagination.vue';

interface Exchange {
  id: number;
  exchange_date: string;
  usd_amount: number;
  cop_amount: number;
  exchange_rate: number;
  bank_fee_usd: number;
  bank_fee_cop: number;
  bank_reference: string | null;
  notes: string | null;
  xero_status: string;
  xero_bill_id: string | null;
  created_at: string;
  updated_at: string;
  xero_synced: boolean;
  xero_sync_error: string | null;
  xero_sync_date: string | null;
  xero_reference: string | null;
}

interface Summary {
  total_usd_exchanged: number;
  total_cop_received: number;
  total_bank_fees_usd: number;
  total_bank_fees_cop: number;
  average_rate: number;
  average_effective_rate: number;
}

const props = defineProps<{
  exchanges: {
    data: Exchange[];
    current_page: number;
    last_page: number;
    total: number;
    links: any[];
  };
  summary: Summary;
}>();

const form = useForm({
  exchange_date: new Date().toISOString().split('T')[0],
  usd_amount: 0,
  cop_amount: 0,
  exchange_rate: 0,
  bank_fee_usd: 0,
  bank_fee_cop: 0,
  bank_name: '',
  bank_reference: '',
  notes: ''
});

const submitForm = () => {
  form.post('/currency-exchanges', {
    onSuccess: () => {
      form.reset();
    }
  });
};

const formatNumber = (number: number) => {
  return new Intl.NumberFormat('en-US', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  }).format(number);
};

const formatDate = (date: string) => {
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  });
};

const hasFailedSyncs = computed(() => {
  return props.exchanges.data.some(exchange => exchange.xero_status === 'failed');
});

const retryFailedSyncs = () => {
  router.post('/currency-exchanges/retry-syncs', {}, {
    onSuccess: () => {
      // Refresh the page to show updated status
      router.reload();
    }
  });
};

const xeroSyncStatus = (exchange: Exchange): string => {
  if (exchange.xero_synced) {
    return 'Synced';
  } else if (exchange.xero_sync_error) {
    return 'Failed';
  } else {
    return 'Pending';
  }
};

const syncingExchange = ref<number | null>(null);

const syncWithXero = (exchangeId: number) => {
  syncingExchange.value = exchangeId;
  router.post(`/currency-exchanges/${exchangeId}/sync-with-xero`, {}, {
    onSuccess: () => {
      syncingExchange.value = null;
    },
    onError: () => {
      syncingExchange.value = null;
    }
  });
};

const checkingConnection = ref(false);

const checkXeroConnection = () => {
  checkingConnection.value = true;
  router.post('/currency-exchanges/check-xero-connection', {}, {
    onSuccess: () => {
      checkingConnection.value = false;
    },
    onError: () => {
      checkingConnection.value = false;
    }
  });
};
</script> 