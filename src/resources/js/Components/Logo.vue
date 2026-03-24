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
        <img src="/logo.png" alt="GuestHub" :class="sizes[size]" class="object-contain logo-brand" />
    </div>
</template>

<style scoped>
.logo-brand {
    filter: brightness(0) saturate(100%) invert(23%) sepia(93%) saturate(5765%) hue-rotate(238deg) brightness(96%) contrast(93%);
}
</style>
