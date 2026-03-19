<script setup>
import { useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import GuestLayout from '../../Layouts/GuestLayout.vue';
import Logo from '../../Components/Logo.vue';
import PhoneInput from '../../Components/PhoneInput.vue';

defineOptions({ layout: GuestLayout });

const { t } = useI18n();

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    phone: '',
    document: '',
});

const submit = () => {
    form.post('/register', {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <!-- Left Panel - Decorative -->
    <div class="hidden lg:flex lg:w-1/2 bg-gradient-to-br from-indigo-600 via-violet-600 to-purple-700 relative overflow-hidden">
        <div class="absolute inset-0 opacity-10">
            <svg class="w-full h-full" viewBox="0 0 800 800">
                <defs>
                    <pattern id="grid" width="60" height="60" patternUnits="userSpaceOnUse">
                        <path d="M 60 0 L 0 0 0 60" fill="none" stroke="white" stroke-width="0.5" />
                    </pattern>
                </defs>
                <rect width="100%" height="100%" fill="url(#grid)" />
            </svg>
        </div>
        <div class="absolute top-20 -left-20 w-72 h-72 bg-white/10 rounded-full blur-3xl"></div>
        <div class="absolute bottom-20 right-10 w-96 h-96 bg-violet-400/20 rounded-full blur-3xl"></div>

        <div class="relative z-10 flex flex-col justify-center px-16 text-white">
            <div class="mb-8">
                <div class="w-14 h-14 rounded-xl bg-white/20 backdrop-blur-sm flex items-center justify-center mb-6 shadow-lg">
                    <svg class="w-8 h-8 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z" />
                        <polyline points="9 22 9 12 15 12 15 22" />
                    </svg>
                </div>
            </div>
            <h1 class="text-4xl font-bold mb-4 leading-tight">{{ $t('auth.join_title') }}<br/>GuestHub</h1>
            <p class="text-lg text-indigo-100 leading-relaxed max-w-md">
                {{ $t('auth.join_subtitle') }}
            </p>
            <div class="mt-12 space-y-3 text-indigo-200 text-sm">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full bg-white/15 flex items-center justify-center">
                        <span class="text-xs font-bold">1</span>
                    </div>
                    {{ $t('auth.step_1') }}
                </div>
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full bg-white/15 flex items-center justify-center">
                        <span class="text-xs font-bold">2</span>
                    </div>
                    {{ $t('auth.step_2') }}
                </div>
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full bg-white/15 flex items-center justify-center">
                        <span class="text-xs font-bold">3</span>
                    </div>
                    {{ $t('auth.step_3') }}
                </div>
            </div>
        </div>
    </div>

    <!-- Right Panel - Form -->
    <div class="flex-1 flex items-center justify-center bg-gradient-to-br from-slate-50 to-white p-8 overflow-y-auto">
        <div class="w-full max-w-md">
            <div class="flex justify-center mb-8 lg:hidden">
                <Logo size="lg" />
            </div>

            <div class="mb-8">
                <h2 class="text-2xl font-bold text-gray-900">{{ $t('auth.create_account_title') }}</h2>
                <p class="mt-2 text-sm text-gray-500">{{ $t('auth.create_account_subtitle') }}</p>
            </div>

            <form @submit.prevent="submit" class="space-y-4">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1.5">{{ $t('auth.full_name') }}</label>
                    <input
                        id="name"
                        v-model="form.name"
                        type="text"
                        required
                        autofocus
                        :placeholder="$t('auth.full_name_placeholder')"
                        class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-shadow"
                        :class="{ 'border-red-400 ring-1 ring-red-400': form.errors.name }"
                    />
                    <p v-if="form.errors.name" class="mt-1.5 text-sm text-red-600">{{ form.errors.name }}</p>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1.5">{{ $t('auth.email') }}</label>
                        <input
                            id="email"
                            v-model="form.email"
                            type="email"
                            required
                            :placeholder="$t('auth.email_placeholder')"
                            class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-shadow"
                            :class="{ 'border-red-400 ring-1 ring-red-400': form.errors.email }"
                        />
                        <p v-if="form.errors.email" class="mt-1.5 text-sm text-red-600">{{ form.errors.email }}</p>
                    </div>
                    <div>
                        <label for="phone" class="block text-sm font-medium text-gray-700 mb-1.5">{{ $t('auth.phone') }}</label>
                        <PhoneInput
                            id="phone"
                            v-model="form.phone"
                            required
                            :placeholder="$t('auth.phone_placeholder')"
                            input-class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-shadow"
                            :has-error="!!form.errors.phone"
                        />
                        <p v-if="form.errors.phone" class="mt-1.5 text-sm text-red-600">{{ form.errors.phone }}</p>
                    </div>
                </div>

                <div>
                    <label for="document" class="block text-sm font-medium text-gray-700 mb-1.5">{{ $t('auth.document') }}</label>
                    <input
                        id="document"
                        v-model="form.document"
                        type="text"
                        required
                        :placeholder="$t('auth.document_placeholder')"
                        class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-shadow"
                        :class="{ 'border-red-400 ring-1 ring-red-400': form.errors.document }"
                    />
                    <p v-if="form.errors.document" class="mt-1.5 text-sm text-red-600">{{ form.errors.document }}</p>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-1.5">{{ $t('auth.password') }}</label>
                        <input
                            id="password"
                            v-model="form.password"
                            type="password"
                            required
                            :placeholder="$t('auth.password_min')"
                            class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-shadow"
                            :class="{ 'border-red-400 ring-1 ring-red-400': form.errors.password }"
                        />
                        <p v-if="form.errors.password" class="mt-1.5 text-sm text-red-600">{{ form.errors.password }}</p>
                    </div>
                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1.5">{{ $t('auth.confirm_password') }}</label>
                        <input
                            id="password_confirmation"
                            v-model="form.password_confirmation"
                            type="password"
                            required
                            :placeholder="$t('auth.repeat_password')"
                            class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-shadow"
                        />
                    </div>
                </div>

                <button
                    type="submit"
                    :disabled="form.processing"
                    class="w-full bg-gradient-to-r from-indigo-600 to-violet-600 text-white py-2.5 px-4 rounded-lg text-sm font-semibold hover:from-indigo-700 hover:to-violet-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed transition-all shadow-sm hover:shadow-md mt-2"
                >
                    <span v-if="form.processing">{{ $t('auth.creating_account') }}</span>
                    <span v-else>{{ $t('auth.create_account') }}</span>
                </button>

                <p class="text-center text-sm text-gray-500 pt-1">
                    {{ $t('auth.has_account') }}
                    <a href="/login" class="text-indigo-600 hover:text-indigo-700 font-semibold">
                        {{ $t('auth.sign_in_link') }}
                    </a>
                </p>
                <p class="text-center text-sm text-gray-500 pt-1">
                    {{ $t('auth.own_hotel_prompt') }}
                    <a href="/register/hotel" class="text-indigo-600 hover:text-indigo-700 font-semibold">
                        {{ $t('auth.register_hotel_link') }}
                    </a>
                </p>
            </form>
        </div>
    </div>
</template>
