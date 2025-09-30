<template>
    <span
        class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold uppercase tracking-wide"
        :class="variantClasses"
    >
        <slot />
    </span>
</template>

<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
    variant?: 'default' | 'success' | 'danger' | 'warning' | 'muted';
}>();

const variantClasses = computed(() => {
    switch (props.variant) {
        case 'success':
            return 'bg-emerald-50 text-emerald-700';
        case 'danger':
            return 'bg-red-50 text-red-700';
        case 'warning':
            return 'bg-amber-50 text-amber-700';
        case 'muted':
            return 'bg-slate-100 text-slate-500';
        case 'default':
        default:
            return 'bg-indigo-50 text-indigo-700';
    }
});
</script>
