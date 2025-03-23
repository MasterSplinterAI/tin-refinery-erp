<template>
  <AuthenticatedLayout>
    <template #header>
      <div class="flex justify-between items-center">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
          Tin Refinery Management
        </h2>
        <button
          @click="showForm = true"
          class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
        >
          Create New Batch
        </button>
      </div>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6 bg-white border-b border-gray-200">
            <BatchList
              v-if="!showForm"
              :batches="batches"
              @edit="handleEdit"
              @delete="handleDelete"
              @statusChange="handleStatusChange"
            />

            <BatchForm
              v-else
              :existingBatch="selectedBatch"
              @submit="handleSubmit"
              @cancel="handleCancel"
            />
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup lang="ts">
import { ref, onMounted } from "vue";
import { router } from "@inertiajs/vue3";
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import BatchList from '@/components/BatchList.vue';
import BatchForm from '@/components/BatchForm.vue';
import type { Batch } from '@/types/batch';
import { route } from "ziggy-js";

const props = defineProps<{
  batches: Batch[]
}>();

const showForm = ref(false);
const selectedBatch = ref<Batch | undefined>(undefined);

const handleSubmit = async (data: any) => {
  try {
    const formData = {
      batchNumber: data.batchNumber,
      date: data.date,
      status: data.status,
      notes: data.notes,
      processes: data.processes
    };

    const method = selectedBatch.value ? 'put' : 'post';
    const url = selectedBatch.value
      ? route('api.batches.update', { batch: selectedBatch.value.id })
      : route('api.batches.store');
    
    console.log('Making request to:', url, 'with method:', method);
    
    router.visit(url, {
      method,
      data: formData,
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      },
      preserveState: true,
      preserveScroll: true,
      onSuccess: () => {
        window.location.reload();
      },
      onError: (errors: any) => {
        console.error('Error saving batch:', errors);
        if (errors?.response?.status === 401) {
          window.location.href = '/login';
        }
      }
    });
  } catch (error) {
    console.error('Error saving batch:', error);
  }
};

const handleEdit = (batch: Batch) => {
  selectedBatch.value = batch;
  showForm.value = true;
};

const handleDelete = async (batchId: number) => {
  try {
    router.delete(route('api.batches.destroy', { batch: batchId }), {
      onSuccess: () => {
        window.location.reload();
      },
      onError: (errors: any) => {
        console.error('Error deleting batch:', errors);
        if (errors?.response?.status === 401) {
          window.location.href = '/login';
        }
      }
    });
  } catch (error) {
    console.error('Error deleting batch:', error);
  }
};

const handleStatusChange = async (batchId: number, newStatus: Batch['status']) => {
  try {
    const url = route('api.batches.update-status', { batch: batchId });
    
    router.visit(url, {
      method: 'put',
      data: { status: newStatus },
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      },
      preserveState: true,
      preserveScroll: true,
      onSuccess: () => {
        window.location.reload();
      },
      onError: (errors: any) => {
        console.error('Error updating batch status:', errors);
        if (errors?.response?.status === 401) {
          window.location.href = '/login';
        }
      }
    });
  } catch (error) {
    console.error('Error updating batch status:', error);
  }
};

const handleCancel = () => {
  showForm.value = false;
  selectedBatch.value = undefined;
};
</script> 