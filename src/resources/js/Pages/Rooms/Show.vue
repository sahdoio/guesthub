<script setup>
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

defineOptions({ layout: AuthenticatedLayout });

const { t } = useI18n();

const props = defineProps({
    hotel: Object,
    room: Object,
});

const r = props.room;
const baseUrl = `/hotels/${props.hotel.slug}/rooms`;

const statusColors = {
    available: 'bg-green-100 text-green-800',
    occupied: 'bg-blue-100 text-blue-800',
    maintenance: 'bg-yellow-100 text-yellow-800',
    out_of_order: 'bg-red-100 text-red-800',
};

const statusLabel = (status) => t('status.' + status);

const showDeleteConfirm = ref(false);

const changeStatus = (status) => {
    router.post(`${baseUrl}/${r.id}/status`, { status });
};

const deleteRoom = () => {
    router.delete(`${baseUrl}/${r.id}`);
};
</script>

<template>
    <div>
        <div class="mb-6 flex items-center gap-4">
            <a :href="baseUrl" class="text-gray-500 hover:text-gray-700">&larr; {{ hotel.name }} - {{ $t('room.title') }}</a>
            <h1 class="text-2xl font-bold text-gray-800">Room {{ r.number }}</h1>
            <span
                class="inline-flex px-2 py-1 text-xs font-semibold rounded-full capitalize"
                :class="statusColors[r.status]"
            >
                {{ statusLabel(r.status) }}
            </span>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-6">
                <!-- Room Details -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h2 class="text-sm font-medium text-gray-500 uppercase tracking-wide mb-3">{{ $t('room.details') }}</h2>
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <span class="text-gray-500">{{ $t('room.room_number') }}</span>
                            <p class="font-medium text-gray-900">{{ r.number }}</p>
                        </div>
                        <div>
                            <span class="text-gray-500">{{ $t('room.type') }}</span>
                            <p class="font-medium text-gray-900">{{ $t('room_type.' + r.type.toLowerCase()) }}</p>
                        </div>
                        <div>
                            <span class="text-gray-500">{{ $t('room.floor') }}</span>
                            <p class="font-medium text-gray-900">{{ r.floor }}</p>
                        </div>
                        <div>
                            <span class="text-gray-500">{{ $t('room.capacity') }}</span>
                            <p class="font-medium text-gray-900">{{ r.capacity }} {{ $t('room.guest_count', r.capacity) }}</p>
                        </div>
                        <div>
                            <span class="text-gray-500">{{ $t('room.price_label') }}</span>
                            <p class="font-medium text-gray-900">${{ r.price_per_night.toFixed(2) }}</p>
                        </div>
                    </div>
                </div>

                <!-- Amenities -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h2 class="text-sm font-medium text-gray-500 uppercase tracking-wide mb-3">{{ $t('room.amenities') }}</h2>
                    <div v-if="r.amenities && r.amenities.length > 0" class="flex flex-wrap gap-2">
                        <span
                            v-for="amenity in r.amenities"
                            :key="amenity"
                            class="inline-flex px-3 py-1 text-sm bg-blue-50 text-blue-700 rounded-full"
                        >
                            {{ amenity }}
                        </span>
                    </div>
                    <p v-else class="text-sm text-gray-500">{{ $t('room.no_amenities') }}</p>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Actions -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h2 class="text-sm font-medium text-gray-500 uppercase tracking-wide mb-3">{{ $t('common.actions') }}</h2>
                    <div class="space-y-3">
                        <a
                            :href="`${baseUrl}/${r.id}/edit`"
                            class="block w-full text-center bg-indigo-600 text-white py-2 px-4 rounded-lg shadow-sm text-sm font-medium hover:bg-indigo-700 transition-colors"
                        >
                            {{ $t('room.edit') }}
                        </a>

                        <!-- Status changes -->
                        <button
                            v-if="r.status !== 'available' && r.status !== 'occupied'"
                            @click="changeStatus('available')"
                            class="w-full bg-green-600 text-white py-2 px-4 rounded-lg shadow-sm text-sm font-medium hover:bg-green-700 transition-colors"
                        >
                            {{ $t('room.mark_available') }}
                        </button>

                        <button
                            v-if="r.status === 'available'"
                            @click="changeStatus('maintenance')"
                            class="w-full bg-yellow-600 text-white py-2 px-4 rounded-lg shadow-sm text-sm font-medium hover:bg-yellow-700 transition-colors"
                        >
                            {{ $t('room.mark_maintenance') }}
                        </button>

                        <button
                            v-if="r.status === 'available'"
                            @click="changeStatus('out_of_order')"
                            class="w-full bg-red-600 text-white py-2 px-4 rounded-lg shadow-sm text-sm font-medium hover:bg-red-700 transition-colors"
                        >
                            {{ $t('room.mark_out_of_order') }}
                        </button>

                        <!-- Delete -->
                        <button
                            v-if="!showDeleteConfirm && r.status !== 'occupied'"
                            @click="showDeleteConfirm = true"
                            class="w-full border border-red-300 text-red-600 py-2 px-4 rounded-lg shadow-sm text-sm font-medium hover:bg-red-50 transition-colors"
                        >
                            {{ $t('room.delete') }}
                        </button>
                        <div v-if="showDeleteConfirm" class="space-y-2">
                            <p class="text-sm text-gray-600">{{ $t('room.are_you_sure') }}</p>
                            <div class="flex gap-2">
                                <button
                                    @click="deleteRoom"
                                    class="flex-1 bg-red-600 text-white py-2 px-4 rounded-lg shadow-sm text-sm font-medium hover:bg-red-700"
                                >
                                    {{ $t('common.confirm') }}
                                </button>
                                <button
                                    @click="showDeleteConfirm = false"
                                    class="px-4 py-2 text-sm text-gray-500 hover:text-gray-700"
                                >
                                    {{ $t('common.cancel') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Timestamps -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h2 class="text-sm font-medium text-gray-500 uppercase tracking-wide mb-3">{{ $t('common.timeline') }}</h2>
                    <div class="space-y-2 text-sm">
                        <div>
                            <span class="text-gray-500">{{ $t('common.created') }}</span>
                            <p class="text-gray-900">{{ r.created_at }}</p>
                        </div>
                        <div v-if="r.updated_at">
                            <span class="text-gray-500">{{ $t('common.updated') }}</span>
                            <p class="text-gray-900">{{ r.updated_at }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
