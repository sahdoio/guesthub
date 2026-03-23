<script setup>
import { router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

defineOptions({ layout: AuthenticatedLayout });

const { t } = useI18n();

const props = defineProps({
    reservations: Array,
    meta: Object,
    filters: Object,
});

const statusColors = {
    pending: 'border-yellow-400 bg-yellow-50',
    confirmed: 'border-blue-400 bg-blue-50',
    checked_in: 'border-green-400 bg-green-50',
    checked_out: 'border-gray-300 bg-gray-50',
    cancelled: 'border-red-400 bg-red-50',
};

const statusBadge = {
    pending: 'bg-yellow-100 text-yellow-800',
    confirmed: 'bg-blue-100 text-blue-800',
    checked_in: 'bg-green-100 text-green-800',
    checked_out: 'bg-gray-100 text-gray-800',
    cancelled: 'bg-red-100 text-red-800',
};

const statusDot = {
    pending: 'bg-yellow-400',
    confirmed: 'bg-blue-400',
    checked_in: 'bg-green-400',
    checked_out: 'bg-gray-400',
    cancelled: 'bg-red-400',
};

const statusLabel = (status) => t('status.' + status);

const applyFilter = (key, value) => {
    const params = { ...props.filters };
    if (value) {
        params[key] = value;
    } else {
        delete params[key];
    }
    router.get('/reservations', params, { preserveState: true });
};

const goToPage = (page) => {
    router.get('/reservations', { ...props.filters, page }, { preserveState: true });
};
</script>

<template>
    <div>
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold text-gray-800">{{ $t('reservation.title') }}</h1>
            <a
                href="/reservations/create"
                class="inline-flex items-center gap-2 bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-indigo-700 transition-colors shadow-sm"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                {{ $t('reservation.new') }}
            </a>
        </div>

        <!-- Filters -->
        <div class="flex flex-wrap gap-3 mb-6">
            <select
                :value="filters.status || ''"
                @change="applyFilter('status', $event.target.value)"
                class="rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
            >
                <option value="">{{ $t('common.all_statuses') }}</option>
                <option value="pending">{{ $t('status.pending') }}</option>
                <option value="confirmed">{{ $t('status.confirmed') }}</option>
                <option value="checked_in">{{ $t('status.checked_in') }}</option>
                <option value="checked_out">{{ $t('status.checked_out') }}</option>
                <option value="cancelled">{{ $t('status.cancelled') }}</option>
            </select>

        </div>

        <!-- Empty State -->
        <div v-if="reservations.length === 0" class="bg-white rounded-xl shadow-sm border border-gray-100 p-12 text-center">
            <div class="w-16 h-16 bg-indigo-50 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
            </div>
            <p class="text-gray-500">{{ $t('reservation.no_reservations') }}</p>
        </div>

        <!-- Reservation Cards Grid -->
        <div v-else class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
            <a
                v-for="r in reservations"
                :key="r.id"
                :href="`/reservations/${r.id}`"
                class="bg-white rounded-xl shadow-sm border-l-4 hover:shadow-md transition-all duration-200 group overflow-hidden"
                :class="statusColors[r.status]"
            >
                <div class="p-5">
                    <!-- Top: Guest + Status -->
                    <div class="flex items-start justify-between mb-4">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="w-10 h-10 rounded-full bg-gradient-to-br from-indigo-400 to-violet-500 flex items-center justify-center text-white text-xs font-bold shrink-0 shadow-sm">
                                {{ r.guest.full_name.charAt(0).toUpperCase() }}
                            </div>
                            <div class="min-w-0">
                                <h3 class="font-semibold text-gray-900 group-hover:text-indigo-600 transition-colors truncate">
                                    {{ r.guest.full_name }}
                                </h3>
                                <p class="text-xs text-gray-500 truncate">{{ r.guest.email }}</p>
                            </div>
                        </div>
                        <span
                            class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-semibold rounded-full capitalize shrink-0 ml-3"
                            :class="statusBadge[r.status]"
                        >
                            <span class="w-1.5 h-1.5 rounded-full" :class="statusDot[r.status]"></span>
                            {{ statusLabel(r.status) }}
                        </span>
                    </div>

                    <!-- Middle: Dates -->
                    <div class="flex items-center gap-3 mb-4">
                        <div class="flex-1 bg-gray-50 rounded-lg border border-gray-100 p-3 text-center">
                            <p class="text-[10px] font-medium text-gray-400 uppercase tracking-wider">{{ $t('reservation.check_in') }}</p>
                            <p class="text-sm font-semibold text-gray-900 mt-1">{{ r.period.check_in }}</p>
                        </div>
                        <div class="flex flex-col items-center shrink-0">
                            <svg class="w-4 h-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                            </svg>
                            <span class="text-[10px] text-gray-400 mt-0.5">{{ r.period.nights }} {{ $t('reservation.night', r.period.nights) }}</span>
                        </div>
                        <div class="flex-1 bg-gray-50 rounded-lg border border-gray-100 p-3 text-center">
                            <p class="text-[10px] font-medium text-gray-400 uppercase tracking-wider">{{ $t('reservation.check_out') }}</p>
                            <p class="text-sm font-semibold text-gray-900 mt-1">{{ r.period.check_out }}</p>
                        </div>
                    </div>

                    <!-- Bottom: Stay info + arrow -->
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-4 text-sm">
                            <div v-if="r.stay" class="flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                                </svg>
                                <span class="text-gray-600 font-medium">{{ r.stay.name }}</span>
                            </div>
                        </div>
                        <svg class="w-5 h-5 text-gray-300 group-hover:text-indigo-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </div>
                </div>
            </a>
        </div>

        <!-- Pagination -->
        <div v-if="meta.last_page > 1" class="mt-6 flex items-center justify-between">
            <span class="text-sm text-gray-500">
                {{ $t('common.page') }} {{ meta.current_page }} {{ $t('common.of') }} {{ meta.last_page }} ({{ meta.total }} {{ $t('common.total') }})
            </span>
            <div class="flex gap-2">
                <button
                    v-if="meta.current_page > 1"
                    @click="goToPage(meta.current_page - 1)"
                    class="px-4 py-2 text-sm border border-gray-200 rounded-lg bg-white hover:bg-gray-50 shadow-sm transition-colors"
                >
                    {{ $t('common.previous') }}
                </button>
                <button
                    v-if="meta.current_page < meta.last_page"
                    @click="goToPage(meta.current_page + 1)"
                    class="px-4 py-2 text-sm border border-gray-200 rounded-lg bg-white hover:bg-gray-50 shadow-sm transition-colors"
                >
                    {{ $t('common.next') }}
                </button>
            </div>
        </div>
    </div>
</template>
