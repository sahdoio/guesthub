<script setup>
import { router, usePage } from '@inertiajs/vue3';
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useI18n } from 'vue-i18n';
import ImpersonationBanner from '../Components/ImpersonationBanner.vue';
import LanguageSwitcher from '../Components/LanguageSwitcher.vue';
import Logo from '../Components/Logo.vue';

const { t } = useI18n();
const page = usePage();

const currentPath = computed(() => page.url.split('?')[0]);

const isActive = (path) => currentPath.value.startsWith(path);

const navClass = (path) => {
    const base = 'inline-flex items-center px-1 pt-1 text-sm font-medium border-b-2 transition-colors duration-200';
    return isActive(path)
        ? `${base} text-indigo-700 border-indigo-500`
        : `${base} text-gray-500 border-transparent hover:text-gray-700 hover:border-gray-300`;
};

const isImpersonating = computed(() => page.props.auth?.impersonating);

const userMenuOpen = ref(false);
const userMenuRef = ref(null);

const toggleUserMenu = () => {
    userMenuOpen.value = !userMenuOpen.value;
};

const closeUserMenu = (e) => {
    if (userMenuRef.value && !userMenuRef.value.contains(e.target)) {
        userMenuOpen.value = false;
    }
};

onMounted(() => document.addEventListener('click', closeUserMenu));
onUnmounted(() => document.removeEventListener('click', closeUserMenu));

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
                        <a href="/dashboard" class="shrink-0">
                            <Logo size="sm" />
                        </a>
                        <div class="hidden sm:flex sm:ml-10 sm:space-x-6">
                            <a href="/dashboard" :class="navClass('/dashboard')">
                                {{ $t('nav.dashboard') }}
                            </a>
                            <a href="/hotels" :class="navClass('/hotels')">
                                {{ $t('nav.hotels') }}
                            </a>
                            <a href="/reservations" :class="navClass('/reservations')">
                                {{ $t('nav.reservations') }}
                            </a>
                            <a href="/guests" :class="navClass('/guests')">
                                {{ $t('nav.guests') }}
                            </a>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <LanguageSwitcher />
                        <div class="hidden sm:flex items-center pl-3 border-l border-gray-200" ref="userMenuRef">
                            <div class="relative">
                                <button
                                    @click="toggleUserMenu"
                                    class="flex items-center gap-2 px-2 py-1.5 rounded-lg hover:bg-gray-100 transition-colors"
                                >
                                    <div class="w-8 h-8 rounded-full bg-gradient-to-br from-indigo-400 to-violet-500 flex items-center justify-center text-white text-xs font-bold shadow-sm">
                                        {{ $page.props.auth?.user?.name?.charAt(0)?.toUpperCase() }}
                                    </div>
                                    <span class="text-sm font-medium text-gray-700">
                                        {{ $page.props.auth?.user?.name }}
                                    </span>
                                    <svg class="w-4 h-4 text-gray-400 transition-transform" :class="{ 'rotate-180': userMenuOpen }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>

                                <Transition
                                    enter-active-class="transition ease-out duration-100"
                                    enter-from-class="transform opacity-0 scale-95"
                                    enter-to-class="transform opacity-100 scale-100"
                                    leave-active-class="transition ease-in duration-75"
                                    leave-from-class="transform opacity-100 scale-100"
                                    leave-to-class="transform opacity-0 scale-95"
                                >
                                    <div
                                        v-if="userMenuOpen"
                                        class="absolute right-0 mt-2 w-56 bg-white rounded-lg shadow-lg ring-1 ring-black/5 py-1 z-50"
                                    >
                                        <div class="px-4 py-3 border-b border-gray-100">
                                            <p class="text-sm font-medium text-gray-900 truncate">{{ $page.props.auth?.user?.name }}</p>
                                            <p class="text-xs text-gray-500 truncate mt-0.5">{{ $page.props.auth?.user?.email }}</p>
                                        </div>

                                        <a
                                            href="/profile"
                                            class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 transition-colors"
                                            @click="userMenuOpen = false"
                                        >
                                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                            </svg>
                                            {{ $t('nav.my_profile') }}
                                        </a>

                                        <div class="border-t border-gray-100 my-1"></div>

                                        <button
                                            @click="logout"
                                            class="flex items-center gap-3 w-full px-4 py-2.5 text-sm text-red-600 hover:bg-red-50 transition-colors"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                            </svg>
                                            {{ $t('nav.logout') }}
                                        </button>
                                    </div>
                                </Transition>
                            </div>
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
