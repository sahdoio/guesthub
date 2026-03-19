<script setup>
import { useI18n } from 'vue-i18n';
import GuestPortalLayout from '@/Layouts/GuestPortalLayout.vue';

defineOptions({ layout: GuestPortalLayout });
const { t } = useI18n();
const props = defineProps({ hotels: Array });
</script>

<template>
    <div>
        <!-- Page Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">{{ $t('hotel.browse_title') }}</h1>
            <p class="mt-2 text-base text-gray-500">{{ $t('hotel.browse_subtitle') }}</p>
        </div>

        <!-- Empty State -->
        <div
            v-if="!hotels || hotels.length === 0"
            class="bg-white rounded-2xl shadow-sm border border-gray-100 py-20 text-center"
        >
            <div class="mx-auto w-16 h-16 rounded-full bg-indigo-50 flex items-center justify-center mb-4">
                <svg class="w-8 h-8 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-2 10v-5a1 1 0 00-1-1h-2a1 1 0 00-1 1v5m4 0H9" />
                </svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-800 mb-1">{{ $t('hotel.no_hotels') }}</h3>
            <p class="text-sm text-gray-500">{{ $t('hotel.no_hotels_subtitle') }}</p>
        </div>

        <!-- Hotel Grid -->
        <div
            v-else
            class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6"
        >
            <article
                v-for="hotel in hotels"
                :key="hotel.uuid"
                class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-md hover:-translate-y-0.5 transition-all duration-200 flex flex-col"
            >
                <!-- Card Header / Banner -->
                <div class="h-36 bg-gradient-to-br from-indigo-500 via-indigo-600 to-violet-600 flex items-center justify-center relative overflow-hidden">
                    <div class="absolute inset-0 opacity-10">
                        <svg class="w-full h-full" viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg">
                            <circle cx="180" cy="20" r="80" fill="white" />
                            <circle cx="20" cy="180" r="60" fill="white" />
                        </svg>
                    </div>
                    <svg class="w-14 h-14 text-white/70 relative z-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.2"
                            d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-2 10v-5a1 1 0 00-1-1h-2a1 1 0 00-1 1v5m4 0H9" />
                    </svg>
                </div>

                <!-- Card Body -->
                <div class="p-5 flex flex-col flex-1">
                    <!-- Hotel Name -->
                    <h2 class="text-lg font-bold text-gray-900 leading-snug mb-2 line-clamp-1">
                        {{ hotel.name }}
                    </h2>

                    <!-- Description -->
                    <p
                        v-if="hotel.description"
                        class="text-sm text-gray-500 leading-relaxed mb-4 line-clamp-2"
                    >
                        {{ hotel.description }}
                    </p>
                    <p v-else class="text-sm text-gray-400 italic mb-4">
                        {{ $t('hotel.no_description') }}
                    </p>

                    <!-- Address -->
                    <div
                        v-if="hotel.address"
                        class="flex items-start gap-2 text-sm text-gray-600 mb-5"
                    >
                        <svg class="w-4 h-4 mt-0.5 shrink-0 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <span class="line-clamp-2">
                            <span class="sr-only">{{ $t('common.address') }}: </span>{{ hotel.address }}
                        </span>
                    </div>

                    <!-- Spacer -->
                    <div class="flex-1" />

                    <!-- Footer -->
                    <div class="flex items-center justify-between pt-4 border-t border-gray-100">
                        <!-- Contact snippet -->
                        <div class="text-xs text-gray-400 truncate max-w-[55%]">
                            <span v-if="hotel.contact_email">{{ hotel.contact_email }}</span>
                            <span v-else-if="hotel.contact_phone">{{ hotel.contact_phone }}</span>
                        </div>

                        <!-- View Details Button -->
                        <a
                            :href="`/portal/hotels/${hotel.slug}`"
                            class="inline-flex items-center gap-1.5 bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors duration-150 shrink-0"
                        >
                            {{ $t('hotel.view_details') }}
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </a>
                    </div>
                </div>
            </article>
        </div>
    </div>
</template>
