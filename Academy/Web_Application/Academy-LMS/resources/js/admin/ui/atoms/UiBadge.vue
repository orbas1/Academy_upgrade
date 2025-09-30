<template>
    <span class="ds-badge" :class="variantClasses">
        <slot />
    </span>
</template>

<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
    variant?: 'default' | 'success' | 'danger' | 'warning' | 'muted';
}>();

const variantClasses = computed(() => {
    const variant = props.variant ?? 'default';
    return `ds-badge--${variant}`;
});
</script>
