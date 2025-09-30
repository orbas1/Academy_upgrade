<template>
    <div
        class="relative flex items-center justify-center overflow-hidden rounded-full bg-slate-100 text-slate-600"
        :class="sizeClasses"
    >
        <img v-if="src" :src="src" :alt="alt" class="h-full w-full object-cover" @error="onError" />
        <span v-else class="font-semibold uppercase">{{ initials }}</span>
        <slot name="status" />
    </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';

const props = defineProps<{
    name?: string;
    src?: string | null;
    alt?: string;
    size?: 'sm' | 'md' | 'lg';
}>();

const failed = ref(false);

const sizeClasses = computed(() => {
    switch (props.size) {
        case 'lg':
            return 'h-16 w-16 text-lg';
        case 'sm':
            return 'h-8 w-8 text-xs';
        case 'md':
        default:
            return 'h-12 w-12 text-sm';
    }
});

const initials = computed(() => {
    if (props.name) {
        const matches = props.name
            .split(' ')
            .filter(Boolean)
            .map((segment) => segment[0]?.toUpperCase() ?? '')
            .slice(0, 2)
            .join('');

        if (matches) {
            return matches;
        }
    }

    return '?';
});

const alt = computed(() => props.alt ?? (props.name ? `${props.name}'s avatar` : 'Avatar'));

const src = computed(() => (failed.value ? null : props.src ?? null));

function onError() {
    failed.value = true;
}
</script>
