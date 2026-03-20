<script setup>
import { useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import GuestPortalLayout from '@/Layouts/GuestPortalLayout.vue';

defineOptions({ layout: GuestPortalLayout });

const { t } = useI18n();

const props = defineProps({
    hotel: Object,
    roomTypes: Array,
});

const form = useForm({
    check_in: '',
    check_out: '',
    room_type: '',
    hotel_slug: props.hotel.slug,
});

const selectRoomType = (type) => {
    form.room_type = type;
    document.getElementById('booking-form')?.scrollIntoView({ behavior: 'smooth' });
};

const submit = () => form.post('/portal/reservations');
</script>

<template>
    <div>
        <!-- Back + Title -->
        <div class="mb-6 flex items-center gap-4">
            <a href="/portal" class="text-gray-500 hover:text-gray-700 text-sm">&larr; {{ $t('common.back') }}</a>
            <h1 class="text-2xl font-bold text-gray-800">{{ hotel.name }}</h1>
        </div>

        <!-- Hotel Details -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-8">
            <h2 class="text-lg font-semibold text-gray-700 mb-4">{{ $t('hotel.details') }}</h2>

            <p class="text-gray-600 leading-relaxed mb-6">{{ hotel.description }}</p>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <!-- Address -->
                <div class="flex items-start gap-3">
                    <svg class="w-5 h-5 text-gray-400 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <p class="text-sm text-gray-700">{{ hotel.address }}</p>
                </div>

                <!-- Contact Info -->
                <div>
                    <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wide mb-2">{{ $t('hotel.contact_info') }}</h3>
                    <div class="space-y-1 text-sm text-gray-700">
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                            <a :href="`mailto:${hotel.contact_email}`" class="hover:text-blue-600 transition-colors">{{ hotel.contact_email }}</a>
                        </div>
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                            </svg>
                            <a :href="`tel:${hotel.contact_phone}`" class="hover:text-blue-600 transition-colors">{{ hotel.contact_phone }}</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Available Room Types -->
        <div class="mb-8">
            <h2 class="text-lg font-semibold text-gray-700 mb-4">{{ $t('hotel.available_rooms') }}</h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <div
                    v-for="roomType in roomTypes"
                    :key="roomType.type"
                    class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 flex flex-col justify-between hover:shadow-md transition-shadow"
                >
                    <div>
                        <h3 class="text-base font-semibold text-gray-800 mb-1">
                            {{ $t('room_type.' + roomType.type.toLowerCase()) }}
                        </h3>
                        <p class="text-sm text-gray-500 mb-3">
                            {{ roomType.available }} {{ roomType.available === 1 ? $t('hotel.available_rooms') : $t('hotel.available_rooms') }}
                        </p>
                        <div class="flex items-baseline gap-1 mb-4">
                            <span class="text-xs text-gray-500">{{ $t('hotel.starting_from') }}</span>
                            <span class="text-xl font-bold text-indigo-700">${{ roomType.min_price.toFixed(2) }}</span>
                            <span class="text-xs text-gray-500">/ {{ $t('hotel.per_night') }}</span>
                        </div>
                    </div>

                    <button
                        type="button"
                        @click="selectRoomType(roomType.type)"
                        class="w-full bg-indigo-600 text-white py-2.5 px-4 rounded-lg text-sm font-medium hover:bg-indigo-700 active:bg-indigo-800 transition-colors shadow-sm"
                    >
                        {{ $t('hotel.book_now') }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Booking Form -->
        <div id="booking-form" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h2 class="text-lg font-semibold text-gray-700 mb-6">{{ $t('reservation.booking') }}</h2>

            <form @submit.prevent="submit" class="space-y-5 max-w-2xl">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- Check-in -->
                    <div>
                        <label for="check_in" class="block text-sm font-medium text-gray-700 mb-1">
                            {{ $t('reservation.check_in') }}
                        </label>
                        <input
                            id="check_in"
                            v-model="form.check_in"
                            type="date"
                            required
                            class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                            :class="{ 'border-red-500': form.errors.check_in }"
                        />
                        <p v-if="form.errors.check_in" class="mt-1 text-sm text-red-600">{{ form.errors.check_in }}</p>
                    </div>

                    <!-- Check-out -->
                    <div>
                        <label for="check_out" class="block text-sm font-medium text-gray-700 mb-1">
                            {{ $t('reservation.check_out') }}
                        </label>
                        <input
                            id="check_out"
                            v-model="form.check_out"
                            type="date"
                            required
                            class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                            :class="{ 'border-red-500': form.errors.check_out }"
                        />
                        <p v-if="form.errors.check_out" class="mt-1 text-sm text-red-600">{{ form.errors.check_out }}</p>
                    </div>
                </div>

                <!-- Room Type -->
                <div>
                    <label for="room_type" class="block text-sm font-medium text-gray-700 mb-1">
                        {{ $t('reservation.room_type') }}
                    </label>
                    <select
                        id="room_type"
                        v-model="form.room_type"
                        required
                        class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                        :class="{ 'border-red-500': form.errors.room_type }"
                    >
                        <option value="" disabled>{{ $t('reservation.room_type') }}</option>
                        <option v-for="roomType in roomTypes" :key="roomType.type" :value="roomType.type">
                            {{ $t('room_type.' + roomType.type.toLowerCase()) }}
                        </option>
                    </select>
                    <p v-if="form.errors.room_type" class="mt-1 text-sm text-red-600">{{ form.errors.room_type }}</p>
                </div>

                <!-- Hidden hotel_slug -->
                <input type="hidden" v-model="form.hotel_slug" />

                <!-- Submit -->
                <div class="flex items-center gap-3 pt-2">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="bg-indigo-600 text-white px-6 py-2 rounded-lg text-sm font-medium hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                    >
                        <span v-if="form.processing">{{ $t('reservation.booking') }}</span>
                        <span v-else>{{ $t('reservation.book') }}</span>
                    </button>
                    <a href="/portal" class="text-sm text-gray-500 hover:text-gray-700">{{ $t('common.back') }}</a>
                </div>
            </form>
        </div>
    </div>
</template>
