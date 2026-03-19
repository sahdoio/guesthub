<script setup>
import { ref, watch } from 'vue';

const props = defineProps({
    modelValue: { type: String, default: '' },
    id: { type: String, default: 'phone' },
    placeholder: { type: String, default: '(11) 99999-9999' },
    required: { type: Boolean, default: false },
    inputClass: { type: String, default: '' },
    hasError: { type: Boolean, default: false },
});

const emit = defineEmits(['update:modelValue']);

const displayValue = ref('');

const formatPhone = (digits) => {
    if (!digits) return '';
    // Brazilian format: (XX) XXXXX-XXXX or (XX) XXXX-XXXX
    if (digits.length <= 2) return `(${digits}`;
    if (digits.length <= 7) return `(${digits.slice(0, 2)}) ${digits.slice(2)}`;
    if (digits.length <= 11) return `(${digits.slice(0, 2)}) ${digits.slice(2, 7)}-${digits.slice(7)}`;
    return `(${digits.slice(0, 2)}) ${digits.slice(2, 7)}-${digits.slice(7, 11)}`;
};

const stripToDigits = (value) => {
    return (value || '').replace(/\D/g, '');
};

// Initialize display from modelValue
const initDisplay = () => {
    const digits = stripToDigits(props.modelValue);
    displayValue.value = formatPhone(digits);
};
initDisplay();

watch(() => props.modelValue, (newVal) => {
    const digits = stripToDigits(newVal);
    const currentDigits = stripToDigits(displayValue.value);
    if (digits !== currentDigits) {
        displayValue.value = formatPhone(digits);
    }
});

const onInput = (event) => {
    const raw = event.target.value;
    let digits = stripToDigits(raw).slice(0, 11);
    displayValue.value = formatPhone(digits);

    // Set cursor position after Vue updates the input
    const pos = displayValue.value.length;
    requestAnimationFrame(() => {
        event.target.setSelectionRange(pos, pos);
    });

    emit('update:modelValue', digits);
};
</script>

<template>
    <input
        :id="id"
        type="text"
        :value="displayValue"
        @input="onInput"
        :required="required"
        :placeholder="placeholder"
        :class="inputClass"
        maxlength="16"
        inputmode="numeric"
    />
</template>
