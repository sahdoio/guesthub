<script setup>
import { useI18n } from 'vue-i18n';
import { router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

defineOptions({ layout: AuthenticatedLayout });

const { t } = useI18n();

const props = defineProps({
    invoices: Array,
    meta: Object,
    filters: Object,
});

const statusColors = {
    draft: 'bg-gray-100 text-gray-800',
    issued: 'bg-yellow-100 text-yellow-800',
    paid: 'bg-green-100 text-green-800',
    void: 'bg-red-100 text-red-800',
    refunded: 'bg-purple-100 text-purple-800',
};

const formatMoney = (cents) => {
    return '$' + (cents / 100).toFixed(2);
};

const applyFilter = (key, value) => {
    const params = { ...props.filters };
    if (value) {
        params[key] = value;
    } else {
        delete params[key];
    }
    delete params.page;
    router.get('/billing', params, { preserveState: true });
};

const goToPage = (page) => {
    router.get('/billing', { ...props.filters, page }, { preserveState: true });
};
</script>

<template>
    <div>
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold text-gray-800">{{ $t('billing.invoices') }}</h1>
        </div>

        <!-- Filters -->
        <div class="flex flex-wrap gap-3 mb-6">
            <select
                :value="filters.status || ''"
                @change="applyFilter('status', $event.target.value)"
                class="rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
            >
                <option value="">{{ $t('common.all_statuses') }}</option>
                <option value="draft">{{ $t('billing.status_draft') }}</option>
                <option value="issued">{{ $t('billing.status_issued') }}</option>
                <option value="paid">{{ $t('billing.status_paid') }}</option>
                <option value="void">{{ $t('billing.status_void') }}</option>
                <option value="refunded">{{ $t('billing.status_refunded') }}</option>
            </select>

            <input
                type="text"
                :value="filters.search || ''"
                @input="applyFilter('search', $event.target.value)"
                :placeholder="$t('guest.search_placeholder')"
                class="rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
            />
        </div>

        <!-- Empty State -->
        <div v-if="invoices.length === 0" class="bg-white rounded-xl shadow-sm border border-gray-100 p-12 text-center">
            <div class="w-16 h-16 bg-indigo-50 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
            </div>
            <p class="text-gray-500">{{ $t('billing.no_invoices') }}</p>
        </div>

        <!-- Invoices Table -->
        <div v-else class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ $t('billing.invoice') }} #</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ $t('reservation.stay') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ $t('reservation.guest') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ $t('billing.status') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ $t('billing.total') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ $t('billing.created_at') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ $t('common.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <tr v-for="invoice in invoices" :key="invoice.id" class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            {{ invoice.invoice_number }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                            <div v-if="invoice.reservation?.stay" class="flex items-center gap-2">
                                <img v-if="invoice.reservation.stay.cover_image_url" :src="invoice.reservation.stay.cover_image_url" :alt="invoice.reservation.stay.name" class="w-8 h-8 rounded object-cover" />
                                <a :href="`/stays/${invoice.reservation.stay.slug}`" class="text-indigo-600 hover:text-indigo-800 hover:underline">{{ invoice.reservation.stay.name }}</a>
                            </div>
                            <span v-else>-</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                            {{ invoice.guest?.full_name || '-' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span
                                class="inline-flex px-2 py-0.5 text-xs font-semibold rounded-full capitalize"
                                :class="statusColors[invoice.status] || 'bg-gray-100 text-gray-800'"
                            >
                                {{ $t('billing.status_' + invoice.status) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">
                            {{ formatMoney(invoice.total_cents) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ invoice.created_at }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <a
                                :href="`/billing/${invoice.uuid}`"
                                class="text-indigo-600 hover:text-indigo-800 font-medium"
                            >
                                {{ $t('common.view') }}
                            </a>
                        </td>
                    </tr>
                </tbody>
            </table>
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
