<script setup>
import { useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PhoneInput from '@/Components/PhoneInput.vue';

defineOptions({ layout: AuthenticatedLayout });

const { t } = useI18n();

const props = defineProps({
    guest: Object,
});

const form = useForm({
    full_name: props.guest.full_name,
    email: props.guest.email,
    phone: props.guest.phone,
    loyalty_tier: props.guest.loyalty_tier,
    preferences: props.guest.preferences || [],
});

const newPreference = useForm({ value: '' });

const addPreference = () => {
    const val = newPreference.value.trim();
    if (val && !form.preferences.includes(val)) {
        form.preferences.push(val);
    }
    newPreference.value = '';
};

const removePreference = (index) => {
    form.preferences.splice(index, 1);
};

const submit = () => {
    form.put(`/guests/${props.guest.id}`);
};
</script>

<template>
    <div>
        <div class="mb-6 flex items-center gap-4">
            <a :href="`/guests/${guest.id}`" class="text-gray-500 hover:text-gray-700">&larr; {{ $t('common.back') }}</a>
            <h1 class="text-2xl font-bold text-gray-800">{{ $t('guest.edit') }}</h1>
        </div>

        <div class="bg-white rounded-lg shadow max-w-2xl">
            <form @submit.prevent="submit" class="p-6 space-y-5">
                <div>
                    <label for="full_name" class="block text-sm font-medium text-gray-700 mb-1">{{ $t('guest.full_name') }}</label>
                    <input
                        id="full_name"
                        v-model="form.full_name"
                        type="text"
                        required
                        class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
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
                        class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
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
                        input-class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        :has-error="!!form.errors.phone"
                    />
                    <p v-if="form.errors.phone" class="mt-1 text-sm text-red-600">
                        {{ form.errors.phone }}
                    </p>
                </div>

                <div>
                    <label for="loyalty_tier" class="block text-sm font-medium text-gray-700 mb-1">{{ $t('guest.loyalty_tier') }}</label>
                    <select
                        id="loyalty_tier"
                        v-model="form.loyalty_tier"
                        class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        :class="{ 'border-red-500': form.errors.loyalty_tier }"
                    >
                        <option value="bronze">{{ $t('tier.bronze') }}</option>
                        <option value="silver">{{ $t('tier.silver') }}</option>
                        <option value="gold">{{ $t('tier.gold') }}</option>
                        <option value="platinum">{{ $t('tier.platinum') }}</option>
                    </select>
                    <p v-if="form.errors.loyalty_tier" class="mt-1 text-sm text-red-600">
                        {{ form.errors.loyalty_tier }}
                    </p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('guest.preferences') }}</label>
                    <div class="flex flex-wrap gap-2 mb-2" v-if="form.preferences.length > 0">
                        <span
                            v-for="(pref, index) in form.preferences"
                            :key="index"
                            class="inline-flex items-center px-3 py-1 text-sm bg-blue-50 text-blue-700 rounded-full"
                        >
                            {{ pref }}
                            <button
                                type="button"
                                @click="removePreference(index)"
                                class="ml-1.5 text-blue-400 hover:text-blue-600"
                            >
                                &times;
                            </button>
                        </span>
                    </div>
                    <div class="flex gap-2">
                        <input
                            v-model="newPreference.value"
                            type="text"
                            :placeholder="$t('guest.add_preference')"
                            @keydown.enter.prevent="addPreference"
                            class="flex-1 rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        />
                        <button
                            type="button"
                            @click="addPreference"
                            class="px-3 py-2 text-sm border border-gray-300 rounded-md hover:bg-gray-50"
                        >
                            {{ $t('special_request.add') }}
                        </button>
                    </div>
                    <p v-if="form.errors.preferences" class="mt-1 text-sm text-red-600">
                        {{ form.errors.preferences }}
                    </p>
                </div>

                <div class="flex items-center gap-3 pt-2">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="bg-blue-600 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                    >
                        <span v-if="form.processing">{{ $t('guest.saving') }}</span>
                        <span v-else>{{ $t('guest.save') }}</span>
                    </button>
                    <a :href="`/guests/${guest.id}`" class="text-sm text-gray-500 hover:text-gray-700">{{ $t('common.cancel') }}</a>
                </div>
            </form>
        </div>
    </div>
</template>
