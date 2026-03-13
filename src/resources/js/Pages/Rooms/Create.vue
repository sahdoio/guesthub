<script setup>
import { useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

defineOptions({ layout: AuthenticatedLayout });

const form = useForm({
    number: '',
    type: '',
    floor: '',
    capacity: '',
    price_per_night: '',
    amenities: [],
});

const newAmenity = useForm({ value: '' });

const addAmenity = () => {
    const val = newAmenity.value.trim();
    if (val && !form.amenities.includes(val)) {
        form.amenities.push(val);
    }
    newAmenity.value = '';
};

const removeAmenity = (index) => {
    form.amenities.splice(index, 1);
};

const submit = () => {
    form.post('/rooms');
};
</script>

<template>
    <div>
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-800">New Room</h1>
        </div>

        <div class="bg-white rounded-lg shadow max-w-2xl">
            <form @submit.prevent="submit" class="p-6 space-y-5">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="number" class="block text-sm font-medium text-gray-700 mb-1">Room Number</label>
                        <input
                            id="number"
                            v-model="form.number"
                            type="text"
                            required
                            placeholder="e.g., 201, 101A"
                            class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            :class="{ 'border-red-500': form.errors.number }"
                        />
                        <p v-if="form.errors.number" class="mt-1 text-sm text-red-600">{{ form.errors.number }}</p>
                    </div>

                    <div>
                        <label for="type" class="block text-sm font-medium text-gray-700 mb-1">Type</label>
                        <select
                            id="type"
                            v-model="form.type"
                            required
                            class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            :class="{ 'border-red-500': form.errors.type }"
                        >
                            <option value="" disabled>Select type</option>
                            <option value="SINGLE">Single</option>
                            <option value="DOUBLE">Double</option>
                            <option value="SUITE">Suite</option>
                        </select>
                        <p v-if="form.errors.type" class="mt-1 text-sm text-red-600">{{ form.errors.type }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <label for="floor" class="block text-sm font-medium text-gray-700 mb-1">Floor</label>
                        <input
                            id="floor"
                            v-model="form.floor"
                            type="number"
                            required
                            min="1"
                            class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            :class="{ 'border-red-500': form.errors.floor }"
                        />
                        <p v-if="form.errors.floor" class="mt-1 text-sm text-red-600">{{ form.errors.floor }}</p>
                    </div>

                    <div>
                        <label for="capacity" class="block text-sm font-medium text-gray-700 mb-1">Capacity</label>
                        <input
                            id="capacity"
                            v-model="form.capacity"
                            type="number"
                            required
                            min="1"
                            class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            :class="{ 'border-red-500': form.errors.capacity }"
                        />
                        <p v-if="form.errors.capacity" class="mt-1 text-sm text-red-600">{{ form.errors.capacity }}</p>
                    </div>

                    <div>
                        <label for="price" class="block text-sm font-medium text-gray-700 mb-1">Price/Night ($)</label>
                        <input
                            id="price"
                            v-model="form.price_per_night"
                            type="number"
                            required
                            min="0"
                            step="0.01"
                            class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            :class="{ 'border-red-500': form.errors.price_per_night }"
                        />
                        <p v-if="form.errors.price_per_night" class="mt-1 text-sm text-red-600">{{ form.errors.price_per_night }}</p>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Amenities</label>
                    <div class="flex flex-wrap gap-2 mb-2" v-if="form.amenities.length > 0">
                        <span
                            v-for="(amenity, index) in form.amenities"
                            :key="index"
                            class="inline-flex items-center px-3 py-1 text-sm bg-blue-50 text-blue-700 rounded-full"
                        >
                            {{ amenity }}
                            <button type="button" @click="removeAmenity(index)" class="ml-1.5 text-blue-400 hover:text-blue-600">&times;</button>
                        </span>
                    </div>
                    <div class="flex gap-2">
                        <input
                            v-model="newAmenity.value"
                            type="text"
                            placeholder="Add an amenity..."
                            @keydown.enter.prevent="addAmenity"
                            class="flex-1 rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        />
                        <button type="button" @click="addAmenity" class="px-3 py-2 text-sm border border-gray-300 rounded-md hover:bg-gray-50">Add</button>
                    </div>
                </div>

                <div class="flex items-center gap-3 pt-2">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="bg-blue-600 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                    >
                        <span v-if="form.processing">Creating...</span>
                        <span v-else>Create Room</span>
                    </button>
                    <a href="/rooms" class="text-sm text-gray-500 hover:text-gray-700">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</template>
