<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import GuestPortalLayout from '@/Layouts/GuestPortalLayout.vue';

defineOptions({ layout: GuestPortalLayout });

const { t } = useI18n();

const props = defineProps({
    hotels: Array,
    meta: Object,
    filters: Object,
});

const searchInput = ref(props.filters?.q || '');
const checkIn = ref(props.filters?.check_in || '');
const checkOut = ref(props.filters?.check_out || '');
const guests = ref(props.filters?.guests || 0);

const today = new Date().toISOString().split('T')[0];

let searchTimeout = null;

const applyFilters = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        submitSearch();
    }, 300);
};

const submitSearch = () => {
    clearTimeout(searchTimeout);
    const params = {};
    if (searchInput.value) params.q = searchInput.value;
    if (checkIn.value) params.check_in = checkIn.value;
    if (checkOut.value) params.check_out = checkOut.value;
    if (guests.value > 0) params.guests = guests.value;
    router.get('/portal/hotels', params, { preserveState: true });
};

const goToPage = (page) => {
    const params = { ...props.filters, page };
    router.get('/portal/hotels', params, { preserveState: true });
};

const gradients = [
    'from-blue-400 to-indigo-500',
    'from-emerald-400 to-teal-500',
    'from-amber-400 to-orange-500',
    'from-rose-400 to-pink-500',
    'from-violet-400 to-purple-500',
    'from-cyan-400 to-sky-500',
];

const getGradient = (index) => gradients[index % gradients.length];

const getInitials = (name) => name.split(' ').map(w => w[0]).join('').toUpperCase().slice(0, 2);
</script>

<template>
    <div>
        <!-- Page Header -->
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">{{ $t('hotel.browse_title') }}</h1>
            <p class="mt-1 text-sm text-gray-500">{{ $t('hotel.browse_subtitle') }}</p>
        </div>

        <!-- Search & Filters -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-6">
            <form @submit.prevent="submitSearch" class="flex flex-col sm:flex-row gap-3">
                <!-- Search input -->
                <div class="relative flex-1">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input
                        v-model="searchInput"
                        @input="applyFilters"
                        type="text"
                        :placeholder="$t('home.where_placeholder')"
                        class="w-full pl-9 pr-3 py-2.5 rounded-lg border border-gray-200 bg-white text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                    />
                </div>

                <!-- Check-in -->
                <div class="sm:w-40">
                    <input
                        v-model="checkIn"
                        @change="submitSearch"
                        type="date"
                        :min="today"
                        class="w-full px-3 py-2.5 rounded-lg border border-gray-200 bg-white text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                    />
                </div>

                <!-- Check-out -->
                <div class="sm:w-40">
                    <input
                        v-model="checkOut"
                        @change="submitSearch"
                        type="date"
                        :min="checkIn || today"
                        class="w-full px-3 py-2.5 rounded-lg border border-gray-200 bg-white text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                    />
                </div>

                <!-- Guests -->
                <div class="sm:w-28">
                    <select
                        v-model="guests"
                        @change="submitSearch"
                        class="w-full px-3 py-2.5 rounded-lg border border-gray-200 bg-white text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                    >
                        <option :value="0">{{ $t('home.guests') }}</option>
                        <option v-for="n in 10" :key="n" :value="n">{{ n }}</option>
                    </select>
                </div>

                <!-- Search button -->
                <button
                    type="submit"
                    class="bg-indigo-600 text-white px-6 py-2.5 rounded-lg text-sm font-medium hover:bg-indigo-700 transition-colors shadow-sm shrink-0"
                >
                    {{ $t('home.search') }}
                </button>
            </form>
        </div>

        <!-- Results count -->
        <div v-if="meta && meta.total > 0" class="mb-4">
            <p class="text-sm text-gray-500">
                {{ meta.total }} {{ meta.total === 1 ? 'hotel' : 'hotels' }}
                <span v-if="filters?.q"> &middot; "{{ filters.q }}"</span>
            </p>
        </div>

        <!-- Empty State -->
        <div
            v-if="!hotels || hotels.length === 0"
            class="bg-white rounded-xl shadow-sm border border-gray-100 py-20 text-center"
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
        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
            <a
                v-for="(hotel, index) in hotels"
                :key="hotel.uuid"
                :href="`/portal/hotels/${hotel.slug}`"
                class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-md hover:-translate-y-0.5 transition-all duration-200 group flex flex-col"
            >
                <!-- Card Banner -->
                <div class="h-32 bg-gradient-to-br relative overflow-hidden" :class="getGradient(index)">
                    <div class="absolute inset-0 opacity-10">
                        <svg class="w-full h-full" viewBox="0 0 200 200">
                            <circle cx="180" cy="20" r="80" fill="white" />
                            <circle cx="20" cy="180" r="60" fill="white" />
                        </svg>
                    </div>
                    <div class="absolute inset-0 flex items-center justify-center">
                        <span class="text-3xl font-extrabold text-white/30">{{ getInitials(hotel.name) }}</span>
                    </div>
                    <!-- Availability badge -->
                    <div v-if="hotel.available_rooms > 0" class="absolute top-3 right-3 bg-white/90 backdrop-blur-sm text-xs font-semibold text-emerald-700 px-2.5 py-1 rounded-full">
                        {{ hotel.available_rooms }} {{ $t('home.rooms_available') }}
                    </div>
                    <div v-else class="absolute top-3 right-3 bg-white/90 backdrop-blur-sm text-xs font-semibold text-gray-500 px-2.5 py-1 rounded-full">
                        {{ $t('home.no_rooms') }}
                    </div>
                </div>

                <!-- Card Body -->
                <div class="p-4 flex flex-col flex-1">
                    <h2 class="text-base font-bold text-gray-900 group-hover:text-indigo-600 transition-colors mb-1 truncate">
                        {{ hotel.name }}
                    </h2>

                    <!-- Address -->
                    <div v-if="hotel.address" class="flex items-center gap-1.5 text-xs text-gray-500 mb-3">
                        <svg class="w-3.5 h-3.5 shrink-0 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <span class="truncate">{{ hotel.address }}</span>
                    </div>

                    <p v-if="hotel.description" class="text-xs text-gray-500 leading-relaxed mb-4 line-clamp-2">
                        {{ hotel.description }}
                    </p>

                    <div class="flex-1"></div>

                    <!-- Room types tags -->
                    <div v-if="hotel.room_types && hotel.room_types.length > 0" class="flex flex-wrap gap-1.5 mb-3">
                        <span
                            v-for="rt in hotel.room_types"
                            :key="rt.type"
                            class="inline-flex items-center gap-1 text-[10px] font-medium bg-gray-100 text-gray-600 px-2 py-0.5 rounded-md"
                        >
                            {{ $t('room_type.' + rt.type.toLowerCase()) }}
                            <span class="text-gray-400">&middot;</span>
                            {{ rt.available }}
                        </span>
                    </div>

                    <!-- Footer: Price + Arrow -->
                    <div class="flex items-center justify-between pt-3 border-t border-gray-100">
                        <div v-if="hotel.min_price !== null">
                            <span class="text-xs text-gray-400">{{ $t('home.from') }}</span>
                            <span class="text-lg font-bold text-indigo-700 ml-1">${{ hotel.min_price.toFixed(2) }}</span>
                            <span class="text-xs text-gray-400">{{ $t('home.per_night') }}</span>
                        </div>
                        <div v-else class="text-xs text-gray-400">{{ $t('home.no_rooms') }}</div>

                        <svg class="w-5 h-5 text-gray-300 group-hover:text-indigo-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </div>
                </div>
            </a>
        </div>

        <!-- Pagination -->
        <div v-if="meta && meta.last_page > 1" class="mt-6 flex items-center justify-between">
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
