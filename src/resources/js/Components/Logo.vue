<script setup>
defineProps({
    size: { type: String, default: 'md' },
});

const sizes = {
    sm: { icon: 'w-6 h-6', text: 'text-lg' },
    md: { icon: 'w-8 h-8', text: 'text-xl' },
    lg: { icon: 'w-12 h-12', text: 'text-3xl' },
};
</script>

<template>
    <div class="flex items-center gap-2">
        <div
            class="rounded-lg bg-gradient-to-br from-indigo-500 to-violet-600 flex items-center justify-center shadow-sm"
            :class="sizes[size].icon"
        >
            <svg class="w-[60%] h-[60%] text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z" />
                <polyline points="9 22 9 12 15 12 15 22" />
            </svg>
        </div>
        <span class="font-bold tracking-tight" :class="sizes[size].text">
            <span class="text-gray-900">Guest</span><span class="text-indigo-600">Hub</span>
        </span>
    </div>
</template>
