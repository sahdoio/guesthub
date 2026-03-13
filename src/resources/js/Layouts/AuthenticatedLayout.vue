<script setup>
import { router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();

const currentPath = computed(() => page.url.split('?')[0]);

const isActive = (path) => currentPath.value.startsWith(path);

const navClass = (path) => {
    const base = 'inline-flex items-center px-1 pt-1 text-sm font-medium border-b-2 transition-colors';
    return isActive(path)
        ? `${base} text-gray-900 border-blue-500`
        : `${base} text-gray-500 border-transparent hover:text-gray-700 hover:border-gray-300`;
};

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
                        </div>
                    </div>

                    <div class="flex items-center">
                        <span class="text-sm text-gray-600 mr-4">
                            {{ $page.props.auth?.user?.email }}
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
