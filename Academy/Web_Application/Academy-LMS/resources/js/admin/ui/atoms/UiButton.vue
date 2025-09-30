<template>
    <button
        :type="type"
        class="inline-flex items-center justify-center gap-2 rounded-md font-medium transition focus:outline-none"
        :class="computedClasses"
        :disabled="disabled || loading"
        @click="onClick"
    >
        <span v-if="loading" class="inline-flex h-4 w-4 animate-spin rounded-full border-2 border-white/60 border-t-transparent" />
        <slot />
    </button>
</template>

<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
    variant?: 'primary' | 'secondary' | 'ghost' | 'danger';
    size?: 'sm' | 'md' | 'lg';
    type?: 'button' | 'submit' | 'reset';
    disabled?: boolean;
    loading?: boolean;
}>();

const emit = defineEmits<{
    (e: 'click', event: MouseEvent): void;
}>();

const computedClasses = computed(() => {
    const variant = props.variant ?? 'primary';
    const size = props.size ?? 'md';

    const base = ['shadow-sm', 'focus-visible:ring-2', 'focus-visible:ring-offset-2'];

    const variantClasses: Record<string, string[]> = {
        primary: [
            'bg-indigo-600 text-white hover:bg-indigo-500 focus-visible:ring-indigo-500 disabled:bg-indigo-400 disabled:text-indigo-100',
        ],
        secondary: [
            'bg-slate-100 text-slate-800 hover:bg-slate-200 focus-visible:ring-slate-400 disabled:bg-slate-100 disabled:text-slate-400',
        ],
        ghost: [
            'bg-transparent text-slate-600 hover:bg-slate-100 focus-visible:ring-slate-300 disabled:text-slate-400',
        ],
        danger: [
            'bg-red-600 text-white hover:bg-red-500 focus-visible:ring-red-500 disabled:bg-red-400 disabled:text-red-100',
        ],
    };

    const sizeClasses: Record<string, string[]> = {
        sm: ['px-3 py-1.5 text-xs'],
        md: ['px-4 py-2 text-sm'],
        lg: ['px-5 py-3 text-base'],
    };

    return [...base, ...(variantClasses[variant] ?? variantClasses.primary), ...(sizeClasses[size] ?? sizeClasses.md)].join(' ');
});

function onClick(event: MouseEvent) {
    if (props.disabled || props.loading) {
        event.preventDefault();
        return;
    }

    emit('click', event);
}
</script>
