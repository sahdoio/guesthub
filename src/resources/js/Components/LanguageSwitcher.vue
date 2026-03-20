<script setup>
import { useI18n } from 'vue-i18n';
import { ref, onMounted, onUnmounted } from 'vue';
import { setLocale } from '../i18n';

const { locale } = useI18n();
const open = ref(false);
const switcherRef = ref(null);

const languages = [
    { code: 'en', label: 'EN', flag: '/flags/us.svg' },
    { code: 'pt', label: 'PT', flag: '/flags/br.svg' },
];

const current = () => languages.find(l => l.code === locale.value) || languages[0];

const switchLocale = (code) => {
    setLocale(code);
    open.value = false;
};

const closeOnClickOutside = (e) => {
    if (switcherRef.value && !switcherRef.value.contains(e.target)) {
        open.value = false;
    }
};

onMounted(() => document.addEventListener('click', closeOnClickOutside));
onUnmounted(() => document.removeEventListener('click', closeOnClickOutside));
</script>

<template>
    <div class="relative" ref="switcherRef">
        <button
            @click="open = !open"
            class="flex items-center gap-2 px-2.5 py-1.5 rounded-lg border border-gray-200 bg-white hover:bg-gray-50 shadow-sm transition-all text-sm"
        >
            <img :src="current().flag" :alt="current().label" class="w-5 h-4 rounded-sm object-cover" />
            <span class="text-xs font-medium text-gray-600 uppercase">{{ current().label }}</span>
            <svg class="w-3.5 h-3.5 text-gray-400 transition-transform" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
        </button>

        <Transition
            enter-active-class="transition ease-out duration-100"
            enter-from-class="opacity-0 scale-95"
            enter-to-class="opacity-100 scale-100"
            leave-active-class="transition ease-in duration-75"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-95"
        >
            <div
                v-if="open"
                class="absolute right-0 mt-1.5 w-36 bg-white rounded-lg shadow-lg border border-gray-100 z-50 overflow-hidden py-1"
            >
                <button
                    v-for="lang in languages"
                    :key="lang.code"
                    @click="switchLocale(lang.code)"
                    class="w-full px-3 py-2 text-sm hover:bg-gray-50 transition-colors flex items-center gap-2.5"
                    :class="{ 'bg-indigo-50': locale === lang.code }"
                >
                    <img :src="lang.flag" :alt="lang.label" class="w-6 h-4.5 rounded-sm object-cover shadow-sm border border-gray-100" />
                    <span class="text-sm font-medium text-gray-700">{{ lang.label }}</span>
                    <svg v-if="locale === lang.code" class="w-4 h-4 text-indigo-600 ml-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                </button>
            </div>
        </Transition>
    </div>
</template>
