import { defineStore } from 'pinia';
import { ref } from 'vue';
import type { ModuleManifestEntry } from '@/core/modules/types';
import { createModerationService, type ModerationBulkActionPayload } from '@/modules/moderation/services/moderationService';
import type { ModerationAppeal, ModerationReport } from '@/modules/moderation/types';
import { httpClient } from '@/core/http/http-client';

let manifest: ModuleManifestEntry | null = null;

export function bindModerationManifest(entry: ModuleManifestEntry) {
    manifest = entry;
}

export const useModerationStore = defineStore('community-admin.moderation', () => {
    if (!manifest) {
        throw new Error('Moderation manifest must be bound before initializing the store.');
    }

    const service = createModerationService(httpClient, manifest);

    const queue = ref<ModerationReport[]>([]);
    const queueCursor = ref<string | null>(null);
    const queueLoading = ref(false);
    const queueError = ref<string | null>(null);

    const appeals = ref<ModerationAppeal[]>([]);
    const appealsCursor = ref<string | null>(null);
    const appealsLoading = ref(false);
    const appealsError = ref<string | null>(null);

    const selectedReportIds = ref<number[]>([]);

    async function fetchQueue(reset = false): Promise<void> {
        if (queueLoading.value && !reset) {
            return;
        }

        queueLoading.value = true;
        queueError.value = null;

        try {
            const result = await service.fetchQueue(reset ? null : queueCursor.value);
            queueCursor.value = result.meta.nextCursor ?? null;
            queue.value = reset ? result.items : [...queue.value, ...result.items];
        } catch (error) {
            queueError.value = error instanceof Error ? error.message : 'Unable to load moderation queue';
            throw error;
        } finally {
            queueLoading.value = false;
        }
    }

    async function fetchAppeals(reset = false): Promise<void> {
        if (appealsLoading.value && !reset) {
            return;
        }

        appealsLoading.value = true;
        appealsError.value = null;

        try {
            const result = await service.fetchAppeals(reset ? null : appealsCursor.value);
            appealsCursor.value = result.meta.nextCursor ?? null;
            appeals.value = reset ? result.items : [...appeals.value, ...result.items];
        } catch (error) {
            appealsError.value = error instanceof Error ? error.message : 'Unable to load appeals';
            throw error;
        } finally {
            appealsLoading.value = false;
        }
    }

    function toggleSelection(reportId: number): void {
        if (selectedReportIds.value.includes(reportId)) {
            selectedReportIds.value = selectedReportIds.value.filter((id) => id !== reportId);
        } else {
            selectedReportIds.value = [...selectedReportIds.value, reportId];
        }
    }

    async function applyBulkAction(action: ModerationBulkActionPayload['action'], notes?: string): Promise<void> {
        if (!selectedReportIds.value.length) {
            return;
        }

        await service.applyBulkAction({
            reportIds: selectedReportIds.value,
            action,
            notes,
        });

        queue.value = queue.value.filter((report) => !selectedReportIds.value.includes(report.id));
        selectedReportIds.value = [];
    }

    return {
        queue,
        queueCursor,
        queueLoading,
        queueError,
        appeals,
        appealsCursor,
        appealsLoading,
        appealsError,
        fetchQueue,
        fetchAppeals,
        toggleSelection,
        selectedReportIds,
        applyBulkAction,
    };
});
