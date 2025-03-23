<template>
    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Inventory Management
                </h2>
                <button
                    @click="showForm = true"
                    class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                    Add New Item
                </button>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <InventoryList
                            :items="items"
                            :transactions="transactions"
                            @edit="handleEdit"
                            @delete="handleDelete"
                            @adjust="handleAdjustClick"
                            @history="handleHistoryClick"
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- Add/Edit Item Modal -->
        <TransitionRoot appear :show="showForm" as="template">
            <Dialog as="div" @close="handleCancel" class="relative z-10">
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
                            <DialogPanel class="w-full max-w-2xl transform overflow-hidden rounded-2xl bg-white p-6 text-left align-middle shadow-xl transition-all">
                                <DialogTitle as="h3" class="text-lg font-medium leading-6 text-gray-900">
                                    {{ selectedItem ? 'Edit Item' : 'Add New Item' }}
                                </DialogTitle>
                                <div class="mt-4">
                                    <InventoryForm
                                        :existingItem="selectedItem"
                                        @submit="handleSubmit"
                                        @cancel="handleCancel"
                                    />
                                </div>
                            </DialogPanel>
                        </TransitionChild>
                    </div>
                </div>
            </Dialog>
        </TransitionRoot>

        <!-- Adjustment Modal -->
        <TransitionRoot appear :show="showAdjustmentForm" as="template">
            <Dialog as="div" @close="handleCancel" class="relative z-10">
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
                            <DialogPanel class="w-full max-w-2xl transform overflow-hidden rounded-2xl bg-white p-6 text-left align-middle shadow-xl transition-all">
                                <DialogTitle as="h3" class="text-lg font-medium leading-6 text-gray-900">
                                    Make Adjustment
                                </DialogTitle>
                                <div class="mt-4">
                                    <InventoryAdjustmentForm
                                        v-if="selectedItem"
                                        :item="selectedItem"
                                        @submit="handleAdjustment"
                                        @cancel="handleCancel"
                                    />
                                </div>
                            </DialogPanel>
                        </TransitionChild>
                    </div>
                </div>
            </Dialog>
        </TransitionRoot>
    </AuthenticatedLayout>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { Dialog, DialogPanel, DialogTitle, TransitionRoot, TransitionChild } from '@headlessui/vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InventoryList from '@/Components/InventoryList.vue';
import InventoryForm from '@/Components/InventoryForm.vue';
import InventoryAdjustmentForm from '@/Components/InventoryAdjustmentForm.vue';
import type { InventoryItem, Transaction } from '@/types/inventory';

const props = defineProps<{
    items: InventoryItem[],
    transactions: Transaction[],
    auth: {
        user: {
            name: string;
            email: string;
        }
    }
}>();

const showForm = ref(false);
const showAdjustmentForm = ref(false);
const selectedItem = ref<InventoryItem | undefined>(undefined);

const handleSubmit = async (data: any) => {
    try {
        const url = selectedItem.value
            ? `/api/inventory/${selectedItem.value.id}`
            : '/api/inventory';
        
        const method = selectedItem.value ? 'put' : 'post';
        
        await router[method](url, data, {
            onSuccess: () => {
                handleCancel();
                window.location.reload();
            },
            onError: (errors) => {
                console.error('Error saving inventory item:', errors);
            }
        });
    } catch (error) {
        console.error('Error saving inventory item:', error);
    }
};

const handleAdjustment = async (data: any) => {
    try {
        await router.post('/api/inventory-transactions', {
            ...data,
            inventory_item_id: selectedItem.value?.id,
            type: 'adjustment'
        }, {
            onSuccess: () => {
                handleCancel();
                window.location.reload();
            },
            onError: (errors) => {
                console.error('Error making adjustment:', errors);
            }
        });
    } catch (error) {
        console.error('Error making adjustment:', error);
    }
};

const handleEdit = (item: InventoryItem) => {
    selectedItem.value = item;
    showForm.value = true;
    showAdjustmentForm.value = false;
};

const handleAdjustClick = (item: InventoryItem) => {
    selectedItem.value = item;
    showForm.value = false;
    showAdjustmentForm.value = true;
};

const handleHistoryClick = (item: InventoryItem) => {
    selectedItem.value = item;
};

const handleDelete = async (itemId: number) => {
    if (!confirm('Are you sure you want to delete this item?')) return;
    
    try {
        await router.delete(`/api/inventory/${itemId}`, {
            onSuccess: () => {
                window.location.reload();
            },
            onError: (errors) => {
                console.error('Error deleting inventory item:', errors);
            }
        });
    } catch (error) {
        console.error('Error deleting inventory item:', error);
    }
};

const handleCancel = () => {
    showForm.value = false;
    showAdjustmentForm.value = false;
    selectedItem.value = undefined;
};
</script> 