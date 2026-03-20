<script setup>
import { useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import GuestLayout from '../../Layouts/GuestLayout.vue';
import Logo from '../../Components/Logo.vue';

defineOptions({ layout: GuestLayout });

const { t } = useI18n();

const form = useForm({
    email: '',
    password: '',
});

const submit = () => {
    form.post('/login', {
        onFinish: () => form.reset('password'),
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
                <img src="/logo.png" alt="GuestHub" class="h-12 brightness-0 invert object-contain mb-6" />
            </div>
            <h1 class="text-4xl font-bold mb-4 leading-tight">{{ $t('auth.welcome_title') }}<br/>GuestHub</h1>
            <p class="text-lg text-indigo-100 leading-relaxed max-w-md">
                {{ $t('auth.welcome_subtitle') }}
            </p>
            <div class="mt-12 flex items-center gap-6 text-indigo-200 text-sm">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    {{ $t('auth.real_time_booking') }}
                </div>
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    {{ $t('auth.guest_management') }}
                </div>
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    {{ $t('auth.multi_tenant') }}
                </div>
            </div>
        </div>
    </div>

    <!-- Right Panel - Form -->
    <div class="flex-1 flex items-center justify-center bg-gradient-to-br from-slate-50 to-white p-8">
        <div class="w-full max-w-md">
            <div class="flex justify-center mb-8 lg:hidden">
                <Logo size="lg" />
            </div>

            <div class="mb-8">
                <h2 class="text-2xl font-bold text-gray-900">{{ $t('auth.sign_in_title') }}</h2>
                <p class="mt-2 text-sm text-gray-500">{{ $t('auth.sign_in_subtitle') }}</p>
            </div>

            <form @submit.prevent="submit" class="space-y-5">
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1.5">
                        {{ $t('auth.email') }}
                    </label>
                    <input
                        id="email"
                        v-model="form.email"
                        type="email"
                        required
                        autofocus
                        :placeholder="$t('auth.email_placeholder')"
                        class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-shadow"
                        :class="{ 'border-red-400 ring-1 ring-red-400': form.errors.email }"
                    />
                    <p v-if="form.errors.email" class="mt-1.5 text-sm text-red-600">
                        {{ form.errors.email }}
                    </p>
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1.5">
                        {{ $t('auth.password') }}
                    </label>
                    <input
                        id="password"
                        v-model="form.password"
                        type="password"
                        required
                        :placeholder="$t('auth.password_placeholder')"
                        class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-shadow"
                        :class="{ 'border-red-400 ring-1 ring-red-400': form.errors.password }"
                    />
                    <p v-if="form.errors.password" class="mt-1.5 text-sm text-red-600">
                        {{ form.errors.password }}
                    </p>
                </div>

                <button
                    type="submit"
                    :disabled="form.processing"
                    class="w-full bg-gradient-to-r from-indigo-600 to-violet-600 text-white py-2.5 px-4 rounded-lg text-sm font-semibold hover:from-indigo-700 hover:to-violet-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed transition-all shadow-sm hover:shadow-md"
                >
                    <span v-if="form.processing">{{ $t('auth.signing_in') }}</span>
                    <span v-else>{{ $t('auth.sign_in') }}</span>
                </button>

                <p class="text-center text-sm text-gray-500 pt-2">
                    {{ $t('auth.no_account') }}
                    <a href="/register" class="text-indigo-600 hover:text-indigo-700 font-semibold">
                        {{ $t('auth.create_one') }}
                    </a>
                </p>
            </form>
        </div>
    </div>
</template>
