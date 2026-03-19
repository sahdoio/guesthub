<script setup>
import { useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

defineOptions({ layout: AuthenticatedLayout });

const { t } = useI18n();

const props = defineProps({
    guests: Array,
});

const form = useForm({
    guest_profile_id: '',
    check_in: '',
    check_out: '',
    room_type: '',
});

const submit = () => {
    form.post('/reservations');
};
</script>

<template>
    <div>
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-800">{{ $t('reservation.new') }}</h1>
        </div>

        <div class="bg-white rounded-lg shadow max-w-2xl">
            <form @submit.prevent="submit" class="p-6 space-y-5">
                <div>
                    <label for="guest" class="block text-sm font-medium text-gray-700 mb-1">{{ $t('reservation.guest') }}</label>
                    <select
                        id="guest"
                        v-model="form.guest_profile_id"
                        required
                        class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
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

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="check_in" class="block text-sm font-medium text-gray-700 mb-1">{{ $t('reservation.check_in') }}</label>
                        <input
                            id="check_in"
                            v-model="form.check_in"
                            type="date"
                            required
                            class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            :class="{ 'border-red-500': form.errors.check_in }"
                        />
                        <p v-if="form.errors.check_in" class="mt-1 text-sm text-red-600">
                            {{ form.errors.check_in }}
                        </p>
                    </div>
                    <div>
                        <label for="check_out" class="block text-sm font-medium text-gray-700 mb-1">{{ $t('reservation.check_out') }}</label>
                        <input
                            id="check_out"
                            v-model="form.check_out"
                            type="date"
                            required
                            class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            :class="{ 'border-red-500': form.errors.check_out }"
                        />
                        <p v-if="form.errors.check_out" class="mt-1 text-sm text-red-600">
                            {{ form.errors.check_out }}
                        </p>
                    </div>
                </div>

                <div>
                    <label for="room_type" class="block text-sm font-medium text-gray-700 mb-1">{{ $t('reservation.room_type') }}</label>
                    <select
                        id="room_type"
                        v-model="form.room_type"
                        required
                        class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        :class="{ 'border-red-500': form.errors.room_type }"
                    >
                        <option value="" disabled>{{ $t('reservation.select_room_type') }}</option>
                        <option value="SINGLE">{{ $t('room_type.single') }}</option>
                        <option value="DOUBLE">{{ $t('room_type.double') }}</option>
                        <option value="SUITE">{{ $t('room_type.suite') }}</option>
                    </select>
                    <p v-if="form.errors.room_type" class="mt-1 text-sm text-red-600">
                        {{ form.errors.room_type }}
                    </p>
                </div>

                <div class="flex items-center gap-3 pt-2">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="bg-blue-600 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
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
