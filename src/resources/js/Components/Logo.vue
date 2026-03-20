<script setup>
defineProps({
    size: { type: String, default: 'md' },
});

const sizes = {
    sm: 'h-8',
    md: 'h-10',
    lg: 'h-12',
};
</script>

<template>
    <div class="flex items-center">
        <img src="/logo.png" alt="GuestHub" :class="sizes[size]" class="object-contain" />
    </div>
</template>
