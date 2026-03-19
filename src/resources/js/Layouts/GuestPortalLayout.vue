<script setup>
import { router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import ImpersonationBanner from '../Components/ImpersonationBanner.vue';
import LanguageSwitcher from '../Components/LanguageSwitcher.vue';
import Logo from '../Components/Logo.vue';

const { t } = useI18n();
const page = usePage();

const currentPath = computed(() => page.url.split('?')[0]);

const isActive = (path) => {
    if (path === '/portal' && currentPath.value === '/portal') return true;
    if (path !== '/portal') return currentPath.value.startsWith(path);
    return false;
};

const navClass = (path) => {
    const base = 'inline-flex items-center px-1 pt-1 text-sm font-medium border-b-2 transition-colors duration-200';
    return isActive(path)
        ? `${base} text-indigo-700 border-indigo-500`
        : `${base} text-gray-500 border-transparent hover:text-gray-700 hover:border-gray-300`;
};

const isImpersonating = computed(() => page.props.auth?.impersonating);

const logout = () => {
    router.post('/logout');
};
</script>

<template>
    <div class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-indigo-50/30">
        <ImpersonationBanner />

        <nav
            class="bg-white/80 backdrop-blur-md border-b border-gray-200/60 sticky z-30"
            :class="isImpersonating ? 'top-10' : 'top-0'"
        >
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center">
                        <a href="/portal" class="shrink-0">
                            <Logo size="sm" />
                        </a>
                        <div class="hidden sm:flex sm:ml-10 sm:space-x-6">
                            <a href="/portal" :class="navClass('/portal')">
                                {{ $t('nav.home') }}
                            </a>
                            <a href="/portal/hotels" :class="navClass('/portal/hotels')">
                                {{ $t('nav.hotels') }}
                            </a>
                            <a href="/portal/reservations" :class="navClass('/portal/reservations')">
                                {{ $t('nav.my_reservations') }}
                            </a>
                            <a href="/portal/profile" :class="navClass('/portal/profile')">
                                {{ $t('nav.my_profile') }}
                            </a>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <LanguageSwitcher />
                        <div class="hidden sm:flex items-center gap-3">
                            <div class="flex items-center gap-2">
                                <div class="w-8 h-8 rounded-full bg-gradient-to-br from-indigo-400 to-violet-500 flex items-center justify-center text-white text-xs font-bold shadow-sm">
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

        <div v-if="$page.props.flash?.success" class="max-w-7xl mx-auto mt-4 px-4 sm:px-6 lg:px-8">
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-lg text-sm flex items-center gap-2">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                {{ $page.props.flash.success }}
            </div>
        </div>

        <main class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
            <slot />
        </main>
    </div>
</template>
