<script setup>
import { ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { DatePicker } from 'v-calendar';
import GuestPortalLayout from '@/Layouts/GuestPortalLayout.vue';

defineOptions({ layout: GuestPortalLayout });

const { t } = useI18n();

const props = defineProps({
    stay: Object,
});

const selectedImage = ref(null);

const dateRange = ref({ start: null, end: null });

const form = useForm({
    check_in: '',
    check_out: '',
    adults: 1,
    children: 0,
    babies: 0,
    pets: 0,
    stay_uuid: props.stay.slug,
});

const formatDate = (date) => {
    if (!date) return '';
    const d = new Date(date);
    const year = d.getFullYear();
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
};

watch(dateRange, (val) => {
    form.check_in = formatDate(val.start);
    form.check_out = formatDate(val.end);
}, { deep: true });

const totalGuests = () => form.adults + form.children;

const submit = () => form.post('/portal/reservations');

const typeColors = {
    room: 'bg-blue-100 text-blue-800',
    entire_space: 'bg-purple-100 text-purple-800',
};

const categoryColors = {
    hotel_room: 'bg-indigo-100 text-indigo-800',
    house: 'bg-emerald-100 text-emerald-800',
    apartment: 'bg-amber-100 text-amber-800',
};
</script>

<template>
    <div>
        <!-- Back + Title -->
        <div class="mb-6 flex items-center gap-4">
            <a href="/portal" class="text-gray-500 hover:text-gray-700 text-sm">&larr; {{ $t('common.back') }}</a>
            <h1 class="text-2xl font-bold text-gray-800">{{ stay.name }}</h1>
        </div>

        <!-- Image Gallery -->
        <div v-if="stay.cover_image_url || stay.images?.length" class="mb-8">
            <div class="rounded-xl overflow-hidden bg-gray-100 relative" style="height: 350px;">
                <img
                    :src="selectedImage || stay.cover_image_url"
                    :alt="stay.name"
                    class="w-full h-full object-cover"
                />
            </div>
            <div v-if="stay.images?.length || stay.cover_image_url" class="flex gap-2 mt-2 overflow-x-auto pb-2">
                <button
                    v-if="stay.cover_image_url"
                    @click="selectedImage = null"
                    class="shrink-0 w-20 h-16 rounded-lg overflow-hidden border-2 transition-colors"
                    :class="selectedImage === null ? 'border-indigo-500' : 'border-transparent hover:border-gray-300'"
                >
                    <img :src="stay.cover_image_url" :alt="stay.name" class="w-full h-full object-cover" />
                </button>
                <button
                    v-for="img in stay.images"
                    :key="img.id"
                    @click="selectedImage = img.url"
                    class="shrink-0 w-20 h-16 rounded-lg overflow-hidden border-2 transition-colors"
                    :class="selectedImage === img.url ? 'border-indigo-500' : 'border-transparent hover:border-gray-300'"
                >
                    <img :src="img.url" alt="" class="w-full h-full object-cover" />
                </button>
            </div>
        </div>

        <!-- Stay Details -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-8">
            <h2 class="text-lg font-semibold text-gray-700 mb-4">{{ $t('stay.details') }}</h2>

            <!-- Type & Category badges -->
            <div class="flex flex-wrap gap-2 mb-4">
                <span
                    class="inline-flex px-2.5 py-1 text-xs font-semibold rounded-full"
                    :class="typeColors[stay.type] || 'bg-gray-100 text-gray-600'"
                >
                    {{ $t('stay.type_' + stay.type) }}
                </span>
                <span
                    class="inline-flex px-2.5 py-1 text-xs font-semibold rounded-full"
                    :class="categoryColors[stay.category] || 'bg-gray-100 text-gray-600'"
                >
                    {{ $t('stay.category_' + stay.category) }}
                </span>
            </div>

            <p v-if="stay.description" class="text-gray-600 leading-relaxed mb-6">{{ stay.description }}</p>

            <!-- Price, Capacity -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
                <div class="bg-indigo-50 rounded-lg p-4 text-center">
                    <p class="text-xs text-indigo-500 font-medium uppercase tracking-wider">{{ $t('stay.price_per_night') }}</p>
                    <p class="text-2xl font-bold text-indigo-700 mt-1">${{ Number(stay.price_per_night).toFixed(2) }}</p>
                </div>
                <div class="bg-gray-50 rounded-lg p-4 text-center">
                    <p class="text-xs text-gray-500 font-medium uppercase tracking-wider">{{ $t('stay.capacity') }}</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1">{{ stay.capacity }}</p>
                    <p class="text-xs text-gray-400">{{ $t('stay.guests') }}</p>
                </div>
                <div class="bg-gray-50 rounded-lg p-4 text-center">
                    <p class="text-xs text-gray-500 font-medium uppercase tracking-wider">{{ $t('stay.type') }}</p>
                    <p class="text-lg font-bold text-gray-900 mt-1">{{ $t('stay.type_' + stay.type) }}</p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <!-- Address -->
                <div v-if="stay.address" class="flex items-start gap-3">
                    <svg class="w-5 h-5 text-gray-400 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <p class="text-sm text-gray-700">{{ stay.address }}</p>
                </div>

                <!-- Contact Info -->
                <div v-if="stay.contact_email || stay.contact_phone">
                    <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wide mb-2">{{ $t('stay.contact_info') }}</h3>
                    <div class="space-y-1 text-sm text-gray-700">
                        <div v-if="stay.contact_email" class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                            <a :href="`mailto:${stay.contact_email}`" class="hover:text-blue-600 transition-colors">{{ stay.contact_email }}</a>
                        </div>
                        <div v-if="stay.contact_phone" class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                            </svg>
                            <a :href="`tel:${stay.contact_phone}`" class="hover:text-blue-600 transition-colors">{{ stay.contact_phone }}</a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Amenities -->
            <div v-if="stay.amenities && stay.amenities.length > 0" class="mt-6">
                <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wide mb-3">{{ $t('stay.amenities') }}</h3>
                <div class="flex flex-wrap gap-2">
                    <span
                        v-for="amenity in stay.amenities"
                        :key="amenity"
                        class="inline-flex px-3 py-1.5 text-xs font-medium bg-gray-100 text-gray-700 rounded-lg"
                    >
                        {{ $t('stay.amenity_' + amenity) }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Booking Form -->
        <div id="booking-form" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h2 class="text-lg font-semibold text-gray-700 mb-6">{{ $t('reservation.booking') }}</h2>

            <form @submit.prevent="submit" class="space-y-5 max-w-2xl">
                <!-- Date Range Picker -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        {{ $t('reservation.check_in') }} &mdash; {{ $t('reservation.check_out') }}
                    </label>
                    <DatePicker
                        v-model.range="dateRange"
                        :min-date="new Date()"
                        :columns="2"
                        :rows="1"
                        color="indigo"
                        is-required
                    >
                        <template #default="{ inputValue, inputEvents }">
                            <div class="flex items-center gap-2">
                                <div class="relative flex-1">
                                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    <input
                                        :value="inputValue.start"
                                        v-on="inputEvents.start"
                                        :placeholder="$t('reservation.check_in')"
                                        readonly
                                        class="w-full pl-9 pr-3 py-2.5 rounded-lg border border-gray-200 bg-white text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent cursor-pointer"
                                        :class="{ 'border-red-500': form.errors.check_in }"
                                    />
                                </div>
                                <svg class="w-5 h-5 text-gray-300 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                </svg>
                                <div class="relative flex-1">
                                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    <input
                                        :value="inputValue.end"
                                        v-on="inputEvents.end"
                                        :placeholder="$t('reservation.check_out')"
                                        readonly
                                        class="w-full pl-9 pr-3 py-2.5 rounded-lg border border-gray-200 bg-white text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent cursor-pointer"
                                        :class="{ 'border-red-500': form.errors.check_out }"
                                    />
                                </div>
                            </div>
                        </template>
                    </DatePicker>
                    <p v-if="form.errors.check_in" class="mt-1 text-sm text-red-600">{{ form.errors.check_in }}</p>
                    <p v-if="form.errors.check_out" class="mt-1 text-sm text-red-600">{{ form.errors.check_out }}</p>
                </div>

                <!-- Guest Counts -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-3">{{ $t('reservation.guest_details') }}</label>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                        <!-- Adults -->
                        <div class="border border-gray-200 rounded-lg p-3">
                            <div class="text-xs text-gray-500 font-medium mb-1">{{ $t('reservation.adults') }}</div>
                            <div class="text-[10px] text-gray-400 mb-2">{{ $t('reservation.adults_age') }}</div>
                            <div class="flex items-center justify-between">
                                <button
                                    type="button"
                                    @click="form.adults = Math.max(1, form.adults - 1)"
                                    class="w-7 h-7 rounded-full border border-gray-300 flex items-center justify-center text-gray-500 hover:border-gray-400 transition-colors disabled:opacity-30"
                                    :disabled="form.adults <= 1"
                                >
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="M5 12h14"/></svg>
                                </button>
                                <span class="text-sm font-semibold text-gray-900 w-6 text-center">{{ form.adults }}</span>
                                <button
                                    type="button"
                                    @click="form.adults++"
                                    class="w-7 h-7 rounded-full border border-gray-300 flex items-center justify-center text-gray-500 hover:border-gray-400 transition-colors"
                                    :disabled="totalGuests() >= (stay.capacity || 20)"
                                >
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="M12 5v14m-7-7h14"/></svg>
                                </button>
                            </div>
                        </div>

                        <!-- Children -->
                        <div class="border border-gray-200 rounded-lg p-3">
                            <div class="text-xs text-gray-500 font-medium mb-1">{{ $t('reservation.children') }}</div>
                            <div class="text-[10px] text-gray-400 mb-2">{{ $t('reservation.children_age') }}</div>
                            <div class="flex items-center justify-between">
                                <button
                                    type="button"
                                    @click="form.children = Math.max(0, form.children - 1)"
                                    class="w-7 h-7 rounded-full border border-gray-300 flex items-center justify-center text-gray-500 hover:border-gray-400 transition-colors disabled:opacity-30"
                                    :disabled="form.children <= 0"
                                >
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="M5 12h14"/></svg>
                                </button>
                                <span class="text-sm font-semibold text-gray-900 w-6 text-center">{{ form.children }}</span>
                                <button
                                    type="button"
                                    @click="form.children++"
                                    class="w-7 h-7 rounded-full border border-gray-300 flex items-center justify-center text-gray-500 hover:border-gray-400 transition-colors"
                                    :disabled="totalGuests() >= (stay.capacity || 20)"
                                >
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="M12 5v14m-7-7h14"/></svg>
                                </button>
                            </div>
                        </div>

                        <!-- Babies -->
                        <div class="border border-gray-200 rounded-lg p-3">
                            <div class="text-xs text-gray-500 font-medium mb-1">{{ $t('reservation.babies') }}</div>
                            <div class="text-[10px] text-gray-400 mb-2">{{ $t('reservation.babies_age') }}</div>
                            <div class="flex items-center justify-between">
                                <button
                                    type="button"
                                    @click="form.babies = Math.max(0, form.babies - 1)"
                                    class="w-7 h-7 rounded-full border border-gray-300 flex items-center justify-center text-gray-500 hover:border-gray-400 transition-colors disabled:opacity-30"
                                    :disabled="form.babies <= 0"
                                >
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="M5 12h14"/></svg>
                                </button>
                                <span class="text-sm font-semibold text-gray-900 w-6 text-center">{{ form.babies }}</span>
                                <button
                                    type="button"
                                    @click="form.babies++"
                                    class="w-7 h-7 rounded-full border border-gray-300 flex items-center justify-center text-gray-500 hover:border-gray-400 transition-colors"
                                    :disabled="form.babies >= 5"
                                >
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="M12 5v14m-7-7h14"/></svg>
                                </button>
                            </div>
                        </div>

                        <!-- Pets -->
                        <div class="border border-gray-200 rounded-lg p-3">
                            <div class="text-xs text-gray-500 font-medium mb-1">{{ $t('reservation.pets') }}</div>
                            <div class="text-[10px] text-gray-400 mb-2">&nbsp;</div>
                            <div class="flex items-center justify-between">
                                <button
                                    type="button"
                                    @click="form.pets = Math.max(0, form.pets - 1)"
                                    class="w-7 h-7 rounded-full border border-gray-300 flex items-center justify-center text-gray-500 hover:border-gray-400 transition-colors disabled:opacity-30"
                                    :disabled="form.pets <= 0"
                                >
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="M5 12h14"/></svg>
                                </button>
                                <span class="text-sm font-semibold text-gray-900 w-6 text-center">{{ form.pets }}</span>
                                <button
                                    type="button"
                                    @click="form.pets++"
                                    class="w-7 h-7 rounded-full border border-gray-300 flex items-center justify-center text-gray-500 hover:border-gray-400 transition-colors"
                                    :disabled="form.pets >= 5"
                                >
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="M12 5v14m-7-7h14"/></svg>
                                </button>
                            </div>
                        </div>
                    </div>
                    <p v-if="stay.capacity && totalGuests() >= stay.capacity" class="mt-2 text-xs text-amber-600">
                        {{ $t('reservation.capacity_warning', { capacity: stay.capacity }) }}
                    </p>
                </div>

                <!-- Hidden stay identifier -->
                <input type="hidden" v-model="form.stay_uuid" />

                <!-- Submit -->
                <div class="flex items-center gap-3 pt-2">
                    <button
                        type="submit"
                        :disabled="form.processing || !form.check_in || !form.check_out"
                        class="bg-indigo-600 text-white px-6 py-2 rounded-lg text-sm font-medium hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                    >
                        <span v-if="form.processing">{{ $t('reservation.booking') }}</span>
                        <span v-else>{{ $t('reservation.book') }}</span>
                    </button>
                    <a href="/portal" class="text-sm text-gray-500 hover:text-gray-700">{{ $t('common.back') }}</a>
                </div>

                <!-- Cancellation Policy Notice -->
                <div class="flex items-start gap-2 mt-4 p-3 bg-green-50 rounded-lg border border-green-100">
                    <svg class="w-4 h-4 text-green-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <p class="text-xs text-green-700">{{ $t('billing.free_cancellation_notice') }}</p>
                </div>
            </form>
        </div>
    </div>
</template>
