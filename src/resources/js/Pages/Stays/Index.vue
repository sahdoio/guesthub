<script setup>
import { useI18n } from 'vue-i18n';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

defineOptions({ layout: AuthenticatedLayout });

const { t } = useI18n();

const props = defineProps({
    stays: Array,
});

const statusColors = {
    active: 'bg-green-100 text-green-800',
    inactive: 'bg-gray-100 text-gray-800',
};

const typeColors = {
    room: 'bg-blue-100 text-blue-800',
    entire_space: 'bg-purple-100 text-purple-800',
};

const categoryColors = {
    hotel_room: 'bg-indigo-100 text-indigo-800',
    house: 'bg-emerald-100 text-emerald-800',
    apartment: 'bg-amber-100 text-amber-800',
};
</script>

<template>
    <div>
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold text-gray-800">{{ $t('stay.title') }}</h1>
            <a
                href="/stays/create"
                class="bg-indigo-600 text-white px-4 py-2.5 rounded-lg shadow-sm text-sm font-medium hover:bg-indigo-700 transition-colors"
            >
                {{ $t('stay.new') }}
            </a>
        </div>

        <div v-if="stays.length === 0" class="bg-white rounded-xl shadow-sm border border-gray-100 p-12 text-center">
            <div class="mx-auto w-16 h-16 bg-indigo-50 rounded-full flex items-center justify-center mb-4">
                <svg class="w-8 h-8 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
            </div>
            <h2 class="text-lg font-semibold text-gray-900 mb-2">{{ $t('stay.no_stays_yet') }}</h2>
            <p class="text-gray-500 mb-6 max-w-md mx-auto">{{ $t('stay.create_first') }}</p>
            <a
                href="/stays/create"
                class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 text-white font-medium rounded-lg hover:bg-indigo-700 transition-colors text-sm"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                {{ $t('stay.create_first_short') }}
            </a>
        </div>

        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <a
                v-for="stay in stays"
                :key="stay.id"
                :href="`/stays/${stay.slug}`"
                class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition-shadow block"
            >
                <div class="flex items-start justify-between mb-3">
                    <h2 class="text-lg font-semibold text-gray-900">{{ stay.name }}</h2>
                    <span
                        class="inline-flex px-2 py-0.5 text-xs font-semibold rounded-full capitalize"
                        :class="statusColors[stay.status] || 'bg-gray-100 text-gray-800'"
                    >
                        {{ stay.status }}
                    </span>
                </div>

                <!-- Type & Category badges -->
                <div class="flex flex-wrap gap-1.5 mb-3">
                    <span
                        class="inline-flex px-2 py-0.5 text-xs font-medium rounded-full"
                        :class="typeColors[stay.type] || 'bg-gray-100 text-gray-600'"
                    >
                        {{ $t('stay.type_' + stay.type) }}
                    </span>
                    <span
                        class="inline-flex px-2 py-0.5 text-xs font-medium rounded-full"
                        :class="categoryColors[stay.category] || 'bg-gray-100 text-gray-600'"
                    >
                        {{ $t('stay.category_' + stay.category) }}
                    </span>
                </div>

                <p v-if="stay.description" class="text-sm text-gray-500 mb-3 line-clamp-2">{{ stay.description }}</p>

                <!-- Price & Capacity -->
                <div class="flex items-center gap-4 mb-3 text-sm">
                    <div v-if="stay.price_per_night" class="flex items-baseline gap-1">
                        <span class="text-lg font-bold text-indigo-700">${{ Number(stay.price_per_night).toFixed(2) }}</span>
                        <span class="text-xs text-gray-400">{{ $t('stay.per_night') }}</span>
                    </div>
                    <div v-if="stay.capacity" class="flex items-center gap-1 text-gray-500">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        {{ stay.capacity }} {{ $t('stay.guests') }}
                    </div>
                </div>

                <div v-if="stay.address" class="text-sm text-gray-400 flex items-center gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    {{ stay.address }}
                </div>
            </a>
        </div>
    </div>
</template>
