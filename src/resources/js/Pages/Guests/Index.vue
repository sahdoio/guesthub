<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

defineOptions({ layout: AuthenticatedLayout });

const { t } = useI18n();

const props = defineProps({
    guests: Array,
    meta: Object,
    filters: Object,
});

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

const searchInput = ref(props.filters.search || '');
let searchTimeout = null;

const onSearch = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        applyFilter('search', searchInput.value);
    }, 300);
};

const applyFilter = (key, value) => {
    const params = { ...props.filters };
    if (value) {
        params[key] = value;
    } else {
        delete params[key];
    }
    delete params.page;
    router.get('/guests', params, { preserveState: true });
};

const goToPage = (page) => {
    router.get('/guests', { ...props.filters, page }, { preserveState: true });
};
</script>

<template>
    <div>
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-800">{{ $t('guest.title') }}</h1>
        </div>

        <!-- Filters -->
        <div class="flex flex-wrap gap-3 mb-6">
            <div class="relative flex-1 max-w-sm">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <input
                    v-model="searchInput"
                    @input="onSearch"
                    type="text"
                    :placeholder="$t('guest.search_placeholder')"
                    class="w-full pl-9 pr-3 py-2 rounded-lg border border-gray-200 bg-white text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                />
            </div>
            <select
                :value="filters.loyalty_tier || ''"
                @change="applyFilter('loyalty_tier', $event.target.value)"
                class="rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
            >
                <option value="">{{ $t('common.all_types') }}</option>
                <option value="bronze">{{ $t('tier.bronze') }}</option>
                <option value="silver">{{ $t('tier.silver') }}</option>
                <option value="gold">{{ $t('tier.gold') }}</option>
                <option value="platinum">{{ $t('tier.platinum') }}</option>
            </select>
        </div>

        <!-- Empty State -->
        <div v-if="guests.length === 0" class="bg-white rounded-xl shadow-sm border border-gray-100 p-12 text-center">
            <div class="w-16 h-16 bg-indigo-50 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
            </div>
            <p class="text-gray-500">{{ $t('guest.no_guests') }}</p>
        </div>

        <!-- Guest Cards Grid -->
        <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3">
            <a
                v-for="guest in guests"
                :key="guest.id"
                :href="`/guests/${guest.id}`"
                class="bg-white rounded-lg shadow-sm border border-gray-100 hover:shadow-md transition-all duration-200 group"
            >
                <div class="px-4 py-3">
                    <div class="flex items-center gap-2.5 mb-2">
                        <div class="w-8 h-8 rounded-full bg-gradient-to-br from-indigo-400 to-violet-500 flex items-center justify-center text-white text-xs font-bold shrink-0">
                            {{ guest.full_name.charAt(0).toUpperCase() }}
                        </div>
                        <div class="min-w-0 flex-1">
                            <h3 class="text-sm font-semibold text-gray-900 group-hover:text-indigo-600 transition-colors truncate">
                                {{ guest.full_name }}
                            </h3>
                            <p class="text-xs text-gray-500 truncate">{{ guest.email }}</p>
                        </div>
                        <span
                            class="inline-flex items-center gap-1 px-2 py-0.5 text-[10px] font-semibold rounded-full capitalize shrink-0"
                            :class="tierColors[guest.loyalty_tier]"
                        >
                            <span class="w-1 h-1 rounded-full" :class="tierDot[guest.loyalty_tier]"></span>
                            {{ $t('tier.' + guest.loyalty_tier) }}
                        </span>
                    </div>
                    <div class="flex items-center gap-3 text-xs text-gray-500">
                        <span class="flex items-center gap-1 truncate">
                            <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                            </svg>
                            {{ guest.phone }}
                        </span>
                        <span class="flex items-center gap-1 truncate">
                            <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2" />
                            </svg>
                            {{ guest.document }}
                        </span>
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
