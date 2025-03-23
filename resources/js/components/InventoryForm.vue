<template>
  <form @submit.prevent="handleSubmit" class="bg-white shadow-sm rounded-lg p-6">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <!-- Name -->
      <div>
        <label for="name" class="block text-sm font-medium text-gray-700">Name</label>
        <input type="text" id="name" v-model="form.name" required
               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
      </div>

      <!-- Type -->
      <div>
        <label for="type" class="block text-sm font-medium text-gray-700">Type</label>
        <select id="type" v-model="form.type" required
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
          <option value="cassiterite">Cassiterite</option>
          <option value="ingot">Ingot</option>
          <option value="finished_tin">Finished Tin</option>
          <option value="slag">Slag</option>
        </select>
      </div>

      <!-- Description -->
      <div class="md:col-span-2">
        <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
        <textarea id="description" v-model="form.description" rows="3"
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
      </div>

      <!-- Quantity -->
      <div>
        <label for="quantity" class="block text-sm font-medium text-gray-700">Quantity</label>
        <input type="number" id="quantity" v-model="form.quantity" required min="0" step="0.01"
               :disabled="editMode"
               :title="editMode ? 'Use the Adjust button to change inventory quantity' : ''"
               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 disabled:bg-gray-100 disabled:text-gray-500">
        <p v-if="editMode" class="mt-1 text-sm text-gray-500">
          Use the Adjust button to change inventory quantity
        </p>
      </div>

      <!-- Unit -->
      <div>
        <label for="unit" class="block text-sm font-medium text-gray-700">Unit</label>
        <select id="unit" v-model="form.unit" required
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
          <option value="kg">Kilograms (kg)</option>
          <option value="ton">Tons</option>
          <option value="pieces">Pieces</option>
        </select>
      </div>

      <!-- Sn Content -->
      <div>
        <label for="sn_content" class="block text-sm font-medium text-gray-700">Sn Content (%)</label>
        <input type="number" id="sn_content" v-model="form.sn_content" required min="0" max="100" step="0.01"
               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
      </div>

      <!-- Location -->
      <div>
        <label for="location" class="block text-sm font-medium text-gray-700">Location</label>
        <input type="text" id="location" v-model="form.location" required
               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
      </div>

      <!-- Status -->
      <div>
        <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
        <select id="status" v-model="form.status" required
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
          <option value="active">Active</option>
          <option value="archived">Archived</option>
        </select>
      </div>
    </div>

    <!-- Form Actions -->
    <div class="mt-6 flex justify-end space-x-3">
      <button type="button" @click="$emit('cancel')"
              class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
        Cancel
      </button>
      <button type="submit"
              class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
        {{ editMode ? 'Update' : 'Create' }} Item
      </button>
    </div>
  </form>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import type { InventoryItem } from '@/types/inventory';

const props = defineProps<{
  existingItem?: InventoryItem
}>();

const emit = defineEmits<{
  (e: 'submit', data: Partial<InventoryItem>): void
  (e: 'cancel'): void
}>();

const editMode = computed(() => !!props.existingItem);

const form = ref<Partial<InventoryItem>>({
  name: '',
  type: 'cassiterite',
  description: '',
  quantity: 0,
  unit: 'kg',
  sn_content: 0,
  location: '',
  status: 'active'
});

onMounted(() => {
  if (props.existingItem) {
    form.value = {
      name: props.existingItem.name,
      type: props.existingItem.type,
      description: props.existingItem.description || '',
      quantity: props.existingItem.quantity,
      unit: props.existingItem.unit,
      sn_content: props.existingItem.sn_content || 0,
      location: props.existingItem.location || '',
      status: props.existingItem.status
    };
  }
});

const handleSubmit = () => {
  // Always submit the form data including quantity
  emit('submit', form.value);
};
</script> 