<script setup>
import { router, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

defineOptions({ layout: AuthenticatedLayout });

const { t } = useI18n();

const props = defineProps({
    reservation: Object,
    availableRooms: { type: Array, default: () => [] },
});

const r = computed(() => props.reservation);

const statusColors = {
    pending: 'bg-yellow-100 text-yellow-800',
    confirmed: 'bg-blue-100 text-blue-800',
    checked_in: 'bg-green-100 text-green-800',
    checked_out: 'bg-gray-100 text-gray-800',
    cancelled: 'bg-red-100 text-red-800',
};

const statusLabel = (status) => t('status.' + status);

// Check-in form
const checkInForm = useForm({ room_number: '' });
const submitCheckIn = () => checkInForm.post(`/reservations/${r.value.id}/check-in`);

// Cancel form
const showCancelForm = ref(false);
const cancelForm = useForm({ reason: '' });
const submitCancel = () => cancelForm.post(`/reservations/${r.value.id}/cancel`);

// Special request form
const showSpecialRequestForm = ref(false);
const specialRequestForm = useForm({ type: '', description: '' });
const submitSpecialRequest = () => {
    specialRequestForm.post(`/reservations/${r.value.id}/special-requests`, {
        onSuccess: () => {
            specialRequestForm.reset();
            showSpecialRequestForm.value = false;
        },
    });
};

const requestTypeLabels = computed(() => ({
    early_check_in: t('special_request.types.early_check_in'),
    late_check_out: t('special_request.types.late_check_out'),
    extra_bed: t('special_request.types.extra_bed'),
    dietary_restriction: t('special_request.types.dietary_restriction'),
    special_occasion: t('special_request.types.special_occasion'),
    other: t('special_request.types.other'),
}));

const requestStatusColors = {
    pending: 'bg-yellow-100 text-yellow-800',
    fulfilled: 'bg-green-100 text-green-800',
    cancelled: 'bg-red-100 text-red-800',
};
</script>

<template>
    <div>
        <div class="mb-6 flex items-center gap-4">
            <a href="/reservations" class="text-gray-500 hover:text-gray-700">&larr; {{ $t('common.back') }}</a>
            <h1 class="text-2xl font-bold text-gray-800">{{ $t('reservation.details') }}</h1>
            <span
                class="inline-flex px-2 py-1 text-xs font-semibold rounded-full capitalize"
                :class="statusColors[r.status]"
            >
                {{ statusLabel(r.status) }}
            </span>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Info -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Guest -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-sm font-medium text-gray-500 uppercase tracking-wide mb-3">{{ $t('reservation.guest') }}</h2>
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <span class="text-gray-500">{{ $t('common.name') }}</span>
                            <p class="font-medium text-gray-900">{{ r.guest.full_name }}</p>
                        </div>
                        <div>
                            <span class="text-gray-500">{{ $t('guest.email') }}</span>
                            <p class="font-medium text-gray-900">{{ r.guest.email }}</p>
                        </div>
                        <div>
                            <span class="text-gray-500">{{ $t('guest.phone') }}</span>
                            <p class="font-medium text-gray-900">{{ r.guest.phone }}</p>
                        </div>
                        <div>
                            <span class="text-gray-500">{{ $t('guest.document') }}</span>
                            <p class="font-medium text-gray-900">{{ r.guest.document }}</p>
                        </div>
                    </div>
                    <div v-if="r.guest.is_vip" class="mt-3">
                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-purple-100 text-purple-800">{{ $t('common.vip') }}</span>
                    </div>
                </div>

                <!-- Stay Details -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-sm font-medium text-gray-500 uppercase tracking-wide mb-3">{{ $t('reservation.stay_details') }}</h2>
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <span class="text-gray-500">{{ $t('reservation.check_in') }}</span>
                            <p class="font-medium text-gray-900">{{ r.period.check_in }}</p>
                        </div>
                        <div>
                            <span class="text-gray-500">{{ $t('reservation.check_out') }}</span>
                            <p class="font-medium text-gray-900">{{ r.period.check_out }}</p>
                        </div>
                        <div>
                            <span class="text-gray-500">{{ $t('reservation.nights') }}</span>
                            <p class="font-medium text-gray-900">{{ r.period.nights }}</p>
                        </div>
                        <div>
                            <span class="text-gray-500">{{ $t('reservation.room_type') }}</span>
                            <p class="font-medium text-gray-900">{{ $t('room_type.' + r.room_type.toLowerCase()) }}</p>
                        </div>
                        <div v-if="r.assigned_room_number">
                            <span class="text-gray-500">{{ $t('reservation.room_number') }}</span>
                            <p class="font-medium text-gray-900">{{ r.assigned_room_number }}</p>
                        </div>
                    </div>
                </div>

                <!-- Special Requests -->
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center justify-between mb-3">
                        <h2 class="text-sm font-medium text-gray-500 uppercase tracking-wide">
                            {{ $t('special_request.title') }} ({{ r.special_requests.length }}/5)
                        </h2>
                        <button
                            v-if="!['cancelled', 'checked_out'].includes(r.status) && r.special_requests.length < 5"
                            @click="showSpecialRequestForm = !showSpecialRequestForm"
                            class="text-sm text-blue-600 hover:text-blue-800 font-medium"
                        >
                            {{ showSpecialRequestForm ? $t('common.cancel') : '+ ' + $t('special_request.add') }}
                        </button>
                    </div>

                    <!-- Add form -->
                    <form v-if="showSpecialRequestForm" @submit.prevent="submitSpecialRequest" class="mb-4 p-4 bg-gray-50 rounded-md space-y-3">
                        <div>
                            <select
                                v-model="specialRequestForm.type"
                                required
                                class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm"
                                :class="{ 'border-red-500': specialRequestForm.errors.type }"
                            >
                                <option value="" disabled>{{ $t('special_request.select_type') }}</option>
                                <option v-for="(label, value) in requestTypeLabels" :key="value" :value="value">
                                    {{ label }}
                                </option>
                            </select>
                            <p v-if="specialRequestForm.errors.type" class="mt-1 text-sm text-red-600">{{ specialRequestForm.errors.type }}</p>
                        </div>
                        <div>
                            <textarea
                                v-model="specialRequestForm.description"
                                required
                                :placeholder="$t('special_request.description')"
                                rows="2"
                                class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm"
                                :class="{ 'border-red-500': specialRequestForm.errors.description }"
                            ></textarea>
                            <p v-if="specialRequestForm.errors.description" class="mt-1 text-sm text-red-600">{{ specialRequestForm.errors.description }}</p>
                        </div>
                        <button
                            type="submit"
                            :disabled="specialRequestForm.processing"
                            class="bg-blue-600 text-white px-3 py-1.5 rounded-md text-sm font-medium hover:bg-blue-700 disabled:opacity-50"
                        >
                            {{ $t('special_request.add_request') }}
                        </button>
                    </form>

                    <div v-if="r.special_requests.length === 0 && !showSpecialRequestForm" class="text-sm text-gray-500">
                        {{ $t('special_request.no_requests') }}
                    </div>
                    <div v-else class="space-y-2">
                        <div
                            v-for="sr in r.special_requests"
                            :key="sr.id"
                            class="flex items-start justify-between p-3 bg-gray-50 rounded-md"
                        >
                            <div class="text-sm">
                                <span class="font-medium text-gray-900">{{ requestTypeLabels[sr.type] || sr.type }}</span>
                                <p class="text-gray-600 mt-0.5">{{ sr.description }}</p>
                                <p class="text-gray-400 text-xs mt-1">{{ sr.created_at }}</p>
                            </div>
                            <span
                                class="inline-flex px-2 py-0.5 text-xs font-semibold rounded-full capitalize"
                                :class="requestStatusColors[sr.status]"
                            >
                                {{ $t('status.' + sr.status) }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar: Actions & Timestamps -->
            <div class="space-y-6">
                <!-- Actions -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-sm font-medium text-gray-500 uppercase tracking-wide mb-3">{{ $t('common.actions') }}</h2>
                    <div class="space-y-3">
                        <!-- Confirm -->
                        <form v-if="r.status === 'pending'" @submit.prevent="router.post(`/reservations/${r.id}/confirm`, {}, { preserveScroll: true })">
                            <button type="submit" class="w-full bg-blue-600 text-white py-2 px-4 rounded-md text-sm font-medium hover:bg-blue-700 transition-colors">
                                {{ $t('reservation.confirm') }}
                            </button>
                        </form>

                        <!-- Check In -->
                        <div v-if="r.status === 'confirmed'">
                            <form @submit.prevent="submitCheckIn" class="space-y-2">
                                <select
                                    v-model="checkInForm.room_number"
                                    required
                                    class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm"
                                    :class="{ 'border-red-500': checkInForm.errors.room_number }"
                                >
                                    <option value="" disabled>{{ $t('reservation.select_room') }}</option>
                                    <option
                                        v-for="room in props.availableRooms"
                                        :key="room.number"
                                        :value="room.number"
                                    >
                                        {{ $t('reservation.room') }} {{ room.number }} — {{ $t('room.floor') }} {{ room.floor }} — ${{ room.price_per_night }}{{ $t('common.per_night') }}
                                    </option>
                                </select>
                                <p v-if="props.availableRooms.length === 0" class="text-sm text-amber-600">
                                    {{ $t('reservation.no_rooms_available') }}
                                </p>
                                <p v-if="checkInForm.errors.room_number" class="text-sm text-red-600">{{ checkInForm.errors.room_number }}</p>
                                <button
                                    type="submit"
                                    :disabled="checkInForm.processing || props.availableRooms.length === 0"
                                    class="w-full bg-green-600 text-white py-2 px-4 rounded-md text-sm font-medium hover:bg-green-700 disabled:opacity-50 transition-colors"
                                >
                                    {{ $t('reservation.check_in_action') }}
                                </button>
                            </form>
                        </div>

                        <!-- Check Out -->
                        <form v-if="r.status === 'checked_in'" @submit.prevent="router.post(`/reservations/${r.id}/check-out`, {}, { preserveScroll: true })">
                            <button type="submit" class="w-full bg-gray-600 text-white py-2 px-4 rounded-md text-sm font-medium hover:bg-gray-700 transition-colors">
                                {{ $t('reservation.check_out_action') }}
                            </button>
                        </form>

                        <!-- Cancel -->
                        <div v-if="['pending', 'confirmed'].includes(r.status)">
                            <button
                                v-if="!showCancelForm"
                                @click="showCancelForm = true"
                                class="w-full bg-red-600 text-white py-2 px-4 rounded-md text-sm font-medium hover:bg-red-700 transition-colors"
                            >
                                {{ $t('reservation.cancel') }}
                            </button>
                            <form v-else @submit.prevent="submitCancel" class="space-y-2">
                                <textarea
                                    v-model="cancelForm.reason"
                                    required
                                    :placeholder="$t('reservation.cancel_reason')"
                                    rows="3"
                                    class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm"
                                    :class="{ 'border-red-500': cancelForm.errors.reason }"
                                ></textarea>
                                <p v-if="cancelForm.errors.reason" class="text-sm text-red-600">{{ cancelForm.errors.reason }}</p>
                                <div class="flex gap-2">
                                    <button
                                        type="submit"
                                        :disabled="cancelForm.processing"
                                        class="flex-1 bg-red-600 text-white py-2 px-4 rounded-md text-sm font-medium hover:bg-red-700 disabled:opacity-50"
                                    >
                                        {{ $t('reservation.confirm_cancel') }}
                                    </button>
                                    <button
                                        type="button"
                                        @click="showCancelForm = false"
                                        class="px-4 py-2 text-sm text-gray-500 hover:text-gray-700"
                                    >
                                        {{ $t('common.back') }}
                                    </button>
                                </div>
                            </form>
                        </div>

                        <p v-if="['checked_out', 'cancelled'].includes(r.status)" class="text-sm text-gray-500 text-center">
                            {{ $t('reservation.no_actions') }}
                        </p>
                    </div>
                </div>

                <!-- Timestamps -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-sm font-medium text-gray-500 uppercase tracking-wide mb-3">{{ $t('common.timeline') }}</h2>
                    <div class="space-y-2 text-sm">
                        <div>
                            <span class="text-gray-500">{{ $t('common.created') }}</span>
                            <p class="text-gray-900">{{ r.timestamps.created_at }}</p>
                        </div>
                        <div v-if="r.timestamps.confirmed_at">
                            <span class="text-gray-500">{{ $t('common.confirmed') }}</span>
                            <p class="text-gray-900">{{ r.timestamps.confirmed_at }}</p>
                        </div>
                        <div v-if="r.timestamps.checked_in_at">
                            <span class="text-gray-500">{{ $t('status.checked_in') }}</span>
                            <p class="text-gray-900">{{ r.timestamps.checked_in_at }}</p>
                        </div>
                        <div v-if="r.timestamps.checked_out_at">
                            <span class="text-gray-500">{{ $t('status.checked_out') }}</span>
                            <p class="text-gray-900">{{ r.timestamps.checked_out_at }}</p>
                        </div>
                        <div v-if="r.timestamps.cancelled_at">
                            <span class="text-gray-500">{{ $t('status.cancelled') }}</span>
                            <p class="text-gray-900">{{ r.timestamps.cancelled_at }}</p>
                        </div>
                    </div>
                </div>

                <!-- Cancellation Reason -->
                <div v-if="r.status === 'cancelled' && r.cancellation_reason" class="bg-red-50 rounded-lg shadow p-6">
                    <h2 class="text-sm font-medium text-red-800 uppercase tracking-wide mb-2">{{ $t('common.cancellation_reason') }}</h2>
                    <p class="text-sm text-red-700">{{ r.cancellation_reason }}</p>
                </div>
            </div>
        </div>
    </div>
</template>
