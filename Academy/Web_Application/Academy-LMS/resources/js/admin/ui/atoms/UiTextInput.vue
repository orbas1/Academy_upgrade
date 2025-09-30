<template>
    <label class="ds-field">
        <span v-if="label" class="ds-field__label">{{ label }}</span>
        <div class="ds-field__control">
            <input
                v-bind="$attrs"
                :type="type"
                :value="modelValue"
                class="ds-input"
                @input="onInput"
                @blur="$emit('blur')"
            />
        </div>
        <p v-if="supportingText" class="ds-field__support">{{ supportingText }}</p>
        <p v-if="error" class="ds-field__error">{{ error }}</p>
    </label>
</template>

<script setup lang="ts">
const props = defineProps<{
    modelValue?: string | number;
    label?: string;
    supportingText?: string;
    error?: string | null;
    type?: string;
}>();

const emit = defineEmits<{
    (e: 'update:modelValue', value: string | number | undefined): void;
    (e: 'blur'): void;
}>();

function onInput(event: Event) {
    const target = event.target as HTMLInputElement;
    emit('update:modelValue', target.value);
}
</script>
