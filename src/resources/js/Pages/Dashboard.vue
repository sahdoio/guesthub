<script setup>
import { computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({
    guestStats: Object,
    reservationStats: Object,
});

const statusLabels = {
    pending: 'Pending',
    confirmed: 'Confirmed',
    checked_in: 'Checked In',
    checked_out: 'Checked Out',
    cancelled: 'Cancelled',
};

const statusColors = {
    pending: 'bg-yellow-400',
    confirmed: 'bg-blue-400',
    checked_in: 'bg-green-400',
    checked_out: 'bg-gray-400',
    cancelled: 'bg-red-400',
};

const tierLabels = {
    bronze: 'Bronze',
    silver: 'Silver',
    gold: 'Gold',
    platinum: 'Platinum',
};

const tierColors = {
    bronze: 'bg-orange-400',
    silver: 'bg-gray-400',
    gold: 'bg-yellow-400',
    platinum: 'bg-purple-400',
};

const roomTypeColors = {
    SINGLE: 'bg-blue-400',
    DOUBLE: 'bg-indigo-400',
    SUITE: 'bg-emerald-400',
};

const statusEntries = computed(() => {
    const byStatus = props.reservationStats.by_status || {};
    return Object.keys(statusLabels).map(key => ({
        key,
        label: statusLabels[key],
        count: byStatus[key] || 0,
        color: statusColors[key],
    }));
});

const statusMax = computed(() => Math.max(1, ...statusEntries.value.map(e => e.count)));

const tierEntries = computed(() => {
    const byTier = props.guestStats.by_loyalty_tier || {};
    return Object.keys(tierLabels).map(key => ({
        key,
        label: tierLabels[key],
        count: byTier[key] || 0,
        color: tierColors[key],
    }));
});

const tierMax = computed(() => Math.max(1, ...tierEntries.value.map(e => e.count)));

const roomEntries = computed(() => {
    const byRoom = props.reservationStats.by_room_type || {};
    return Object.keys(roomTypeColors).map(key => ({
        key,
        label: key.charAt(0) + key.slice(1).toLowerCase(),
        count: byRoom[key] || 0,
        color: roomTypeColors[key],
    }));
});

const roomTotal = computed(() => Math.max(1, roomEntries.value.reduce((sum, e) => sum + e.count, 0)));
</script>

<template>
    <div>
        <h1 class="text-2xl font-bold text-gray-800 mb-6">Dashboard</h1>

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-sm font-medium text-gray-500 uppercase tracking-wide">Total Reservations</h2>
                <p class="mt-2 text-3xl font-bold text-gray-900">{{ reservationStats.total }}</p>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-sm font-medium text-gray-500 uppercase tracking-wide">Total Guests</h2>
                <p class="mt-2 text-3xl font-bold text-gray-900">{{ guestStats.total }}</p>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-sm font-medium text-gray-500 uppercase tracking-wide">Check-ins Today</h2>
                <p class="mt-2 text-3xl font-bold text-green-600">{{ reservationStats.today_check_ins }}</p>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-sm font-medium text-gray-500 uppercase tracking-wide">Check-outs Today</h2>
                <p class="mt-2 text-3xl font-bold text-gray-600">{{ reservationStats.today_check_outs }}</p>
            </div>
        </div>

        <!-- Charts -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Reservations by Status -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-sm font-medium text-gray-500 uppercase tracking-wide mb-4">Reservations by Status</h2>
                <div class="space-y-3">
                    <div v-for="entry in statusEntries" :key="entry.key" class="flex items-center gap-3">
                        <span class="text-sm text-gray-600 w-24 shrink-0">{{ entry.label }}</span>
                        <div class="flex-1 bg-gray-100 rounded-full h-6 overflow-hidden">
                            <div
                                class="h-full rounded-full transition-all duration-500"
                                :class="entry.color"
                                :style="{ width: (entry.count / statusMax * 100) + '%', minWidth: entry.count > 0 ? '1.5rem' : '0' }"
                            ></div>
                        </div>
                        <span class="text-sm font-semibold text-gray-700 w-8 text-right">{{ entry.count }}</span>
                    </div>
                </div>
            </div>

            <!-- Guests by Loyalty Tier -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-sm font-medium text-gray-500 uppercase tracking-wide mb-4">Guests by Loyalty Tier</h2>
                <div class="space-y-3">
                    <div v-for="entry in tierEntries" :key="entry.key" class="flex items-center gap-3">
                        <span class="text-sm text-gray-600 w-24 shrink-0">{{ entry.label }}</span>
                        <div class="flex-1 bg-gray-100 rounded-full h-6 overflow-hidden">
                            <div
                                class="h-full rounded-full transition-all duration-500"
                                :class="entry.color"
                                :style="{ width: (entry.count / tierMax * 100) + '%', minWidth: entry.count > 0 ? '1.5rem' : '0' }"
                            ></div>
                        </div>
                        <span class="text-sm font-semibold text-gray-700 w-8 text-right">{{ entry.count }}</span>
                    </div>
                </div>
            </div>

            <!-- Reservations by Room Type -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-sm font-medium text-gray-500 uppercase tracking-wide mb-4">Reservations by Room Type</h2>
                <div class="flex items-end justify-center gap-8 h-48">
                    <div v-for="entry in roomEntries" :key="entry.key" class="flex flex-col items-center gap-2">
                        <span class="text-sm font-semibold text-gray-700">{{ entry.count }}</span>
                        <div class="w-16 bg-gray-100 rounded-t-md overflow-hidden flex items-end" style="height: 120px;">
                            <div
                                class="w-full rounded-t-md transition-all duration-500"
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
