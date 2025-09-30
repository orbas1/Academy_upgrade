<template>
    <div class="space-y-6">
        <header class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-slate-900">Appeals</h1>
                <p class="text-sm text-slate-500">Member appeals for previously enforced actions.</p>
            </div>
            <button
                type="button"
                class="rounded-md border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
                :disabled="store.appealsLoading"
                @click="refresh"
            >
                Refresh
            </button>
        </header>

        <section class="rounded-xl border border-slate-200 bg-white shadow-sm">
            <header class="flex items-center justify-between border-b border-slate-200 px-4 py-3 text-sm text-slate-600">
                <span>{{ headerLabel }}</span>
                <span v-if="store.appealsError" class="text-red-600">{{ store.appealsError }}</span>
            </header>
            <div v-if="store.appealsLoading && !store.appeals.length" class="p-6 text-sm text-slate-500">Loading appeals…</div>
            <div v-else>
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th scope="col" class="px-4 py-3">Member</th>
                            <th scope="col" class="px-4 py-3">Summary</th>
                            <th scope="col" class="px-4 py-3">Status</th>
                            <th scope="col" class="px-4 py-3">Submitted</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="appeal in store.appeals" :key="appeal.id">
                            <td class="px-4 py-3 text-sm text-slate-700">{{ appeal.memberName }}</td>
                            <td class="px-4 py-3 text-sm text-slate-600">{{ appeal.summary }}</td>
                            <td class="px-4 py-3 text-sm text-slate-600">
                                <span
                                    :class="[
                                        'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold',
                                        appeal.status === 'approved'
                                            ? 'bg-emerald-100 text-emerald-700'
                                            : appeal.status === 'denied'
                                                ? 'bg-red-100 text-red-700'
                                                : 'bg-amber-100 text-amber-700',
                                    ]"
                                >
                                    {{ appeal.status }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-600">{{ formatRelative(appeal.submittedAt) }}</td>
                        </tr>
                    </tbody>
                </table>
                <div class="border-t border-slate-200 px-4 py-3 text-sm text-slate-600">
                    <button
                        v-if="store.appealsCursor"
                        type="button"
                        class="rounded-md border border-slate-300 px-4 py-2 font-semibold hover:bg-slate-50"
                        :disabled="store.appealsLoading"
                        @click="loadMore"
                    >
                        Load more appeals
                    </button>
                    <span v-else>No additional appeals</span>
                </div>
            </div>
        </section>
    </div>
</template>

<script setup lang="ts">
import { computed, onMounted } from 'vue';
import { useModerationStore } from '@/modules/moderation/stores/moderationQueueStore';

const store = useModerationStore();

const headerLabel = computed(() => {
    if (!store.appeals.length) {
        return 'No appeals at the moment';
    }

    return `${store.appeals.length} appeals loaded`;
});

function formatRelative(timestamp: string): string {
    return new Date(timestamp).toLocaleString();
}

async function loadMore() {
    await store.fetchAppeals();
}

async function refresh() {
    store.appealsCursor = null;
    await store.fetchAppeals(true);
}

onMounted(async () => {
    if (!store.appeals.length) {
        await store.fetchAppeals(true);
    }
});
</script>
