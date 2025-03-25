<template>
  <div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-200">
      <thead class="bg-gray-50">
        <tr>
          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
            Batch Number
          </th>
          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
            Date
          </th>
          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
            Processes
          </th>
          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
            Status
          </th>
          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
            Actions
          </th>
        </tr>
      </thead>
      <tbody class="bg-white divide-y divide-gray-200">
        <tr v-for="batch in batches" :key="batch.id">
          <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
            {{ batch.batchNumber }}
          </td>
          <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
            {{ formatDate(batch.date) }}
          </td>
          <td class="px-6 py-4 text-sm text-gray-900">
            {{ batch.processes.length }} Process{{ batch.processes.length !== 1 ? 'es' : '' }}
          </td>
          <td class="px-6 py-4 whitespace-nowrap">
            <span :class="['px-2 inline-flex text-xs leading-5 font-semibold rounded-full', getStatusColor(batch.status)]">
              {{ getStatusText(batch.status) }}
            </span>
          </td>
          <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
            <div class="space-x-2">
              <button
                @click="$emit('edit', batch)"
                class="text-blue-600 hover:text-blue-900"
              >
                Edit
              </button>
              <template v-if="batch.status === 'in_progress'">
                <button
                  @click="$emit('statusChange', batch.id, 'completed')"
                  class="text-green-600 hover:text-green-900"
                >
                  Complete
                </button>
                <button
                  @click="$emit('statusChange', batch.id, 'cancelled')"
                  class="text-red-600 hover:text-red-900"
                >
                  Cancel
                </button>
              </template>
              <template v-else-if="batch.status === 'completed'">
                <button
                  @click="$emit('statusChange', batch.id, 'in_progress')"
                  class="text-blue-600 hover:text-blue-900"
                >
                  Reopen
                </button>
              </template>
              <button
                @click="confirmDelete(batch)"
                class="text-red-600 hover:text-red-900"
              >
                Delete
              </button>
            </div>
          </td>
        </tr>
      </tbody>
    </table>
  </div>

  <!-- Delete Confirmation Modal -->
  <div v-if="deleteConfirmBatch" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
      <div class="mt-3 text-center">
        <h3 class="text-lg leading-6 font-medium text-gray-900">Delete Batch</h3>
        <div class="mt-2 px-7 py-3">
          <p class="text-sm text-gray-500">
            Are you sure you want to delete batch {{ deleteConfirmBatch.batchNumber }}?
          </p>
        </div>
        <div class="items-center px-4 py-3">
          <button
            @click="handleDeleteConfirm"
            class="px-4 py-2 bg-red-500 text-white text-base font-medium rounded-md w-full shadow-sm hover:bg-red-600 focus:outline-none focus:ring-2 focus:ring-red-500"
          >
            Delete
          </button>
          <button
            @click="deleteConfirmBatch = null"
            class="mt-2 px-4 py-2 bg-gray-500 text-white text-base font-medium rounded-md w-full shadow-sm hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-500"
          >
            Cancel
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import type { Batch } from '@/types/batch';

const props = defineProps<{
  batches: Batch[]
}>();

const emit = defineEmits<{
  (e: 'statusChange', batchId: number, newStatus: Batch['status']): void
  (e: 'edit', batch: Batch): void
  (e: 'delete', batchId: number): void
}>();

const deleteConfirmBatch = ref<Batch | null>(null);

const formatDate = (dateString: string) => {
  // Create a date object in ET timezone
  const date = new Date(dateString);
  const options: Intl.DateTimeFormatOptions = { 
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    timeZone: 'America/New_York'
  };
  
  // Format the date in US format (MM/DD/YYYY)
  return date.toLocaleDateString('en-US', options);
};

const getStatusColor = (status: Batch['status']) => {
  switch (status) {
    case 'completed':
      return 'bg-green-100 text-green-800';
    case 'in_progress':
      return 'bg-blue-100 text-blue-800';
    case 'cancelled':
      return 'bg-red-100 text-red-800';
    default:
      return 'bg-gray-100 text-gray-800';
  }
};

const getStatusText = (status: Batch['status']) => {
  switch (status) {
    case 'completed':
      return 'Completed';
    case 'in_progress':
      return 'In Progress';
    case 'cancelled':
      return 'Cancelled';
    default:
      return status;
  }
};

const confirmDelete = (batch: Batch) => {
  deleteConfirmBatch.value = batch;
};

const handleDeleteConfirm = () => {
  if (deleteConfirmBatch.value?.id) {
    emit('delete', deleteConfirmBatch.value.id);
    deleteConfirmBatch.value = null;
  }
};
</script>