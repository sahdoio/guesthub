<script setup>
import { useI18n } from 'vue-i18n';
import { useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

defineOptions({ layout: AuthenticatedLayout });

const { t } = useI18n();

const props = defineProps({
    invoice: Object,
});

const inv = computed(() => props.invoice);

const statusColors = {
    draft: 'bg-gray-100 text-gray-800',
    issued: 'bg-yellow-100 text-yellow-800',
    paid: 'bg-green-100 text-green-800',
    void: 'bg-red-100 text-red-800',
    refunded: 'bg-purple-100 text-purple-800',
};

const paymentStatusColors = {
    pending: 'bg-yellow-100 text-yellow-800',
    succeeded: 'bg-green-100 text-green-800',
    failed: 'bg-red-100 text-red-800',
    refunded: 'bg-purple-100 text-purple-800',
};

const formatMoney = (cents) => {
    return '$' + (cents / 100).toFixed(2);
};

const issueForm = useForm({});
const voidForm = useForm({});
const refundForm = useForm({});

const issueInvoice = () => {
    issueForm.post(`/billing/${inv.value.uuid}/issue`);
};

const voidInvoice = () => {
    if (confirm(t('billing.confirm_void'))) {
        voidForm.post(`/billing/${inv.value.uuid}/void`);
    }
};

const refundInvoice = () => {
    if (confirm(t('billing.confirm_refund'))) {
        refundForm.post(`/billing/${inv.value.uuid}/refund`);
    }
};
</script>

<template>
    <div>
        <div class="mb-6 flex items-center gap-4">
            <a href="/billing" class="text-gray-500 hover:text-gray-700">&larr; {{ $t('common.back') }}</a>
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
                    <h2 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-4">{{ $t('billing.invoice') }}</h2>
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <span class="text-gray-500">{{ $t('billing.invoice') }} #</span>
                            <p class="font-medium text-gray-900">{{ inv.invoice_number }}</p>
                        </div>
                        <div>
                            <span class="text-gray-500">{{ $t('billing.status') }}</span>
                            <p class="mt-1">
                                <span
                                    class="inline-flex px-2 py-0.5 text-xs font-semibold rounded-full capitalize"
                                    :class="statusColors[inv.status]"
                                >
                                    {{ $t('billing.status_' + inv.status) }}
                                </span>
                            </p>
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

                <!-- Stay & Guest Info -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <div v-if="inv.reservation?.stay">
                            <h2 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">{{ $t('reservation.stay') }}</h2>
                            <div class="flex items-center gap-3">
                                <img v-if="inv.reservation.stay.cover_image_url" :src="inv.reservation.stay.cover_image_url" :alt="inv.reservation.stay.name" class="w-12 h-12 rounded-lg object-cover" />
                                <div class="w-12 h-12 rounded-lg bg-indigo-100 flex items-center justify-center shrink-0" v-else>
                                    <svg class="w-6 h-6 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                                    </svg>
                                </div>
                                <div>
                                    <a :href="`/stays/${inv.reservation.stay.slug}`" class="text-sm font-medium text-indigo-600 hover:text-indigo-800 hover:underline">{{ inv.reservation.stay.name }}</a>
                                    <p class="text-xs text-gray-500 mt-0.5">{{ inv.reservation.check_in }} &rarr; {{ inv.reservation.check_out }}</p>
                                </div>
                            </div>
                        </div>
                        <div v-if="inv.guest">
                            <h2 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">{{ $t('reservation.guest') }}</h2>
                            <div class="text-sm">
                                <p class="font-medium text-gray-900">{{ inv.guest.full_name }}</p>
                                <p v-if="inv.guest.email" class="text-gray-500 mt-0.5">{{ inv.guest.email }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Line Items -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="p-6 pb-0">
                        <h2 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-4">{{ $t('billing.line_items') }}</h2>
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

                <!-- Payments -->
                <div v-if="inv.payments && inv.payments.length > 0" class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="p-6 pb-0">
                        <h2 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-4">{{ $t('billing.payments') }}</h2>
                    </div>
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ $t('billing.amount') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ $t('billing.payment_method') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ $t('billing.payment_status') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Stripe ID</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ $t('billing.payment_date') }}</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="payment in inv.payments" :key="payment.id">
                                <td class="px-6 py-4 text-sm font-semibold text-gray-900">{{ formatMoney(payment.amount_cents) }}</td>
                                <td class="px-6 py-4 text-sm text-gray-600 capitalize">{{ payment.method || '-' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        class="inline-flex px-2 py-0.5 text-xs font-semibold rounded-full capitalize"
                                        :class="paymentStatusColors[payment.status] || 'bg-gray-100 text-gray-800'"
                                    >
                                        {{ payment.status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500 font-mono">{{ payment.stripe_id || '-' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ payment.created_at }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Actions -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h2 class="text-sm font-medium text-gray-500 uppercase tracking-wide mb-3">{{ $t('common.actions') }}</h2>
                    <div class="space-y-3">
                        <!-- Issue (draft only) -->
                        <button
                            v-if="inv.status === 'draft'"
                            @click="issueInvoice"
                            :disabled="issueForm.processing"
                            class="w-full bg-indigo-600 text-white py-2 px-4 rounded-lg shadow-sm text-sm font-medium hover:bg-indigo-700 transition-colors disabled:opacity-50"
                        >
                            {{ $t('billing.issue_invoice') }}
                        </button>

                        <!-- Void (draft or issued) -->
                        <button
                            v-if="['draft', 'issued'].includes(inv.status)"
                            @click="voidInvoice"
                            :disabled="voidForm.processing"
                            class="w-full bg-red-600 text-white py-2 px-4 rounded-lg shadow-sm text-sm font-medium hover:bg-red-700 transition-colors disabled:opacity-50"
                        >
                            {{ $t('billing.void_invoice') }}
                        </button>

                        <!-- Refund (paid only) -->
                        <button
                            v-if="inv.status === 'paid'"
                            @click="refundInvoice"
                            :disabled="refundForm.processing"
                            class="w-full bg-purple-600 text-white py-2 px-4 rounded-lg shadow-sm text-sm font-medium hover:bg-purple-700 transition-colors disabled:opacity-50"
                        >
                            {{ $t('billing.refund_invoice') }}
                        </button>

                        <p v-if="inv.status === 'issued'" class="text-sm text-gray-500 text-center">
                            {{ $t('billing.status_issued') }}
                        </p>

                        <p v-if="['void', 'refunded'].includes(inv.status)" class="text-sm text-gray-500 text-center">
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
