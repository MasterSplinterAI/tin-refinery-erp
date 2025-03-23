<template>
  <div>
    <!-- Filters -->
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6 p-6">
      <div class="flex gap-4">
        <div class="flex-1">
          <label class="block text-sm font-medium text-gray-700">Type</label>
          <select v-model="filters.type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            <option value="">All Types</option>
            <option value="cassiterite">Cassiterite</option>
            <option value="ingot">Ingot</option>
            <option value="finished_tin">Finished Tin</option>
            <option value="slag">Slag</option>
          </select>
        </div>
        <div class="flex-1">
          <label class="block text-sm font-medium text-gray-700">Status</label>
          <select v-model="filters.status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            <option value="">All Status</option>
            <option value="active">Active</option>
            <option value="archived">Archived</option>
          </select>
        </div>
      </div>
    </div>

    <!-- Inventory Items Table -->
    <div class="overflow-x-auto">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Unit</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sn Content</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Location</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-for="item in filteredItems" :key="item.id" class="hover:bg-gray-50">
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ item.name }}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ item.type }}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ formatQuantity(item.quantity) }}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ item.unit }}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ item.sn_content }}%</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ item.location }}</td>
            <td class="px-6 py-4 whitespace-nowrap">
              <span :class="getStatusClass(item.status)" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full">
                {{ item.status }}
              </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
              <button
                @click="$emit('adjust', item)"
                class="text-green-600 hover:text-green-900"
                title="Make Adjustment"
              >
                Adjust
              </button>
              <button
                @click="handleHistory(item)"
                class="text-blue-600 hover:text-blue-900"
                title="View History"
              >
                History
              </button>
              <button
                @click="$emit('edit', item)"
                class="text-indigo-600 hover:text-indigo-900"
              >
                Edit
              </button>
              <button
                @click="$emit('delete', item.id)"
                class="text-red-600 hover:text-red-900"
              >
                Delete
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Transaction History Modal -->
    <TransitionRoot appear :show="showHistory" as="template">
      <Dialog as="div" @close="closeHistory" class="relative z-10">
        <TransitionChild
          enter="ease-out duration-300"
          enter-from="opacity-0"
          enter-to="opacity-100"
          leave="ease-in duration-200"
          leave-from="opacity-100"
          leave-to="opacity-0"
        >
          <div class="fixed inset-0 bg-black bg-opacity-25" />
        </TransitionChild>

        <div class="fixed inset-0 overflow-y-auto">
          <div class="flex min-h-full items-center justify-center p-4 text-center">
            <TransitionChild
              enter="ease-out duration-300"
              enter-from="opacity-0 scale-95"
              enter-to="opacity-100 scale-100"
              leave="ease-in duration-200"
              leave-from="opacity-100 scale-100"
              leave-to="opacity-0 scale-95"
            >
              <DialogPanel class="w-full max-w-4xl transform overflow-hidden rounded-2xl bg-white p-6 text-left align-middle shadow-xl transition-all">
                <DialogTitle as="h3" class="text-lg font-medium leading-6 text-gray-900">
                  Transaction History - {{ selectedItem?.name }}
                </DialogTitle>
                <div class="mt-4">
                  <TransactionHistory 
                    :transactions="filteredTransactions" 
                    :show-item-name="false"
                  />
                </div>
                <div class="mt-4 flex justify-end">
                  <button
                    type="button"
                    class="inline-flex justify-center rounded-md border border-transparent bg-gray-100 px-4 py-2 text-sm font-medium text-gray-900 hover:bg-gray-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-gray-500 focus-visible:ring-offset-2"
                    @click="closeHistory"
                  >
                    Close
                  </button>
                </div>
              </DialogPanel>
            </TransitionChild>
          </div>
        </div>
      </Dialog>
    </TransitionRoot>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { Dialog, DialogPanel, DialogTitle, TransitionRoot, TransitionChild } from '@headlessui/vue';
import TransactionHistory from './TransactionHistory.vue';
import type { InventoryItem, Transaction } from '@/types/inventory';

const props = defineProps<{
  items: InventoryItem[],
  transactions?: Transaction[]
}>();

const emit = defineEmits<{
  (e: 'edit', item: InventoryItem): void
  (e: 'delete', id: number): void
  (e: 'adjust', item: InventoryItem): void
  (e: 'history', item: InventoryItem): void
}>();

const showHistory = ref(false);
const selectedItem = ref<InventoryItem | null>(null);

const filters = ref({
  type: '',
  status: ''
});

const filteredItems = computed(() => {
  return props.items.filter(item => {
    if (filters.value.type && item.type !== filters.value.type) return false;
    if (filters.value.status && item.status !== filters.value.status) return false;
    return true;
  });
});

const filteredTransactions = computed(() => {
  if (!selectedItem.value || !props.transactions) return [];
  return props.transactions.filter(t => t.inventory_item_id === selectedItem.value?.id);
});

const formatQuantity = (quantity: number) => {
  return quantity.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

const getStatusClass = (status: string) => {
  return {
    active: 'bg-green-100 text-green-800',
    inactive: 'bg-gray-100 text-gray-800'
  }[status] || 'bg-gray-100 text-gray-800';
};

const handleHistory = (item: InventoryItem) => {
  selectedItem.value = item;
  showHistory.value = true;
  emit('history', item);
};

const closeHistory = () => {
  showHistory.value = false;
  selectedItem.value = null;
};
</script> 