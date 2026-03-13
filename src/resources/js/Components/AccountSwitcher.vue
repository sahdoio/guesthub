<script setup>
import { router, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const page = usePage();
const open = ref(false);

const accounts = computed(() => page.props.auth?.accounts || []);
const currentAccount = computed(() => page.props.auth?.currentAccount);

const switchAccount = (accountId) => {
    router.post('/switch-account', { account_id: accountId }, {
        preserveState: false,
    });
    open.value = false;
};
</script>

<template>
    <div class="relative">
        <button
            @click="open = !open"
            class="flex items-center gap-2 text-sm text-gray-600 hover:text-gray-800 bg-gray-100 hover:bg-gray-200 rounded-md px-3 py-1.5 transition-colors"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
            </svg>
            <span>{{ currentAccount?.name || 'Select Account' }}</span>
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
        </button>

        <div
            v-if="open"
            class="absolute right-0 mt-1 w-64 bg-white rounded-md shadow-lg border border-gray-200 z-50 max-h-64 overflow-y-auto"
        >
            <button
                v-for="account in accounts"
                :key="account.id"
                @click="switchAccount(account.id)"
                class="w-full text-left px-4 py-2 text-sm hover:bg-gray-50 transition-colors"
                :class="{ 'bg-blue-50 text-blue-700 font-medium': currentAccount?.id === account.id }"
            >
                {{ account.name }}
            </button>
            <div v-if="accounts.length === 0" class="px-4 py-2 text-sm text-gray-400">
                No accounts available
            </div>
        </div>

        <div v-if="open" class="fixed inset-0 z-40" @click="open = false"></div>
    </div>
</template>
