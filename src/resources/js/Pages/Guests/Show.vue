<script setup>
import { useI18n } from 'vue-i18n';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

defineOptions({ layout: AuthenticatedLayout });

const { t } = useI18n();

const props = defineProps({
    guest: Object,
    reservations: { type: Array, default: () => [] },
    reservationsMeta: { type: Object, default: () => ({}) },
});

const g = props.guest;

const tierColors = {
    bronze: 'bg-orange-100 text-orange-800',
    silver: 'bg-gray-100 text-gray-800',
    gold: 'bg-yellow-100 text-yellow-800',
    platinum: 'bg-purple-100 text-purple-800',
};

const tierDot = {
    bronze: 'bg-orange-400',
    silver: 'bg-gray-400',
    gold: 'bg-yellow-400',
    platinum: 'bg-purple-400',
};

const reservationStatusColors = {
    pending: 'bg-yellow-100 text-yellow-800',
    confirmed: 'bg-blue-100 text-blue-800',
    checked_in: 'bg-green-100 text-green-800',
    checked_out: 'bg-gray-100 text-gray-800',
    cancelled: 'bg-red-100 text-red-800',
};


</script>

<template>
    <div>
        <div class="mb-6 flex items-center gap-4">
            <a href="/guests" class="text-gray-500 hover:text-gray-700">&larr; {{ $t('common.back') }}</a>
            <h1 class="text-2xl font-bold text-gray-800">{{ $t('guest.profile') }}</h1>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-6">
                <!-- Guest header card -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <div class="flex items-center gap-4 mb-5">
                        <div class="w-14 h-14 rounded-full bg-gradient-to-br from-indigo-400 to-violet-500 flex items-center justify-center text-white text-xl font-bold shadow-sm">
                            {{ g.full_name.charAt(0).toUpperCase() }}
                        </div>
                        <div>
                            <h2 class="text-xl font-semibold text-gray-900">{{ g.full_name }}</h2>
                            <span
                                class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-semibold rounded-full capitalize mt-1"
                                :class="tierColors[g.loyalty_tier]"
                            >
                                <span class="w-1.5 h-1.5 rounded-full" :class="tierDot[g.loyalty_tier]"></span>
                                {{ $t('tier.' + g.loyalty_tier) }}
                            </span>
                        </div>
                    </div>

                    <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wide mb-3">{{ $t('guest.contact_info') }}</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="flex items-start gap-3">
                            <div class="w-9 h-9 bg-indigo-50 rounded-lg flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-xs text-gray-400 uppercase tracking-wider">{{ $t('guest.email') }}</p>
                                <p class="font-medium text-gray-900 mt-0.5 text-sm">{{ g.email }}</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <div class="w-9 h-9 bg-indigo-50 rounded-lg flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-xs text-gray-400 uppercase tracking-wider">{{ $t('guest.phone') }}</p>
                                <p class="font-medium text-gray-900 mt-0.5 text-sm">{{ g.phone }}</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <div class="w-9 h-9 bg-indigo-50 rounded-lg flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-xs text-gray-400 uppercase tracking-wider">{{ $t('guest.document') }}</p>
                                <p class="font-medium text-gray-900 mt-0.5 text-sm">{{ g.document }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Preferences -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h2 class="text-sm font-medium text-gray-500 uppercase tracking-wide mb-3">{{ $t('guest.preferences') }}</h2>
                    <div v-if="g.preferences && g.preferences.length > 0" class="flex flex-wrap gap-2">
                        <span
                            v-for="pref in g.preferences"
                            :key="pref"
                            class="inline-flex px-3 py-1.5 text-sm bg-indigo-50 text-indigo-700 rounded-lg font-medium"
                        >
                            {{ pref }}
                        </span>
                    </div>
                    <p v-else class="text-sm text-gray-500">{{ $t('guest.no_preferences') }}</p>
                </div>

                <!-- Reservation History -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-sm font-medium text-gray-500 uppercase tracking-wide">{{ $t('guest.reservation_history') }} ({{ reservationsMeta.total ?? 0 }})</h2>
                    </div>
                    <div v-if="reservations.length === 0" class="text-sm text-gray-500">
                        {{ $t('guest.no_reservations') }}
                    </div>
                    <div v-else class="space-y-3">
                        <div
                            v-for="res in reservations"
                            :key="res.id"
                            class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors"
                        >
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 mb-1">
                                    <a v-if="res.stay?.slug" :href="`/stays/${res.stay.slug}`" class="text-sm font-medium text-indigo-600 hover:text-indigo-800 hover:underline truncate">{{ res.stay?.name || '-' }}</a>
                                    <span v-else class="text-sm font-medium text-gray-900 truncate">{{ res.stay?.name || '-' }}</span>
                                    <span class="inline-flex px-2 py-0.5 text-xs font-semibold rounded-full capitalize shrink-0" :class="reservationStatusColors[res.status]">
                                        {{ $t('status.' + res.status) }}
                                    </span>
                                </div>
                                <p class="text-xs text-gray-500">{{ res.period.check_in }} &rarr; {{ res.period.check_out }} ({{ res.period.nights }} {{ $t('reservation.nights') }})</p>
                            </div>
                            <a :href="`/reservations/${res.id}`" class="text-indigo-600 hover:text-indigo-800 text-xs font-medium shrink-0 ml-3">{{ $t('common.view') }}</a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Timestamps -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h2 class="text-sm font-medium text-gray-500 uppercase tracking-wide mb-3">{{ $t('common.timeline') }}</h2>
                    <div class="space-y-3">
                        <div class="flex items-start gap-3">
                            <div class="w-8 h-8 bg-gray-50 rounded-lg flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-xs text-gray-400 uppercase tracking-wider">{{ $t('common.created') }}</p>
                                <p class="text-sm text-gray-900 mt-0.5">{{ g.created_at }}</p>
                            </div>
                        </div>
                        <div v-if="g.updated_at" class="flex items-start gap-3">
                            <div class="w-8 h-8 bg-gray-50 rounded-lg flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-xs text-gray-400 uppercase tracking-wider">{{ $t('common.updated') }}</p>
                                <p class="text-sm text-gray-900 mt-0.5">{{ g.updated_at }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
