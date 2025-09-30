<template>
    <aside class="hidden w-64 flex-shrink-0 border-r border-slate-200 bg-white p-4 md:flex md:flex-col">
        <div class="mb-6">
            <h1 class="text-lg font-semibold text-slate-900">Community Ops</h1>
            <p class="text-xs text-slate-500">Control center</p>
        </div>
        <nav class="flex-1 space-y-1">
            <RouterLink
                v-for="item in items"
                :key="item.route"
                :to="{ name: item.route }"
                :data-route-prefetch="item.route"
                class="group flex items-center rounded-md px-3 py-2 text-sm font-medium text-slate-600 hover:bg-indigo-50 hover:text-indigo-600"
                active-class="bg-indigo-100 text-indigo-700"
            >
                <span class="mr-2 h-2 w-2 rounded-full bg-indigo-300 transition group-hover:bg-indigo-500" />
                <span>{{ item.label }}</span>
            </RouterLink>
        </nav>
    </aside>
</template>

<script setup lang="ts">
import type { NavigationItem } from '@/core/modules/types';

defineProps<{
    items: NavigationItem[];
}>();
</script>
