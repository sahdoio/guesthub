<script setup>
import GuestPortalLayout from '@/Layouts/GuestPortalLayout.vue';

defineOptions({ layout: GuestPortalLayout });

const props = defineProps({
    guest: Object,
    reservations: Array,
    reservationsMeta: Object,
});

const statusColors = {
    pending: 'bg-yellow-100 text-yellow-800',
    confirmed: 'bg-blue-100 text-blue-800',
    checked_in: 'bg-green-100 text-green-800',
    checked_out: 'bg-gray-100 text-gray-800',
    cancelled: 'bg-red-100 text-red-800',
};

const statusLabel = (status) => status.replace('_', ' ');

const tierColors = {
    bronze: 'bg-orange-100 text-orange-800',
    silver: 'bg-gray-100 text-gray-800',
    gold: 'bg-yellow-100 text-yellow-800',
    platinum: 'bg-purple-100 text-purple-800',
};
</script>

<template>
    <div>
        <h1 class="text-2xl font-bold text-gray-800 mb-6">Welcome, {{ guest?.full_name }}</h1>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
            <!-- Profile Summary -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-sm font-medium text-gray-500 uppercase tracking-wide mb-3">My Profile</h2>
                <div class="space-y-2 text-sm" v-if="guest">
                    <div>
                        <span class="text-gray-500">Email</span>
                        <p class="font-medium text-gray-900">{{ guest.email }}</p>
                    </div>
                    <div>
                        <span class="text-gray-500">Phone</span>
                        <p class="font-medium text-gray-900">{{ guest.phone }}</p>
                    </div>
                    <div>
                        <span class="text-gray-500">Loyalty Tier</span>
                        <p>
                            <span
                                class="inline-flex px-2 py-0.5 text-xs font-semibold rounded-full capitalize"
                                :class="tierColors[guest.loyalty_tier]"
                            >
                                {{ guest.loyalty_tier }}
                            </span>
                        </p>
                    </div>
                </div>
                <a
                    href="/portal/profile"
                    class="mt-4 inline-block text-sm text-blue-600 hover:text-blue-800 font-medium"
                >
                    View full profile
                </a>
            </div>

            <!-- Quick Stats -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-sm font-medium text-gray-500 uppercase tracking-wide mb-3">Reservations</h2>
                <p class="text-3xl font-bold text-gray-900">{{ reservationsMeta.total }}</p>
                <p class="text-sm text-gray-500 mt-1">Total reservations</p>
                <a
                    href="/portal/reservations"
                    class="mt-4 inline-block text-sm text-blue-600 hover:text-blue-800 font-medium"
                >
                    View all reservations
                </a>
            </div>

            <!-- Quick Action -->
            <div class="bg-white rounded-lg shadow p-6 flex flex-col justify-between">
                <div>
                    <h2 class="text-sm font-medium text-gray-500 uppercase tracking-wide mb-3">Quick Actions</h2>
                    <p class="text-sm text-gray-600 mb-4">Book a new stay or manage your profile.</p>
                </div>
                <div class="space-y-2">
                    <a
                        href="/portal/reservations/create"
                        class="block w-full text-center bg-blue-600 text-white py-2 px-4 rounded-md text-sm font-medium hover:bg-blue-700 transition-colors"
                    >
                        New Reservation
                    </a>
                    <a
                        href="/portal/profile/edit"
                        class="block w-full text-center border border-gray-300 text-gray-700 py-2 px-4 rounded-md text-sm font-medium hover:bg-gray-50 transition-colors"
                    >
                        Edit Profile
                    </a>
                </div>
            </div>
        </div>

        <!-- Recent Reservations -->
        <div class="bg-white rounded-lg shadow">
            <div class="p-4 border-b border-gray-200 flex items-center justify-between">
                <h2 class="text-sm font-medium text-gray-500 uppercase tracking-wide">Recent Reservations</h2>
                <a href="/portal/reservations" class="text-sm text-blue-600 hover:text-blue-800 font-medium">
                    View all
                </a>
            </div>

            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Period</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Room Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3"></th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <tr v-if="reservations.length === 0">
                        <td colspan="4" class="px-6 py-8 text-center text-sm text-gray-500">
                            No reservations yet.
                            <a href="/portal/reservations/create" class="text-blue-600 hover:text-blue-800 font-medium ml-1">Book your first stay</a>
                        </td>
                    </tr>
                    <tr v-for="r in reservations" :key="r.id" class="hover:bg-gray-50">
                        <td class="px-6 py-4 text-sm text-gray-700">
                            {{ r.period.check_in }} &rarr; {{ r.period.check_out }}
                            <div class="text-gray-500">{{ r.period.nights }} night{{ r.period.nights !== 1 ? 's' : '' }}</div>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-700">
                            <div>{{ r.room_type }}</div>
                            <div v-if="r.assigned_room_number" class="text-gray-500">Room {{ r.assigned_room_number }}</div>
                        </td>
                        <td class="px-6 py-4">
                            <span
                                class="inline-flex px-2 py-1 text-xs font-semibold rounded-full capitalize"
                                :class="statusColors[r.status]"
                            >
                                {{ statusLabel(r.status) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <a
                                :href="`/portal/reservations/${r.id}`"
                                class="text-blue-600 hover:text-blue-800 text-sm font-medium"
                            >
                                View
                            </a>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
