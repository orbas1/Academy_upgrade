<template>
    <label class="ds-field">
        <span v-if="label" class="ds-field__label">{{ label }}</span>
        <div class="ds-field__control">
            <textarea
                v-bind="$attrs"
                :rows="rows"
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
    modelValue?: string;
    label?: string;
    supportingText?: string;
    error?: string | null;
    rows?: number;
}>();

const emit = defineEmits<{
    (e: 'update:modelValue', value: string | undefined): void;
    (e: 'blur'): void;
}>();

function onInput(event: Event) {
    const target = event.target as HTMLTextAreaElement;
    emit('update:modelValue', target.value);
}
</script>
