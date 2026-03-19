<script setup>
import { useI18n } from 'vue-i18n';
import { ref } from 'vue';
import { setLocale } from '../i18n';

const { locale } = useI18n();
const open = ref(false);

const languages = [
    { code: 'en', flag: '🇺🇸' },
    { code: 'pt', flag: '🇧🇷' },
];

const currentFlag = () => (languages.find(l => l.code === locale.value) || languages[0]).flag;

const switchLocale = (code) => {
    setLocale(code);
    open.value = false;
};
</script>

<template>
    <div class="relative">
        <button
            @click="open = !open"
            class="text-sm leading-none px-1.5 py-1 rounded hover:bg-gray-100 transition-colors"
        >
            {{ currentFlag() }}
        </button>

        <div
            v-if="open"
            class="absolute right-0 mt-1 bg-white rounded-md shadow-lg border border-gray-200 z-50 overflow-hidden"
        >
            <button
                v-for="lang in languages"
                :key="lang.code"
                @click="switchLocale(lang.code)"
                class="w-full px-3 py-1.5 text-sm leading-none hover:bg-gray-50 transition-colors flex items-center gap-2"
                :class="{ 'bg-indigo-50': locale === lang.code }"
            >
                <span>{{ lang.flag }}</span>
                <span class="text-xs text-gray-500 uppercase">{{ lang.code }}</span>
            </button>
        </div>

        <div v-if="open" class="fixed inset-0 z-40" @click="open = false"></div>
    </div>
</template>
