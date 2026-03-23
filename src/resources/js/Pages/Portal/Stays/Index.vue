<script setup>
import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { DatePicker } from 'v-calendar';
import GuestPortalLayout from '@/Layouts/GuestPortalLayout.vue';

defineOptions({ layout: GuestPortalLayout });

const { t } = useI18n();

const props = defineProps({
    stays: Array,
    meta: Object,
    filters: Object,
});

const searchInput = ref(props.filters?.q || '');
const dateRange = ref({
    start: props.filters?.check_in ? new Date(props.filters.check_in + 'T12:00:00') : null,
    end: props.filters?.check_out ? new Date(props.filters.check_out + 'T12:00:00') : null,
});
const adults = ref(Number(props.filters?.adults) || 1);
const children = ref(Number(props.filters?.children) || 0);
const babies = ref(Number(props.filters?.babies) || 0);
const pets = ref(Number(props.filters?.pets) || 0);
const showGuestDropdown = ref(false);

const formatDate = (date) => {
    if (!date) return '';
    const d = new Date(date);
    return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
};

const totalGuests = () => adults.value + children.value;

const guestSummary = () => {
    const parts = [];
    if (adults.value > 0) parts.push(`${adults.value} ${adults.value === 1 ? t('reservation.adult_singular') : t('reservation.adults')}`);
    if (children.value > 0) parts.push(`${children.value} ${children.value === 1 ? t('reservation.child_singular') : t('reservation.children')}`);
    if (babies.value > 0) parts.push(`${babies.value} ${babies.value === 1 ? t('reservation.baby_singular') : t('reservation.babies')}`);
    if (pets.value > 0) parts.push(`${pets.value} ${pets.value === 1 ? t('reservation.pet_singular') : t('reservation.pets')}`);
    return parts.length > 0 ? parts.join(', ') : t('home.guests');
};

let searchTimeout = null;

const applyFilters = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        submitSearch();
    }, 300);
};

watch(dateRange, () => submitSearch(), { deep: true });

const submitSearch = () => {
    clearTimeout(searchTimeout);
    const params = {};
    if (searchInput.value) params.q = searchInput.value;
    const ci = formatDate(dateRange.value.start);
    const co = formatDate(dateRange.value.end);
    if (ci) params.check_in = ci;
    if (co) params.check_out = co;
    if (adults.value > 1) params.adults = adults.value;
    if (children.value > 0) params.children = children.value;
    if (babies.value > 0) params.babies = babies.value;
    if (pets.value > 0) params.pets = pets.value;
    router.get('/portal/stays', params, { preserveState: true });
};

const goToPage = (page) => {
    const params = { ...props.filters, page };
    router.get('/portal/stays', params, { preserveState: true });
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
        <!-- Page Header -->
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">{{ $t('stay.browse_title') }}</h1>
            <p class="mt-1 text-sm text-gray-500">{{ $t('stay.browse_subtitle') }}</p>
        </div>

        <!-- Search & Filters -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-6">
            <form @submit.prevent="submitSearch" class="space-y-3">
                <div class="flex flex-col sm:flex-row gap-3">
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

                    <!-- Date Range -->
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
                                    :placeholder="$t('reservation.check_in')"
                                    readonly
                                    class="w-32 px-3 py-2.5 rounded-lg border border-gray-200 bg-white text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent cursor-pointer"
                                />
                                <svg class="w-4 h-4 text-gray-300 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                </svg>
                                <input
                                    :value="inputValue.end"
                                    v-on="inputEvents.end"
                                    :placeholder="$t('reservation.check_out')"
                                    readonly
                                    class="w-32 px-3 py-2.5 rounded-lg border border-gray-200 bg-white text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent cursor-pointer"
                                />
                            </div>
                        </template>
                    </DatePicker>

                    <!-- Guest Dropdown -->
                    <div class="relative">
                        <button
                            type="button"
                            @click="showGuestDropdown = !showGuestDropdown"
                            class="w-full sm:w-56 px-3 py-2.5 rounded-lg border border-gray-200 bg-white text-sm text-left focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent flex items-center justify-between"
                        >
                            <span class="truncate text-gray-700">{{ guestSummary() }}</span>
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
                                @click="showGuestDropdown = false; submitSearch()"
                                class="w-full bg-indigo-600 text-white py-2 rounded-lg text-sm font-medium hover:bg-indigo-700 transition-colors"
                            >
                                {{ $t('common.confirm') }}
                            </button>
                        </div>
                    </div>

                    <!-- Search button -->
                    <button
                        type="submit"
                        class="bg-indigo-600 text-white px-6 py-2.5 rounded-lg text-sm font-medium hover:bg-indigo-700 transition-colors shadow-sm shrink-0"
                    >
                        {{ $t('home.search') }}
                    </button>
                </div>
            </form>
        </div>

        <!-- Results count -->
        <div v-if="meta && meta.total > 0" class="mb-4">
            <p class="text-sm text-gray-500">
                {{ meta.total }} {{ meta.total === 1 ? $t('stay.stay_singular') : $t('stay.stay_plural') }}
                <span v-if="filters?.q"> &middot; "{{ filters.q }}"</span>
            </p>
        </div>

        <!-- Empty State -->
        <div
            v-if="!stays || stays.length === 0"
            class="bg-white rounded-xl shadow-sm border border-gray-100 py-20 text-center"
        >
            <div class="mx-auto w-16 h-16 rounded-full bg-indigo-50 flex items-center justify-center mb-4">
                <svg class="w-8 h-8 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-800 mb-1">{{ $t('stay.no_stays') }}</h3>
            <p class="text-sm text-gray-500">{{ $t('stay.no_stays_subtitle') }}</p>
        </div>

        <!-- Stay Grid -->
        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
            <a
                v-for="(stay, index) in stays"
                :key="stay.uuid"
                :href="`/portal/stays/${stay.slug}`"
                class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-md hover:-translate-y-0.5 transition-all duration-200 group flex flex-col"
            >
                <!-- Card Banner -->
                <div class="h-32 relative overflow-hidden" :class="stay.cover_image_url ? '' : 'bg-gradient-to-br ' + getGradient(index)">
                    <img v-if="stay.cover_image_url" :src="stay.cover_image_url" :alt="stay.name" class="w-full h-full object-cover" />
                    <template v-else>
                        <div class="absolute inset-0 opacity-10">
                            <svg class="w-full h-full" viewBox="0 0 200 200">
                                <circle cx="180" cy="20" r="80" fill="white" />
                                <circle cx="20" cy="180" r="60" fill="white" />
                            </svg>
                        </div>
                        <div class="absolute inset-0 flex items-center justify-center">
                            <span class="text-3xl font-extrabold text-white/30">{{ getInitials(stay.name) }}</span>
                        </div>
                    </template>
                    <!-- Type badge -->
                    <div class="absolute top-3 right-3 bg-white/90 backdrop-blur-sm text-xs font-semibold px-2.5 py-1 rounded-full"
                        :class="stay.type === 'room' ? 'text-blue-700' : 'text-purple-700'">
                        {{ $t('stay.type_' + stay.type) }}
                    </div>
                </div>

                <!-- Card Body -->
                <div class="p-4 flex flex-col flex-1">
                    <h2 class="text-base font-bold text-gray-900 group-hover:text-indigo-600 transition-colors mb-1 truncate">
                        {{ stay.name }}
                    </h2>

                    <!-- Address -->
                    <div v-if="stay.address" class="flex items-center gap-1.5 text-xs text-gray-500 mb-3">
                        <svg class="w-3.5 h-3.5 shrink-0 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <span class="truncate">{{ stay.address }}</span>
                    </div>

                    <p v-if="stay.description" class="text-xs text-gray-500 leading-relaxed mb-4 line-clamp-2">
                        {{ stay.description }}
                    </p>

                    <div class="flex-1"></div>

                    <!-- Category + Capacity tags -->
                    <div class="flex flex-wrap gap-1.5 mb-3">
                        <span
                            class="inline-flex items-center text-[10px] font-medium px-2 py-0.5 rounded-md"
                            :class="categoryColors[stay.category] || 'bg-gray-100 text-gray-600'"
                        >
                            {{ $t('stay.category_' + stay.category) }}
                        </span>
                        <span v-if="stay.capacity" class="inline-flex items-center gap-1 text-[10px] font-medium bg-gray-100 text-gray-600 px-2 py-0.5 rounded-md">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            {{ stay.capacity }}
                        </span>
                    </div>

                    <!-- Footer: Price + Arrow -->
                    <div class="flex items-center justify-between pt-3 border-t border-gray-100">
                        <div v-if="stay.price_per_night !== null">
                            <span class="text-lg font-bold text-indigo-700">${{ Number(stay.price_per_night).toFixed(2) }}</span>
                            <span class="text-xs text-gray-400">{{ $t('home.per_night') }}</span>
                        </div>
                        <div v-else class="text-xs text-gray-400">{{ $t('stay.no_price') }}</div>

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
