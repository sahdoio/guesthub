<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import SuperadminLayout from '@/Layouts/SuperadminLayout.vue';

defineOptions({ layout: SuperadminLayout });

const { t } = useI18n();

const props = defineProps({
    accounts: { type: Array, default: () => [] },
});

const search = ref('');

const filteredAccounts = computed(() => {
    const q = search.value.toLowerCase().trim();
    if (!q) return props.accounts;
    return props.accounts.filter(a =>
        a.name.toLowerCase().includes(q) ||
        a.slug.toLowerCase().includes(q) ||
        a.actors.some(actor =>
            actor.name.toLowerCase().includes(q) ||
            actor.email.toLowerCase().includes(q)
        )
    );
});

const roleColor = (role) => {
    const colors = {
        owner: 'bg-blue-100 text-blue-700',
        guest: 'bg-green-100 text-green-700',
        superadmin: 'bg-red-100 text-red-700',
    };
    return colors[role] || 'bg-gray-100 text-gray-700';
};

const impersonate = (actorId) => {
    router.post(`/impersonate/${actorId}`);
};
</script>

<template>
    <div>
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-gray-900">{{ $t('superadmin.title') }}</h1>
            <p class="mt-1 text-sm text-gray-500">{{ $t('superadmin.subtitle') }}</p>
        </div>

        <div class="mb-6">
            <input
                v-model="search"
                type="text"
                :placeholder="$t('superadmin.search_placeholder')"
                class="w-full max-w-md rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none transition-colors"
            />
        </div>

        <div v-if="filteredAccounts.length === 0" class="text-center py-12 text-gray-400">
            {{ $t('superadmin.no_accounts') }}
        </div>

        <div class="space-y-4">
            <div
                v-for="account in filteredAccounts"
                :key="account.uuid"
                class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden"
            >
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="font-semibold text-gray-900">{{ account.name }}</h2>
                            <span class="text-xs text-gray-400">{{ account.slug }}</span>
                        </div>
                        <span class="text-xs text-gray-400">
                            {{ account.actors.length }} {{ $t('superadmin.actors').toLowerCase() }}
                        </span>
                    </div>
                </div>

                <div v-if="account.actors.length > 0" class="divide-y divide-gray-50">
                    <div
                        v-for="actor in account.actors"
                        :key="actor.id"
                        class="px-6 py-3 flex items-center justify-between hover:bg-gray-50/50 transition-colors"
                    >
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-gradient-to-br from-gray-300 to-gray-400 flex items-center justify-center text-white text-xs font-bold">
                                {{ actor.name.charAt(0).toUpperCase() }}
                            </div>
                            <div>
                                <div class="text-sm font-medium text-gray-900">{{ actor.name }}</div>
                                <div class="text-xs text-gray-400">{{ actor.email }}</div>
                            </div>
                            <div class="flex gap-1 ml-2">
                                <span
                                    v-for="role in actor.roles"
                                    :key="role"
                                    class="inline-flex px-2 py-0.5 text-xs font-medium rounded-full capitalize"
                                    :class="roleColor(role)"
                                >
                                    {{ $t('role.' + role) }}
                                </span>
                            </div>
                        </div>
                        <button
                            @click="impersonate(actor.id)"
                            class="text-xs font-medium text-indigo-600 hover:text-indigo-800 bg-indigo-50 hover:bg-indigo-100 px-3 py-1.5 rounded-lg transition-colors"
                        >
                            {{ $t('superadmin.impersonate') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
