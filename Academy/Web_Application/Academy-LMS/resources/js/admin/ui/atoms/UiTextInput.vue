<template>
    <label class="block text-sm font-medium text-slate-600">
        <span v-if="label">{{ label }}</span>
        <div class="mt-1">
            <input
                v-bind="$attrs"
                :type="type"
                :value="modelValue"
                class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 disabled:cursor-not-allowed disabled:bg-slate-50"
                @input="onInput"
                @blur="$emit('blur')"
            />
        </div>
        <p v-if="supportingText" class="mt-1 text-xs text-slate-500">{{ supportingText }}</p>
        <p v-if="error" class="mt-1 text-xs text-red-600">{{ error }}</p>
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
