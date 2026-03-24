<script setup>
import { useI18n } from 'vue-i18n';
import { ref, computed, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';
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

const formatMoney = (cents) => '$' + (cents / 100).toFixed(2);

const formatDate = (dateStr) => {
    if (!dateStr) return '';
    const d = new Date(dateStr + 'T00:00:00');
    return d.toLocaleDateString(undefined, { year: 'numeric', month: 'long', day: 'numeric' });
};

// Payment state
const loading = ref(false);
const paymentError = ref(null);
const paymentSuccess = ref(false);
const cardElement = ref(null);
let stripe = null;
let elements = null;

const stripeConfigured = computed(() => !!props.stripePublishableKey);

onMounted(async () => {
    if (!stripeConfigured.value) return;

    // Load Stripe.js dynamically
    if (!window.Stripe) {
        const script = document.createElement('script');
        script.src = 'https://js.stripe.com/v3/';
        script.async = true;
        script.onload = () => initStripe();
        document.head.appendChild(script);
    } else {
        initStripe();
    }
});

const initStripe = () => {
    stripe = window.Stripe(props.stripePublishableKey);
    elements = stripe.elements();
    const card = elements.create('card', {
        style: {
            base: {
                fontSize: '16px',
                color: '#1f2937',
                fontFamily: 'ui-sans-serif, system-ui, sans-serif',
                '::placeholder': { color: '#9ca3af' },
            },
            invalid: { color: '#dc2626' },
        },
    });
    card.mount('#card-element');
    cardElement.value = card;

    card.on('change', (event) => {
        paymentError.value = event.error ? event.error.message : null;
    });
};

const submitPayment = async () => {
    if (loading.value) return;
    loading.value = true;
    paymentError.value = null;

    try {
        // Step 1: Create PaymentIntent on the server
        const { data } = await axios.post(`/portal/billing/${inv.value.uuid}/pay`);

        if (data.simulated) {
            // Stripe not configured — payment was simulated on server
            paymentSuccess.value = true;
            setTimeout(() => {
                router.visit(`/portal/reservations/${r.value.id}`);
            }, 2000);
            return;
        }

        if (data.error) {
            paymentError.value = data.error;
            loading.value = false;
            return;
        }

        // Step 2: Confirm payment with Stripe Elements
        const { error, paymentIntent } = await stripe.confirmCardPayment(data.client_secret, {
            payment_method: { card: cardElement.value },
        });

        if (error) {
            paymentError.value = error.message;
            loading.value = false;
            return;
        }

        if (paymentIntent.status === 'succeeded') {
            paymentSuccess.value = true;
            // Wait a moment for webhook to process, then redirect
            setTimeout(() => {
                router.visit(`/portal/reservations/${r.value.id}`);
            }, 3000);
        }
    } catch (err) {
        paymentError.value = 'An unexpected error occurred. Please try again.';
        loading.value = false;
    }
};

const simulatePayment = async () => {
    if (loading.value) return;
    loading.value = true;
    paymentError.value = null;

    try {
        const { data } = await axios.post(`/portal/billing/${inv.value.uuid}/pay`);

        if (data.simulated || data.client_secret) {
            paymentSuccess.value = true;
            setTimeout(() => {
                router.visit(`/portal/reservations/${r.value.id}`);
            }, 2000);
        } else if (data.error) {
            paymentError.value = data.error;
            loading.value = false;
        }
    } catch (err) {
        paymentError.value = 'An unexpected error occurred.';
        loading.value = false;
    }
};
</script>

<template>
    <div class="max-w-4xl mx-auto">
        <!-- Header -->
        <div class="mb-8">
            <a href="/portal/stays" class="text-gray-500 hover:text-gray-700 text-sm">&larr; {{ $t('common.back') }}</a>
            <h1 class="text-2xl font-bold text-gray-900 mt-2">{{ $t('billing.checkout_title') }}</h1>
            <p class="text-gray-500 text-sm mt-1">{{ $t('billing.checkout_subtitle') }}</p>
        </div>

        <!-- Success State -->
        <div v-if="paymentSuccess" class="bg-green-50 border border-green-200 rounded-xl p-8 text-center">
            <svg class="w-16 h-16 text-green-500 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <h2 class="text-xl font-bold text-green-800 mb-2">{{ $t('billing.payment_success') }}</h2>
            <p class="text-green-600 text-sm">{{ $t('billing.payment_success_description') }}</p>
            <div class="mt-4">
                <div class="w-6 h-6 border-2 border-green-500 border-t-transparent rounded-full animate-spin mx-auto"></div>
                <p class="text-xs text-gray-500 mt-2">{{ $t('billing.redirecting') }}</p>
            </div>
        </div>

        <!-- Checkout Form -->
        <div v-else class="grid grid-cols-1 lg:grid-cols-5 gap-8">
            <!-- Order Summary (right on desktop) -->
            <div class="lg:col-span-2 lg:order-2">
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 sticky top-24">
                    <h2 class="text-sm font-semibold text-gray-900 uppercase tracking-wide mb-4">{{ $t('billing.order_summary') }}</h2>

                    <!-- Stay Info -->
                    <div v-if="r.stay?.name" class="flex items-start gap-3 mb-4 pb-4 border-b border-gray-100">
                        <div class="w-10 h-10 bg-indigo-50 rounded-lg flex items-center justify-center shrink-0">
                            <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                            </svg>
                        </div>
                        <div>
                            <p class="font-semibold text-gray-900 text-sm">{{ r.stay.name }}</p>
                            <p v-if="r.stay.address" class="text-xs text-gray-500 mt-0.5">{{ r.stay.address }}</p>
                        </div>
                    </div>

                    <!-- Dates -->
                    <div class="grid grid-cols-2 gap-3 mb-4 pb-4 border-b border-gray-100 text-sm">
                        <div>
                            <span class="text-gray-500 text-xs">{{ $t('reservation.check_in') }}</span>
                            <p class="font-medium text-gray-900">{{ formatDate(r.period.check_in) }}</p>
                        </div>
                        <div>
                            <span class="text-gray-500 text-xs">{{ $t('reservation.check_out') }}</span>
                            <p class="font-medium text-gray-900">{{ formatDate(r.period.check_out) }}</p>
                        </div>
                        <div class="col-span-2">
                            <span class="text-gray-500 text-xs">{{ $t('reservation.nights') }}</span>
                            <p class="font-medium text-gray-900">{{ r.period.nights }}</p>
                        </div>
                    </div>

                    <!-- Line Items -->
                    <div v-if="inv" class="space-y-2 mb-4 pb-4 border-b border-gray-100">
                        <div v-for="item in inv.line_items" :key="item.id" class="flex justify-between text-sm">
                            <span class="text-gray-600">{{ item.description }}</span>
                            <span class="font-medium text-gray-900">{{ formatMoney(item.total_cents) }}</span>
                        </div>
                    </div>

                    <!-- Totals -->
                    <div v-if="inv" class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-500">{{ $t('billing.subtotal') }}</span>
                            <span class="text-gray-900">{{ formatMoney(inv.subtotal_cents) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">{{ $t('billing.tax') }}</span>
                            <span class="text-gray-900">{{ formatMoney(inv.tax_cents) }}</span>
                        </div>
                        <div class="flex justify-between pt-2 border-t border-gray-200">
                            <span class="font-semibold text-gray-900">{{ $t('billing.total') }}</span>
                            <span class="font-bold text-lg text-indigo-700">{{ formatMoney(inv.total_cents) }}</span>
                        </div>
                    </div>

                    <!-- Cancellation Policy -->
                    <div v-if="r.free_cancellation_until" class="mt-4 pt-4 border-t border-gray-100">
                        <div class="flex items-start gap-2">
                            <svg class="w-4 h-4 text-green-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <p class="text-xs text-green-700">{{ $t('billing.free_cancellation_notice') }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Payment Form (left on desktop) -->
            <div class="lg:col-span-3 lg:order-1">
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h2 class="text-sm font-semibold text-gray-900 uppercase tracking-wide mb-4">{{ $t('billing.payment_details') }}</h2>

                    <!-- Stripe Card Element -->
                    <div v-if="stripeConfigured">
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">{{ $t('billing.card_information') }}</label>
                            <div
                                id="card-element"
                                class="border border-gray-200 rounded-lg px-4 py-3 bg-white focus-within:ring-2 focus-within:ring-indigo-500 focus-within:border-transparent transition"
                            ></div>
                        </div>

                        <!-- Error -->
                        <div v-if="paymentError" class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg">
                            <p class="text-sm text-red-700">{{ paymentError }}</p>
                        </div>

                        <!-- Pay Button -->
                        <button
                            @click="submitPayment"
                            :disabled="loading"
                            class="w-full bg-indigo-600 text-white py-3 px-4 rounded-lg shadow-sm text-sm font-semibold hover:bg-indigo-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
                        >
                            <div v-if="loading" class="w-5 h-5 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
                            <span v-if="loading">{{ $t('billing.processing_payment') }}</span>
                            <span v-else>
                                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                                {{ $t('billing.pay_amount', { amount: inv ? formatMoney(inv.total_cents) : '' }) }}
                            </span>
                        </button>
                    </div>

                    <!-- No Stripe configured (dev mode) -->
                    <div v-else>
                        <div class="mb-4 p-4 bg-amber-50 border border-amber-200 rounded-lg">
                            <p class="text-sm text-amber-700">{{ $t('billing.stripe_not_configured') }}</p>
                        </div>

                        <!-- Error -->
                        <div v-if="paymentError" class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg">
                            <p class="text-sm text-red-700">{{ paymentError }}</p>
                        </div>

                        <button
                            @click="simulatePayment"
                            :disabled="loading"
                            class="w-full bg-indigo-600 text-white py-3 px-4 rounded-lg shadow-sm text-sm font-semibold hover:bg-indigo-700 transition-colors disabled:opacity-50 flex items-center justify-center gap-2"
                        >
                            <div v-if="loading" class="w-5 h-5 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
                            <span v-if="loading">{{ $t('billing.processing_payment') }}</span>
                            <span v-else>{{ $t('billing.simulate_payment', { amount: inv ? formatMoney(inv.total_cents) : '' }) }}</span>
                        </button>
                    </div>

                    <!-- Secure badge -->
                    <div class="mt-4 flex items-center justify-center gap-2 text-xs text-gray-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                        {{ $t('billing.secure_payment') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
