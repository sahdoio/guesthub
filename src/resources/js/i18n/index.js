import { createI18n } from 'vue-i18n';
import en from './locales/en.json';
import pt from './locales/pt.json';

const savedLocale = localStorage.getItem('locale');
const browserLocale = navigator.language.split('-')[0];
const defaultLocale = savedLocale || (['pt'].includes(browserLocale) ? 'pt' : 'en');

const i18n = createI18n({
    legacy: false,
    locale: defaultLocale,
    fallbackLocale: 'en',
    messages: { en, pt },
});

export default i18n;

export function setLocale(locale) {
    i18n.global.locale.value = locale;
    localStorage.setItem('locale', locale);
    document.documentElement.lang = locale;
}
