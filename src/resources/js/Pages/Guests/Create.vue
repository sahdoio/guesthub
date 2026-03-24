<script setup>
import { useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PhoneInput from '@/Components/PhoneInput.vue';

defineOptions({ layout: AuthenticatedLayout });

const { t } = useI18n();

const form = useForm({
    full_name: '',
    email: '',
    phone: '',
    document: '',
});

const submit = () => {
    form.post('/guests');
};
</script>

<template>
    <div>
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-800">{{ $t('guest.new') }}</h1>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 max-w-2xl">
            <form @submit.prevent="submit" class="p-6 space-y-5">
                <div>
                    <label for="full_name" class="block text-sm font-medium text-gray-700 mb-1">{{ $t('guest.full_name') }}</label>
                    <input
                        id="full_name"
                        v-model="form.full_name"
                        type="text"
                        required
                        class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                        :class="{ 'border-red-500': form.errors.full_name }"
                    />
                    <p v-if="form.errors.full_name" class="mt-1 text-sm text-red-600">
                        {{ form.errors.full_name }}
                    </p>
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">{{ $t('guest.email') }}</label>
                    <input
                        id="email"
                        v-model="form.email"
                        type="email"
                        required
                        class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                        :class="{ 'border-red-500': form.errors.email }"
                    />
                    <p v-if="form.errors.email" class="mt-1 text-sm text-red-600">
                        {{ form.errors.email }}
                    </p>
                </div>

                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">{{ $t('guest.phone') }}</label>
                    <PhoneInput
                        id="phone"
                        v-model="form.phone"
                        required
                        :placeholder="$t('auth.phone_placeholder')"
                        input-class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                        :has-error="!!form.errors.phone"
                    />
                    <p v-if="form.errors.phone" class="mt-1 text-sm text-red-600">
                        {{ form.errors.phone }}
                    </p>
                </div>

                <div>
                    <label for="document" class="block text-sm font-medium text-gray-700 mb-1">{{ $t('guest.document') }}</label>
                    <input
                        id="document"
                        v-model="form.document"
                        type="text"
                        required
                        class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                        :class="{ 'border-red-500': form.errors.document }"
                    />
                    <p v-if="form.errors.document" class="mt-1 text-sm text-red-600">
                        {{ form.errors.document }}
                    </p>
                </div>

                <div class="flex items-center gap-3 pt-2">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="bg-indigo-600 text-white px-5 py-2.5 rounded-lg shadow-sm text-sm font-medium hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                    >
                        <span v-if="form.processing">{{ $t('guest.creating') }}</span>
                        <span v-else>{{ $t('guest.create') }}</span>
                    </button>
                    <a href="/guests" class="text-sm text-gray-500 hover:text-gray-700">{{ $t('common.cancel') }}</a>
                </div>
            </form>
        </div>
    </div>
</template>
