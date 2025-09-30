<template>
    <div class="space-y-6">
        <header class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-slate-900">Moderation queue</h1>
                <p class="text-sm text-slate-500">Flagged content awaiting review and enforcement.</p>
            </div>
            <div class="flex items-center gap-2">
                <button
                    type="button"
                    class="rounded-md border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
                    :disabled="store.queueLoading"
                    @click="refresh"
                >
                    Refresh
                </button>
                <button
                    type="button"
                    class="rounded-md bg-emerald-600 px-3 py-2 text-sm font-semibold text-white hover:bg-emerald-500 disabled:opacity-60"
                    :disabled="store.selectedReportIds.length === 0 || store.queueLoading"
                    @click="applyAction('approve')"
                >
                    Approve selected
                </button>
                <button
                    type="button"
                    class="rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white hover:bg-red-500 disabled:opacity-60"
                    :disabled="store.selectedReportIds.length === 0 || store.queueLoading"
                    @click="applyAction('reject')"
                >
                    Reject selected
                </button>
            </div>
        </header>

        <section class="rounded-xl border border-slate-200 bg-white shadow-sm">
            <header class="flex items-center justify-between border-b border-slate-200 px-4 py-3 text-sm text-slate-600">
                <span>{{ selectionLabel }}</span>
                <span v-if="store.queueError" class="text-red-600">{{ store.queueError }}</span>
            </header>

            <div v-if="store.queueLoading && !store.queue.length" class="p-6 text-sm text-slate-500">Loading reports…</div>
            <div v-else>
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th scope="col" class="px-4 py-3">
                                <input type="checkbox" class="h-4 w-4" :checked="allSelected" @change="toggleAll" />
                            </th>
                            <th scope="col" class="px-4 py-3">Content</th>
                            <th scope="col" class="px-4 py-3">Reason</th>
                            <th scope="col" class="px-4 py-3">Severity</th>
                            <th scope="col" class="px-4 py-3">Reporter</th>
                            <th scope="col" class="px-4 py-3">Reported at</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="report in store.queue" :key="report.id">
                            <td class="px-4 py-3">
                                <input
                                    type="checkbox"
                                    class="h-4 w-4"
                                    :checked="store.selectedReportIds.includes(report.id)"
                                    @change="() => store.toggleSelection(report.id)"
                                />
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-700">
                                <div class="font-semibold text-slate-900">{{ report.target.summary }}</div>
                                <div class="text-xs text-slate-500">{{ report.target.type }} • {{ report.target.author }}</div>
                                <a :href="report.target.permalink" class="text-xs text-indigo-600" target="_blank">Open</a>
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-600">{{ report.reason }}</td>
                            <td class="px-4 py-3 text-sm text-slate-600">
                                <span
                                    :class="[
                                        'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold',
                                        report.severity === 'high'
                                            ? 'bg-red-100 text-red-700'
                                            : report.severity === 'medium'
                                                ? 'bg-amber-100 text-amber-700'
                                                : 'bg-slate-100 text-slate-700',
                                    ]"
                                >
                                    {{ report.severity }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-600">
                                <div class="font-medium text-slate-900">{{ report.reporter.name }}</div>
                                <div class="text-xs text-slate-500">ID {{ report.reporter.id }}</div>
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-600">{{ formatRelative(report.reportedAt) }}</td>
                        </tr>
                    </tbody>
                </table>
                <div class="border-t border-slate-200 px-4 py-3 text-sm text-slate-600">
                    <button
                        v-if="store.queueCursor"
                        type="button"
                        class="rounded-md border border-slate-300 px-4 py-2 font-semibold hover:bg-slate-50"
                        :disabled="store.queueLoading"
                        @click="loadMore"
                    >
                        Load more reports
                    </button>
                    <span v-else>No additional reports</span>
                </div>
            </div>
        </section>
    </div>
</template>

<script setup lang="ts">
import { computed, onMounted } from 'vue';
import { useModerationStore } from '@/modules/moderation/stores/moderationQueueStore';

const store = useModerationStore();

const selectionLabel = computed(() => {
    if (!store.queue.length) {
        return 'Queue empty';
    }

    if (!store.selectedReportIds.length) {
        return `${store.queue.length} reports in queue`;
    }

    return `${store.selectedReportIds.length} selected of ${store.queue.length}`;
});

const allSelected = computed(
    () => store.queue.length > 0 && store.selectedReportIds.length === store.queue.length,
);

function formatRelative(timestamp: string): string {
    return new Date(timestamp).toLocaleString();
}

function toggleAll(event: Event) {
    const checked = (event.target as HTMLInputElement).checked;

    if (checked) {
        store.selectedReportIds = store.queue.map((report) => report.id);
    } else {
        store.selectedReportIds = [];
    }
}

async function loadMore() {
    await store.fetchQueue();
}

async function refresh() {
    store.queueCursor = null;
    await store.fetchQueue(true);
}

async function applyAction(action: 'approve' | 'reject' | 'snooze') {
    await store.applyBulkAction(action);
}

onMounted(async () => {
    if (!store.queue.length) {
        await store.fetchQueue(true);
    }
});
</script>
