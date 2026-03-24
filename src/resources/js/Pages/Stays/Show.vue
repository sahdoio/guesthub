<script setup>
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

defineOptions({ layout: AuthenticatedLayout });

const { t } = useI18n();

const props = defineProps({
    stay: Object,
    reservations: { type: Array, default: () => [] },
    reservationsMeta: { type: Object, default: () => ({}) },
});

const statusColors = {
    active: 'bg-green-100 text-green-800',
    inactive: 'bg-gray-100 text-gray-800',
};

const typeColors = {
    room: 'bg-blue-100 text-blue-800',
    entire_space: 'bg-purple-100 text-purple-800',
};

const categoryColors = {
    hotel_room: 'bg-indigo-100 text-indigo-800',
    house: 'bg-emerald-100 text-emerald-800',
    apartment: 'bg-amber-100 text-amber-800',
};

const reservationStatusColors = {
    pending: 'bg-yellow-100 text-yellow-800',
    confirmed: 'bg-blue-100 text-blue-800',
    checked_in: 'bg-green-100 text-green-800',
    checked_out: 'bg-gray-100 text-gray-800',
    cancelled: 'bg-red-100 text-red-800',
};

// Image upload
const uploading = ref(false);
const coverInput = ref(null);
const imagesInput = ref(null);

const uploadCover = (event) => {
    const file = event.target.files[0];
    if (!file) return;
    const formData = new FormData();
    formData.append('cover', file);
    uploading.value = true;
    router.post(`/stays/${props.stay.slug}/images`, formData, {
        forceFormData: true,
        preserveScroll: true,
        onFinish: () => { uploading.value = false; },
    });
};

const uploadImages = (event) => {
    const files = event.target.files;
    if (!files.length) return;
    const formData = new FormData();
    for (let i = 0; i < files.length; i++) {
        formData.append('images[]', files[i]);
    }
    uploading.value = true;
    router.post(`/stays/${props.stay.slug}/images`, formData, {
        forceFormData: true,
        preserveScroll: true,
        onFinish: () => { uploading.value = false; },
    });
};

const deleteCover = () => {
    router.delete(`/stays/${props.stay.slug}/images/cover`, { preserveScroll: true });
};

const deleteImage = (imageId) => {
    router.delete(`/stays/${props.stay.slug}/images/${imageId}`, { preserveScroll: true });
};

const selectedImage = ref(null);
</script>

<template>
    <div>
        <div class="mb-6">
            <a href="/stays" class="text-sm text-gray-500 hover:text-gray-700 mb-2 inline-block">&larr; {{ $t('common.back') }}</a>
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold text-gray-800">{{ stay.name }}</h1>
                <a
                    :href="`/stays/${stay.slug}/edit`"
                    class="bg-indigo-600 text-white px-4 py-2.5 rounded-lg shadow-sm text-sm font-medium hover:bg-indigo-700 transition-colors"
                >
                    {{ $t('common.edit') }}
                </a>
            </div>
        </div>

        <!-- Cover Image -->
        <div v-if="stay.cover_image_url || stay.images?.length" class="mb-6">
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-3">
                <!-- Main Cover -->
                <div class="lg:col-span-3 relative rounded-xl overflow-hidden bg-gray-100" style="min-height: 300px;">
                    <img
                        v-if="selectedImage || stay.cover_image_url"
                        :src="selectedImage || stay.cover_image_url"
                        :alt="stay.name"
                        class="w-full h-full object-cover absolute inset-0"
                    />
                    <div v-else class="flex items-center justify-center h-full text-gray-400">
                        <svg class="w-16 h-16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                </div>
                <!-- Thumbnails -->
                <div v-if="stay.images?.length" class="flex lg:flex-col gap-2 overflow-x-auto lg:overflow-y-auto" style="max-height: 300px;">
                    <button
                        v-if="stay.cover_image_url"
                        @click="selectedImage = null"
                        class="shrink-0 w-20 h-20 rounded-lg overflow-hidden border-2 transition-colors"
                        :class="selectedImage === null ? 'border-indigo-500' : 'border-transparent hover:border-gray-300'"
                    >
                        <img :src="stay.cover_image_url" :alt="stay.name" class="w-full h-full object-cover" />
                    </button>
                    <button
                        v-for="img in stay.images"
                        :key="img.id"
                        @click="selectedImage = img.url"
                        class="shrink-0 w-20 h-20 rounded-lg overflow-hidden border-2 transition-colors"
                        :class="selectedImage === img.url ? 'border-indigo-500' : 'border-transparent hover:border-gray-300'"
                    >
                        <img :src="img.url" alt="" class="w-full h-full object-cover" />
                    </button>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-6">
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h2 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-4">{{ $t('stay.details') }}</h2>

                    <div class="space-y-4">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">{{ $t('stay.status') }}</dt>
                            <dd class="mt-1">
                                <span
                                    class="inline-flex px-2 py-0.5 text-xs font-semibold rounded-full capitalize"
                                    :class="statusColors[stay.status] || 'bg-gray-100 text-gray-800'"
                                >
                                    {{ stay.status }}
                                </span>
                            </dd>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <div>
                                <dt class="text-sm font-medium text-gray-500 mb-1">{{ $t('stay.type') }}</dt>
                                <dd>
                                    <span
                                        class="inline-flex px-2 py-0.5 text-xs font-semibold rounded-full"
                                        :class="typeColors[stay.type] || 'bg-gray-100 text-gray-600'"
                                    >
                                        {{ $t('stay.type_' + stay.type) }}
                                    </span>
                                </dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500 mb-1">{{ $t('stay.category') }}</dt>
                                <dd>
                                    <span
                                        class="inline-flex px-2 py-0.5 text-xs font-semibold rounded-full"
                                        :class="categoryColors[stay.category] || 'bg-gray-100 text-gray-600'"
                                    >
                                        {{ $t('stay.category_' + stay.category) }}
                                    </span>
                                </dd>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <dt class="text-sm font-medium text-gray-500">{{ $t('stay.price_per_night') }}</dt>
                                <dd class="mt-1 text-lg font-bold text-indigo-700">${{ Number(stay.price_per_night).toFixed(2) }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">{{ $t('stay.capacity') }}</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ stay.capacity }} {{ $t('stay.guests') }}</dd>
                            </div>
                        </div>

                        <div v-if="stay.description">
                            <dt class="text-sm font-medium text-gray-500">{{ $t('stay.description') }}</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ stay.description }}</dd>
                        </div>

                        <div v-if="stay.address">
                            <dt class="text-sm font-medium text-gray-500">{{ $t('stay.address') }}</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ stay.address }}</dd>
                        </div>

                        <!-- Amenities -->
                        <div v-if="stay.amenities && stay.amenities.length > 0">
                            <dt class="text-sm font-medium text-gray-500 mb-2">{{ $t('stay.amenities') }}</dt>
                            <dd class="flex flex-wrap gap-2">
                                <span
                                    v-for="amenity in stay.amenities"
                                    :key="amenity"
                                    class="inline-flex px-2.5 py-1 text-xs font-medium bg-gray-100 text-gray-700 rounded-lg"
                                >
                                    {{ $t('stay.amenity_' + amenity) }}
                                </span>
                            </dd>
                        </div>
                    </div>
                </div>

                <!-- Reservations -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-xs font-semibold text-gray-400 uppercase tracking-wider">{{ $t('stay.reservations') }} ({{ reservationsMeta.total ?? 0 }})</h2>
                    </div>
                    <div v-if="reservations.length === 0" class="text-sm text-gray-500">
                        {{ $t('stay.no_reservations') }}
                    </div>
                    <div v-else class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-gray-100">
                                    <th class="text-left py-2 pr-4 font-medium text-gray-500">{{ $t('reservation.guest') }}</th>
                                    <th class="text-left py-2 pr-4 font-medium text-gray-500">{{ $t('reservation.period') }}</th>
                                    <th class="text-left py-2 pr-4 font-medium text-gray-500">{{ $t('reservation.status') }}</th>
                                    <th class="text-right py-2 font-medium text-gray-500"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="res in reservations" :key="res.id" class="border-b border-gray-50 hover:bg-gray-50">
                                    <td class="py-2.5 pr-4 text-gray-900">{{ res.guest?.full_name || '-' }}</td>
                                    <td class="py-2.5 pr-4 text-gray-600">{{ res.period.check_in }} &rarr; {{ res.period.check_out }}</td>
                                    <td class="py-2.5 pr-4">
                                        <span class="inline-flex px-2 py-0.5 text-xs font-semibold rounded-full capitalize" :class="reservationStatusColors[res.status]">
                                            {{ $t('status.' + res.status) }}
                                        </span>
                                    </td>
                                    <td class="py-2.5 text-right">
                                        <a :href="`/reservations/${res.id}`" class="text-indigo-600 hover:text-indigo-800 text-xs font-medium">{{ $t('common.view') }}</a>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="space-y-6">
                <!-- Image Management -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h2 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-4">{{ $t('stay.images') }}</h2>

                    <!-- Cover Image -->
                    <div class="mb-4">
                        <p class="text-sm font-medium text-gray-500 mb-2">{{ $t('stay.cover_image') }}</p>
                        <div v-if="stay.cover_image_url" class="relative group">
                            <img :src="stay.cover_image_url" :alt="stay.name" class="w-full h-32 object-cover rounded-lg" />
                            <button
                                @click="deleteCover"
                                class="absolute top-2 right-2 bg-red-600 text-white w-6 h-6 rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity text-xs"
                            >
                                &times;
                            </button>
                        </div>
                        <div v-else>
                            <input ref="coverInput" type="file" accept="image/*" class="hidden" @change="uploadCover" />
                            <button
                                @click="coverInput.click()"
                                :disabled="uploading"
                                class="w-full h-24 border-2 border-dashed border-gray-300 rounded-lg flex items-center justify-center text-sm text-gray-400 hover:border-indigo-400 hover:text-indigo-500 transition-colors disabled:opacity-50"
                            >
                                {{ $t('stay.upload_cover') }}
                            </button>
                        </div>
                    </div>

                    <!-- Secondary Images -->
                    <p class="text-sm font-medium text-gray-500 mb-2">{{ $t('stay.gallery') }} ({{ stay.images?.length || 0 }}/10)</p>
                    <div class="grid grid-cols-3 gap-2 mb-3">
                        <div v-for="img in stay.images" :key="img.id" class="relative group">
                            <img :src="img.url" alt="" class="w-full h-16 object-cover rounded-lg" />
                            <button
                                @click="deleteImage(img.id)"
                                class="absolute top-1 right-1 bg-red-600 text-white w-5 h-5 rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity text-xs"
                            >
                                &times;
                            </button>
                        </div>
                    </div>
                    <div v-if="!stay.images || stay.images.length < 10">
                        <input ref="imagesInput" type="file" accept="image/*" multiple class="hidden" @change="uploadImages" />
                        <button
                            @click="imagesInput.click()"
                            :disabled="uploading"
                            class="w-full py-2 border-2 border-dashed border-gray-300 rounded-lg text-sm text-gray-400 hover:border-indigo-400 hover:text-indigo-500 transition-colors disabled:opacity-50"
                        >
                            {{ $t('stay.upload_images') }}
                        </button>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h2 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-4">{{ $t('stay.contact_info') }}</h2>
                    <div class="space-y-3">
                        <div v-if="stay.contact_email">
                            <dt class="text-sm font-medium text-gray-500">{{ $t('stay.contact_email') }}</dt>
                            <dd class="mt-0.5 text-sm text-gray-900">{{ stay.contact_email }}</dd>
                        </div>
                        <div v-if="stay.contact_phone">
                            <dt class="text-sm font-medium text-gray-500">{{ $t('stay.contact_phone') }}</dt>
                            <dd class="mt-0.5 text-sm text-gray-900">{{ stay.contact_phone }}</dd>
                        </div>
                        <p v-if="!stay.contact_email && !stay.contact_phone" class="text-sm text-gray-400 italic">
                            {{ $t('stay.no_contact') }}
                        </p>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h2 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-4">{{ $t('common.timeline') }}</h2>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-500">{{ $t('common.created') }}</span>
                            <span class="text-gray-900">{{ stay.created_at }}</span>
                        </div>
                        <div v-if="stay.updated_at" class="flex justify-between">
                            <span class="text-gray-500">{{ $t('common.updated') }}</span>
                            <span class="text-gray-900">{{ stay.updated_at }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
