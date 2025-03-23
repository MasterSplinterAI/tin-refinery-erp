<template>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Item</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Unit Price</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Notes</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <tr v-for="transaction in sortedTransactions" :key="transaction.id" class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {{ formatDate(transaction.created_at) }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ transaction.inventory_item?.name }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                        <span :class="getTypeClass(transaction.type)" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full">
                            {{ formatType(transaction.type) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ formatQuantity(transaction.quantity) }} {{ transaction.inventory_item?.unit }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ transaction.unit_price ? formatCurrency(transaction.unit_price, transaction.currency) : '-' }}
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-500 max-w-xs truncate">
                        {{ transaction.notes || '-' }}
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import type { Transaction } from '@/types/inventory';

const props = defineProps<{
    transactions: Transaction[]
}>();

const sortedTransactions = computed(() => {
    return [...props.transactions].sort((a, b) => 
        new Date(b.created_at).getTime() - new Date(a.created_at).getTime()
    );
});

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleString();
};

const formatType = (type: string) => {
    return type.charAt(0).toUpperCase() + type.slice(1);
};

const getTypeClass = (type: string) => {
    const classes = {
        production: 'bg-green-100 text-green-800',
        consumption: 'bg-red-100 text-red-800',
        adjustment: 'bg-yellow-100 text-yellow-800',
        reversal: 'bg-blue-100 text-blue-800'
    };
    return classes[type as keyof typeof classes] || 'bg-gray-100 text-gray-800';
};

const formatQuantity = (quantity: number) => {
    return quantity.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

const formatCurrency = (amount: number, currency: string) => {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: currency
    }).format(amount);
};
</script> 