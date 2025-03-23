<template>
  <form @submit.prevent="handleSubmit" class="space-y-6">
    <div>
      <label class="block text-sm font-medium text-gray-700">Batch Number</label>
      <div class="mt-1">
        <input
          type="text"
          v-model="formData.batchNumber"
          class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md"
          :readonly="!!existingBatch"
        />
      </div>
    </div>

    <div>
      <label class="block text-sm font-medium text-gray-700">Date</label>
      <div class="mt-1">
        <input
          type="date"
          v-model="formData.date"
          class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md"
        />
      </div>
    </div>

    <div>
      <label class="block text-sm font-medium text-gray-700">Notes</label>
      <div class="mt-1">
        <textarea
          v-model="formData.notes"
          rows="3"
          class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md"
        ></textarea>
      </div>
    </div>

    <div class="space-y-4">
      <div class="flex justify-between items-center">
        <h3 class="text-lg font-medium text-gray-900">Processes</h3>
        <button
          type="button"
          @click="addProcess"
          class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
        >
          Add Process
        </button>
      </div>

      <div v-for="(process, index) in formData.processes" :key="index" class="border rounded-lg p-4 space-y-4">
        <div class="flex justify-between items-center">
          <h4 class="text-md font-medium text-gray-900">Process {{ index + 1 }}</h4>
          <button
            type="button"
            @click="removeProcess(index)"
            class="text-red-600 hover:text-red-900"
          >
            Remove
          </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <!-- Processing Type -->
          <div class="space-y-2">
            <label class="block text-sm font-medium text-gray-700">Processing Type</label>
            <select
              v-model="process.processingType"
              class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md"
            >
              <option value="kaldo_furnace">Kaldo Furnace</option>
              <option value="refining_kettle">Refining Kettle</option>
            </select>
          </div>

          <!-- Input Tin Section -->
          <div class="col-span-2 border rounded-lg p-4 bg-gray-50">
            <h5 class="text-sm font-medium text-gray-700 mb-4">Input Tin</h5>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">Quantity (kg)</label>
                <DecimalInput
                  v-model="process.inputTinKilos"
                  :min="0"
                  class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md"
                />
              </div>

              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">Sn Content (%)</label>
                <DecimalInput
                  v-model="process.inputTinSnContent"
                  :min="0"
                  :max="100"
                  class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md"
                />
              </div>

              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">Inventory Item</label>
                <select
                  v-model.number="process.inputTinInventoryItemId"
                  class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md"
                >
                  <option :value="0">Select an item</option>
                  <option v-for="item in inventoryItems" :key="item.id" :value="item.id">
                    {{ item.name }} ({{ item.quantity }} kg, {{ item.sn_content }}% Sn)
                  </option>
                </select>
              </div>
            </div>
          </div>

          <!-- Output Tin Section -->
          <div class="col-span-2 border rounded-lg p-4 bg-gray-50">
            <h5 class="text-sm font-medium text-gray-700 mb-4">Output Tin</h5>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">Quantity (kg)</label>
                <DecimalInput
                  v-model="process.outputTinKilos"
                  :min="0"
                  class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md"
                />
              </div>

              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">Sn Content (%)</label>
                <DecimalInput
                  v-model="process.outputTinSnContent"
                  :min="0"
                  :max="100"
                  class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md"
                />
              </div>

              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">Inventory Item</label>
                <select
                  v-model.number="process.outputTinInventoryItemId"
                  class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md"
                >
                  <option :value="0">Select an item</option>
                  <option v-for="item in inventoryItems" :key="item.id" :value="item.id">
                    {{ item.name }} ({{ item.quantity }} kg, {{ item.sn_content }}% Sn)
                  </option>
                </select>
              </div>
            </div>
          </div>

          <!-- Input Slag Section -->
          <div class="col-span-2 border rounded-lg p-4 bg-gray-50">
            <h5 class="text-sm font-medium text-gray-700 mb-4">Input Slag</h5>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">Quantity (kg)</label>
                <DecimalInput
                  v-model="process.inputSlagKilos"
                  :min="0"
                  class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md"
                />
              </div>

              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">Sn Content (%)</label>
                <DecimalInput
                  v-model="process.inputSlagSnContent"
                  :min="0"
                  :max="100"
                  class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md"
                />
              </div>

              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">Inventory Item</label>
                <select
                  v-model.number="process.inputSlagInventoryItemId"
                  class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md"
                >
                  <option :value="0">Select an item</option>
                  <option v-for="item in inventoryItems" :key="item.id" :value="item.id">
                    {{ item.name }} ({{ item.quantity }} kg, {{ item.sn_content }}% Sn)
                  </option>
                </select>
              </div>
            </div>
          </div>

          <!-- Output Slag Section -->
          <div class="col-span-2 border rounded-lg p-4 bg-gray-50">
            <h5 class="text-sm font-medium text-gray-700 mb-4">Output Slag</h5>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">Quantity (kg)</label>
                <DecimalInput
                  v-model="process.outputSlagKilos"
                  :min="0"
                  class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md"
                />
              </div>

              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">Sn Content (%)</label>
                <DecimalInput
                  v-model="process.outputSlagSnContent"
                  :min="0"
                  :max="100"
                  class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md"
                />
              </div>

              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">Inventory Item</label>
                <select
                  v-model.number="process.outputSlagInventoryItemId"
                  class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md"
                >
                  <option :value="0">Select an item</option>
                  <option v-for="item in inventoryItems" :key="item.id" :value="item.id">
                    {{ item.name }} ({{ item.quantity }} kg, {{ item.sn_content }}% Sn)
                  </option>
                </select>
              </div>
            </div>
          </div>

          <!-- Notes Section -->
          <div class="col-span-2">
            <label class="block text-sm font-medium text-gray-700">Notes</label>
            <textarea
              v-model="process.notes"
              rows="2"
              class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md"
            ></textarea>
          </div>
        </div>
      </div>
    </div>

    <div class="flex justify-end space-x-3">
      <button
        type="button"
        @click="$emit('cancel')"
        class="inline-flex justify-center py-2 px-4 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
      >
        Cancel
      </button>
      <button
        type="submit"
        class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
      >
        {{ existingBatch ? 'Update' : 'Create' }} Batch
      </button>
    </div>
  </form>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import type { Batch, Process, InventoryItem } from '@/types/batch';
import DecimalInput from './DecimalInput.vue';

const props = defineProps<{
  existingBatch?: Batch
}>();

const emit = defineEmits<{
  (e: 'submit', data: Batch): void
  (e: 'cancel'): void
}>();

const createEmptyProcess = (): Process => ({
  processNumber: 1,
  processingType: 'kaldo_furnace',
  inputTinKilos: 0,
  inputTinSnContent: 0,
  inputTinInventoryItemId: 0,
  outputTinKilos: 0,
  outputTinSnContent: 0,
  outputTinInventoryItemId: 0,
  inputSlagKilos: 0,
  inputSlagSnContent: 0,
  inputSlagInventoryItemId: 0,
  outputSlagKilos: 0,
  outputSlagSnContent: 0,
  outputSlagInventoryItemId: 0,
  notes: ''
});

const formData = ref<Batch>({
  batchNumber: '',
  date: new Date().toISOString().split('T')[0],
  status: 'in_progress',
  notes: '',
  processes: [createEmptyProcess()]
});

const inventoryItems = ref<InventoryItem[]>([]);

const fetchInventoryItems = async () => {
  try {
    const response = await fetch('/api/inventory', {
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'Content-Type': 'application/json'
      },
      credentials: 'same-origin'
    });
    
    if (!response.ok) {
      const errorText = await response.text();
      console.error('Error response:', errorText);
      throw new Error(`HTTP error! status: ${response.status}`);
    }
    
    const data = await response.json();
    console.log('Raw API response:', data);
    
    // Extract items from the response
    const items = data.items || data;
    inventoryItems.value = Array.isArray(items) ? items.filter(item => item.status === 'active') : [];
    console.log('Filtered inventory items:', inventoryItems.value);
  } catch (error) {
    console.error('Error fetching inventory items:', error);
    inventoryItems.value = []; // Set empty array on error
  }
};

const addProcess = () => {
  const newProcess = createEmptyProcess();
  newProcess.processNumber = formData.value.processes.length + 1;
  formData.value.processes.push(newProcess);
};

const removeProcess = (index: number) => {
  if (formData.value.processes.length > 1) {
    formData.value.processes.splice(index, 1);
    formData.value.processes.forEach((process, idx) => {
      process.processNumber = idx + 1;
    });
  }
};

const generateBatchNumber = async () => {
  if (!formData.value.batchNumber) {
    try {
      const response = await fetch('/api/batches/next-number');
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }
      const data = await response.json();
      if (!data.nextNumber) {
        throw new Error('Invalid response format');
      }
      
      const date = new Date();
      const year = date.getFullYear().toString().slice(-2);
      const month = (date.getMonth() + 1).toString().padStart(2, '0');
      const day = date.getDate().toString().padStart(2, '0');
      const sequentialNumber = data.nextNumber.toString().padStart(3, '0');
      
      formData.value.batchNumber = `${day}${month}${year}-${sequentialNumber}`;
      console.log('Generated batch number:', formData.value.batchNumber);
    } catch (error) {
      console.error('Error generating batch number:', error);
      // Generate a fallback batch number
      const date = new Date();
      const year = date.getFullYear().toString().slice(-2);
      const month = (date.getMonth() + 1).toString().padStart(2, '0');
      const day = date.getDate().toString().padStart(2, '0');
      const timestamp = Date.now().toString().slice(-3);
      formData.value.batchNumber = `${day}${month}${year}-${timestamp}`;
      console.log('Generated fallback batch number:', formData.value.batchNumber);
    }
  }
};

const validateAndUpdateNumber = (event: Event, process: Process, field: keyof Process, isSnContent = false) => {
  const input = event.target as HTMLInputElement;
  let value = input.value.replace(',', '.');
  
  // Allow empty input
  if (!value) {
    process[field] = 0;
    return;
  }

  // Remove any non-numeric characters except decimal point
  value = value.replace(/[^\d.]/g, '');
  
  // Ensure only one decimal point
  const parts = value.split('.');
  if (parts.length > 2) {
    value = parts[0] + '.' + parts.slice(1).join('');
  }
  
  const numValue = parseFloat(value);
  
  if (isSnContent) {
    // For Sn content fields (0-100)
    if (!isNaN(numValue)) {
      if (numValue > 100) process[field] = 100;
      else if (numValue < 0) process[field] = 0;
      else process[field] = numValue;
    }
  } else {
    // For quantity fields (no upper limit, but must be >= 0)
    if (!isNaN(numValue)) {
      if (numValue < 0) process[field] = 0;
      else process[field] = numValue;
    }
  }
  
  // Update the input value to show the validated number
  input.value = process[field].toString();
};

const handleSubmit = () => {
  // Format the processes data to match the expected structure
  const formattedProcesses = formData.value.processes.map(process => ({
    processNumber: process.processNumber,
    processingType: process.processingType,
    inputTinKilos: Number(process.inputTinKilos) || 0,
    inputTinSnContent: Number(process.inputTinSnContent) || 0,
    inputTinInventoryItemId: process.inputTinInventoryItemId || null,
    outputTinKilos: Number(process.outputTinKilos) || 0,
    outputTinSnContent: Number(process.outputTinSnContent) || 0,
    outputTinInventoryItemId: process.outputTinInventoryItemId || null,
    inputSlagKilos: Number(process.inputSlagKilos) || 0,
    inputSlagSnContent: Number(process.inputSlagSnContent) || 0,
    inputSlagInventoryItemId: process.inputSlagInventoryItemId || null,
    outputSlagKilos: Number(process.outputSlagKilos) || 0,
    outputSlagSnContent: Number(process.outputSlagSnContent) || 0,
    outputSlagInventoryItemId: process.outputSlagInventoryItemId || null,
    notes: process.notes || ''
  }));

  // Create the submission data as a plain object
  const submissionData = {
    batchNumber: formData.value.batchNumber,
    date: formData.value.date,
    status: formData.value.status,
    notes: formData.value.notes || '',
    processes: formattedProcesses
  };

  console.log('Form data before submission:', submissionData);
  emit('submit', submissionData);
};

onMounted(async () => {
  await fetchInventoryItems();
  if (props.existingBatch) {
    console.log('Existing batch:', props.existingBatch);
    
    // Extract the date part from the ISO string
    const formattedDate = props.existingBatch.date.split('T')[0];
    
    // Create a new object with all the batch data
    const transformedBatch: Batch = {
      id: props.existingBatch.id,
      batchNumber: props.existingBatch.batchNumber,
      date: formattedDate,
      status: props.existingBatch.status,
      notes: props.existingBatch.notes || '',
      processes: props.existingBatch.processes.map(process => ({
        id: process.id,
        batch_id: process.batch_id,
        processNumber: process.processNumber,
        processingType: process.processingType,
        inputTinKilos: Number(process.inputTinKilos) || 0,
        inputTinSnContent: Number(process.inputTinSnContent) || 0,
        inputTinInventoryItemId: process.inputTinInventoryItemId || null,
        outputTinKilos: Number(process.outputTinKilos) || 0,
        outputTinSnContent: Number(process.outputTinSnContent) || 0,
        outputTinInventoryItemId: process.outputTinInventoryItemId || null,
        inputSlagKilos: Number(process.inputSlagKilos) || 0,
        inputSlagSnContent: Number(process.inputSlagSnContent) || 0,
        inputSlagInventoryItemId: process.inputSlagInventoryItemId || null,
        outputSlagKilos: Number(process.outputSlagKilos) || 0,
        outputSlagSnContent: Number(process.outputSlagSnContent) || 0,
        outputSlagInventoryItemId: process.outputSlagInventoryItemId || null,
        notes: process.notes || '',
        createdAt: process.createdAt,
        updatedAt: process.updatedAt
      })),
      createdAt: props.existingBatch.createdAt,
      updatedAt: props.existingBatch.updatedAt
    };

    console.log('Transformed batch:', transformedBatch);
    formData.value = transformedBatch;
  } else {
    await generateBatchNumber();
  }
});
</script> 