<script setup>
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

defineOptions({ layout: AuthenticatedLayout });

const { t } = useI18n();

const props = defineProps({
    guest: Object,
});

const g = props.guest;

const tierColors = {
    bronze: 'bg-orange-100 text-orange-800',
    silver: 'bg-gray-100 text-gray-800',
    gold: 'bg-yellow-100 text-yellow-800',
    platinum: 'bg-purple-100 text-purple-800',
};

const showDeleteConfirm = ref(false);

const deleteGuest = () => {
    router.delete(`/guests/${g.id}`);
};
</script>

<template>
    <div>
        <div class="mb-6 flex items-center gap-4">
            <a href="/guests" class="text-gray-500 hover:text-gray-700">&larr; {{ $t('common.back') }}</a>
            <h1 class="text-2xl font-bold text-gray-800">{{ $t('guest.profile') }}</h1>
            <span
                class="inline-flex px-2 py-1 text-xs font-semibold rounded-full capitalize"
                :class="tierColors[g.loyalty_tier]"
            >
                {{ $t('tier.' + g.loyalty_tier) }}
            </span>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-6">
                <!-- Contact Info -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-sm font-medium text-gray-500 uppercase tracking-wide mb-3">{{ $t('guest.contact_info') }}</h2>
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <span class="text-gray-500">{{ $t('guest.full_name') }}</span>
                            <p class="font-medium text-gray-900">{{ g.full_name }}</p>
                        </div>
                        <div>
                            <span class="text-gray-500">{{ $t('guest.email') }}</span>
                            <p class="font-medium text-gray-900">{{ g.email }}</p>
                        </div>
                        <div>
                            <span class="text-gray-500">{{ $t('guest.phone') }}</span>
                            <p class="font-medium text-gray-900">{{ g.phone }}</p>
                        </div>
                        <div>
                            <span class="text-gray-500">{{ $t('guest.document') }}</span>
                            <p class="font-medium text-gray-900">{{ g.document }}</p>
                        </div>
                    </div>
                </div>

                <!-- Preferences -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-sm font-medium text-gray-500 uppercase tracking-wide mb-3">{{ $t('guest.preferences') }}</h2>
                    <div v-if="g.preferences && g.preferences.length > 0" class="flex flex-wrap gap-2">
                        <span
                            v-for="pref in g.preferences"
                            :key="pref"
                            class="inline-flex px-3 py-1 text-sm bg-blue-50 text-blue-700 rounded-full"
                        >
                            {{ pref }}
                        </span>
                    </div>
                    <p v-else class="text-sm text-gray-500">{{ $t('guest.no_preferences') }}</p>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Actions -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-sm font-medium text-gray-500 uppercase tracking-wide mb-3">{{ $t('common.actions') }}</h2>
                    <div class="space-y-3">
                        <a
                            :href="`/guests/${g.id}/edit`"
                            class="block w-full text-center bg-blue-600 text-white py-2 px-4 rounded-md text-sm font-medium hover:bg-blue-700 transition-colors"
                        >
                            {{ $t('guest.edit_profile') }}
                        </a>

                        <button
                            v-if="!showDeleteConfirm"
                            @click="showDeleteConfirm = true"
                            class="w-full bg-red-600 text-white py-2 px-4 rounded-md text-sm font-medium hover:bg-red-700 transition-colors"
                        >
                            {{ $t('guest.delete') }}
                        </button>
                        <div v-else class="space-y-2">
                            <p class="text-sm text-gray-600">{{ $t('guest.delete_confirm') }}</p>
                            <div class="flex gap-2">
                                <button
                                    @click="deleteGuest"
                                    class="flex-1 bg-red-600 text-white py-2 px-4 rounded-md text-sm font-medium hover:bg-red-700"
                                >
                                    {{ $t('guest.confirm_delete') }}
                                </button>
                                <button
                                    @click="showDeleteConfirm = false"
                                    class="px-4 py-2 text-sm text-gray-500 hover:text-gray-700"
                                >
                                    {{ $t('common.cancel') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Timestamps -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-sm font-medium text-gray-500 uppercase tracking-wide mb-3">{{ $t('common.timeline') }}</h2>
                    <div class="space-y-2 text-sm">
                        <div>
                            <span class="text-gray-500">{{ $t('common.created') }}</span>
                            <p class="text-gray-900">{{ g.created_at }}</p>
                        </div>
                        <div v-if="g.updated_at">
                            <span class="text-gray-500">{{ $t('common.updated') }}</span>
                            <p class="text-gray-900">{{ g.updated_at }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
