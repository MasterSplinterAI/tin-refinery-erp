<template>
  <div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-8">
      <h1 class="text-3xl font-bold">{{ t('batchManagement') }}</h1>
      <LanguageSwitcher />
    </div>

    <div v-if="showForm" class="mb-8">
      <BatchForm
        :existing-batch="selectedBatch"
        :existing-batches="batches"
        @submit="handleBatchSubmit"
      />
    </div>
    <div v-else class="mb-8">
      <button
        @click="showForm = true"
        class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500"
      >
        {{ t('newBatch') }}
      </button>
    </div>

    <BatchList
      :batches="batches"
      @status-change="handleStatusChange"
      @edit="handleEdit"
      @delete="handleDelete"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useLanguage } from '@/composables/useLanguage';
import { router } from '@inertiajs/vue3';
import BatchForm from '@/components/BatchForm.vue';
import BatchList from '@/components/BatchList.vue';
import LanguageSwitcher from '@/components/LanguageSwitcher.vue';
import type { Batch } from '@/types/batch';

interface BatchProcess {
  processNumber: number;
  processingType: 'kaldo_furnace' | 'refining_kettle';
  inputTinKilos: number;
  inputTinSnContent: number;
  inputTinInventoryItemId: number | null;
  outputTinKilos: number;
  outputTinSnContent: number;
  outputTinInventoryItemId: number | null;
  inputSlagKilos: number;
  inputSlagSnContent: number;
  inputSlagInventoryItemId: number | null;
  outputSlagKilos: number;
  outputSlagSnContent: number;
  outputSlagInventoryItemId: number | null;
  notes: string;
}

interface BatchData {
  id?: number;
  batchNumber: string;
  date: string;
  status: 'in_progress' | 'completed' | 'cancelled';
  notes: string;
  processes: BatchProcess[];
}

const { t } = useLanguage();
const batches = ref<Batch[]>([]);
const showForm = ref(false);
const selectedBatch = ref<Batch | undefined>();

onMounted(async () => {
  await fetchBatches();
});

const fetchBatches = async () => {
  try {
    const response = await fetch(route('api.batches.index'), {
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      }
    });
    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }
    const data = await response.json();
    batches.value = data;
  } catch (error) {
    console.error('Error fetching batches:', error);
  }
};

const handleBatchSubmit = async (batchData: BatchData) => {
  try {
    console.log('Submitting batch data:', batchData);
    
    const method = selectedBatch.value ? 'put' : 'post';
    const url = selectedBatch.value 
      ? route('api.batches.update', { batch: selectedBatch.value.id })
      : route('api.batches.store');
    
    console.log('Making request to:', url, 'with method:', method);
    
    const payload = {
      batchNumber: batchData.batchNumber,
      date: batchData.date,
      status: batchData.status,
      notes: batchData.notes,
      processes: batchData.processes.map(process => ({
        processNumber: process.processNumber,
        processingType: process.processingType,
        inputTinKilos: process.inputTinKilos,
        inputTinSnContent: process.inputTinSnContent,
        inputTinInventoryItemId: process.inputTinInventoryItemId,
        outputTinKilos: process.outputTinKilos,
        outputTinSnContent: process.outputTinSnContent,
        outputTinInventoryItemId: process.outputTinInventoryItemId,
        inputSlagKilos: process.inputSlagKilos,
        inputSlagSnContent: process.inputSlagSnContent,
        inputSlagInventoryItemId: process.inputSlagInventoryItemId,
        outputSlagKilos: process.outputSlagKilos,
        outputSlagSnContent: process.outputSlagSnContent,
        outputSlagInventoryItemId: process.outputSlagInventoryItemId,
        notes: process.notes
      }))
    };
    
    router.visit(url, {
      method,
      data: payload,
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      },
      preserveState: true,
      preserveScroll: true,
      onSuccess: () => {
        fetchBatches();
        showForm.value = false;
        selectedBatch.value = undefined;
      },
      onError: (errors) => {
        console.error('Error saving batch:', errors);
        if (errors?.response?.status === 401) {
          // Redirect to login page if unauthorized
          router.visit(route('login'));
        }
      }
    });
  } catch (error) {
    console.error('Error saving batch:', error);
  }
};

const handleStatusChange = async (batchId: number, newStatus: Batch['status']) => {
  try {
    await router.put(route('api.batches.update-status', { batch: batchId }), {
      status: newStatus
    });
    await fetchBatches();
  } catch (error) {
    console.error('Error updating batch status:', error);
  }
};

const handleEdit = (batch: Batch) => {
  selectedBatch.value = batch;
  showForm.value = true;
};

const handleDelete = async (batchId: number) => {
  try {
    await router.delete(route('api.batches.destroy', { batch: batchId }));
    await fetchBatches();
  } catch (error) {
    console.error('Error deleting batch:', error);
  }
};
</script> 