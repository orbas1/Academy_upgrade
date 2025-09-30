<template>
    <button
        :type="type"
        class="ds-button"
        :class="computedClasses"
        :disabled="disabled || loading"
        @click="onClick"
    >
        <span v-if="loading" class="ds-button__spinner" />
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

    return [`ds-button--${variant}`, `ds-button--${size}`];
});

function onClick(event: MouseEvent) {
    if (props.disabled || props.loading) {
        event.preventDefault();
        return;
    }

    emit('click', event);
}
</script>
