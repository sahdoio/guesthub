<script setup>
import { useI18n } from 'vue-i18n';
import { useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

defineOptions({ layout: AuthenticatedLayout });

const { t } = useI18n();

const props = defineProps({
    profile: Object,
});

const form = useForm({
    name: props.profile.name,
    email: props.profile.email,
});

const submit = () => {
    form.put('/profile');
};
</script>

<template>
    <div>
        <div class="mb-6 flex items-center gap-4">
            <a href="/profile" class="text-gray-500 hover:text-gray-700">&larr; {{ $t('common.back') }}</a>
            <h1 class="text-2xl font-bold text-gray-800">{{ $t('guest.edit_profile') }}</h1>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 max-w-2xl">
            <form @submit.prevent="submit" class="p-6 space-y-5">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">{{ $t('auth.full_name') }}</label>
                    <input
                        id="name"
                        v-model="form.name"
                        type="text"
                        required
                        class="w-full rounded-lg border border-gray-200 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                        :class="{ 'border-red-500': form.errors.name }"
                    />
                    <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</p>
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">{{ $t('guest.email') }}</label>
                    <input
                        id="email"
                        v-model="form.email"
                        type="email"
                        required
                        class="w-full rounded-lg border border-gray-200 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                        :class="{ 'border-red-500': form.errors.email }"
                    />
                    <p v-if="form.errors.email" class="mt-1 text-sm text-red-600">{{ form.errors.email }}</p>
                </div>

                <div class="flex items-center gap-3 pt-2">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="bg-indigo-600 text-white px-5 py-2.5 rounded-lg text-sm font-medium hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors shadow-sm"
                    >
                        <span v-if="form.processing">{{ $t('guest.saving') }}</span>
                        <span v-else>{{ $t('guest.save') }}</span>
                    </button>
                    <a href="/profile" class="text-sm text-gray-500 hover:text-gray-700">{{ $t('common.cancel') }}</a>
                </div>
            </form>
        </div>
    </div>
</template>
