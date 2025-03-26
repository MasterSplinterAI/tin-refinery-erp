<template>
  <AuthenticatedLayout>
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        Chart of Accounts Settings
      </h2>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6 bg-white border-b border-gray-200">
            <!-- Notification area -->
            <div v-if="($page.props.flash as any)?.success" class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
              <p>{{ ($page.props.flash as any).success }}</p>
            </div>
            <div v-if="($page.props.flash as any)?.error" class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
              <p>{{ ($page.props.flash as any).error }}</p>
            </div>
            <div v-if="errorMessage" class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
              <p>{{ errorMessage }}</p>
            </div>

            <!-- Debug Info -->
            <div class="mb-6 bg-gray-100 p-4 rounded border">
              <h3 class="font-bold mb-2">Debug Information:</h3>
              <p>Accounts loaded: {{ chartOfAccounts.length }}</p>
              <p>Mappings loaded: {{ mappings.length }}</p>
              <details class="mt-2">
                <summary class="cursor-pointer text-blue-600">View Chart of Accounts Data</summary>
                <pre class="mt-2 bg-gray-800 text-white p-2 rounded text-xs overflow-auto max-h-40">{{ JSON.stringify(chartOfAccounts, null, 2) }}</pre>
              </details>
              <details class="mt-2">
                <summary class="cursor-pointer text-blue-600">View Mappings Data</summary>
                <pre class="mt-2 bg-gray-800 text-white p-2 rounded text-xs overflow-auto max-h-40">{{ JSON.stringify(mappings, null, 2) }}</pre>
              </details>
            </div>

            <!-- Sync Button -->
            <div class="mb-6 flex space-x-4">
              <button
                @click="syncChartOfAccounts"
                class="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                :disabled="syncing"
              >
                <span v-if="syncing">Syncing...</span>
                <span v-else>Sync Xero Chart of Accounts</span>
              </button>
              
              <button
                @click="createNewMapping"
                class="bg-green-500 text-white px-4 py-2 rounded-md hover:bg-green-600 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2"
              >
                Create New Mapping
              </button>
            </div>

            <!-- Mappings Table -->
            <div class="overflow-x-auto">
              <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                  <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Module</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Transaction Type</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Xero Account</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                  </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                  <tr v-if="mappings.length === 0">
                    <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500">
                      No account mappings found. Click "Create New Mapping" to add one.
                    </td>
                  </tr>
                  <tr v-for="mapping in mappings" :key="mapping.id">
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                      {{ getModuleName(mapping.module) }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                      {{ mapping.transaction_type || '-' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                      {{ mapping.xero_account_name }} ({{ mapping.xero_account_code }})
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                      <button
                        @click="editMapping(mapping)"
                        class="text-blue-600 hover:text-blue-900 mr-3"
                      >
                        Edit
                      </button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Edit/Add Mapping Modal -->
            <Modal :show="showMappingModal" @close="closeMappingModal">
              <div class="p-6">
                <h2 class="text-lg font-medium mb-4">
                  {{ editingMapping ? 'Edit' : 'Add' }} Account Mapping
                </h2>
                <form @submit.prevent="submitMapping" class="space-y-4">
                  <div>
                    <label class="block text-sm font-medium text-gray-700">Module</label>
                    <select
                      v-model="form.module"
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                      required
                    >
                      <option value="">Select a module</option>
                      <option value="CurrencyExchange">Currency Exchange</option>
                      <option value="Inventory">Inventory</option>
                      <option value="Purchase">Purchase</option>
                      <option value="Sales">Sales</option>
                    </select>
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700">Transaction Type</label>
                    <select
                      v-model="form.transaction_type"
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    >
                      <option value="">Select a transaction type</option>
                      <template v-if="form.module === 'CurrencyExchange'">
                        <option value="UsdAccount">USD Bank Account</option>
                        <option value="CopAccount">COP Bank Account</option>
                        <option value="BankFees">Bank Fees</option>
                        <option value="MainAccount">Main Bank Account</option>
                        <option value="Clearing">Clearing Account</option>
                      </template>
                      <template v-else-if="form.module === 'Inventory'">
                        <option value="Asset">Inventory Asset</option>
                        <option value="COGS">Cost of Goods Sold</option>
                      </template>
                      <template v-else-if="form.module === 'Purchase'">
                        <option value="Expense">Purchase Expense</option>
                        <option value="Liability">Accounts Payable</option>
                      </template>
                      <template v-else-if="form.module === 'Sales'">
                        <option value="Revenue">Sales Revenue</option>
                        <option value="Receivable">Accounts Receivable</option>
                      </template>
                    </select>
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700">Xero Account</label>
                    <select
                      v-model="form.xero_account_code"
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                      required
                      @change="updateAccountName"
                    >
                      <option value="">Select an account</option>
                      <option 
                        v-for="account in chartOfAccounts" 
                        :key="account.code" 
                        :value="account.code"
                      >
                        {{ account.name }} ({{ account.code }})
                      </option>
                    </select>
                  </div>
                  <div class="flex justify-end space-x-3">
                    <button
                      type="button"
                      @click="closeMappingModal"
                      class="bg-gray-100 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-200"
                    >
                      Cancel
                    </button>
                    <button
                      type="submit"
                      class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700"
                      :disabled="form.processing"
                    >
                      {{ form.processing ? 'Saving...' : 'Save' }}
                    </button>
                  </div>
                </form>
              </div>
            </Modal>
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/components/Modal.vue';
import axios from 'axios';

interface ChartOfAccount {
  code: string;
  name: string;
  type: string;
  status: string;
}

interface Module {
  id: number;
  name: string;
  description: string;
}

interface AccountMapping {
  id: number;
  module: string | Module;
  module_id?: number;
  transaction_type: string | null;
  xero_account_code: string;
  xero_account_name: string;
}

const props = defineProps<{
  mappings: AccountMapping[];
  chartOfAccounts: ChartOfAccount[];
  errorMessage?: string | null;
}>();

const showMappingModal = ref(false);
const editingMapping = ref<AccountMapping | null>(null);
const syncing = ref(false);

const form = useForm({
  id: null as number | null,
  module: '',
  transaction_type: '',
  xero_account_code: '',
  xero_account_name: '',
});

const syncChartOfAccounts = async () => {
  syncing.value = true;
  try {
    const response = await axios.get(route('api.settings.chart-of-accounts'));
    console.log('Sync response:', response.data);
    alert('Successfully synced chart of accounts. Please refresh the page to see updated accounts.');
    // Reload the page to show the updated data
    window.location.reload();
  } catch (error) {
    console.error('Failed to sync chart of accounts:', error);
    const errorMsg = error.response?.data?.error || 'Unknown error occurred';
    console.log('Error details:', errorMsg);
    alert('Failed to sync: ' + errorMsg);
  } finally {
    syncing.value = false;
  }
};

const editMapping = (mapping: AccountMapping) => {
  editingMapping.value = mapping;
  form.module = mapping.module;
  form.transaction_type = mapping.transaction_type || '';
  form.xero_account_code = mapping.xero_account_code;
  form.xero_account_name = mapping.xero_account_name || '';
  showMappingModal.value = true;
};

const closeMappingModal = () => {
  showMappingModal.value = false;
  editingMapping.value = null;
  form.reset();
};

const submitMapping = () => {
  // Log what we're submitting
  console.log("Submitting mapping:", form.data());
  
  // Make sure account name is set
  if (form.xero_account_code && !form.xero_account_name) {
    const selectedAccount = props.chartOfAccounts.find(account => account.code === form.xero_account_code);
    if (selectedAccount) {
      form.xero_account_name = selectedAccount.name;
    }
  }
  
  // If we're editing an existing mapping, include the ID
  if (editingMapping.value?.id) {
    form.id = editingMapping.value.id;
  }
  
  form.post(route('api.settings.update-mapping'), {
    preserveScroll: true,
    onSuccess: () => {
      closeMappingModal();
      // No need to reload manually, Inertia will handle the redirect
    },
    onError: (errors) => {
      console.error("Form submission errors:", errors);
      alert("Error saving mapping: " + Object.values(errors).join(", "));
    }
  });
};

const updateAccountName = () => {
  const selectedAccount = props.chartOfAccounts.find(account => account.code === form.xero_account_code);
  if (selectedAccount) {
    form.xero_account_name = selectedAccount.name;
  }
};

const createNewMapping = () => {
  editingMapping.value = null;
  form.reset();
  showMappingModal.value = true;
};

// Add a function to extract the module name
const getModuleName = (module: string | Module | null) => {
  if (!module) return '';
  if (typeof module === 'string') return module;
  return module.name || '';
};
</script> 