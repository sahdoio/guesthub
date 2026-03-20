<script setup>
import { useI18n } from 'vue-i18n';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

defineOptions({ layout: AuthenticatedLayout });

const { t } = useI18n();

const props = defineProps({
    hotel: Object,
});

const statusColors = {
    active: 'bg-green-100 text-green-800',
    inactive: 'bg-gray-100 text-gray-800',
};
</script>

<template>
    <div>
        <div class="mb-6">
            <a href="/hotels" class="text-sm text-gray-500 hover:text-gray-700 mb-2 inline-block">&larr; {{ $t('common.back') }}</a>
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold text-gray-800">{{ hotel.name }}</h1>
                <div class="flex items-center gap-3">
                    <a
                        :href="`/hotels/${hotel.slug}/rooms`"
                        class="bg-indigo-600 text-white px-4 py-2.5 rounded-lg shadow-sm text-sm font-medium hover:bg-indigo-700 transition-colors"
                    >
                        {{ $t('hotel.manage_rooms') }}
                    </a>
                    <a
                        :href="`/hotels/${hotel.slug}/edit`"
                        class="bg-indigo-600 text-white px-4 py-2.5 rounded-lg shadow-sm text-sm font-medium hover:bg-indigo-700 transition-colors"
                    >
                        {{ $t('common.edit') }}
                    </a>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-6">
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h2 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-4">{{ $t('hotel.details') }}</h2>

                    <div class="space-y-4">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">{{ $t('hotel.status') }}</dt>
                            <dd class="mt-1">
                                <span
                                    class="inline-flex px-2 py-0.5 text-xs font-semibold rounded-full capitalize"
                                    :class="statusColors[hotel.status] || 'bg-gray-100 text-gray-800'"
                                >
                                    {{ hotel.status }}
                                </span>
                            </dd>
                        </div>

                        <div v-if="hotel.description">
                            <dt class="text-sm font-medium text-gray-500">{{ $t('hotel.description') }}</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ hotel.description }}</dd>
                        </div>

                        <div v-if="hotel.address">
                            <dt class="text-sm font-medium text-gray-500">{{ $t('hotel.address') }}</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ hotel.address }}</dd>
                        </div>
                    </div>
                </div>
            </div>

            <div class="space-y-6">
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h2 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-4">{{ $t('hotel.contact_info') }}</h2>
                    <div class="space-y-3">
                        <div v-if="hotel.contact_email">
                            <dt class="text-sm font-medium text-gray-500">{{ $t('hotel.contact_email') }}</dt>
                            <dd class="mt-0.5 text-sm text-gray-900">{{ hotel.contact_email }}</dd>
                        </div>
                        <div v-if="hotel.contact_phone">
                            <dt class="text-sm font-medium text-gray-500">{{ $t('hotel.contact_phone') }}</dt>
                            <dd class="mt-0.5 text-sm text-gray-900">{{ hotel.contact_phone }}</dd>
                        </div>
                        <p v-if="!hotel.contact_email && !hotel.contact_phone" class="text-sm text-gray-400 italic">
                            {{ $t('hotel.no_description') }}
                        </p>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h2 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-4">{{ $t('common.timeline') }}</h2>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-500">{{ $t('common.created') }}</span>
                            <span class="text-gray-900">{{ hotel.created_at }}</span>
                        </div>
                        <div v-if="hotel.updated_at" class="flex justify-between">
                            <span class="text-gray-500">{{ $t('common.updated') }}</span>
                            <span class="text-gray-900">{{ hotel.updated_at }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
