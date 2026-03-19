<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

defineOptions({ layout: AuthenticatedLayout });

const { t } = useI18n();
const page = usePage();
const hasHotels = computed(() => page.props.auth?.hasHotels ?? false);

const props = defineProps({
    guestStats: { type: Object, default: () => ({}) },
    reservationStats: { type: Object, default: () => ({}) },
    roomStats: { type: Object, default: () => ({}) },
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

const roomTypeColors = {
    SINGLE: 'bg-blue-400',
    DOUBLE: 'bg-indigo-400',
    SUITE: 'bg-emerald-400',
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

const roomEntries = computed(() => {
    const byRoom = props.reservationStats.by_room_type || {};
    return Object.keys(roomTypeColors).map(key => ({
        key,
        label: t('room_type.' + key.toLowerCase()),
        count: byRoom[key] || 0,
        color: roomTypeColors[key],
    }));
});

const roomTotal = computed(() => Math.max(1, roomEntries.value.reduce((sum, e) => sum + e.count, 0)));

const roomStatusColors = {
    available: 'bg-emerald-400',
    occupied: 'bg-blue-400',
    maintenance: 'bg-amber-400',
    out_of_order: 'bg-red-400',
};

const roomStatusEntries = computed(() => {
    const byStatus = props.roomStats.by_status || {};
    const keys = ['available', 'occupied', 'maintenance', 'out_of_order'];
    return keys.map(key => ({
        key,
        label: t('status.' + key),
        count: byStatus[key] || 0,
        color: roomStatusColors[key],
    }));
});

const roomStatusMax = computed(() => Math.max(1, ...roomStatusEntries.value.map(e => e.count)));
</script>

<template>
    <div>
        <h1 class="text-2xl font-bold text-gray-900 mb-8">{{ $t('dashboard.title') }}</h1>

        <!-- No Hotels CTA -->
        <div v-if="!hasHotels" class="bg-white rounded-xl shadow-sm border border-gray-100 p-12 text-center mb-8">
            <div class="mx-auto w-16 h-16 bg-indigo-50 rounded-full flex items-center justify-center mb-4">
                <svg class="w-8 h-8 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
            </div>
            <h2 class="text-xl font-semibold text-gray-900 mb-2">{{ $t('dashboard.no_hotels_title') }}</h2>
            <p class="text-gray-500 mb-6 max-w-md mx-auto">{{ $t('dashboard.no_hotels_subtitle') }}</p>
            <a
                href="/hotels/create"
                class="inline-flex items-center gap-2 px-6 py-3 bg-indigo-600 text-white font-medium rounded-lg hover:bg-indigo-700 transition-colors"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                {{ $t('dashboard.create_hotel') }}
            </a>
        </div>

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-5 gap-5 mb-8">
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
                <h2 class="text-xs font-semibold text-gray-400 uppercase tracking-wider">{{ $t('dashboard.total_rooms') }}</h2>
                <p class="mt-2 text-3xl font-bold text-gray-900">{{ roomStats.total ?? 0 }}</p>
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

            <!-- Room Inventory by Status -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h2 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-5">{{ $t('dashboard.room_inventory') }}</h2>
                <div class="space-y-3">
                    <div v-for="entry in roomStatusEntries" :key="entry.key" class="flex items-center gap-3">
                        <span class="text-sm text-gray-600 w-24 shrink-0">{{ entry.label }}</span>
                        <div class="flex-1 bg-gray-100 rounded-full h-5 overflow-hidden">
                            <div
                                class="h-full rounded-full transition-all duration-500"
                                :class="entry.color"
                                :style="{ width: (entry.count / roomStatusMax * 100) + '%', minWidth: entry.count > 0 ? '1.25rem' : '0' }"
                            ></div>
                        </div>
                        <span class="text-sm font-semibold text-gray-700 w-8 text-right tabular-nums">{{ entry.count }}</span>
                    </div>
                </div>
            </div>

            <!-- Reservations by Room Type -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h2 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-5">{{ $t('dashboard.reservations_by_room_type') }}</h2>
                <div class="flex items-end justify-center gap-10 h-48">
                    <div v-for="entry in roomEntries" :key="entry.key" class="flex flex-col items-center gap-2">
                        <span class="text-sm font-bold text-gray-700 tabular-nums">{{ entry.count }}</span>
                        <div class="w-16 bg-gray-100 rounded-t-lg overflow-hidden flex items-end" style="height: 120px;">
                            <div
                                class="w-full rounded-t-lg transition-all duration-500"
                                :class="entry.color"
                                :style="{ height: (entry.count / roomTotal * 100) + '%', minHeight: entry.count > 0 ? '0.5rem' : '0' }"
                            ></div>
                        </div>
                        <span class="text-xs text-gray-500 font-medium">{{ entry.label }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
