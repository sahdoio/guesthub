<script setup>
import LanguageSwitcher from '../Components/LanguageSwitcher.vue';
</script>

<template>
    <div class="min-h-screen flex relative">
        <div class="absolute top-4 right-4 z-50">
            <LanguageSwitcher />
        </div>
        <slot />
    </div>
</template>
