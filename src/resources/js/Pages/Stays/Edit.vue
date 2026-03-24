<script setup>
import { useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

defineOptions({ layout: AuthenticatedLayout });

const { t } = useI18n();

const allAmenities = ['wifi', 'tv', 'kitchen', 'parking', 'pool', 'air_conditioning', 'heating', 'washer', 'workspace', 'balcony', 'minibar', 'jacuzzi', 'room_service'];

const props = defineProps({
    stay: Object,
});

const form = useForm({
    name: props.stay.name,
    description: props.stay.description || '',
    address: props.stay.address || '',
    contact_email: props.stay.contact_email || '',
    contact_phone: props.stay.contact_phone || '',
    type: props.stay.type || '',
    category: props.stay.category || '',
    price_per_night: props.stay.price_per_night || '',
    capacity: props.stay.capacity || '',
    amenities: props.stay.amenities || [],
});

const toggleAmenity = (amenity) => {
    const idx = form.amenities.indexOf(amenity);
    if (idx >= 0) {
        form.amenities.splice(idx, 1);
    } else {
        form.amenities.push(amenity);
    }
};

const submit = () => {
    form.put(`/stays/${props.stay.slug}`);
};
</script>

<template>
    <div>
        <div class="mb-6">
            <a :href="`/stays/${stay.slug}`" class="text-sm text-gray-500 hover:text-gray-700 mb-2 inline-block">&larr; {{ $t('common.back') }}</a>
            <h1 class="text-2xl font-bold text-gray-800">{{ $t('stay.edit') }}</h1>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 max-w-2xl">
            <form @submit.prevent="submit" class="p-6 space-y-5">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">{{ $t('stay.name') }}</label>
                    <input
                        id="name"
                        v-model="form.name"
                        type="text"
                        required
                        :placeholder="$t('stay.name_placeholder')"
                        class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                        :class="{ 'border-red-500': form.errors.name }"
                    />
                    <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</p>
                </div>

                <!-- Type selector -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">{{ $t('stay.type') }}</label>
                    <div class="flex gap-4">
                        <label
                            v-for="typeOption in ['room', 'entire_space']"
                            :key="typeOption"
                            class="flex items-center gap-2 px-4 py-2.5 rounded-lg border cursor-pointer transition-colors text-sm"
                            :class="form.type === typeOption ? 'border-indigo-500 bg-indigo-50 text-indigo-700' : 'border-gray-200 hover:bg-gray-50'"
                        >
                            <input
                                type="radio"
                                v-model="form.type"
                                :value="typeOption"
                                class="sr-only"
                            />
                            <span class="font-medium">{{ $t('stay.type_' + typeOption) }}</span>
                        </label>
                    </div>
                    <p v-if="form.errors.type" class="mt-1 text-sm text-red-600">{{ form.errors.type }}</p>
                </div>

                <!-- Category selector -->
                <div>
                    <label for="category" class="block text-sm font-medium text-gray-700 mb-1">{{ $t('stay.category') }}</label>
                    <select
                        id="category"
                        v-model="form.category"
                        required
                        class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                        :class="{ 'border-red-500': form.errors.category }"
                    >
                        <option value="" disabled>{{ $t('stay.select_category') }}</option>
                        <option value="hotel_room">{{ $t('stay.category_hotel_room') }}</option>
                        <option value="house">{{ $t('stay.category_house') }}</option>
                        <option value="apartment">{{ $t('stay.category_apartment') }}</option>
                    </select>
                    <p v-if="form.errors.category" class="mt-1 text-sm text-red-600">{{ form.errors.category }}</p>
                </div>

                <!-- Price & Capacity -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="price_per_night" class="block text-sm font-medium text-gray-700 mb-1">{{ $t('stay.price_per_night') }}</label>
                        <input
                            id="price_per_night"
                            v-model="form.price_per_night"
                            type="number"
                            step="0.01"
                            min="0"
                            required
                            placeholder="0.00"
                            class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                            :class="{ 'border-red-500': form.errors.price_per_night }"
                        />
                        <p v-if="form.errors.price_per_night" class="mt-1 text-sm text-red-600">{{ form.errors.price_per_night }}</p>
                    </div>
                    <div>
                        <label for="capacity" class="block text-sm font-medium text-gray-700 mb-1">{{ $t('stay.capacity') }}</label>
                        <input
                            id="capacity"
                            v-model="form.capacity"
                            type="number"
                            min="1"
                            required
                            placeholder="1"
                            class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                            :class="{ 'border-red-500': form.errors.capacity }"
                        />
                        <p v-if="form.errors.capacity" class="mt-1 text-sm text-red-600">{{ form.errors.capacity }}</p>
                    </div>
                </div>

                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-1">
                        {{ $t('stay.description') }}
                        <span class="text-gray-400 font-normal">({{ $t('auth.optional') }})</span>
                    </label>
                    <textarea
                        id="description"
                        v-model="form.description"
                        rows="3"
                        :placeholder="$t('stay.description_placeholder')"
                        class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                        :class="{ 'border-red-500': form.errors.description }"
                    ></textarea>
                    <p v-if="form.errors.description" class="mt-1 text-sm text-red-600">{{ form.errors.description }}</p>
                </div>

                <div>
                    <label for="address" class="block text-sm font-medium text-gray-700 mb-1">
                        {{ $t('stay.address') }}
                        <span class="text-gray-400 font-normal">({{ $t('auth.optional') }})</span>
                    </label>
                    <input
                        id="address"
                        v-model="form.address"
                        type="text"
                        :placeholder="$t('stay.address_placeholder')"
                        class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                        :class="{ 'border-red-500': form.errors.address }"
                    />
                    <p v-if="form.errors.address" class="mt-1 text-sm text-red-600">{{ form.errors.address }}</p>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="contact_email" class="block text-sm font-medium text-gray-700 mb-1">
                            {{ $t('stay.contact_email') }}
                            <span class="text-gray-400 font-normal">({{ $t('auth.optional') }})</span>
                        </label>
                        <input
                            id="contact_email"
                            v-model="form.contact_email"
                            type="email"
                            :placeholder="$t('stay.contact_email_placeholder')"
                            class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                            :class="{ 'border-red-500': form.errors.contact_email }"
                        />
                        <p v-if="form.errors.contact_email" class="mt-1 text-sm text-red-600">{{ form.errors.contact_email }}</p>
                    </div>

                    <div>
                        <label for="contact_phone" class="block text-sm font-medium text-gray-700 mb-1">
                            {{ $t('stay.contact_phone') }}
                            <span class="text-gray-400 font-normal">({{ $t('auth.optional') }})</span>
                        </label>
                        <input
                            id="contact_phone"
                            v-model="form.contact_phone"
                            type="text"
                            :placeholder="$t('stay.contact_phone_placeholder')"
                            class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                            :class="{ 'border-red-500': form.errors.contact_phone }"
                        />
                        <p v-if="form.errors.contact_phone" class="mt-1 text-sm text-red-600">{{ form.errors.contact_phone }}</p>
                    </div>
                </div>

                <!-- Amenities -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">{{ $t('stay.amenities') }}</label>
                    <div class="flex flex-wrap gap-2">
                        <button
                            v-for="amenity in allAmenities"
                            :key="amenity"
                            type="button"
                            @click="toggleAmenity(amenity)"
                            class="px-3 py-1.5 rounded-lg border text-sm font-medium transition-colors"
                            :class="form.amenities.includes(amenity)
                                ? 'border-indigo-500 bg-indigo-50 text-indigo-700'
                                : 'border-gray-200 text-gray-600 hover:bg-gray-50'"
                        >
                            {{ $t('stay.amenity_' + amenity) }}
                        </button>
                    </div>
                    <p v-if="form.errors.amenities" class="mt-1 text-sm text-red-600">{{ form.errors.amenities }}</p>
                </div>

                <div class="flex items-center gap-3 pt-2">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="bg-indigo-600 text-white px-4 py-2.5 rounded-lg shadow-sm text-sm font-medium hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                    >
                        <span v-if="form.processing">{{ $t('stay.saving') }}</span>
                        <span v-else>{{ $t('stay.save') }}</span>
                    </button>
                    <a :href="`/stays/${stay.slug}`" class="text-sm text-gray-500 hover:text-gray-700">{{ $t('common.cancel') }}</a>
                </div>
            </form>
        </div>
    </div>
</template>
