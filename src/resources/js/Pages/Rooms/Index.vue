<script setup>
import { router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({
    rooms: Array,
    meta: Object,
    filters: Object,
});

const statusColors = {
    available: 'bg-green-100 text-green-800',
    occupied: 'bg-blue-100 text-blue-800',
    maintenance: 'bg-yellow-100 text-yellow-800',
    out_of_order: 'bg-red-100 text-red-800',
};

const statusLabel = (status) => status.replace('_', ' ');

const applyFilter = (key, value) => {
    const params = { ...props.filters };
    if (value) {
        params[key] = value;
    } else {
        delete params[key];
    }
    router.get('/rooms', params, { preserveState: true });
};

const goToPage = (page) => {
    router.get('/rooms', { ...props.filters, page }, { preserveState: true });
};
</script>

<template>
    <div>
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold text-gray-800">Rooms</h1>
            <a
                href="/rooms/create"
                class="bg-blue-600 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-blue-700 transition-colors"
            >
                New Room
            </a>
        </div>

        <div class="bg-white rounded-lg shadow">
            <div class="p-4 border-b border-gray-200 flex gap-4">
                <select
                    :value="filters.status || ''"
                    @change="applyFilter('status', $event.target.value)"
                    class="rounded-md border border-gray-300 px-3 py-1.5 text-sm"
                >
                    <option value="">All Statuses</option>
                    <option value="available">Available</option>
                    <option value="occupied">Occupied</option>
                    <option value="maintenance">Maintenance</option>
                    <option value="out_of_order">Out of Order</option>
                </select>

                <select
                    :value="filters.type || ''"
                    @change="applyFilter('type', $event.target.value)"
                    class="rounded-md border border-gray-300 px-3 py-1.5 text-sm"
                >
                    <option value="">All Types</option>
                    <option value="SINGLE">Single</option>
                    <option value="DOUBLE">Double</option>
                    <option value="SUITE">Suite</option>
                </select>
            </div>

            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Room</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Floor</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price/Night</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3"></th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <tr v-if="rooms.length === 0">
                        <td colspan="6" class="px-6 py-8 text-center text-sm text-gray-500">
                            No rooms found.
                        </td>
                    </tr>
                    <tr v-for="room in rooms" :key="room.id" class="hover:bg-gray-50">
                        <td class="px-6 py-4 text-sm">
                            <div class="font-medium text-gray-900">{{ room.number }}</div>
                            <div class="text-gray-500">{{ room.capacity }} guest{{ room.capacity !== 1 ? 's' : '' }}</div>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-700">{{ room.type }}</td>
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
                                :href="`/rooms/${room.id}`"
                                class="text-blue-600 hover:text-blue-800 text-sm font-medium"
                            >
                                View
                            </a>
                        </td>
                    </tr>
                </tbody>
            </table>

            <div v-if="meta.last_page > 1" class="px-6 py-3 border-t border-gray-200 flex items-center justify-between">
                <span class="text-sm text-gray-500">
                    Page {{ meta.current_page }} of {{ meta.last_page }} ({{ meta.total }} total)
                </span>
                <div class="flex gap-2">
                    <button
                        v-if="meta.current_page > 1"
                        @click="goToPage(meta.current_page - 1)"
                        class="px-3 py-1 text-sm border border-gray-300 rounded hover:bg-gray-50"
                    >
                        Previous
                    </button>
                    <button
                        v-if="meta.current_page < meta.last_page"
                        @click="goToPage(meta.current_page + 1)"
                        class="px-3 py-1 text-sm border border-gray-300 rounded hover:bg-gray-50"
                    >
                        Next
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
