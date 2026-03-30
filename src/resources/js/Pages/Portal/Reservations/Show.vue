<script setup>
import { useI18n } from 'vue-i18n';
import { useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import axios from 'axios';
import GuestPortalLayout from '@/Layouts/GuestPortalLayout.vue';

defineOptions({ layout: GuestPortalLayout });

const { t } = useI18n();

const props = defineProps({
    reservation: Object,
    invoice: Object,
    stripePublishableKey: String,
});

const r = computed(() => props.reservation);
const inv = computed(() => props.invoice);

const statusColors = {
    pending: 'bg-yellow-100 text-yellow-800',
    confirmed: 'bg-blue-100 text-blue-800',
    checked_in: 'bg-green-100 text-green-800',
    checked_out: 'bg-gray-100 text-gray-800',
    cancelled: 'bg-red-100 text-red-800',
};

const statusLabel = (status) => t('status.' + status);

const formatMoney = (cents) => {
    return '$' + (cents / 100).toFixed(2);
};

// Free cancellation logic
const isFreeCancellation = computed(() => {
    return r.value.free_cancellation_until && new Date() < new Date(r.value.free_cancellation_until);
});

const formatDate = (dateStr) => {
    if (!dateStr) return '';
    const d = new Date(dateStr);
    return d.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
};

// Payment
const processingPayment = ref(false);
const paymentError = ref(null);
const payNow = async () => {
    processingPayment.value = true;
    paymentError.value = null;
    try {
        const { data } = await axios.post(`/portal/billing/${inv.value.uuid}/pay`);
        if (data.simulated) {
            router.reload();
        }
    } catch (e) {
        paymentError.value = e.response?.data?.error || 'Payment failed.';
    } finally {
        processingPayment.value = false;
    }
};

// Cancel form
const showCancelForm = ref(false);
const cancelForm = useForm({ reason: '' });
const submitCancel = () => cancelForm.post(`/portal/reservations/${r.value.id}/cancel`);

// Special request form
const showSpecialRequestForm = ref(false);
const specialRequestForm = useForm({ type: '', description: '' });
const submitSpecialRequest = () => {
    specialRequestForm.post(`/portal/reservations/${r.value.id}/special-requests`, {
        onSuccess: () => {
            specialRequestForm.reset();
            showSpecialRequestForm.value = false;
        },
    });
};

const requestTypeLabels = computed(() => ({
    early_check_in: t('special_request.types.early_check_in'),
    late_check_out: t('special_request.types.late_check_out'),
    extra_bed: t('special_request.types.extra_bed'),
    dietary_restriction: t('special_request.types.dietary_restriction'),
    special_occasion: t('special_request.types.special_occasion'),
    other: t('special_request.types.other'),
}));

const requestStatusColors = {
    pending: 'bg-yellow-100 text-yellow-800',
    fulfilled: 'bg-green-100 text-green-800',
    cancelled: 'bg-red-100 text-red-800',
};
</script>

<template>
    <div>
        <div class="mb-6 flex items-center gap-4">
            <a href="/portal/reservations" class="text-gray-500 hover:text-gray-700">&larr; {{ $t('common.back') }}</a>
            <h1 class="text-2xl font-bold text-gray-800">{{ $t('reservation.details') }}</h1>
            <span
                class="inline-flex px-2 py-1 text-xs font-semibold rounded-full capitalize"
                :class="statusColors[r.status]"
            >
                {{ statusLabel(r.status) }}
            </span>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Info -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Stay Info -->
                <div v-if="r.stay?.name" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h2 class="text-sm font-medium text-gray-500 uppercase tracking-wide mb-3">{{ $t('stay.details') }}</h2>
                    <div class="flex items-start gap-3">
                        <div class="w-10 h-10 bg-indigo-50 rounded-lg flex items-center justify-center shrink-0">
                            <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                            </svg>
                        </div>
                        <div>
                            <p class="font-semibold text-gray-900">{{ r.stay.name }}</p>
                            <div class="flex gap-1.5 mt-1">
                                <span v-if="r.stay.type" class="inline-flex px-2 py-0.5 text-xs font-medium rounded-full bg-blue-100 text-blue-800">
                                    {{ $t('stay.type_' + r.stay.type) }}
                                </span>
                                <span v-if="r.stay.category" class="inline-flex px-2 py-0.5 text-xs font-medium rounded-full bg-indigo-100 text-indigo-800">
                                    {{ $t('stay.category_' + r.stay.category) }}
                                </span>
                            </div>
                            <p v-if="r.stay.address" class="text-sm text-gray-500 mt-1 flex items-center gap-1">
                                <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                {{ r.stay.address }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Stay Details -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h2 class="text-sm font-medium text-gray-500 uppercase tracking-wide mb-3">{{ $t('reservation.stay_details') }}</h2>
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <span class="text-gray-500">{{ $t('reservation.check_in') }}</span>
                            <p class="font-medium text-gray-900">{{ r.period.check_in }}</p>
                        </div>
                        <div>
                            <span class="text-gray-500">{{ $t('reservation.check_out') }}</span>
                            <p class="font-medium text-gray-900">{{ r.period.check_out }}</p>
                        </div>
                        <div>
                            <span class="text-gray-500">{{ $t('reservation.nights') }}</span>
                            <p class="font-medium text-gray-900">{{ r.period.nights }}</p>
                        </div>
                        <div v-if="r.stay">
                            <span class="text-gray-500">{{ $t('reservation.stay') }}</span>
                            <p class="font-medium text-gray-900">{{ r.stay.name }}</p>
                        </div>
                    </div>
                </div>

                <!-- Invoice Summary (when invoice exists) -->
                <div v-if="inv" class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="p-6 pb-0">
                        <h2 class="text-sm font-medium text-gray-500 uppercase tracking-wide mb-4">{{ $t('billing.invoice_summary') }}</h2>
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

                <!-- Special Requests -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <div class="flex items-center justify-between mb-3">
                        <h2 class="text-sm font-medium text-gray-500 uppercase tracking-wide">
                            {{ $t('special_request.title') }} ({{ r.special_requests.length }}/5)
                        </h2>
                        <button
                            v-if="!['cancelled', 'checked_out'].includes(r.status) && r.special_requests.length < 5"
                            @click="showSpecialRequestForm = !showSpecialRequestForm"
                            class="text-sm text-blue-600 hover:text-blue-800 font-medium"
                        >
                            {{ showSpecialRequestForm ? $t('common.cancel') : '+ ' + $t('special_request.add') }}
                        </button>
                    </div>

                    <!-- Add form -->
                    <form v-if="showSpecialRequestForm" @submit.prevent="submitSpecialRequest" class="mb-4 p-4 bg-gray-50 rounded-lg space-y-3">
                        <div>
                            <select
                                v-model="specialRequestForm.type"
                                required
                                class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm"
                                :class="{ 'border-red-500': specialRequestForm.errors.type }"
                            >
                                <option value="" disabled>{{ $t('special_request.select_type') }}</option>
                                <option v-for="(label, value) in requestTypeLabels" :key="value" :value="value">
                                    {{ label }}
                                </option>
                            </select>
                            <p v-if="specialRequestForm.errors.type" class="mt-1 text-sm text-red-600">{{ specialRequestForm.errors.type }}</p>
                        </div>
                        <div>
                            <textarea
                                v-model="specialRequestForm.description"
                                required
                                :placeholder="$t('special_request.description')"
                                rows="2"
                                class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm"
                                :class="{ 'border-red-500': specialRequestForm.errors.description }"
                            ></textarea>
                            <p v-if="specialRequestForm.errors.description" class="mt-1 text-sm text-red-600">{{ specialRequestForm.errors.description }}</p>
                        </div>
                        <button
                            type="submit"
                            :disabled="specialRequestForm.processing"
                            class="bg-indigo-600 text-white px-3 py-1.5 rounded-lg shadow-sm text-sm font-medium hover:bg-indigo-700 disabled:opacity-50"
                        >
                            {{ $t('special_request.add_request') }}
                        </button>
                    </form>

                    <div v-if="r.special_requests.length === 0 && !showSpecialRequestForm" class="text-sm text-gray-500">
                        {{ $t('special_request.no_requests') }}
                    </div>
                    <div v-else class="space-y-2">
                        <div
                            v-for="sr in r.special_requests"
                            :key="sr.id"
                            class="flex items-start justify-between p-3 bg-gray-50 rounded-lg"
                        >
                            <div class="text-sm">
                                <span class="font-medium text-gray-900">{{ requestTypeLabels[sr.type] || sr.type }}</span>
                                <p class="text-gray-600 mt-0.5">{{ sr.description }}</p>
                                <p class="text-gray-400 text-xs mt-1">{{ sr.created_at }}</p>
                            </div>
                            <span
                                class="inline-flex px-2 py-0.5 text-xs font-semibold rounded-full capitalize"
                                :class="requestStatusColors[sr.status]"
                            >
                                {{ sr.status }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Payment Required (pending + invoice issued) -->
                <div v-if="r.status === 'pending' && inv && inv.status === 'issued'" class="bg-white rounded-xl shadow-sm border-2 border-indigo-200 p-6">
                    <div class="flex items-center gap-2 mb-3">
                        <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                        </svg>
                        <h2 class="text-sm font-semibold text-indigo-900 uppercase tracking-wide">{{ $t('billing.payment_required') }}</h2>
                    </div>
                    <p class="text-sm text-gray-600 mb-4">{{ $t('billing.payment_required_description') }}</p>
                    <div class="bg-indigo-50 rounded-lg p-3 mb-4">
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-indigo-700 font-medium">{{ $t('billing.total') }}</span>
                            <span class="text-xl font-bold text-indigo-700">{{ formatMoney(inv.total_cents) }}</span>
                        </div>
                    </div>
                    <div v-if="processingPayment" class="text-center py-4">
                        <div class="w-8 h-8 border-2 border-indigo-600 border-t-transparent rounded-full animate-spin mx-auto mb-2"></div>
                        <p class="text-sm text-gray-500">{{ $t('billing.processing_payment') }}</p>
                    </div>
                    <button
                        v-else
                        @click="payNow"
                        :disabled="processingPayment"
                        class="w-full bg-indigo-600 text-white py-3 px-4 rounded-lg shadow-sm text-sm font-semibold hover:bg-indigo-700 transition-colors disabled:opacity-50"
                    >
                        {{ $t('billing.pay_to_confirm') }}
                    </button>
                </div>

                <!-- Booking Confirmed & Paid -->
                <div v-if="r.status === 'confirmed' && inv && inv.status === 'paid'" class="bg-green-50 rounded-xl shadow-sm border border-green-200 p-6 text-center">
                    <svg class="w-10 h-10 text-green-500 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <p class="text-sm font-semibold text-green-800">{{ $t('billing.booking_confirmed_paid') }}</p>
                    <p class="text-xs text-green-600 mt-1">{{ formatMoney(inv.total_cents) }}</p>
                </div>

                <!-- Cancellation Policy -->
                <div v-if="['pending', 'confirmed'].includes(r.status) && r.free_cancellation_until" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h2 class="text-sm font-medium text-gray-500 uppercase tracking-wide mb-3">{{ $t('billing.cancellation_policy') }}</h2>
                    <div v-if="isFreeCancellation" class="flex items-start gap-2">
                        <svg class="w-5 h-5 text-green-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <div>
                            <p class="text-sm font-medium text-green-700">{{ $t('billing.within_free_cancellation') }}</p>
                            <p class="text-xs text-gray-500 mt-1">{{ $t('billing.free_cancellation_until') }} {{ formatDate(r.free_cancellation_until) }}</p>
                        </div>
                    </div>
                    <div v-else class="flex items-start gap-2">
                        <svg class="w-5 h-5 text-amber-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.07 16.5c-.77.833.192 2.5 1.732 2.5z" />
                        </svg>
                        <div>
                            <p class="text-sm font-medium text-amber-700">{{ $t('billing.past_free_cancellation') }}</p>
                            <p class="text-xs text-gray-500 mt-1">{{ $t('billing.cancellation_no_refund') }}</p>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h2 class="text-sm font-medium text-gray-500 uppercase tracking-wide mb-3">{{ $t('common.actions') }}</h2>
                    <div class="space-y-3">
                        <!-- Cancel -->
                        <div v-if="['pending', 'confirmed'].includes(r.status)">
                            <button
                                v-if="!showCancelForm"
                                @click="showCancelForm = true"
                                class="w-full bg-red-600 text-white py-2 px-4 rounded-lg shadow-sm text-sm font-medium hover:bg-red-700 transition-colors"
                            >
                                {{ $t('reservation.cancel') }}
                            </button>
                            <div v-if="!isFreeCancellation && r.status === 'confirmed' && !showCancelForm" class="mt-1">
                                <p class="text-xs text-amber-600 text-center">{{ $t('billing.cancellation_no_refund') }}</p>
                            </div>
                            <form v-if="showCancelForm" @submit.prevent="submitCancel" class="space-y-2">
                                <textarea
                                    v-model="cancelForm.reason"
                                    required
                                    :placeholder="$t('reservation.cancel_reason')"
                                    rows="3"
                                    class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm"
                                    :class="{ 'border-red-500': cancelForm.errors.reason }"
                                ></textarea>
                                <p v-if="cancelForm.errors.reason" class="text-sm text-red-600">{{ cancelForm.errors.reason }}</p>
                                <div class="flex gap-2">
                                    <button
                                        type="submit"
                                        :disabled="cancelForm.processing"
                                        class="flex-1 bg-red-600 text-white py-2 px-4 rounded-lg shadow-sm text-sm font-medium hover:bg-red-700 disabled:opacity-50"
                                    >
                                        {{ $t('reservation.confirm_cancel') }}
                                    </button>
                                    <button
                                        type="button"
                                        @click="showCancelForm = false"
                                        class="px-4 py-2 text-sm text-gray-500 hover:text-gray-700"
                                    >
                                        {{ $t('common.back') }}
                                    </button>
                                </div>
                            </form>
                        </div>

                        <p v-if="['checked_in'].includes(r.status)" class="text-sm text-gray-500 text-center">
                            {{ $t('reservation.checked_in_message') }}
                        </p>

                        <p v-if="['checked_out', 'cancelled'].includes(r.status)" class="text-sm text-gray-500 text-center">
                            {{ $t('reservation.no_actions') }}
                        </p>
                    </div>
                </div>

                <!-- Timeline -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h2 class="text-sm font-medium text-gray-500 uppercase tracking-wide mb-3">{{ $t('common.timeline') }}</h2>
                    <div class="space-y-2 text-sm">
                        <div>
                            <span class="text-gray-500">{{ $t('common.booked') }}</span>
                            <p class="text-gray-900">{{ r.timestamps.created_at }}</p>
                        </div>
                        <div v-if="r.timestamps.confirmed_at">
                            <span class="text-gray-500">{{ $t('common.confirmed') }}</span>
                            <p class="text-gray-900">{{ r.timestamps.confirmed_at }}</p>
                        </div>
                        <div v-if="r.timestamps.checked_in_at">
                            <span class="text-gray-500">{{ $t('status.checked_in') }}</span>
                            <p class="text-gray-900">{{ r.timestamps.checked_in_at }}</p>
                        </div>
                        <div v-if="r.timestamps.checked_out_at">
                            <span class="text-gray-500">{{ $t('status.checked_out') }}</span>
                            <p class="text-gray-900">{{ r.timestamps.checked_out_at }}</p>
                        </div>
                        <div v-if="r.timestamps.cancelled_at">
                            <span class="text-gray-500">{{ $t('status.cancelled') }}</span>
                            <p class="text-gray-900">{{ r.timestamps.cancelled_at }}</p>
                        </div>
                    </div>
                </div>

                <!-- Cancellation Reason -->
                <div v-if="r.status === 'cancelled' && r.cancellation_reason" class="bg-red-50 rounded-lg shadow p-6">
                    <h2 class="text-sm font-medium text-red-800 uppercase tracking-wide mb-2">{{ $t('common.cancellation_reason') }}</h2>
                    <p class="text-sm text-red-700">{{ r.cancellation_reason }}</p>
                </div>
            </div>
        </div>
    </div>
</template>
