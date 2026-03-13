<script setup>
import { router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AccountSwitcher from '../Components/AccountSwitcher.vue';

const page = usePage();

const currentPath = computed(() => page.url.split('?')[0]);

const isActive = (path) => currentPath.value.startsWith(path);

const navClass = (path) => {
    const base = 'inline-flex items-center px-1 pt-1 text-sm font-medium border-b-2 transition-colors';
    return isActive(path)
        ? `${base} text-gray-900 border-blue-500`
        : `${base} text-gray-500 border-transparent hover:text-gray-700 hover:border-gray-300`;
};

const isSuperAdmin = computed(() => {
    const roles = page.props.auth?.user?.roles || [];
    return roles.includes('superadmin');
});

const logout = () => {
    router.post('/logout');
};
</script>

<template>
    <div class="min-h-screen bg-gray-100">
        <nav class="bg-white border-b border-gray-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center">
                        <a href="/dashboard" class="text-xl font-bold text-gray-800">
                            GuestHub
                        </a>
                        <a href="https://github.com/sahdoio/guesthub" target="_blank" rel="noopener noreferrer" class="ml-3 text-gray-400 hover:text-gray-600 transition-colors" title="GitHub">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 0C5.37 0 0 5.37 0 12c0 5.3 3.438 9.8 8.205 11.385.6.113.82-.258.82-.577 0-.285-.01-1.04-.015-2.04-3.338.724-4.042-1.61-4.042-1.61-.546-1.385-1.335-1.755-1.335-1.755-1.087-.744.084-.729.084-.729 1.205.084 1.838 1.236 1.838 1.236 1.07 1.835 2.809 1.305 3.495.998.108-.776.417-1.305.76-1.605-2.665-.3-5.466-1.332-5.466-5.93 0-1.31.465-2.38 1.235-3.22-.135-.303-.54-1.523.105-3.176 0 0 1.005-.322 3.3 1.23.96-.267 1.98-.399 3-.405 1.02.006 2.04.138 3 .405 2.28-1.552 3.285-1.23 3.285-1.23.645 1.653.24 2.873.12 3.176.765.84 1.23 1.91 1.23 3.22 0 4.61-2.805 5.625-5.475 5.92.42.36.81 1.096.81 2.22 0 1.606-.015 2.896-.015 3.286 0 .315.21.69.825.57C20.565 21.795 24 17.295 24 12c0-6.63-5.37-12-12-12z"/></svg>
                        </a>
                        <div class="hidden sm:flex sm:ml-10 sm:space-x-8">
                            <a href="/dashboard" :class="navClass('/dashboard')">
                                Dashboard
                            </a>
                            <a href="/reservations" :class="navClass('/reservations')">
                                Reservations
                            </a>
                            <a href="/guests" :class="navClass('/guests')">
                                Guests
                            </a>
                            <a href="/rooms" :class="navClass('/rooms')">
                                Rooms
                            </a>
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        <AccountSwitcher v-if="isSuperAdmin" />
                        <span class="text-sm text-gray-600">
                            {{ $page.props.auth?.user?.name }}
                        </span>
                        <button
                            @click="logout"
                            class="text-sm text-gray-500 hover:text-gray-700 transition-colors"
                        >
                            Logout
                        </button>
                    </div>
                </div>
            </div>
        </nav>

        <div v-if="$page.props.flash?.success" class="max-w-7xl mx-auto mt-4 px-4 sm:px-6 lg:px-8">
            <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded text-sm">
                {{ $page.props.flash.success }}
            </div>
        </div>

        <main class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
            <slot />
        </main>
    </div>
</template>
