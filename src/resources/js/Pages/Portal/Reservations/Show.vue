<script setup>
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import GuestPortalLayout from '@/Layouts/GuestPortalLayout.vue';

defineOptions({ layout: GuestPortalLayout });

const props = defineProps({
    reservation: Object,
});

const r = props.reservation;

const statusColors = {
    pending: 'bg-yellow-100 text-yellow-800',
    confirmed: 'bg-blue-100 text-blue-800',
    checked_in: 'bg-green-100 text-green-800',
    checked_out: 'bg-gray-100 text-gray-800',
    cancelled: 'bg-red-100 text-red-800',
};

const statusLabel = (status) => status.replace('_', ' ');

// Cancel form
const showCancelForm = ref(false);
const cancelForm = useForm({ reason: '' });
const submitCancel = () => cancelForm.post(`/portal/reservations/${r.id}/cancel`);

// Special request form
const showSpecialRequestForm = ref(false);
const specialRequestForm = useForm({ type: '', description: '' });
const submitSpecialRequest = () => {
    specialRequestForm.post(`/portal/reservations/${r.id}/special-requests`, {
        onSuccess: () => {
            specialRequestForm.reset();
            showSpecialRequestForm.value = false;
        },
    });
};

const requestTypeLabels = {
    early_check_in: 'Early Check-in',
    late_check_out: 'Late Check-out',
    extra_bed: 'Extra Bed',
    dietary_restriction: 'Dietary Restriction',
    special_occasion: 'Special Occasion',
    other: 'Other',
};

const requestStatusColors = {
    pending: 'bg-yellow-100 text-yellow-800',
    fulfilled: 'bg-green-100 text-green-800',
    cancelled: 'bg-red-100 text-red-800',
};
</script>

<template>
    <div>
        <div class="mb-6 flex items-center gap-4">
            <a href="/portal/reservations" class="text-gray-500 hover:text-gray-700">&larr; Back</a>
            <h1 class="text-2xl font-bold text-gray-800">Reservation Details</h1>
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
                <!-- Stay Details -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-sm font-medium text-gray-500 uppercase tracking-wide mb-3">Stay Details</h2>
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <span class="text-gray-500">Check-in</span>
                            <p class="font-medium text-gray-900">{{ r.period.check_in }}</p>
                        </div>
                        <div>
                            <span class="text-gray-500">Check-out</span>
                            <p class="font-medium text-gray-900">{{ r.period.check_out }}</p>
                        </div>
                        <div>
                            <span class="text-gray-500">Nights</span>
                            <p class="font-medium text-gray-900">{{ r.period.nights }}</p>
                        </div>
                        <div>
                            <span class="text-gray-500">Room Type</span>
                            <p class="font-medium text-gray-900">{{ r.room_type }}</p>
                        </div>
                        <div v-if="r.assigned_room_number">
                            <span class="text-gray-500">Room Number</span>
                            <p class="font-medium text-gray-900">{{ r.assigned_room_number }}</p>
                        </div>
                    </div>
                </div>

                <!-- Special Requests -->
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center justify-between mb-3">
                        <h2 class="text-sm font-medium text-gray-500 uppercase tracking-wide">
                            Special Requests ({{ r.special_requests.length }}/5)
                        </h2>
                        <button
                            v-if="!['cancelled', 'checked_out'].includes(r.status) && r.special_requests.length < 5"
                            @click="showSpecialRequestForm = !showSpecialRequestForm"
                            class="text-sm text-blue-600 hover:text-blue-800 font-medium"
                        >
                            {{ showSpecialRequestForm ? 'Cancel' : '+ Add' }}
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
                                <option value="" disabled>Select type</option>
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
                                placeholder="Description (min 3 characters)"
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
                            Add Request
                        </button>
                    </form>

                    <div v-if="r.special_requests.length === 0 && !showSpecialRequestForm" class="text-sm text-gray-500">
                        No special requests.
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
                                {{ sr.status }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Actions -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-sm font-medium text-gray-500 uppercase tracking-wide mb-3">Actions</h2>
                    <div class="space-y-3">
                        <!-- Cancel -->
                        <div v-if="['pending', 'confirmed'].includes(r.status)">
                            <button
                                v-if="!showCancelForm"
                                @click="showCancelForm = true"
                                class="w-full bg-red-600 text-white py-2 px-4 rounded-md text-sm font-medium hover:bg-red-700 transition-colors"
                            >
                                Cancel Reservation
                            </button>
                            <form v-else @submit.prevent="submitCancel" class="space-y-2">
                                <textarea
                                    v-model="cancelForm.reason"
                                    required
                                    placeholder="Reason for cancellation (min 10 characters)"
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
                                        Confirm Cancel
                                    </button>
                                    <button
                                        type="button"
                                        @click="showCancelForm = false"
                                        class="px-4 py-2 text-sm text-gray-500 hover:text-gray-700"
                                    >
                                        Back
                                    </button>
                                </div>
                            </form>
                        </div>

                        <p v-if="['checked_in'].includes(r.status)" class="text-sm text-gray-500 text-center">
                            You are currently checked in. Contact the front desk for assistance.
                        </p>

                        <p v-if="['checked_out', 'cancelled'].includes(r.status)" class="text-sm text-gray-500 text-center">
                            No actions available.
                        </p>
                    </div>
                </div>

                <!-- Timeline -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-sm font-medium text-gray-500 uppercase tracking-wide mb-3">Timeline</h2>
                    <div class="space-y-2 text-sm">
                        <div>
                            <span class="text-gray-500">Booked</span>
                            <p class="text-gray-900">{{ r.timestamps.created_at }}</p>
                        </div>
                        <div v-if="r.timestamps.confirmed_at">
                            <span class="text-gray-500">Confirmed</span>
                            <p class="text-gray-900">{{ r.timestamps.confirmed_at }}</p>
                        </div>
                        <div v-if="r.timestamps.checked_in_at">
                            <span class="text-gray-500">Checked In</span>
                            <p class="text-gray-900">{{ r.timestamps.checked_in_at }}</p>
                        </div>
                        <div v-if="r.timestamps.checked_out_at">
                            <span class="text-gray-500">Checked Out</span>
                            <p class="text-gray-900">{{ r.timestamps.checked_out_at }}</p>
                        </div>
                        <div v-if="r.timestamps.cancelled_at">
                            <span class="text-gray-500">Cancelled</span>
                            <p class="text-gray-900">{{ r.timestamps.cancelled_at }}</p>
                        </div>
                    </div>
                </div>

                <!-- Cancellation Reason -->
                <div v-if="r.status === 'cancelled' && r.cancellation_reason" class="bg-red-50 rounded-lg shadow p-6">
                    <h2 class="text-sm font-medium text-red-800 uppercase tracking-wide mb-2">Cancellation Reason</h2>
                    <p class="text-sm text-red-700">{{ r.cancellation_reason }}</p>
                </div>
            </div>
        </div>
    </div>
</template>
