<script setup>
import { useI18n } from 'vue-i18n';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

defineOptions({ layout: AuthenticatedLayout });

const { t } = useI18n();

const props = defineProps({
    profile: Object,
});
</script>

<template>
    <div>
        <div class="mb-6 flex items-center justify-between">
            <h1 class="text-2xl font-bold text-gray-800">{{ $t('nav.my_profile') }}</h1>
            <a
                href="/profile/edit"
                class="inline-flex items-center gap-2 bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-indigo-700 transition-colors shadow-sm"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
                {{ $t('guest.edit_profile') }}
            </a>
        </div>

        <div class="max-w-2xl space-y-6">
            <!-- Contact Info -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h2 class="text-sm font-medium text-gray-500 uppercase tracking-wide mb-4">{{ $t('guest.contact_info') }}</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div class="flex items-start gap-3">
                        <div class="w-9 h-9 bg-indigo-50 rounded-lg flex items-center justify-center shrink-0">
                            <svg class="w-4.5 h-4.5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 uppercase tracking-wider">{{ $t('auth.full_name') }}</p>
                            <p class="font-medium text-gray-900 mt-0.5">{{ profile.name }}</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <div class="w-9 h-9 bg-indigo-50 rounded-lg flex items-center justify-center shrink-0">
                            <svg class="w-4.5 h-4.5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 uppercase tracking-wider">{{ $t('guest.email') }}</p>
                            <p class="font-medium text-gray-900 mt-0.5">{{ profile.email }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Account Info -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h2 class="text-sm font-medium text-gray-500 uppercase tracking-wide mb-4">{{ $t('common.account') }}</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div class="flex items-start gap-3">
                        <div class="w-9 h-9 bg-gray-50 rounded-lg flex items-center justify-center shrink-0">
                            <svg class="w-4.5 h-4.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 uppercase tracking-wider">{{ $t('common.member_since') }}</p>
                            <p class="font-medium text-gray-900 mt-0.5">{{ profile.created_at }}</p>
                        </div>
                    </div>
                    <div v-if="profile.updated_at" class="flex items-start gap-3">
                        <div class="w-9 h-9 bg-gray-50 rounded-lg flex items-center justify-center shrink-0">
                            <svg class="w-4.5 h-4.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 uppercase tracking-wider">{{ $t('common.last_updated') }}</p>
                            <p class="font-medium text-gray-900 mt-0.5">{{ profile.updated_at }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
