<script setup>
import { useI18n } from 'vue-i18n';
import GuestPortalLayout from '@/Layouts/GuestPortalLayout.vue';

defineOptions({ layout: GuestPortalLayout });

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
</script>

<template>
    <div>
        <div class="mb-6 flex items-center gap-4">
            <h1 class="text-2xl font-bold text-gray-800">{{ $t('nav.my_profile') }}</h1>
            <span
                v-if="g"
                class="inline-flex px-2 py-1 text-xs font-semibold rounded-full capitalize"
                :class="tierColors[g.loyalty_tier]"
            >
                {{ $t('tier.' + g.loyalty_tier) }}
            </span>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6" v-if="g">
            <div class="lg:col-span-2 space-y-6">
                <!-- Contact Info -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
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
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
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
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h2 class="text-sm font-medium text-gray-500 uppercase tracking-wide mb-3">{{ $t('common.actions') }}</h2>
                    <a
                        href="/portal/profile/edit"
                        class="block w-full text-center bg-indigo-600 text-white py-2.5 px-4 rounded-lg shadow-sm text-sm font-medium hover:bg-indigo-700 transition-colors"
                    >
                        {{ $t('guest.edit_profile') }}
                    </a>
                </div>

                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h2 class="text-sm font-medium text-gray-500 uppercase tracking-wide mb-3">{{ $t('common.account') }}</h2>
                    <div class="space-y-2 text-sm">
                        <div>
                            <span class="text-gray-500">{{ $t('common.member_since') }}</span>
                            <p class="text-gray-900">{{ g.created_at }}</p>
                        </div>
                        <div v-if="g.updated_at">
                            <span class="text-gray-500">{{ $t('common.last_updated') }}</span>
                            <p class="text-gray-900">{{ g.updated_at }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
