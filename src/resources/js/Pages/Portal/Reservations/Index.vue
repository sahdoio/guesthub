<script setup>
import { useI18n } from 'vue-i18n';
import { router } from '@inertiajs/vue3';
import GuestPortalLayout from '@/Layouts/GuestPortalLayout.vue';

defineOptions({ layout: GuestPortalLayout });

const { t } = useI18n();

const props = defineProps({
    reservations: Array,
    meta: Object,
    filters: Object,
});

const statusColors = {
    pending: 'bg-yellow-100 text-yellow-800',
    confirmed: 'bg-blue-100 text-blue-800',
    checked_in: 'bg-green-100 text-green-800',
    checked_out: 'bg-gray-100 text-gray-800',
    cancelled: 'bg-red-100 text-red-800',
};

const statusLabel = (status) => t('status.' + status);

const applyFilter = (key, value) => {
    const params = { ...props.filters };
    if (value) {
        params[key] = value;
    } else {
        delete params[key];
    }
    router.get('/portal/reservations', params, { preserveState: true });
};

const goToPage = (page) => {
    router.get('/portal/reservations', { ...props.filters, page }, { preserveState: true });
};
</script>

<template>
    <div>
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold text-gray-800">{{ $t('nav.my_reservations') }}</h1>
            <a
                href="/portal/reservations/create"
                class="bg-blue-600 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-blue-700 transition-colors"
            >
                {{ $t('reservation.new') }}
            </a>
        </div>

        <div class="bg-white rounded-lg shadow">
            <div class="p-4 border-b border-gray-200 flex gap-4">
                <select
                    :value="filters.status || ''"
                    @change="applyFilter('status', $event.target.value)"
                    class="rounded-md border border-gray-300 px-3 py-1.5 text-sm"
                >
                    <option value="">{{ $t('common.all_statuses') }}</option>
                    <option value="pending">{{ $t('status.pending') }}</option>
                    <option value="confirmed">{{ $t('status.confirmed') }}</option>
                    <option value="checked_in">{{ $t('status.checked_in') }}</option>
                    <option value="checked_out">{{ $t('status.checked_out') }}</option>
                    <option value="cancelled">{{ $t('status.cancelled') }}</option>
                </select>

                <select
                    :value="filters.room_type || ''"
                    @change="applyFilter('room_type', $event.target.value)"
                    class="rounded-md border border-gray-300 px-3 py-1.5 text-sm"
                >
                    <option value="">{{ $t('common.all_room_types') }}</option>
                    <option value="SINGLE">{{ $t('room_type.single') }}</option>
                    <option value="DOUBLE">{{ $t('room_type.double') }}</option>
                    <option value="SUITE">{{ $t('room_type.suite') }}</option>
                </select>
            </div>

            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('hotel.details') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('reservation.period') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('reservation.room') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('reservation.status') }}</th>
                        <th class="px-6 py-3"></th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <tr v-if="reservations.length === 0">
                        <td colspan="5" class="px-6 py-8 text-center text-sm text-gray-500">
                            {{ $t('reservation.no_reservations') }}
                        </td>
                    </tr>
                    <tr v-for="r in reservations" :key="r.id" class="hover:bg-gray-50">
                        <td class="px-6 py-4 text-sm">
                            <div class="font-medium text-gray-900">{{ r.hotel?.name }}</div>
                            <div v-if="r.hotel?.address" class="text-gray-500 text-xs mt-0.5">{{ r.hotel.address }}</div>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-700">
                            {{ r.period.check_in }} &rarr; {{ r.period.check_out }}
                            <div class="text-gray-500">{{ r.period.nights }} night{{ r.period.nights !== 1 ? 's' : '' }}</div>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-700">
                            <div>{{ $t('room_type.' + r.room_type.toLowerCase()) }}</div>
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
                                {{ $t('common.view') }}
                            </a>
                        </td>
                    </tr>
                </tbody>
            </table>

            <div v-if="meta.last_page > 1" class="px-6 py-3 border-t border-gray-200 flex items-center justify-between">
                <span class="text-sm text-gray-500">
                    {{ $t('common.page') }} {{ meta.current_page }} {{ $t('common.of') }} {{ meta.last_page }} ({{ meta.total }} {{ $t('common.total') }})
                </span>
                <div class="flex gap-2">
                    <button
                        v-if="meta.current_page > 1"
                        @click="goToPage(meta.current_page - 1)"
                        class="px-3 py-1 text-sm border border-gray-300 rounded hover:bg-gray-50"
                    >
                        {{ $t('common.previous') }}
                    </button>
                    <button
                        v-if="meta.current_page < meta.last_page"
                        @click="goToPage(meta.current_page + 1)"
                        class="px-3 py-1 text-sm border border-gray-300 rounded hover:bg-gray-50"
                    >
                        {{ $t('common.next') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
