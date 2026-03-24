<script setup>
import { useI18n } from 'vue-i18n';
import GuestPortalLayout from '@/Layouts/GuestPortalLayout.vue';

defineOptions({ layout: GuestPortalLayout });

const { t } = useI18n();

const props = defineProps({
    invoices: Array,
});

const statusColors = {
    draft: 'border-gray-300 bg-gray-50',
    issued: 'border-yellow-400 bg-yellow-50',
    paid: 'border-green-400 bg-green-50',
    void: 'border-red-400 bg-red-50',
    refunded: 'border-purple-400 bg-purple-50',
};

const statusBadge = {
    draft: 'bg-gray-100 text-gray-800',
    issued: 'bg-yellow-100 text-yellow-800',
    paid: 'bg-green-100 text-green-800',
    void: 'bg-red-100 text-red-800',
    refunded: 'bg-purple-100 text-purple-800',
};

const statusDot = {
    draft: 'bg-gray-400',
    issued: 'bg-yellow-400',
    paid: 'bg-green-400',
    void: 'bg-red-400',
    refunded: 'bg-purple-400',
};

const formatMoney = (cents) => {
    return '$' + (cents / 100).toFixed(2);
};
</script>

<template>
    <div>
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold text-gray-800">{{ $t('billing.invoices') }}</h1>
        </div>

        <!-- Empty State -->
        <div v-if="invoices.length === 0" class="bg-white rounded-xl shadow-sm border border-gray-100 p-12 text-center">
            <div class="w-16 h-16 bg-indigo-50 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
            </div>
            <p class="text-gray-500">{{ $t('billing.no_invoices') }}</p>
        </div>

        <!-- Invoice Cards Grid -->
        <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <a
                v-for="invoice in invoices"
                :key="invoice.id"
                :href="`/portal/billing/${invoice.uuid}`"
                class="bg-white rounded-xl shadow-sm border-l-4 hover:shadow-md transition-all duration-200 group overflow-hidden"
                :class="statusColors[invoice.status]"
            >
                <div class="p-5">
                    <!-- Top: Invoice # + Status -->
                    <div class="flex items-start justify-between mb-4">
                        <div class="flex items-start gap-3 min-w-0">
                            <div class="w-10 h-10 bg-indigo-50 rounded-lg flex items-center justify-center shrink-0">
                                <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                            <div class="min-w-0">
                                <h3 class="font-semibold text-gray-900 group-hover:text-indigo-600 transition-colors truncate">
                                    {{ $t('billing.invoice') }} #{{ invoice.invoice_number }}
                                </h3>
                                <p class="text-xs text-gray-500 mt-0.5">{{ invoice.created_at }}</p>
                            </div>
                        </div>
                        <span
                            class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-semibold rounded-full capitalize shrink-0 ml-3"
                            :class="statusBadge[invoice.status]"
                        >
                            <span class="w-1.5 h-1.5 rounded-full" :class="statusDot[invoice.status]"></span>
                            {{ $t('billing.status_' + invoice.status) }}
                        </span>
                    </div>

                    <!-- Total -->
                    <div class="flex items-center justify-between">
                        <div class="flex items-baseline gap-1">
                            <span class="text-lg font-bold text-indigo-700">{{ formatMoney(invoice.total_cents) }}</span>
                            <span class="text-xs text-gray-400">{{ $t('billing.total') }}</span>
                        </div>
                        <svg class="w-5 h-5 text-gray-300 group-hover:text-indigo-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </div>
                </div>
            </a>
        </div>
    </div>
</template>
