<template>
    <form @submit.prevent="handleSubmit" class="space-y-6">
        <h3 class="text-lg font-medium text-gray-900">Make Inventory Adjustment</h3>

        <!-- Item Information -->
        <div class="bg-gray-50 p-4 rounded-lg">
            <div class="grid grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Item</label>
                    <p class="mt-1 text-sm text-gray-900">{{ item.name }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Current Quantity</label>
                    <p class="mt-1 text-sm text-gray-900">{{ item.quantity }} {{ item.unit }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Location</label>
                    <p class="mt-1 text-sm text-gray-900">{{ item.location }}</p>
                </div>
            </div>
        </div>

        <!-- Adjustment Type -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Adjustment Type</label>
            <div class="flex space-x-4">
                <label class="inline-flex items-center">
                    <input
                        type="radio"
                        v-model="adjustmentType"
                        value="add"
                        class="form-radio h-4 w-4 text-blue-600"
                    />
                    <span class="ml-2">Add Inventory</span>
                </label>
                <label class="inline-flex items-center">
                    <input
                        type="radio"
                        v-model="adjustmentType"
                        value="subtract"
                        class="form-radio h-4 w-4 text-blue-600"
                    />
                    <span class="ml-2">Remove Inventory</span>
                </label>
            </div>
        </div>

        <!-- Quantity -->
        <div>
            <label for="quantity" class="block text-sm font-medium text-gray-700">
                Adjustment Quantity
            </label>
            <div class="mt-1">
                <input
                    type="number"
                    id="quantity"
                    v-model="form.quantity"
                    min="0"
                    step="0.01"
                    class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md"
                    required
                />
            </div>
            <p class="mt-1 text-sm text-gray-500">
                New quantity will be: {{ newQuantity }} {{ item.unit }}
            </p>
        </div>

        <!-- Unit Price -->
        <div>
            <label for="unit_price" class="block text-sm font-medium text-gray-700">Unit Price</label>
            <div class="mt-1">
                <input
                    type="number"
                    id="unit_price"
                    v-model="form.unit_price"
                    step="0.01"
                    class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md"
                />
            </div>
        </div>

        <!-- Currency -->
        <div>
            <label for="currency" class="block text-sm font-medium text-gray-700">Currency</label>
            <select
                id="currency"
                v-model="form.currency"
                class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md"
                required
            >
                <option value="USD">USD</option>
                <option value="EUR">EUR</option>
                <option value="GBP">GBP</option>
            </select>
        </div>

        <!-- Notes -->
        <div>
            <label for="notes" class="block text-sm font-medium text-gray-700">Notes</label>
            <div class="mt-1">
                <textarea
                    id="notes"
                    v-model="form.notes"
                    rows="3"
                    class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md"
                    placeholder="Reason for adjustment"
                    required
                ></textarea>
            </div>
        </div>

        <!-- Form Actions -->
        <div class="flex justify-end space-x-3">
            <button
                type="button"
                @click="$emit('cancel')"
                class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
            >
                Cancel
            </button>
            <button
                type="submit"
                class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
            >
                Save Adjustment
            </button>
        </div>
    </form>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import type { InventoryItem, AdjustmentFormData } from '@/types/inventory';

const props = defineProps<{
    item: InventoryItem
}>();

const emit = defineEmits<{
    (e: 'submit', data: AdjustmentFormData): void
    (e: 'cancel'): void
}>();

const adjustmentType = ref('add');

const form = ref<AdjustmentFormData>({
    inventory_item_id: props.item.id,
    quantity: 0,
    unit_price: undefined,
    currency: 'USD',
    notes: ''
});

const newQuantity = computed(() => {
    const adjustedQuantity = Number(form.value.quantity);
    return adjustmentType.value === 'add' 
        ? Number(props.item.quantity) + adjustedQuantity
        : Number(props.item.quantity) - adjustedQuantity;
});

const handleSubmit = () => {
    const finalQuantity = adjustmentType.value === 'add' 
        ? Number(form.value.quantity) 
        : -Number(form.value.quantity);
        
    emit('submit', {
        ...form.value,
        quantity: finalQuantity
    });
};
</script> 