<script setup>
import { useI18n } from 'vue-i18n';
import { router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import axios from 'axios';
import GuestPortalLayout from '@/Layouts/GuestPortalLayout.vue';

defineOptions({ layout: GuestPortalLayout });

const { t } = useI18n();

const props = defineProps({
    invoice: Object,
    stripePublishableKey: String,
});

const inv = computed(() => props.invoice);

const statusColors = {
    draft: 'bg-gray-100 text-gray-800',
    issued: 'bg-yellow-100 text-yellow-800',
    paid: 'bg-green-100 text-green-800',
    void: 'bg-red-100 text-red-800',
    refunded: 'bg-purple-100 text-purple-800',
};

const formatMoney = (cents) => {
    return '$' + (cents / 100).toFixed(2);
};

const processingPayment = ref(false);
const paymentError = ref(null);

const payNow = async () => {
    if (processingPayment.value) return;
    processingPayment.value = true;
    paymentError.value = null;

    try {
        const { data } = await axios.post(`/portal/billing/${inv.value.uuid}/pay`);

        if (data.simulated || data.client_secret) {
            // Payment succeeded — reload the page to show updated status
            router.reload();
        } else if (data.error) {
            paymentError.value = data.error;
            processingPayment.value = false;
        }
    } catch (err) {
        paymentError.value = err.response?.data?.message || 'An unexpected error occurred.';
        processingPayment.value = false;
    }
};
</script>

<template>
    <div>
        <div class="mb-6 flex items-center gap-4">
            <a href="/portal/billing" class="text-gray-500 hover:text-gray-700">&larr; {{ $t('common.back') }}</a>
            <h1 class="text-2xl font-bold text-gray-800">{{ $t('billing.invoice_details') }}</h1>
            <span
                class="inline-flex px-2 py-1 text-xs font-semibold rounded-full capitalize"
                :class="statusColors[inv.status]"
            >
                {{ $t('billing.status_' + inv.status) }}
            </span>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Content -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Invoice Header -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h2 class="text-sm font-medium text-gray-500 uppercase tracking-wide mb-3">{{ $t('billing.invoice') }}</h2>
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <span class="text-gray-500">{{ $t('billing.invoice') }} #</span>
                            <p class="font-medium text-gray-900">{{ inv.invoice_number }}</p>
                        </div>
                        <div>
                            <span class="text-gray-500">{{ $t('billing.created_at') }}</span>
                            <p class="font-medium text-gray-900">{{ inv.created_at }}</p>
                        </div>
                        <div v-if="inv.issued_at">
                            <span class="text-gray-500">{{ $t('billing.issued_at') }}</span>
                            <p class="font-medium text-gray-900">{{ inv.issued_at }}</p>
                        </div>
                        <div v-if="inv.paid_at">
                            <span class="text-gray-500">{{ $t('billing.paid_at') }}</span>
                            <p class="font-medium text-gray-900">{{ inv.paid_at }}</p>
                        </div>
                    </div>
                </div>

                <!-- Line Items -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="p-6 pb-0">
                        <h2 class="text-sm font-medium text-gray-500 uppercase tracking-wide mb-4">{{ $t('billing.line_items') }}</h2>
                    </div>
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ $t('billing.description') }}</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ $t('billing.unit_price') }}</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ $t('billing.quantity') }}</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ $t('billing.total') }}</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="item in inv.line_items" :key="item.id">
                                <td class="px-6 py-4 text-sm text-gray-900">{{ item.description }}</td>
                                <td class="px-6 py-4 text-sm text-gray-900 text-right">{{ formatMoney(item.unit_price_cents) }}</td>
                                <td class="px-6 py-4 text-sm text-gray-900 text-right">{{ item.quantity }}</td>
                                <td class="px-6 py-4 text-sm font-semibold text-gray-900 text-right">{{ formatMoney(item.total_cents) }}</td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Totals -->
                    <div class="border-t border-gray-200 p-6">
                        <div class="flex justify-end">
                            <div class="w-64 space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-500">{{ $t('billing.subtotal') }}</span>
                                    <span class="font-medium text-gray-900">{{ formatMoney(inv.subtotal_cents) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-500">{{ $t('billing.tax') }}</span>
                                    <span class="font-medium text-gray-900">{{ formatMoney(inv.tax_cents) }}</span>
                                </div>
                                <div class="flex justify-between border-t border-gray-200 pt-2">
                                    <span class="font-semibold text-gray-900">{{ $t('billing.total') }}</span>
                                    <span class="font-bold text-lg text-indigo-700">{{ formatMoney(inv.total_cents) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Payment Action -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h2 class="text-sm font-medium text-gray-500 uppercase tracking-wide mb-3">{{ $t('common.actions') }}</h2>
                    <div class="space-y-3">
                        <!-- Pay Now (issued only) -->
                        <div v-if="inv.status === 'issued'">
                            <div v-if="paymentError" class="mb-3 p-3 bg-red-50 border border-red-200 rounded-lg">
                                <p class="text-sm text-red-700">{{ paymentError }}</p>
                            </div>
                            <div v-if="processingPayment" class="text-center py-4">
                                <div class="w-8 h-8 border-2 border-indigo-600 border-t-transparent rounded-full animate-spin mx-auto mb-2"></div>
                                <p class="text-sm text-gray-500">{{ $t('billing.processing_payment') }}</p>
                            </div>
                            <button
                                v-else
                                @click="payNow"
                                :disabled="processingPayment"
                                class="w-full bg-indigo-600 text-white py-2 px-4 rounded-lg shadow-sm text-sm font-medium hover:bg-indigo-700 transition-colors disabled:opacity-50"
                            >
                                {{ $t('billing.pay_now') }}
                            </button>
                        </div>

                        <!-- Paid status -->
                        <div v-if="inv.status === 'paid'" class="text-center py-2">
                            <span class="inline-flex items-center gap-2 px-4 py-2 bg-green-50 text-green-700 rounded-lg text-sm font-medium">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                {{ $t('billing.status_paid') }}
                            </span>
                            <p v-if="inv.paid_at" class="text-xs text-gray-500 mt-2">{{ $t('billing.paid_at') }}: {{ inv.paid_at }}</p>
                        </div>

                        <!-- Refunded status -->
                        <div v-if="inv.status === 'refunded'" class="text-center py-2">
                            <span class="inline-flex items-center gap-2 px-4 py-2 bg-purple-50 text-purple-700 rounded-lg text-sm font-medium">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                                </svg>
                                {{ $t('billing.status_refunded') }}
                            </span>
                        </div>

                        <!-- Draft / Void -->
                        <p v-if="['draft', 'void'].includes(inv.status)" class="text-sm text-gray-500 text-center">
                            {{ $t('reservation.no_actions') }}
                        </p>
                    </div>
                </div>

                <!-- Timeline -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h2 class="text-sm font-medium text-gray-500 uppercase tracking-wide mb-3">{{ $t('common.timeline') }}</h2>
                    <div class="space-y-2 text-sm">
                        <div>
                            <span class="text-gray-500">{{ $t('billing.created_at') }}</span>
                            <p class="text-gray-900">{{ inv.created_at }}</p>
                        </div>
                        <div v-if="inv.issued_at">
                            <span class="text-gray-500">{{ $t('billing.issued_at') }}</span>
                            <p class="text-gray-900">{{ inv.issued_at }}</p>
                        </div>
                        <div v-if="inv.paid_at">
                            <span class="text-gray-500">{{ $t('billing.paid_at') }}</span>
                            <p class="text-gray-900">{{ inv.paid_at }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
