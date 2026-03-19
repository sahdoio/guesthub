<script setup>
import { router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();
const page = usePage();

const isImpersonating = computed(() => page.props.auth?.impersonating);
const userName = computed(() => page.props.auth?.user?.name);
const userRoles = computed(() => page.props.auth?.user?.roles || []);

const roleLabel = computed(() => {
    if (userRoles.value.includes('owner')) return t('role.owner');
    if (userRoles.value.includes('guest')) return t('role.guest');
    return '';
});

const stopImpersonation = () => {
    router.post('/stop-impersonation');
};
</script>

<template>
    <div
        v-if="isImpersonating"
        class="fixed top-0 left-0 right-0 z-50 bg-amber-500 text-white text-center text-sm py-2.5 px-4 shadow-md"
    >
        <div class="max-w-7xl mx-auto flex items-center justify-center gap-3">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
            </svg>
            <span>
                <span class="font-semibold">{{ $t('common.impersonating') }}</span>
                {{ userName }}
                <span v-if="roleLabel" class="opacity-80">({{ roleLabel }})</span>
            </span>
            <button
                @click="stopImpersonation"
                class="ml-2 bg-white/20 hover:bg-white/30 px-3 py-0.5 rounded-full text-xs font-semibold transition-colors"
            >
                {{ $t('common.stop_impersonation') }}
            </button>
        </div>
    </div>
</template>
