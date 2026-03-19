<script setup>
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

const goToPage = (page) => {
    router.get('/guests', { ...props.filters, page }, { preserveState: true });
};
</script>

<template>
    <div>
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold text-gray-800">{{ $t('guest.title') }}</h1>
            <a
                href="/guests/create"
                class="bg-blue-600 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-blue-700 transition-colors"
            >
                {{ $t('guest.new') }}
            </a>
        </div>

        <div class="bg-white rounded-lg shadow">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('common.name') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('common.contact') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('guest.document') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('guest.loyalty_tier') }}</th>
                        <th class="px-6 py-3"></th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <tr v-if="guests.length === 0">
                        <td colspan="5" class="px-6 py-8 text-center text-sm text-gray-500">
                            {{ $t('guest.no_guests') }}
                        </td>
                    </tr>
                    <tr v-for="guest in guests" :key="guest.id" class="hover:bg-gray-50">
                        <td class="px-6 py-4 text-sm">
                            <div class="font-medium text-gray-900">{{ guest.full_name }}</div>
                        </td>
                        <td class="px-6 py-4 text-sm">
                            <div class="text-gray-900">{{ guest.email }}</div>
                            <div class="text-gray-500">{{ guest.phone }}</div>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-700">
                            {{ guest.document }}
                        </td>
                        <td class="px-6 py-4">
                            <span
                                class="inline-flex px-2 py-1 text-xs font-semibold rounded-full capitalize"
                                :class="tierColors[guest.loyalty_tier]"
                            >
                                {{ $t('tier.' + guest.loyalty_tier) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <a
                                :href="`/guests/${guest.id}`"
                                class="text-blue-600 hover:text-blue-800 text-sm font-medium"
                            >
                                {{ $t('common.view') }}
                            </a>
                        </td>
                    </tr>
                </tbody>
            </table>

            <div v-if="meta.last_page > 1" class="px-6 py-3 border-t border-gray-200 flex items-center justify-between">
                <span class="text-sm text-gray-500">
                    {{ $t('common.page') }} {{ meta.current_page }} {{ $t('common.of') }} {{ meta.last_page }} ({{ meta.total }} {{ $t('common.total') }})
                </span>
                <div class="flex gap-2">
                    <button
                        v-if="meta.current_page > 1"
                        @click="goToPage(meta.current_page - 1)"
                        class="px-3 py-1 text-sm border border-gray-300 rounded hover:bg-gray-50"
                    >
                        {{ $t('common.previous') }}
                    </button>
                    <button
                        v-if="meta.current_page < meta.last_page"
                        @click="goToPage(meta.current_page + 1)"
                        class="px-3 py-1 text-sm border border-gray-300 rounded hover:bg-gray-50"
                    >
                        {{ $t('common.next') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
