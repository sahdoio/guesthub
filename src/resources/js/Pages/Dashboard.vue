<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { router, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

defineOptions({ layout: AuthenticatedLayout });

const { t } = useI18n();
const page = usePage();
const hasStays = computed(() => page.props.auth?.hasStays ?? page.props.auth?.hasHotels ?? false);

const props = defineProps({
    guestStats: { type: Object, default: () => ({}) },
    reservationStats: { type: Object, default: () => ({}) },
    stayStats: { type: Object, default: () => ({}) },
    billingStats: { type: Object, default: () => ({}) },
    pendingReservations: { type: Array, default: () => [] },
});

const statusColors = {
    pending: 'bg-amber-400',
    confirmed: 'bg-blue-400',
    checked_in: 'bg-emerald-400',
    checked_out: 'bg-gray-400',
    cancelled: 'bg-red-400',
};

const tierColors = {
    bronze: 'bg-orange-400',
    silver: 'bg-gray-400',
    gold: 'bg-amber-400',
    platinum: 'bg-violet-400',
};

const stayTypeColors = {
    room: 'bg-blue-400',
    entire_space: 'bg-purple-400',
};

const stayCategoryColors = {
    hotel_room: 'bg-indigo-400',
    house: 'bg-emerald-400',
    apartment: 'bg-amber-400',
};

const statusEntries = computed(() => {
    const byStatus = props.reservationStats.by_status || {};
    const keys = ['pending', 'confirmed', 'checked_in', 'checked_out', 'cancelled'];
    return keys.map(key => ({
        key,
        label: t('status.' + key),
        count: byStatus[key] || 0,
        color: statusColors[key],
    }));
});

const statusMax = computed(() => Math.max(1, ...statusEntries.value.map(e => e.count)));

const tierEntries = computed(() => {
    const byTier = props.guestStats.by_loyalty_tier || {};
    const keys = ['bronze', 'silver', 'gold', 'platinum'];
    return keys.map(key => ({
        key,
        label: t('tier.' + key),
        count: byTier[key] || 0,
        color: tierColors[key],
    }));
});

const tierMax = computed(() => Math.max(1, ...tierEntries.value.map(e => e.count)));

const stayTypeEntries = computed(() => {
    const byType = props.stayStats.by_type || {};
    return Object.keys(stayTypeColors).map(key => ({
        key,
        label: t('stay.type_' + key),
        count: byType[key] || 0,
        color: stayTypeColors[key],
    }));
});

const stayTypeTotal = computed(() => Math.max(1, stayTypeEntries.value.reduce((sum, e) => sum + e.count, 0)));

const stayCategoryEntries = computed(() => {
    const byCategory = props.stayStats.by_category || {};
    return Object.keys(stayCategoryColors).map(key => ({
        key,
        label: t('stay.category_' + key),
        count: byCategory[key] || 0,
        color: stayCategoryColors[key],
    }));
});

const stayCategoryMax = computed(() => Math.max(1, ...stayCategoryEntries.value.map(e => e.count)));

const invoiceStatusColors = {
    draft: 'bg-gray-400',
    issued: 'bg-amber-400',
    paid: 'bg-emerald-400',
    void: 'bg-red-400',
    refunded: 'bg-purple-400',
};

const invoiceStatusEntries = computed(() => {
    const byStatus = props.billingStats.by_status || {};
    return Object.keys(invoiceStatusColors).map(key => ({
        key,
        label: t('billing.status_' + key),
        count: byStatus[key] || 0,
        color: invoiceStatusColors[key],
    }));
});

const invoiceStatusMax = computed(() => Math.max(1, ...invoiceStatusEntries.value.map(e => e.count)));

const formatCurrency = (cents) => {
    return '$' + (cents / 100).toFixed(2);
};
</script>

<template>
    <div>
        <h1 class="text-2xl font-bold text-gray-900 mb-8">{{ $t('dashboard.title') }}</h1>

        <!-- No Stays CTA -->
        <div v-if="!hasStays" class="bg-white rounded-xl shadow-sm border border-gray-100 p-12 text-center mb-8">
            <div class="mx-auto w-16 h-16 bg-indigo-50 rounded-full flex items-center justify-center mb-4">
                <svg class="w-8 h-8 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
            </div>
            <h2 class="text-xl font-semibold text-gray-900 mb-2">{{ $t('dashboard.no_stays_title') }}</h2>
            <p class="text-gray-500 mb-6 max-w-md mx-auto">{{ $t('dashboard.no_stays_subtitle') }}</p>
            <a
                href="/stays/create"
                class="inline-flex items-center gap-2 px-6 py-3 bg-indigo-600 text-white font-medium rounded-lg hover:bg-indigo-700 transition-colors"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                {{ $t('dashboard.create_stay') }}
            </a>
        </div>

        <!-- Pending Reservations -->
        <div v-if="pendingReservations.length > 0" class="bg-amber-50 border border-amber-200 rounded-xl p-6 mb-8">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 bg-amber-100 rounded-full flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
                    </svg>
                </div>
                <div>
                    <h2 class="text-sm font-semibold text-amber-800">{{ $t('dashboard.pending_reservations') }}</h2>
                    <p class="text-xs text-amber-600">{{ $t('dashboard.pending_reservations_subtitle', { count: pendingReservations.length }) }}</p>
                </div>
            </div>
            <div class="space-y-2">
                <div
                    v-for="res in pendingReservations"
                    :key="res.id"
                    class="flex items-center justify-between bg-white rounded-lg p-3 shadow-sm"
                >
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-medium text-gray-900">{{ res.guest?.full_name || '-' }}</span>
                            <span v-if="res.stay?.name" class="text-xs text-gray-500">&mdash; {{ res.stay.name }}</span>
                        </div>
                        <p class="text-xs text-gray-500 mt-0.5">{{ res.period.check_in }} &rarr; {{ res.period.check_out }} ({{ res.period.nights }} {{ $t('reservation.nights') }})</p>
                    </div>
                    <div class="flex items-center gap-2 shrink-0 ml-3">
                        <a :href="`/reservations/${res.id}`" class="text-xs text-indigo-600 hover:text-indigo-800 font-medium">{{ $t('common.view') }}</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-6 gap-5 mb-8">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition-shadow">
                <h2 class="text-xs font-semibold text-gray-400 uppercase tracking-wider">{{ $t('dashboard.total_reservations') }}</h2>
                <p class="mt-2 text-3xl font-bold text-gray-900">{{ reservationStats.total ?? 0 }}</p>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition-shadow">
                <h2 class="text-xs font-semibold text-gray-400 uppercase tracking-wider">{{ $t('dashboard.total_guests') }}</h2>
                <p class="mt-2 text-3xl font-bold text-gray-900">{{ guestStats.total ?? 0 }}</p>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition-shadow">
                <h2 class="text-xs font-semibold text-gray-400 uppercase tracking-wider">{{ $t('dashboard.checkins_today') }}</h2>
                <p class="mt-2 text-3xl font-bold text-emerald-600">{{ reservationStats.today_check_ins ?? 0 }}</p>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition-shadow">
                <h2 class="text-xs font-semibold text-gray-400 uppercase tracking-wider">{{ $t('dashboard.checkouts_today') }}</h2>
                <p class="mt-2 text-3xl font-bold text-gray-600">{{ reservationStats.today_check_outs ?? 0 }}</p>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition-shadow">
                <h2 class="text-xs font-semibold text-gray-400 uppercase tracking-wider">{{ $t('dashboard.total_stays') }}</h2>
                <p class="mt-2 text-3xl font-bold text-gray-900">{{ stayStats.total ?? 0 }}</p>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition-shadow">
                <h2 class="text-xs font-semibold text-gray-400 uppercase tracking-wider">{{ $t('dashboard.revenue') }}</h2>
                <p class="mt-2 text-3xl font-bold text-emerald-600">{{ formatCurrency(billingStats.total_revenue_cents ?? 0) }}</p>
            </div>
        </div>

        <!-- Charts -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Reservations by Status -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h2 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-5">{{ $t('dashboard.reservations_by_status') }}</h2>
                <div class="space-y-3">
                    <div v-for="entry in statusEntries" :key="entry.key" class="flex items-center gap-3">
                        <span class="text-sm text-gray-600 w-24 shrink-0">{{ entry.label }}</span>
                        <div class="flex-1 bg-gray-100 rounded-full h-5 overflow-hidden">
                            <div
                                class="h-full rounded-full transition-all duration-500"
                                :class="entry.color"
                                :style="{ width: (entry.count / statusMax * 100) + '%', minWidth: entry.count > 0 ? '1.25rem' : '0' }"
                            ></div>
                        </div>
                        <span class="text-sm font-semibold text-gray-700 w-8 text-right tabular-nums">{{ entry.count }}</span>
                    </div>
                </div>
            </div>

            <!-- Guests by Loyalty Tier -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h2 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-5">{{ $t('dashboard.guests_by_tier') }}</h2>
                <div class="space-y-3">
                    <div v-for="entry in tierEntries" :key="entry.key" class="flex items-center gap-3">
                        <span class="text-sm text-gray-600 w-24 shrink-0">{{ entry.label }}</span>
                        <div class="flex-1 bg-gray-100 rounded-full h-5 overflow-hidden">
                            <div
                                class="h-full rounded-full transition-all duration-500"
                                :class="entry.color"
                                :style="{ width: (entry.count / tierMax * 100) + '%', minWidth: entry.count > 0 ? '1.25rem' : '0' }"
                            ></div>
                        </div>
                        <span class="text-sm font-semibold text-gray-700 w-8 text-right tabular-nums">{{ entry.count }}</span>
                    </div>
                </div>
            </div>

            <!-- Stays by Category -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h2 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-5">{{ $t('dashboard.stays_by_category') }}</h2>
                <div class="space-y-3">
                    <div v-for="entry in stayCategoryEntries" :key="entry.key" class="flex items-center gap-3">
                        <span class="text-sm text-gray-600 w-24 shrink-0">{{ entry.label }}</span>
                        <div class="flex-1 bg-gray-100 rounded-full h-5 overflow-hidden">
                            <div
                                class="h-full rounded-full transition-all duration-500"
                                :class="entry.color"
                                :style="{ width: (entry.count / stayCategoryMax * 100) + '%', minWidth: entry.count > 0 ? '1.25rem' : '0' }"
                            ></div>
                        </div>
                        <span class="text-sm font-semibold text-gray-700 w-8 text-right tabular-nums">{{ entry.count }}</span>
                    </div>
                </div>
            </div>

            <!-- Invoices by Status -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h2 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-5">{{ $t('dashboard.invoices_by_status') }}</h2>
                <div class="space-y-3">
                    <div v-for="entry in invoiceStatusEntries" :key="entry.key" class="flex items-center gap-3">
                        <span class="text-sm text-gray-600 w-24 shrink-0">{{ entry.label }}</span>
                        <div class="flex-1 bg-gray-100 rounded-full h-5 overflow-hidden">
                            <div
                                class="h-full rounded-full transition-all duration-500"
                                :class="entry.color"
                                :style="{ width: (entry.count / invoiceStatusMax * 100) + '%', minWidth: entry.count > 0 ? '1.25rem' : '0' }"
                            ></div>
                        </div>
                        <span class="text-sm font-semibold text-gray-700 w-8 text-right tabular-nums">{{ entry.count }}</span>
                    </div>
                </div>
            </div>

            <!-- Stays by Type -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h2 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-5">{{ $t('dashboard.stays_by_type') }}</h2>
                <div class="flex items-end justify-center gap-10 h-48">
                    <div v-for="entry in stayTypeEntries" :key="entry.key" class="flex flex-col items-center gap-2">
                        <span class="text-sm font-bold text-gray-700 tabular-nums">{{ entry.count }}</span>
                        <div class="w-16 bg-gray-100 rounded-t-lg overflow-hidden flex items-end" style="height: 120px;">
                            <div
                                class="w-full rounded-t-lg transition-all duration-500"
                                :class="entry.color"
                                :style="{ height: (entry.count / stayTypeTotal * 100) + '%', minHeight: entry.count > 0 ? '0.5rem' : '0' }"
                            ></div>
                        </div>
                        <span class="text-xs text-gray-500 font-medium">{{ entry.label }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
