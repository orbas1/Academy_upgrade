<template>
    <div v-if="!community" class="space-y-6">
        <div class="h-6 w-56 animate-pulse rounded bg-slate-200" />
        <div class="h-80 animate-pulse rounded-xl bg-slate-100" />
    </div>
    <div v-else class="space-y-6">
        <header>
            <h1 class="text-2xl font-semibold text-slate-900">Insights: {{ community.name }}</h1>
            <p class="mt-2 text-sm text-slate-500">
                Data-informed guidance for retention, monetization, and moderation capacity.
            </p>
        </header>

        <section v-if="metrics" class="grid gap-4 lg:grid-cols-3">
            <article class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Retention posture</h2>
                <p class="mt-3 text-3xl font-bold text-slate-900">{{ toPercent(metrics.retention28) }}</p>
                <p class="mt-1 text-sm text-slate-500">
                    28-day retention benchmark. Hold above 40% for healthy growth; below 25% triggers lifecycle campaigns.
                </p>
            </article>
            <article class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Revenue per member</h2>
                <p class="mt-3 text-3xl font-bold text-slate-900">
                    {{ metrics.arpu.toLocaleString(undefined, { style: 'currency', currency: 'USD' }) }}
                </p>
                <p class="mt-1 text-sm text-slate-500">
                    Target ARPU ≥ $7 to sustain creator payouts. Churn at {{ toPercent(metrics.churnRate) }}.
                </p>
            </article>
            <article class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Moderation load</h2>
                <p class="mt-3 text-3xl font-bold text-slate-900">{{ metrics.queueSize }} reports</p>
                <p class="mt-1 text-sm text-slate-500">
                    Queue breaches SLA at 25. Automations escalate when posts per minute exceed {{ metrics.postsPerMinute.toFixed(2) }}.
                </p>
            </article>
        </section>

        <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-slate-900">Owner roster</h2>
            <p class="text-sm text-slate-500">Co-owners accountable for moderation and paywall adjustments.</p>
            <ul class="mt-4 grid gap-3 sm:grid-cols-2">
                <li
                    v-for="owner in community.owners"
                    :key="owner.id"
                    class="flex items-center gap-3 rounded-lg border border-slate-200 bg-slate-50 px-4 py-3"
                >
                    <span
                        class="flex h-10 w-10 items-center justify-center rounded-full bg-indigo-100 text-sm font-semibold text-indigo-700"
                    >
                        {{ initials(owner.name) }}
                    </span>
                    <div>
                        <div class="font-medium text-slate-900">{{ owner.name }}</div>
                        <div class="text-xs text-slate-500">{{ owner.avatarUrl ?? 'No avatar on file' }}</div>
                    </div>
                </li>
            </ul>
        </section>
    </div>
</template>

<script setup lang="ts">
import { computed, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import { useCommunityStore } from '@/modules/communities/stores/communityStore';

const store = useCommunityStore();
const route = useRoute();

const community = computed(() => store.activeCommunity);
const metrics = computed(() => store.metrics);

function toPercent(value: number): string {
    return `${(value * 100).toFixed(1)}%`;
}

function initials(name: string): string {
    return name
        .split(' ')
        .filter(Boolean)
        .map((part) => part[0])
        .join('')
        .toUpperCase()
        .slice(0, 2);
}

onMounted(async () => {
    const identifier = route.params.id as string;
    await store.loadCommunity(identifier);
});
</script>
