<script setup>
import { useI18n } from 'vue-i18n';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

defineOptions({ layout: AuthenticatedLayout });

const { t } = useI18n();

const props = defineProps({
    hotels: Array,
});

const statusColors = {
    active: 'bg-green-100 text-green-800',
    inactive: 'bg-gray-100 text-gray-800',
};
</script>

<template>
    <div>
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold text-gray-800">{{ $t('hotel.title') }}</h1>
            <a
                href="/hotels/create"
                class="bg-blue-600 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-blue-700 transition-colors"
            >
                {{ $t('hotel.new') }}
            </a>
        </div>

        <div v-if="hotels.length === 0" class="bg-white rounded-xl shadow-sm border border-gray-100 p-12 text-center">
            <div class="mx-auto w-16 h-16 bg-indigo-50 rounded-full flex items-center justify-center mb-4">
                <svg class="w-8 h-8 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
            </div>
            <h2 class="text-lg font-semibold text-gray-900 mb-2">{{ $t('hotel.no_hotels_yet') }}</h2>
            <p class="text-gray-500 mb-6 max-w-md mx-auto">{{ $t('hotel.create_first') }}</p>
            <a
                href="/hotels/create"
                class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 text-white font-medium rounded-lg hover:bg-indigo-700 transition-colors text-sm"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                {{ $t('hotel.create_first_short') }}
            </a>
        </div>

        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <a
                v-for="hotel in hotels"
                :key="hotel.id"
                :href="`/hotels/${hotel.slug}`"
                class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition-shadow block"
            >
                <div class="flex items-start justify-between mb-3">
                    <h2 class="text-lg font-semibold text-gray-900">{{ hotel.name }}</h2>
                    <span
                        class="inline-flex px-2 py-0.5 text-xs font-semibold rounded-full capitalize"
                        :class="statusColors[hotel.status] || 'bg-gray-100 text-gray-800'"
                    >
                        {{ hotel.status }}
                    </span>
                </div>
                <p v-if="hotel.description" class="text-sm text-gray-500 mb-3 line-clamp-2">{{ hotel.description }}</p>
                <div v-if="hotel.address" class="text-sm text-gray-400 flex items-center gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    {{ hotel.address }}
                </div>
            </a>
        </div>
    </div>
</template>
