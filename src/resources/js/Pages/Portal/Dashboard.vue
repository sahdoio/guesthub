<script setup>
import { useI18n } from 'vue-i18n';
import GuestPortalLayout from '@/Layouts/GuestPortalLayout.vue';

defineOptions({ layout: GuestPortalLayout });

const { t } = useI18n();

const props = defineProps({
    guest: Object,
    reservations: Array,
    reservationsMeta: Object,
    hotels: { type: Array, default: () => [] },
});

const statusColors = {
    pending: 'bg-yellow-100 text-yellow-800',
    confirmed: 'bg-blue-100 text-blue-800',
    checked_in: 'bg-green-100 text-green-800',
    checked_out: 'bg-gray-100 text-gray-800',
    cancelled: 'bg-red-100 text-red-800',
};

const statusLabel = (status) => t('status.' + status);

const tierColors = {
    bronze: 'bg-orange-100 text-orange-800',
    silver: 'bg-gray-100 text-gray-800',
    gold: 'bg-yellow-100 text-yellow-800',
    platinum: 'bg-purple-100 text-purple-800',
};
</script>

<template>
    <div>
        <h1 class="text-2xl font-bold text-gray-800 mb-6">{{ $t('common.welcome') }} {{ guest?.full_name }}</h1>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
            <!-- Profile Summary -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-sm font-medium text-gray-500 uppercase tracking-wide mb-3">{{ $t('nav.my_profile') }}</h2>
                <div class="space-y-2 text-sm" v-if="guest">
                    <div>
                        <span class="text-gray-500">{{ $t('guest.email') }}</span>
                        <p class="font-medium text-gray-900">{{ guest.email }}</p>
                    </div>
                    <div>
                        <span class="text-gray-500">{{ $t('guest.phone') }}</span>
                        <p class="font-medium text-gray-900">{{ guest.phone }}</p>
                    </div>
                    <div>
                        <span class="text-gray-500">{{ $t('guest.loyalty_tier') }}</span>
                        <p>
                            <span
                                class="inline-flex px-2 py-0.5 text-xs font-semibold rounded-full capitalize"
                                :class="tierColors[guest.loyalty_tier]"
                            >
                                {{ $t('tier.' + guest.loyalty_tier) }}
                            </span>
                        </p>
                    </div>
                </div>
                <a
                    href="/portal/profile"
                    class="mt-4 inline-block text-sm text-blue-600 hover:text-blue-800 font-medium"
                >
                    {{ $t('common.view_full_profile') }}
                </a>
            </div>

            <!-- Quick Stats -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-sm font-medium text-gray-500 uppercase tracking-wide mb-3">{{ $t('reservation.title') }}</h2>
                <p class="text-3xl font-bold text-gray-900">{{ reservationsMeta.total }}</p>
                <p class="text-sm text-gray-500 mt-1">{{ $t('reservation.total_reservations') }}</p>
                <a
                    href="/portal/reservations"
                    class="mt-4 inline-block text-sm text-blue-600 hover:text-blue-800 font-medium"
                >
                    {{ $t('reservation.view_all') }}
                </a>
            </div>

            <!-- Quick Action -->
            <div class="bg-white rounded-lg shadow p-6 flex flex-col justify-between">
                <div>
                    <h2 class="text-sm font-medium text-gray-500 uppercase tracking-wide mb-3">{{ $t('common.quick_actions') }}</h2>
                    <p class="text-sm text-gray-600 mb-4">{{ $t('common.quick_actions_subtitle') }}</p>
                </div>
                <div class="space-y-2">
                    <a
                        href="/portal/hotels"
                        class="block w-full text-center bg-blue-600 text-white py-2 px-4 rounded-md text-sm font-medium hover:bg-blue-700 transition-colors"
                    >
                        {{ $t('hotel.browse_hotels') }}
                    </a>
                    <a
                        href="/portal/profile/edit"
                        class="block w-full text-center border border-gray-300 text-gray-700 py-2 px-4 rounded-md text-sm font-medium hover:bg-gray-50 transition-colors"
                    >
                        {{ $t('guest.edit_profile') }}
                    </a>
                </div>
            </div>
        </div>

        <!-- Featured Hotels -->
        <div v-if="hotels.length > 0" class="mb-8">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-gray-800">{{ $t('hotel.featured_hotels') }}</h2>
                <a href="/portal/hotels" class="text-sm text-blue-600 hover:text-blue-800 font-medium">
                    {{ $t('common.view_all') }}
                </a>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <a
                    v-for="hotel in hotels.slice(0, 3)"
                    :key="hotel.uuid"
                    :href="`/portal/hotels/${hotel.slug}`"
                    class="bg-white rounded-lg shadow p-5 hover:shadow-md transition-shadow group"
                >
                    <h3 class="font-semibold text-gray-900 group-hover:text-blue-600 transition-colors">{{ hotel.name }}</h3>
                    <p v-if="hotel.description" class="text-sm text-gray-600 mt-1 line-clamp-2">{{ hotel.description }}</p>
                    <p v-if="hotel.address" class="text-xs text-gray-400 mt-2">{{ hotel.address }}</p>
                </a>
            </div>
        </div>

        <!-- Recent Reservations -->
        <div class="bg-white rounded-lg shadow">
            <div class="p-4 border-b border-gray-200 flex items-center justify-between">
                <h2 class="text-sm font-medium text-gray-500 uppercase tracking-wide">{{ $t('reservation.recent') }}</h2>
                <a href="/portal/reservations" class="text-sm text-blue-600 hover:text-blue-800 font-medium">
                    {{ $t('common.view_all') }}
                </a>
            </div>

            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('hotel.details') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('reservation.period') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('reservation.room_type') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('reservation.status') }}</th>
                        <th class="px-6 py-3"></th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <tr v-if="reservations.length === 0">
                        <td colspan="5" class="px-6 py-8 text-center text-sm text-gray-500">
                            {{ $t('reservation.no_reservations_yet') }}
                            <a href="/portal/hotels" class="text-blue-600 hover:text-blue-800 font-medium ml-1">{{ $t('reservation.book_first_stay') }}</a>
                        </td>
                    </tr>
                    <tr v-for="r in reservations" :key="r.id" class="hover:bg-gray-50">
                        <td class="px-6 py-4 text-sm">
                            <div class="font-medium text-gray-900">{{ r.hotel?.name }}</div>
                            <div v-if="r.hotel?.address" class="text-gray-500 text-xs mt-0.5">{{ r.hotel.address }}</div>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-700">
                            {{ r.period.check_in }} &rarr; {{ r.period.check_out }}
                            <div class="text-gray-500">{{ r.period.nights }} {{ $t('reservation.nights') }}</div>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-700">
                            <div>{{ $t('room_type.' + r.room_type.toLowerCase()) }}</div>
                            <div v-if="r.assigned_room_number" class="text-gray-500">{{ $t('reservation.room') }} {{ r.assigned_room_number }}</div>
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
                                {{ $t('common.view') }}
                            </a>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
