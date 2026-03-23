<script setup>
import { ref, computed, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { DatePicker } from 'v-calendar';
import GuestPortalLayout from '@/Layouts/GuestPortalLayout.vue';

defineOptions({ layout: GuestPortalLayout });

const { t } = useI18n();

const props = defineProps({
    stays: { type: Array, default: () => [] },
});

// Search form state
const searchQuery = ref('');
const dateRange = ref({ start: null, end: null });
const adults = ref(1);
const children = ref(0);
const babies = ref(0);
const pets = ref(0);
const showGuestDropdown = ref(false);

const formatDate = (date) => {
    if (!date) return '';
    const d = new Date(date);
    return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
};

const guestSummary = () => {
    const parts = [];
    if (adults.value > 0) parts.push(`${adults.value} ${adults.value === 1 ? t('reservation.adult_singular') : t('reservation.adults')}`);
    if (children.value > 0) parts.push(`${children.value} ${children.value === 1 ? t('reservation.child_singular') : t('reservation.children')}`);
    if (babies.value > 0) parts.push(`${babies.value} ${babies.value === 1 ? t('reservation.baby_singular') : t('reservation.babies')}`);
    if (pets.value > 0) parts.push(`${pets.value} ${pets.value === 1 ? t('reservation.pet_singular') : t('reservation.pets')}`);
    return parts.length > 0 ? parts.join(', ') : t('home.guests');
};

const submitSearch = () => {
    const params = {};
    if (searchQuery.value) params.q = searchQuery.value;
    const ci = formatDate(dateRange.value.start);
    const co = formatDate(dateRange.value.end);
    if (ci) params.check_in = ci;
    if (co) params.check_out = co;
    if (adults.value > 1) params.adults = adults.value;
    if (children.value > 0) params.children = children.value;
    if (babies.value > 0) params.babies = babies.value;
    if (pets.value > 0) params.pets = pets.value;
    router.get('/portal/stays', params);
};

// Split stays into trending (first 4) and deals (rest, up to 4)
const trendingStays = computed(() => props.stays.slice(0, 4));
const dealStays = computed(() => props.stays.filter(s => s.price_per_night !== null).slice(0, 4));

// Placeholder gradient colors for stay cards
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

const typeColors = {
    room: 'text-blue-700',
    entire_space: 'text-purple-700',
};

const categoryColors = {
    hotel_room: 'bg-indigo-50 text-indigo-700',
    house: 'bg-emerald-50 text-emerald-700',
    apartment: 'bg-amber-50 text-amber-700',
};
</script>

<template>
    <div class="-mt-8 -mx-4 sm:-mx-6 lg:-mx-8">
        <!-- Hero Section -->
        <div class="relative bg-gradient-to-br from-indigo-600 via-blue-600 to-violet-700">
            <!-- Background pattern (clipped independently) -->
            <div class="absolute inset-0 opacity-10 overflow-hidden pointer-events-none">
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

                        <!-- Date Range -->
                        <div class="lg:col-span-4">
                            <label class="block text-xs font-medium text-gray-500 mb-1 ml-3">{{ $t('home.check_in') }} &mdash; {{ $t('home.check_out') }}</label>
                            <DatePicker
                                v-model.range="dateRange"
                                :min-date="new Date()"
                                :columns="2"
                                color="indigo"
                            >
                                <template #default="{ inputValue, inputEvents }">
                                    <div class="flex items-center gap-1">
                                        <input
                                            :value="inputValue.start"
                                            v-on="inputEvents.start"
                                            :placeholder="$t('home.check_in')"
                                            readonly
                                            class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent cursor-pointer"
                                        />
                                        <svg class="w-4 h-4 text-gray-300 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                        </svg>
                                        <input
                                            :value="inputValue.end"
                                            v-on="inputEvents.end"
                                            :placeholder="$t('home.check_out')"
                                            readonly
                                            class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent cursor-pointer"
                                        />
                                    </div>
                                </template>
                            </DatePicker>
                        </div>

                        <!-- Guests Dropdown -->
                        <div class="lg:col-span-2 relative">
                            <label class="block text-xs font-medium text-gray-500 mb-1 ml-3">{{ $t('home.guests') }}</label>
                            <button
                                type="button"
                                @click="showGuestDropdown = !showGuestDropdown"
                                class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm text-left focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent bg-white flex items-center justify-between"
                            >
                                <span class="truncate text-gray-700 text-xs">{{ guestSummary() }}</span>
                                <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                            <div
                                v-if="showGuestDropdown"
                                class="absolute right-0 top-full mt-1 w-72 bg-white rounded-xl shadow-lg border border-gray-200 p-4 z-50 space-y-3"
                            >
                                <!-- Adults -->
                                <div class="flex items-center justify-between">
                                    <div>
                                        <div class="text-sm font-medium text-gray-800">{{ $t('reservation.adults') }}</div>
                                        <div class="text-xs text-gray-400">{{ $t('reservation.adults_age') }}</div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <button type="button" @click="adults = Math.max(1, adults - 1)" class="w-7 h-7 rounded-full border border-gray-300 flex items-center justify-center text-gray-500 hover:border-gray-400 disabled:opacity-30" :disabled="adults <= 1">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="M5 12h14"/></svg>
                                        </button>
                                        <span class="text-sm font-semibold w-5 text-center">{{ adults }}</span>
                                        <button type="button" @click="adults++" class="w-7 h-7 rounded-full border border-gray-300 flex items-center justify-center text-gray-500 hover:border-gray-400">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="M12 5v14m-7-7h14"/></svg>
                                        </button>
                                    </div>
                                </div>
                                <!-- Children -->
                                <div class="flex items-center justify-between">
                                    <div>
                                        <div class="text-sm font-medium text-gray-800">{{ $t('reservation.children') }}</div>
                                        <div class="text-xs text-gray-400">{{ $t('reservation.children_age') }}</div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <button type="button" @click="children = Math.max(0, children - 1)" class="w-7 h-7 rounded-full border border-gray-300 flex items-center justify-center text-gray-500 hover:border-gray-400 disabled:opacity-30" :disabled="children <= 0">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="M5 12h14"/></svg>
                                        </button>
                                        <span class="text-sm font-semibold w-5 text-center">{{ children }}</span>
                                        <button type="button" @click="children++" class="w-7 h-7 rounded-full border border-gray-300 flex items-center justify-center text-gray-500 hover:border-gray-400">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="M12 5v14m-7-7h14"/></svg>
                                        </button>
                                    </div>
                                </div>
                                <!-- Babies -->
                                <div class="flex items-center justify-between">
                                    <div>
                                        <div class="text-sm font-medium text-gray-800">{{ $t('reservation.babies') }}</div>
                                        <div class="text-xs text-gray-400">{{ $t('reservation.babies_age') }}</div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <button type="button" @click="babies = Math.max(0, babies - 1)" class="w-7 h-7 rounded-full border border-gray-300 flex items-center justify-center text-gray-500 hover:border-gray-400 disabled:opacity-30" :disabled="babies <= 0">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="M5 12h14"/></svg>
                                        </button>
                                        <span class="text-sm font-semibold w-5 text-center">{{ babies }}</span>
                                        <button type="button" @click="babies++" class="w-7 h-7 rounded-full border border-gray-300 flex items-center justify-center text-gray-500 hover:border-gray-400" :disabled="babies >= 5">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="M12 5v14m-7-7h14"/></svg>
                                        </button>
                                    </div>
                                </div>
                                <!-- Pets -->
                                <div class="flex items-center justify-between">
                                    <div>
                                        <div class="text-sm font-medium text-gray-800">{{ $t('reservation.pets') }}</div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <button type="button" @click="pets = Math.max(0, pets - 1)" class="w-7 h-7 rounded-full border border-gray-300 flex items-center justify-center text-gray-500 hover:border-gray-400 disabled:opacity-30" :disabled="pets <= 0">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="M5 12h14"/></svg>
                                        </button>
                                        <span class="text-sm font-semibold w-5 text-center">{{ pets }}</span>
                                        <button type="button" @click="pets++" class="w-7 h-7 rounded-full border border-gray-300 flex items-center justify-center text-gray-500 hover:border-gray-400" :disabled="pets >= 5">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="M12 5v14m-7-7h14"/></svg>
                                        </button>
                                    </div>
                                </div>
                                <button
                                    type="button"
                                    @click="showGuestDropdown = false"
                                    class="w-full bg-indigo-600 text-white py-2 rounded-lg text-sm font-medium hover:bg-indigo-700 transition-colors"
                                >
                                    {{ $t('common.confirm') }}
                                </button>
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

        <!-- Trending Stays -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12" v-if="trendingStays.length > 0">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">{{ $t('home.trending') }}</h2>
                    <p class="text-sm text-gray-500 mt-1">{{ $t('home.trending_subtitle') }}</p>
                </div>
                <a
                    href="/portal/stays"
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
                    v-for="(stay, index) in trendingStays"
                    :key="stay.uuid"
                    :href="`/portal/stays/${stay.slug}`"
                    class="group rounded-xl overflow-hidden shadow-sm hover:shadow-lg transition-all duration-300 bg-white"
                >
                    <!-- Image / placeholder -->
                    <div
                        class="h-48 flex items-center justify-center relative overflow-hidden"
                        :class="stay.cover_image_url ? '' : 'bg-gradient-to-br ' + getGradient(index)"
                    >
                        <img v-if="stay.cover_image_url" :src="stay.cover_image_url" :alt="stay.name" class="w-full h-full object-cover" />
                        <span v-else class="text-white/30 text-6xl font-bold">{{ getInitials(stay.name) }}</span>
                        <div class="absolute inset-0 bg-black/0 group-hover:bg-black/10 transition-colors"></div>
                        <!-- Type badge -->
                        <div class="absolute top-3 right-3 bg-white/90 backdrop-blur-sm px-2.5 py-1 rounded-full text-xs font-semibold shadow-sm"
                            :class="typeColors[stay.type] || 'text-gray-700'">
                            {{ $t('stay.type_' + stay.type) }}
                        </div>
                    </div>

                    <div class="p-4">
                        <h3 class="font-semibold text-gray-900 group-hover:text-indigo-600 transition-colors text-lg">
                            {{ stay.name }}
                        </h3>
                        <p v-if="stay.address" class="text-sm text-gray-500 mt-1 flex items-center gap-1">
                            <svg class="w-3.5 h-3.5 shrink-0 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            {{ stay.address }}
                        </p>

                        <!-- Category + Capacity -->
                        <div class="flex flex-wrap gap-1.5 mt-2">
                            <span
                                class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                                :class="categoryColors[stay.category] || 'bg-gray-50 text-gray-700'"
                            >
                                {{ $t('stay.category_' + stay.category) }}
                            </span>
                            <span v-if="stay.capacity" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-gray-50 text-gray-600">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                                {{ stay.capacity }}
                            </span>
                        </div>

                        <div v-if="stay.price_per_night" class="mt-3 flex items-baseline gap-1">
                            <span class="text-lg font-bold text-indigo-600">{{ formatPrice(stay.price_per_night) }}</span>
                            <span class="text-xs text-gray-500">{{ $t('home.per_night') }}</span>
                        </div>
                    </div>
                </a>
            </div>
        </div>

        <!-- Deals Section -->
        <div class="bg-gray-50 py-12" v-if="dealStays.length > 0">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900">{{ $t('home.deals') }}</h2>
                        <p class="text-sm text-gray-500 mt-1">{{ $t('home.deals_subtitle') }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <a
                        v-for="(stay, index) in dealStays"
                        :key="stay.uuid"
                        :href="`/portal/stays/${stay.slug}`"
                        class="group flex bg-white rounded-xl overflow-hidden shadow-sm hover:shadow-lg transition-all duration-300"
                    >
                        <!-- Side image -->
                        <div
                            class="w-40 sm:w-48 shrink-0 flex items-center justify-center relative overflow-hidden"
                            :class="stay.cover_image_url ? '' : 'bg-gradient-to-br ' + getGradient(index + 2)"
                        >
                            <img v-if="stay.cover_image_url" :src="stay.cover_image_url" :alt="stay.name" class="w-full h-full object-cover" />
                            <span v-else class="text-white/30 text-4xl font-bold">{{ getInitials(stay.name) }}</span>
                            <div class="absolute inset-0 bg-black/0 group-hover:bg-black/10 transition-colors"></div>
                        </div>

                        <div class="p-5 flex-1 min-w-0">
                            <h3 class="font-semibold text-gray-900 group-hover:text-indigo-600 transition-colors text-lg truncate">
                                {{ stay.name }}
                            </h3>
                            <p v-if="stay.address" class="text-sm text-gray-500 mt-1 flex items-center gap-1">
                                <svg class="w-3.5 h-3.5 shrink-0 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                <span class="truncate">{{ stay.address }}</span>
                            </p>
                            <p v-if="stay.description" class="text-sm text-gray-400 mt-2 line-clamp-2">
                                {{ stay.description }}
                            </p>

                            <!-- Category + Capacity tags -->
                            <div class="flex flex-wrap gap-1.5 mt-3">
                                <span
                                    class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                                    :class="categoryColors[stay.category] || 'bg-gray-50 text-gray-700'"
                                >
                                    {{ $t('stay.category_' + stay.category) }}
                                </span>
                                <span v-if="stay.capacity" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-gray-50 text-gray-600">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                    {{ stay.capacity }}
                                </span>
                            </div>

                            <div class="mt-4 flex items-center justify-between">
                                <div v-if="stay.price_per_night" class="flex items-baseline gap-1">
                                    <span class="text-xl font-bold text-indigo-600">{{ formatPrice(stay.price_per_night) }}</span>
                                    <span class="text-xs text-gray-500">{{ $t('home.per_night') }}</span>
                                </div>
                                <span class="text-sm font-medium text-indigo-600 group-hover:text-indigo-700 flex items-center gap-1">
                                    {{ $t('stay.view_details') }}
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
        <div v-if="stays.length === 0" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20">
            <div class="text-center">
                <div class="w-20 h-20 bg-indigo-50 rounded-full flex items-center justify-center mx-auto mb-6">
                    <svg class="w-10 h-10 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 mb-2">{{ $t('stay.no_stays') }}</h3>
                <p class="text-gray-500">{{ $t('stay.no_stays_subtitle') }}</p>
            </div>
        </div>
    </div>
</template>
