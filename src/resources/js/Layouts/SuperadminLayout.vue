<script setup>
import { router, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import LanguageSwitcher from '../Components/LanguageSwitcher.vue';
import Logo from '../Components/Logo.vue';

const { t } = useI18n();
const page = usePage();

const logout = () => {
    router.post('/logout');
};
</script>

<template>
    <div class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-indigo-50/30">
        <nav class="bg-white/80 backdrop-blur-md border-b border-gray-200/60 sticky top-0 z-30">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center">
                        <a href="/superadmin" class="shrink-0">
                            <Logo size="sm" />
                        </a>
                        <span class="ml-4 text-xs font-semibold text-gray-400 uppercase tracking-wider">{{ $t('nav.superadmin') }}</span>
                    </div>

                    <div class="flex items-center gap-3">
                        <LanguageSwitcher />
                        <div class="hidden sm:flex items-center gap-3 pl-3 border-l border-gray-200">
                            <div class="flex items-center gap-2">
                                <div class="w-8 h-8 rounded-full bg-gradient-to-br from-red-400 to-orange-500 flex items-center justify-center text-white text-xs font-bold shadow-sm">
                                    {{ $page.props.auth?.user?.name?.charAt(0)?.toUpperCase() }}
                                </div>
                                <span class="text-sm font-medium text-gray-700">
                                    {{ $page.props.auth?.user?.name }}
                                </span>
                            </div>
                            <button
                                @click="logout"
                                class="text-sm text-gray-400 hover:text-gray-600 transition-colors"
                                :title="$t('nav.logout')"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </nav>

        <main class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
            <slot />
        </main>
    </div>
</template>
