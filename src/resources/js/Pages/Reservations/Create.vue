<script setup>
import { ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { DatePicker } from 'v-calendar';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

defineOptions({ layout: AuthenticatedLayout });

const { t } = useI18n();

const props = defineProps({
    guests: Array,
    stays: { type: Array, default: () => [] },
});

const dateRange = ref({ start: null, end: null });

const form = useForm({
    guest_profile_id: '',
    check_in: '',
    check_out: '',
    stay_id: '',
    adults: 1,
    children: 0,
    babies: 0,
    pets: 0,
});

const formatDate = (date) => {
    if (!date) return '';
    const d = new Date(date);
    return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
};

watch(dateRange, (val) => {
    form.check_in = formatDate(val.start);
    form.check_out = formatDate(val.end);
}, { deep: true });

const submit = () => {
    form.post('/reservations');
};
</script>

<template>
    <div>
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-800">{{ $t('reservation.new') }}</h1>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 max-w-2xl">
            <form @submit.prevent="submit" class="p-6 space-y-5">
                <div>
                    <label for="guest" class="block text-sm font-medium text-gray-700 mb-1">{{ $t('reservation.guest') }}</label>
                    <select
                        id="guest"
                        v-model="form.guest_profile_id"
                        required
                        class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                        :class="{ 'border-red-500': form.errors.guest_profile_id }"
                    >
                        <option value="" disabled>{{ $t('reservation.select_guest') }}</option>
                        <option v-for="guest in guests" :key="guest.id" :value="guest.id">
                            {{ guest.full_name }} ({{ guest.email }})
                        </option>
                    </select>
                    <p v-if="form.errors.guest_profile_id" class="mt-1 text-sm text-red-600">
                        {{ form.errors.guest_profile_id }}
                    </p>
                </div>

                <!-- Date Range -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        {{ $t('reservation.check_in') }} &mdash; {{ $t('reservation.check_out') }}
                    </label>
                    <DatePicker
                        v-model.range="dateRange"
                        :min-date="new Date()"
                        :columns="2"
                        color="indigo"
                        is-required
                    >
                        <template #default="{ inputValue, inputEvents }">
                            <div class="flex items-center gap-2">
                                <input
                                    :value="inputValue.start"
                                    v-on="inputEvents.start"
                                    :placeholder="$t('reservation.check_in')"
                                    readonly
                                    class="flex-1 rounded-lg border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent cursor-pointer"
                                    :class="{ 'border-red-500': form.errors.check_in }"
                                />
                                <svg class="w-4 h-4 text-gray-300 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                </svg>
                                <input
                                    :value="inputValue.end"
                                    v-on="inputEvents.end"
                                    :placeholder="$t('reservation.check_out')"
                                    readonly
                                    class="flex-1 rounded-lg border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent cursor-pointer"
                                    :class="{ 'border-red-500': form.errors.check_out }"
                                />
                            </div>
                        </template>
                    </DatePicker>
                    <p v-if="form.errors.check_in" class="mt-1 text-sm text-red-600">{{ form.errors.check_in }}</p>
                    <p v-if="form.errors.check_out" class="mt-1 text-sm text-red-600">{{ form.errors.check_out }}</p>
                </div>

                <div>
                    <label for="stay_id" class="block text-sm font-medium text-gray-700 mb-1">{{ $t('reservation.stay') }}</label>
                    <select
                        id="stay_id"
                        v-model="form.stay_id"
                        required
                        class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                        :class="{ 'border-red-500': form.errors.stay_id }"
                    >
                        <option value="" disabled>{{ $t('reservation.select_stay') }}</option>
                        <option v-for="stay in stays" :key="stay.id" :value="stay.id">
                            {{ stay.name }} - {{ $t('stay.type_' + stay.type) }} ({{ $t('stay.category_' + stay.category) }})
                        </option>
                    </select>
                    <p v-if="form.errors.stay_id" class="mt-1 text-sm text-red-600">
                        {{ form.errors.stay_id }}
                    </p>
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
                                <button type="button" @click="form.adults = Math.max(1, form.adults - 1)" class="w-7 h-7 rounded-full border border-gray-300 flex items-center justify-center text-gray-500 hover:border-gray-400 disabled:opacity-30" :disabled="form.adults <= 1">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="M5 12h14"/></svg>
                                </button>
                                <span class="text-sm font-semibold w-6 text-center">{{ form.adults }}</span>
                                <button type="button" @click="form.adults++" class="w-7 h-7 rounded-full border border-gray-300 flex items-center justify-center text-gray-500 hover:border-gray-400">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="M12 5v14m-7-7h14"/></svg>
                                </button>
                            </div>
                        </div>
                        <!-- Children -->
                        <div class="border border-gray-200 rounded-lg p-3">
                            <div class="text-xs text-gray-500 font-medium mb-1">{{ $t('reservation.children') }}</div>
                            <div class="text-[10px] text-gray-400 mb-2">{{ $t('reservation.children_age') }}</div>
                            <div class="flex items-center justify-between">
                                <button type="button" @click="form.children = Math.max(0, form.children - 1)" class="w-7 h-7 rounded-full border border-gray-300 flex items-center justify-center text-gray-500 hover:border-gray-400 disabled:opacity-30" :disabled="form.children <= 0">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="M5 12h14"/></svg>
                                </button>
                                <span class="text-sm font-semibold w-6 text-center">{{ form.children }}</span>
                                <button type="button" @click="form.children++" class="w-7 h-7 rounded-full border border-gray-300 flex items-center justify-center text-gray-500 hover:border-gray-400">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="M12 5v14m-7-7h14"/></svg>
                                </button>
                            </div>
                        </div>
                        <!-- Babies -->
                        <div class="border border-gray-200 rounded-lg p-3">
                            <div class="text-xs text-gray-500 font-medium mb-1">{{ $t('reservation.babies') }}</div>
                            <div class="text-[10px] text-gray-400 mb-2">{{ $t('reservation.babies_age') }}</div>
                            <div class="flex items-center justify-between">
                                <button type="button" @click="form.babies = Math.max(0, form.babies - 1)" class="w-7 h-7 rounded-full border border-gray-300 flex items-center justify-center text-gray-500 hover:border-gray-400 disabled:opacity-30" :disabled="form.babies <= 0">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="M5 12h14"/></svg>
                                </button>
                                <span class="text-sm font-semibold w-6 text-center">{{ form.babies }}</span>
                                <button type="button" @click="form.babies++" class="w-7 h-7 rounded-full border border-gray-300 flex items-center justify-center text-gray-500 hover:border-gray-400" :disabled="form.babies >= 5">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="M12 5v14m-7-7h14"/></svg>
                                </button>
                            </div>
                        </div>
                        <!-- Pets -->
                        <div class="border border-gray-200 rounded-lg p-3">
                            <div class="text-xs text-gray-500 font-medium mb-1">{{ $t('reservation.pets') }}</div>
                            <div class="text-[10px] text-gray-400 mb-2">&nbsp;</div>
                            <div class="flex items-center justify-between">
                                <button type="button" @click="form.pets = Math.max(0, form.pets - 1)" class="w-7 h-7 rounded-full border border-gray-300 flex items-center justify-center text-gray-500 hover:border-gray-400 disabled:opacity-30" :disabled="form.pets <= 0">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="M5 12h14"/></svg>
                                </button>
                                <span class="text-sm font-semibold w-6 text-center">{{ form.pets }}</span>
                                <button type="button" @click="form.pets++" class="w-7 h-7 rounded-full border border-gray-300 flex items-center justify-center text-gray-500 hover:border-gray-400" :disabled="form.pets >= 5">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="M12 5v14m-7-7h14"/></svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-3 pt-2">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="bg-indigo-600 text-white px-4 py-2 rounded-lg shadow-sm text-sm font-medium hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                    >
                        <span v-if="form.processing">{{ $t('reservation.creating') }}</span>
                        <span v-else>{{ $t('reservation.create') }}</span>
                    </button>
                    <a href="/reservations" class="text-sm text-gray-500 hover:text-gray-700">{{ $t('common.cancel') }}</a>
                </div>
            </form>
        </div>
    </div>
</template>
