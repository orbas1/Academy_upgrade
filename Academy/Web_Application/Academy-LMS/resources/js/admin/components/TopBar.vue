<template>
    <header class="flex items-center justify-between border-b border-slate-200 bg-white px-6 py-4">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Operational pulse</p>
            <p class="text-sm text-slate-600">Monitor engagement, revenue, and moderation from a single surface.</p>
        </div>
        <div class="flex items-center gap-3">
            <div class="text-right">
                <div class="text-sm font-semibold text-slate-900">{{ user.name }}</div>
                <div class="text-xs uppercase tracking-wide text-slate-500">{{ user.role }}</div>
            </div>
            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-indigo-600 text-sm font-semibold text-white">
                {{ initials }}
            </div>
        </div>
    </header>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { useAppContext } from '@/core/composables/useAppContext';

const { user } = useAppContext();

const initials = computed(() =>
    user.name
        .split(' ')
        .filter(Boolean)
        .map((part) => part[0])
        .join('')
        .toUpperCase()
        .slice(0, 2),
);
</script>
