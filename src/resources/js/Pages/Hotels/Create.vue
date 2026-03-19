<script setup>
import { useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

defineOptions({ layout: AuthenticatedLayout });

const { t } = useI18n();

const form = useForm({
    name: '',
    description: '',
    address: '',
    contact_email: '',
    contact_phone: '',
});

const submit = () => {
    form.post('/hotels');
};
</script>

<template>
    <div>
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-800">{{ $t('hotel.new') }}</h1>
        </div>

        <div class="bg-white rounded-lg shadow max-w-2xl">
            <form @submit.prevent="submit" class="p-6 space-y-5">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">{{ $t('hotel.name') }}</label>
                    <input
                        id="name"
                        v-model="form.name"
                        type="text"
                        required
                        :placeholder="$t('hotel.name_placeholder')"
                        class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        :class="{ 'border-red-500': form.errors.name }"
                    />
                    <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</p>
                </div>

                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-1">
                        {{ $t('hotel.description') }}
                        <span class="text-gray-400 font-normal">({{ $t('auth.optional') }})</span>
                    </label>
                    <textarea
                        id="description"
                        v-model="form.description"
                        rows="3"
                        :placeholder="$t('hotel.description_placeholder')"
                        class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        :class="{ 'border-red-500': form.errors.description }"
                    ></textarea>
                    <p v-if="form.errors.description" class="mt-1 text-sm text-red-600">{{ form.errors.description }}</p>
                </div>

                <div>
                    <label for="address" class="block text-sm font-medium text-gray-700 mb-1">
                        {{ $t('hotel.address') }}
                        <span class="text-gray-400 font-normal">({{ $t('auth.optional') }})</span>
                    </label>
                    <input
                        id="address"
                        v-model="form.address"
                        type="text"
                        :placeholder="$t('hotel.address_placeholder')"
                        class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        :class="{ 'border-red-500': form.errors.address }"
                    />
                    <p v-if="form.errors.address" class="mt-1 text-sm text-red-600">{{ form.errors.address }}</p>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="contact_email" class="block text-sm font-medium text-gray-700 mb-1">
                            {{ $t('hotel.contact_email') }}
                            <span class="text-gray-400 font-normal">({{ $t('auth.optional') }})</span>
                        </label>
                        <input
                            id="contact_email"
                            v-model="form.contact_email"
                            type="email"
                            :placeholder="$t('hotel.contact_email_placeholder')"
                            class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            :class="{ 'border-red-500': form.errors.contact_email }"
                        />
                        <p v-if="form.errors.contact_email" class="mt-1 text-sm text-red-600">{{ form.errors.contact_email }}</p>
                    </div>

                    <div>
                        <label for="contact_phone" class="block text-sm font-medium text-gray-700 mb-1">
                            {{ $t('hotel.contact_phone') }}
                            <span class="text-gray-400 font-normal">({{ $t('auth.optional') }})</span>
                        </label>
                        <input
                            id="contact_phone"
                            v-model="form.contact_phone"
                            type="text"
                            :placeholder="$t('hotel.contact_phone_placeholder')"
                            class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            :class="{ 'border-red-500': form.errors.contact_phone }"
                        />
                        <p v-if="form.errors.contact_phone" class="mt-1 text-sm text-red-600">{{ form.errors.contact_phone }}</p>
                    </div>
                </div>

                <div class="flex items-center gap-3 pt-2">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="bg-blue-600 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                    >
                        <span v-if="form.processing">{{ $t('hotel.creating') }}</span>
                        <span v-else>{{ $t('hotel.create') }}</span>
                    </button>
                    <a href="/hotels" class="text-sm text-gray-500 hover:text-gray-700">{{ $t('common.cancel') }}</a>
                </div>
            </form>
        </div>
    </div>
</template>
