<script setup>
import { useI18n } from 'vue-i18n';
import LanguageSwitcher from '@/Components/LanguageSwitcher.vue';
import Logo from '@/Components/Logo.vue';

const { t } = useI18n();

const features = [
    { key: 'reservations', icon: 'calendar', color: 'from-indigo-500 to-violet-500' },
    { key: 'guests', icon: 'users', color: 'from-emerald-500 to-teal-500' },
    { key: 'rooms', icon: 'building', color: 'from-amber-500 to-orange-500' },
    { key: 'multi', icon: 'globe', color: 'from-sky-500 to-blue-500' },
    { key: 'analytics', icon: 'chart', color: 'from-rose-500 to-pink-500' },
    { key: 'portal', icon: 'device', color: 'from-purple-500 to-fuchsia-500' },
];

const stats = [
    { key: 'hotels', value: '500+' },
    { key: 'reservations', value: '1M+' },
    { key: 'guests', value: '250K+' },
    { key: 'uptime', value: '99.9%' },
];
</script>

<template>
    <div class="min-h-screen bg-white">
        <!-- Navigation -->
        <nav class="fixed top-0 inset-x-0 z-50 bg-white/80 backdrop-blur-lg border-b border-gray-100">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-16">
                    <Logo size="md" />
                    <div class="flex items-center gap-3">
                        <LanguageSwitcher />
                        <a
                            href="/login"
                            class="text-sm font-medium text-gray-600 hover:text-gray-900 transition-colors px-4 py-2"
                        >
                            {{ $t('landing.sign_in') }}
                        </a>
                        <a
                            href="/register"
                            class="text-sm font-medium text-white bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-700 hover:to-violet-700 px-5 py-2.5 rounded-lg shadow-sm hover:shadow-md transition-all"
                        >
                            {{ $t('landing.get_started') }}
                        </a>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Hero Section -->
        <section class="relative pt-32 pb-20 sm:pt-40 sm:pb-28 overflow-hidden">
            <!-- Background decorations -->
            <div class="absolute inset-0 -z-10">
                <div class="absolute top-0 left-1/2 -translate-x-1/2 w-[800px] h-[600px] bg-gradient-to-br from-indigo-50 via-violet-50 to-purple-50 rounded-full blur-3xl opacity-60"></div>
                <div class="absolute top-40 -left-20 w-72 h-72 bg-indigo-100/40 rounded-full blur-3xl"></div>
                <div class="absolute top-20 -right-20 w-96 h-96 bg-violet-100/30 rounded-full blur-3xl"></div>
            </div>
            <!-- Grid pattern -->
            <div class="absolute inset-0 -z-10 opacity-[0.03]">
                <svg class="w-full h-full" xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <pattern id="hero-grid" width="40" height="40" patternUnits="userSpaceOnUse">
                            <path d="M 40 0 L 0 0 0 40" fill="none" stroke="currentColor" stroke-width="1" />
                        </pattern>
                    </defs>
                    <rect width="100%" height="100%" fill="url(#hero-grid)" />
                </svg>
            </div>

            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <!-- Badge -->
                <div class="inline-flex items-center gap-2 bg-indigo-50 text-indigo-700 text-xs font-semibold px-4 py-1.5 rounded-full mb-8 border border-indigo-100">
                    <span class="w-1.5 h-1.5 rounded-full bg-indigo-500 animate-pulse"></span>
                    Hospitality Management Platform
                </div>

                <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold text-gray-900 tracking-tight leading-[1.1] max-w-4xl mx-auto">
                    {{ $t('landing.hero_title') }}
                </h1>

                <p class="mt-6 text-lg sm:text-xl text-gray-500 max-w-2xl mx-auto leading-relaxed">
                    {{ $t('landing.hero_subtitle') }}
                </p>

                <!-- CTA Buttons -->
                <div class="mt-10 flex flex-col sm:flex-row items-center justify-center gap-4">
                    <a
                        href="/register"
                        class="inline-flex items-center gap-2 bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-700 hover:to-violet-700 text-white px-8 py-3.5 rounded-xl text-base font-semibold shadow-lg shadow-indigo-500/25 hover:shadow-xl hover:shadow-indigo-500/30 transition-all"
                    >
                        {{ $t('landing.get_started') }}
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                        </svg>
                    </a>
                    <a
                        href="#features"
                        class="inline-flex items-center gap-2 text-gray-600 hover:text-gray-900 px-6 py-3.5 rounded-xl text-base font-medium transition-colors"
                    >
                        {{ $t('landing.learn_more') }}
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </a>
                </div>

                <!-- Hero Illustration / Dashboard Preview -->
                <div class="mt-16 sm:mt-20 relative max-w-5xl mx-auto">
                    <div class="absolute -inset-4 bg-gradient-to-r from-indigo-500/20 via-violet-500/20 to-purple-500/20 rounded-2xl blur-2xl"></div>
                    <div class="relative bg-white rounded-2xl shadow-2xl border border-gray-200/60 overflow-hidden">
                        <!-- Fake browser chrome -->
                        <div class="flex items-center gap-2 px-4 py-3 bg-gray-50 border-b border-gray-100">
                            <div class="flex gap-1.5">
                                <div class="w-3 h-3 rounded-full bg-red-400"></div>
                                <div class="w-3 h-3 rounded-full bg-yellow-400"></div>
                                <div class="w-3 h-3 rounded-full bg-green-400"></div>
                            </div>
                            <div class="flex-1 mx-4">
                                <div class="bg-white rounded-md border border-gray-200 px-3 py-1 text-xs text-gray-400 max-w-xs mx-auto text-center">
                                    guesthub.app/dashboard
                                </div>
                            </div>
                        </div>
                        <!-- Dashboard mockup -->
                        <div class="p-6 sm:p-8 bg-gradient-to-br from-slate-50 to-white">
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
                                <div class="bg-white rounded-xl border border-gray-100 p-4 shadow-sm">
                                    <div class="text-xs text-gray-400 font-medium">Reservations</div>
                                    <div class="text-2xl font-bold text-gray-900 mt-1">1,284</div>
                                    <div class="text-xs text-emerald-600 font-medium mt-1">+12.5%</div>
                                </div>
                                <div class="bg-white rounded-xl border border-gray-100 p-4 shadow-sm">
                                    <div class="text-xs text-gray-400 font-medium">Guests</div>
                                    <div class="text-2xl font-bold text-gray-900 mt-1">856</div>
                                    <div class="text-xs text-emerald-600 font-medium mt-1">+8.3%</div>
                                </div>
                                <div class="bg-white rounded-xl border border-gray-100 p-4 shadow-sm">
                                    <div class="text-xs text-gray-400 font-medium">Check-ins</div>
                                    <div class="text-2xl font-bold text-gray-900 mt-1">23</div>
                                    <div class="text-xs text-gray-400 font-medium mt-1">Today</div>
                                </div>
                                <div class="bg-white rounded-xl border border-gray-100 p-4 shadow-sm">
                                    <div class="text-xs text-gray-400 font-medium">Occupancy</div>
                                    <div class="text-2xl font-bold text-gray-900 mt-1">87%</div>
                                    <div class="text-xs text-emerald-600 font-medium mt-1">+5.2%</div>
                                </div>
                            </div>
                            <!-- Chart placeholder bars -->
                            <div class="bg-white rounded-xl border border-gray-100 p-5 shadow-sm">
                                <div class="text-sm font-semibold text-gray-700 mb-4">Weekly Overview</div>
                                <div class="flex items-end gap-3 h-32">
                                    <div class="flex-1 bg-gradient-to-t from-indigo-500 to-indigo-400 rounded-t-md" style="height: 60%"></div>
                                    <div class="flex-1 bg-gradient-to-t from-indigo-500 to-indigo-400 rounded-t-md" style="height: 80%"></div>
                                    <div class="flex-1 bg-gradient-to-t from-indigo-500 to-indigo-400 rounded-t-md" style="height: 45%"></div>
                                    <div class="flex-1 bg-gradient-to-t from-indigo-500 to-indigo-400 rounded-t-md" style="height: 90%"></div>
                                    <div class="flex-1 bg-gradient-to-t from-violet-500 to-violet-400 rounded-t-md" style="height: 100%"></div>
                                    <div class="flex-1 bg-gradient-to-t from-indigo-500 to-indigo-400 rounded-t-md" style="height: 70%"></div>
                                    <div class="flex-1 bg-gradient-to-t from-indigo-500 to-indigo-400 rounded-t-md" style="height: 55%"></div>
                                </div>
                                <div class="flex justify-between mt-2 text-[10px] text-gray-400">
                                    <span>Mon</span><span>Tue</span><span>Wed</span><span>Thu</span><span>Fri</span><span>Sat</span><span>Sun</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Stats Section -->
        <section class="py-16 bg-gray-50 border-y border-gray-100">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-8">
                    <div v-for="stat in stats" :key="stat.key" class="text-center">
                        <div class="text-3xl sm:text-4xl font-extrabold text-gray-900">{{ stat.value }}</div>
                        <div class="mt-1 text-sm text-gray-500 font-medium">{{ $t('landing.stats_' + stat.key) }}</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Features Section -->
        <section id="features" class="py-20 sm:py-28">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center max-w-2xl mx-auto mb-16">
                    <h2 class="text-3xl sm:text-4xl font-extrabold text-gray-900 tracking-tight">
                        {{ $t('landing.features_title') }}
                    </h2>
                    <p class="mt-4 text-lg text-gray-500">
                        {{ $t('landing.features_subtitle') }}
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <div
                        v-for="feature in features"
                        :key="feature.key"
                        class="group relative bg-white rounded-2xl border border-gray-100 p-7 hover:shadow-lg hover:border-gray-200 transition-all duration-300"
                    >
                        <!-- Icon -->
                        <div
                            class="w-12 h-12 rounded-xl bg-gradient-to-br flex items-center justify-center mb-5 shadow-sm"
                            :class="feature.color"
                        >
                            <!-- Calendar -->
                            <svg v-if="feature.icon === 'calendar'" class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <!-- Users -->
                            <svg v-if="feature.icon === 'users'" class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <!-- Building -->
                            <svg v-if="feature.icon === 'building'" class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                            <!-- Globe -->
                            <svg v-if="feature.icon === 'globe'" class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                            </svg>
                            <!-- Chart -->
                            <svg v-if="feature.icon === 'chart'" class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                            <!-- Device -->
                            <svg v-if="feature.icon === 'device'" class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                            </svg>
                        </div>

                        <h3 class="text-lg font-bold text-gray-900 mb-2">
                            {{ $t('landing.feature_' + feature.key + '_title') }}
                        </h3>
                        <p class="text-sm text-gray-500 leading-relaxed">
                            {{ $t('landing.feature_' + feature.key + '_desc') }}
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA Section -->
        <section class="py-20 sm:py-28 relative overflow-hidden">
            <div class="absolute inset-0 bg-gradient-to-br from-indigo-600 via-violet-600 to-purple-700"></div>
            <!-- Pattern overlay -->
            <div class="absolute inset-0 opacity-10">
                <svg class="w-full h-full" viewBox="0 0 800 400">
                    <defs>
                        <pattern id="cta-grid" width="60" height="60" patternUnits="userSpaceOnUse">
                            <path d="M 60 0 L 0 0 0 60" fill="none" stroke="white" stroke-width="0.5" />
                        </pattern>
                    </defs>
                    <rect width="100%" height="100%" fill="url(#cta-grid)" />
                </svg>
            </div>
            <div class="absolute top-10 -left-20 w-80 h-80 bg-white/10 rounded-full blur-3xl"></div>
            <div class="absolute bottom-10 right-10 w-96 h-96 bg-violet-400/20 rounded-full blur-3xl"></div>

            <div class="relative max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <h2 class="text-3xl sm:text-4xl font-extrabold text-white tracking-tight">
                    {{ $t('landing.cta_title') }}
                </h2>
                <p class="mt-4 text-lg text-indigo-100 max-w-2xl mx-auto">
                    {{ $t('landing.cta_subtitle') }}
                </p>
                <div class="mt-10 flex flex-col sm:flex-row items-center justify-center gap-4">
                    <a
                        href="/register"
                        class="inline-flex items-center gap-2 bg-white text-indigo-700 px-8 py-3.5 rounded-xl text-base font-semibold shadow-lg hover:shadow-xl hover:bg-gray-50 transition-all"
                    >
                        {{ $t('landing.cta_button') }}
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                        </svg>
                    </a>
                    <a
                        href="/register/hotel"
                        class="inline-flex items-center gap-2 text-white border border-white/30 px-8 py-3.5 rounded-xl text-base font-medium hover:bg-white/10 transition-all"
                    >
                        {{ $t('landing.cta_hotel_owner') }}
                    </a>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="bg-gray-900 text-gray-400 pt-16 pb-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-8 pb-12 border-b border-gray-800">
                    <!-- Brand -->
                    <div class="col-span-2 md:col-span-1">
                        <div class="mb-4">
                            <img src="/logo.png" alt="GuestHub" class="h-8 brightness-0 invert object-contain" />
                        </div>
                        <p class="text-sm leading-relaxed">
                            {{ $t('landing.footer_description') }}
                        </p>
                    </div>

                    <!-- Product -->
                    <div>
                        <h4 class="text-sm font-semibold text-white mb-4">{{ $t('landing.footer_product') }}</h4>
                        <ul class="space-y-2.5 text-sm">
                            <li><a href="#features" class="hover:text-white transition-colors">{{ $t('landing.footer_features') }}</a></li>
                            <li><a href="#" class="hover:text-white transition-colors">{{ $t('landing.footer_pricing') }}</a></li>
                        </ul>
                    </div>

                    <!-- Company -->
                    <div>
                        <h4 class="text-sm font-semibold text-white mb-4">{{ $t('landing.footer_company') }}</h4>
                        <ul class="space-y-2.5 text-sm">
                            <li><a href="#" class="hover:text-white transition-colors">{{ $t('landing.footer_about') }}</a></li>
                            <li><a href="#" class="hover:text-white transition-colors">{{ $t('landing.footer_contact') }}</a></li>
                        </ul>
                    </div>

                    <!-- Legal -->
                    <div>
                        <h4 class="text-sm font-semibold text-white mb-4">{{ $t('landing.footer_legal') }}</h4>
                        <ul class="space-y-2.5 text-sm">
                            <li><a href="#" class="hover:text-white transition-colors">{{ $t('landing.footer_privacy') }}</a></li>
                            <li><a href="#" class="hover:text-white transition-colors">{{ $t('landing.footer_terms') }}</a></li>
                        </ul>
                    </div>
                </div>

                <!-- Bottom bar -->
                <div class="flex flex-col sm:flex-row items-center justify-between pt-8 text-sm">
                    <p>&copy; {{ new Date().getFullYear() }} GuestHub. {{ $t('landing.footer_rights') }}</p>
                    <div class="flex items-center gap-4 mt-4 sm:mt-0">
                        <LanguageSwitcher />
                    </div>
                </div>
            </div>
        </footer>
    </div>
</template>
