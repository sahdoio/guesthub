<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import GuestPortalLayout from '@/Layouts/GuestPortalLayout.vue';

defineOptions({ layout: GuestPortalLayout });

const { t } = useI18n();

const props = defineProps({
    hotels: { type: Array, default: () => [] },
});

// Search form state
const searchQuery = ref('');
const checkIn = ref('');
const checkOut = ref('');
const guestCount = ref(1);

const today = new Date().toISOString().split('T')[0];

const submitSearch = () => {
    const params = {};
    if (searchQuery.value) params.q = searchQuery.value;
    if (checkIn.value) params.check_in = checkIn.value;
    if (checkOut.value) params.check_out = checkOut.value;
    if (guestCount.value > 1) params.guests = guestCount.value;
    router.get('/portal/hotels', params);
};

// Split hotels into trending (first 4) and deals (rest, up to 4)
const trendingHotels = computed(() => props.hotels.slice(0, 4));
const dealHotels = computed(() => props.hotels.filter(h => h.min_price !== null).slice(0, 4));

// Placeholder gradient colors for hotel cards
const gradients = [
    'from-blue-400 to-indigo-500',
    'from-emerald-400 to-teal-500',
    'from-amber-400 to-orange-500',
    'from-rose-400 to-pink-500',
    'from-violet-400 to-purple-500',
    'from-cyan-400 to-sky-500',
];

const getGradient = (index) => gradients[index % gradients.length];

const getInitials = (name) => {
    return name.split(' ').map(w => w[0]).join('').toUpperCase().slice(0, 2);
};

const formatPrice = (price) => {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
        minimumFractionDigits: 0,
    }).format(price);
};
</script>

<template>
    <div class="-mt-8 -mx-4 sm:-mx-6 lg:-mx-8">
        <!-- Hero Section -->
        <div class="relative bg-gradient-to-br from-indigo-600 via-blue-600 to-violet-700 overflow-hidden">
            <!-- Background pattern -->
            <div class="absolute inset-0 opacity-10">
                <div class="absolute top-0 left-0 w-72 h-72 bg-white rounded-full -translate-x-1/2 -translate-y-1/2"></div>
                <div class="absolute bottom-0 right-0 w-96 h-96 bg-white rounded-full translate-x-1/3 translate-y-1/3"></div>
                <div class="absolute top-1/2 left-1/2 w-64 h-64 bg-white rounded-full -translate-x-1/2 -translate-y-1/2"></div>
            </div>

            <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-16 pb-24">
                <div class="text-center mb-10">
                    <h1 class="text-4xl sm:text-5xl font-bold text-white mb-3 tracking-tight">
                        {{ $t('home.hero_title') }}
                    </h1>
                    <p class="text-lg text-blue-100 max-w-2xl mx-auto">
                        {{ $t('home.hero_subtitle') }}
                    </p>
                </div>

                <!-- Search Bar -->
                <form @submit.prevent="submitSearch" class="bg-white rounded-xl shadow-2xl p-3 max-w-4xl mx-auto">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-2">
                        <!-- Location -->
                        <div class="lg:col-span-4 relative">
                            <label class="block text-xs font-medium text-gray-500 mb-1 ml-3">{{ $t('home.where') }}</label>
                            <div class="relative">
                                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                                <input
                                    v-model="searchQuery"
                                    type="text"
                                    :placeholder="$t('home.where_placeholder')"
                                    class="w-full pl-9 pr-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                />
                            </div>
                        </div>

                        <!-- Check-in -->
                        <div class="lg:col-span-2">
                            <label class="block text-xs font-medium text-gray-500 mb-1 ml-3">{{ $t('home.check_in') }}</label>
                            <input
                                v-model="checkIn"
                                type="date"
                                :min="today"
                                class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                            />
                        </div>

                        <!-- Check-out -->
                        <div class="lg:col-span-2">
                            <label class="block text-xs font-medium text-gray-500 mb-1 ml-3">{{ $t('home.check_out') }}</label>
                            <input
                                v-model="checkOut"
                                type="date"
                                :min="checkIn || today"
                                class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                            />
                        </div>

                        <!-- Guests -->
                        <div class="lg:col-span-2">
                            <label class="block text-xs font-medium text-gray-500 mb-1 ml-3">{{ $t('home.guests') }}</label>
                            <div class="relative">
                                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                                <select
                                    v-model="guestCount"
                                    class="w-full pl-9 pr-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent appearance-none bg-white"
                                >
                                    <option v-for="n in 10" :key="n" :value="n">
                                        {{ n }} {{ n === 1 ? $t('home.guests') : $t('home.guests') }}
                                    </option>
                                </select>
                            </div>
                        </div>

                        <!-- Search Button -->
                        <div class="lg:col-span-2 flex items-end">
                            <button
                                type="submit"
                                class="w-full bg-indigo-600 text-white py-2.5 px-6 rounded-lg text-sm font-semibold hover:bg-indigo-700 transition-colors shadow-md hover:shadow-lg flex items-center justify-center gap-2"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                                {{ $t('home.search') }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Trending Destinations -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12" v-if="trendingHotels.length > 0">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">{{ $t('home.trending') }}</h2>
                    <p class="text-sm text-gray-500 mt-1">{{ $t('home.trending_subtitle') }}</p>
                </div>
                <a
                    href="/portal/hotels"
                    class="text-sm font-medium text-indigo-600 hover:text-indigo-700 flex items-center gap-1"
                >
                    {{ $t('home.explore_all') }}
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </a>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                <a
                    v-for="(hotel, index) in trendingHotels"
                    :key="hotel.uuid"
                    :href="`/portal/hotels/${hotel.slug}`"
                    class="group rounded-xl overflow-hidden shadow-sm hover:shadow-lg transition-all duration-300 bg-white"
                >
                    <!-- Image placeholder -->
                    <div
                        class="h-48 bg-gradient-to-br flex items-center justify-center relative overflow-hidden"
                        :class="getGradient(index)"
                    >
                        <span class="text-white/30 text-6xl font-bold">{{ getInitials(hotel.name) }}</span>
                        <div class="absolute inset-0 bg-black/0 group-hover:bg-black/10 transition-colors"></div>
                        <!-- Available rooms badge -->
                        <div v-if="hotel.available_rooms > 0" class="absolute top-3 right-3 bg-white/90 backdrop-blur-sm px-2.5 py-1 rounded-full text-xs font-semibold text-gray-700 shadow-sm">
                            {{ hotel.available_rooms }} {{ $t('home.rooms_available') }}
                        </div>
                        <div v-else class="absolute top-3 right-3 bg-gray-800/70 backdrop-blur-sm px-2.5 py-1 rounded-full text-xs font-medium text-gray-200">
                            {{ $t('home.no_rooms') }}
                        </div>
                    </div>

                    <div class="p-4">
                        <h3 class="font-semibold text-gray-900 group-hover:text-indigo-600 transition-colors text-lg">
                            {{ hotel.name }}
                        </h3>
                        <p v-if="hotel.address" class="text-sm text-gray-500 mt-1 flex items-center gap-1">
                            <svg class="w-3.5 h-3.5 shrink-0 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            {{ hotel.address }}
                        </p>
                        <p v-if="hotel.description" class="text-sm text-gray-400 mt-2 line-clamp-2">
                            {{ hotel.description }}
                        </p>
                        <div v-if="hotel.min_price" class="mt-3 flex items-baseline gap-1">
                            <span class="text-xs text-gray-500">{{ $t('home.from') }}</span>
                            <span class="text-lg font-bold text-indigo-600">{{ formatPrice(hotel.min_price) }}</span>
                            <span class="text-xs text-gray-500">{{ $t('home.per_night') }}</span>
                        </div>
                    </div>
                </a>
            </div>
        </div>

        <!-- Deals Section -->
        <div class="bg-gray-50 py-12" v-if="dealHotels.length > 0">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900">{{ $t('home.deals') }}</h2>
                        <p class="text-sm text-gray-500 mt-1">{{ $t('home.deals_subtitle') }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <a
                        v-for="(hotel, index) in dealHotels"
                        :key="hotel.uuid"
                        :href="`/portal/hotels/${hotel.slug}`"
                        class="group flex bg-white rounded-xl overflow-hidden shadow-sm hover:shadow-lg transition-all duration-300"
                    >
                        <!-- Side image -->
                        <div
                            class="w-40 sm:w-48 shrink-0 bg-gradient-to-br flex items-center justify-center relative"
                            :class="getGradient(index + 2)"
                        >
                            <span class="text-white/30 text-4xl font-bold">{{ getInitials(hotel.name) }}</span>
                            <div class="absolute inset-0 bg-black/0 group-hover:bg-black/10 transition-colors"></div>
                        </div>

                        <div class="p-5 flex-1 min-w-0">
                            <h3 class="font-semibold text-gray-900 group-hover:text-indigo-600 transition-colors text-lg truncate">
                                {{ hotel.name }}
                            </h3>
                            <p v-if="hotel.address" class="text-sm text-gray-500 mt-1 flex items-center gap-1">
                                <svg class="w-3.5 h-3.5 shrink-0 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                <span class="truncate">{{ hotel.address }}</span>
                            </p>
                            <p v-if="hotel.description" class="text-sm text-gray-400 mt-2 line-clamp-2">
                                {{ hotel.description }}
                            </p>

                            <!-- Room types preview -->
                            <div v-if="hotel.room_types?.length" class="flex flex-wrap gap-1.5 mt-3">
                                <span
                                    v-for="rt in hotel.room_types.slice(0, 3)"
                                    :key="rt.type"
                                    class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-50 text-indigo-700"
                                >
                                    {{ $t('room_type.' + rt.type.toLowerCase()) }}
                                </span>
                            </div>

                            <div class="mt-4 flex items-center justify-between">
                                <div v-if="hotel.min_price" class="flex items-baseline gap-1">
                                    <span class="text-xs text-gray-500">{{ $t('home.from') }}</span>
                                    <span class="text-xl font-bold text-indigo-600">{{ formatPrice(hotel.min_price) }}</span>
                                    <span class="text-xs text-gray-500">{{ $t('home.per_night') }}</span>
                                </div>
                                <span class="text-sm font-medium text-indigo-600 group-hover:text-indigo-700 flex items-center gap-1">
                                    {{ $t('hotel.view_details') }}
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                </span>
                            </div>
                        </div>
                    </a>
                </div>
            </div>
        </div>

        <!-- Empty State -->
        <div v-if="hotels.length === 0" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20">
            <div class="text-center">
                <div class="w-20 h-20 bg-indigo-50 rounded-full flex items-center justify-center mx-auto mb-6">
                    <svg class="w-10 h-10 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 mb-2">{{ $t('hotel.no_hotels') }}</h3>
                <p class="text-gray-500">{{ $t('hotel.no_hotels_subtitle') }}</p>
            </div>
        </div>
    </div>
</template>
