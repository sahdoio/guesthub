<script setup>
import { router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

defineOptions({ layout: AuthenticatedLayout });

const { t } = useI18n();

const props = defineProps({
    hotel: Object,
    rooms: Array,
    meta: Object,
    filters: Object,
});

const baseUrl = `/hotels/${props.hotel.slug}/rooms`;

const statusColors = {
    available: 'bg-green-100 text-green-800',
    occupied: 'bg-blue-100 text-blue-800',
    maintenance: 'bg-yellow-100 text-yellow-800',
    out_of_order: 'bg-red-100 text-red-800',
};

const statusLabel = (status) => t('status.' + status);

const applyFilter = (key, value) => {
    const params = { ...props.filters };
    if (value) {
        params[key] = value;
    } else {
        delete params[key];
    }
    router.get(baseUrl, params, { preserveState: true });
};

const goToPage = (page) => {
    router.get(baseUrl, { ...props.filters, page }, { preserveState: true });
};
</script>

<template>
    <div>
        <div class="mb-2">
            <a :href="`/hotels/${hotel.slug}`" class="text-sm text-gray-500 hover:text-gray-700">&larr; {{ hotel.name }}</a>
        </div>

        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold text-gray-800">{{ $t('room.title') }}</h1>
            <a
                :href="`${baseUrl}/create`"
                class="bg-blue-600 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-blue-700 transition-colors"
            >
                {{ $t('room.new') }}
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
                    <option value="available">{{ $t('status.available') }}</option>
                    <option value="occupied">{{ $t('status.occupied') }}</option>
                    <option value="maintenance">{{ $t('status.maintenance') }}</option>
                    <option value="out_of_order">{{ $t('status.out_of_order') }}</option>
                </select>

                <select
                    :value="filters.type || ''"
                    @change="applyFilter('type', $event.target.value)"
                    class="rounded-md border border-gray-300 px-3 py-1.5 text-sm"
                >
                    <option value="">{{ $t('common.all_types') }}</option>
                    <option value="SINGLE">{{ $t('room_type.single') }}</option>
                    <option value="DOUBLE">{{ $t('room_type.double') }}</option>
                    <option value="SUITE">{{ $t('room_type.suite') }}</option>
                </select>
            </div>

            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('room.room_number') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('room.type') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('room.floor') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('room.price_per_night') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('reservation.status') }}</th>
                        <th class="px-6 py-3"></th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <tr v-if="rooms.length === 0">
                        <td colspan="6" class="px-6 py-8 text-center text-sm text-gray-500">
                            {{ $t('room.no_rooms') }}
                        </td>
                    </tr>
                    <tr v-for="room in rooms" :key="room.id" class="hover:bg-gray-50">
                        <td class="px-6 py-4 text-sm">
                            <div class="font-medium text-gray-900">{{ room.number }}</div>
                            <div class="text-gray-500">{{ room.capacity }} {{ $t('room.guest_count', room.capacity) }}</div>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-700">{{ $t('room_type.' + room.type.toLowerCase()) }}</td>
                        <td class="px-6 py-4 text-sm text-gray-700">{{ room.floor }}</td>
                        <td class="px-6 py-4 text-sm text-gray-700">${{ room.price_per_night.toFixed(2) }}</td>
                        <td class="px-6 py-4">
                            <span
                                class="inline-flex px-2 py-1 text-xs font-semibold rounded-full capitalize"
                                :class="statusColors[room.status]"
                            >
                                {{ statusLabel(room.status) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <a
                                :href="`${baseUrl}/${room.id}`"
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
