import type { AxiosInstance } from 'axios';
import type { ApiEnvelope } from '@/core/http/types';
import type { ModuleManifestEntry } from '@/core/modules/types';
import type { ModerationAppeal, ModerationReport, PaginationResult } from '@/modules/moderation/types';

interface ModerationReportPayload {
    id: number;
    reason: string;
    status: string;
    severity: string;
    reported_at: string;
    reporter: {
        id: number;
        name: string;
        avatar_url?: string | null;
    };
    target: {
        id: number;
        type: string;
        summary: string;
        author: string;
        permalink: string;
    };
    evidence_urls?: string[];
}

interface ModerationAppealPayload {
    id: number;
    submitted_at: string;
    member_name: string;
    status: string;
    summary: string;
}

export interface ModerationBulkActionPayload {
    reportIds: number[];
    action: 'approve' | 'reject' | 'snooze';
    notes?: string;
}

function mapReport(payload: ModerationReportPayload): ModerationReport {
    const targetType = ['post', 'comment', 'member'].includes(payload.target.type)
        ? (payload.target.type as ModerationReport['target']['type'])
        : 'post';

    return {
        id: payload.id,
        reason: payload.reason,
        status: ['open', 'snoozed', 'resolved'].includes(payload.status)
            ? (payload.status as ModerationReport['status'])
            : 'open',
        severity: ['low', 'medium', 'high'].includes(payload.severity)
            ? (payload.severity as ModerationReport['severity'])
            : 'low',
        reportedAt: payload.reported_at,
        reporter: {
            id: payload.reporter.id,
            name: payload.reporter.name,
            avatarUrl: payload.reporter.avatar_url ?? null,
        },
        target: {
            id: payload.target.id,
            type: targetType,
            summary: payload.target.summary,
            author: payload.target.author,
            permalink: payload.target.permalink,
        },
        evidenceUrls: payload.evidence_urls ?? [],
    };
}

function mapAppeal(payload: ModerationAppealPayload): ModerationAppeal {
    return {
        id: payload.id,
        submittedAt: payload.submitted_at,
        memberName: payload.member_name,
        status: ['pending', 'approved', 'denied'].includes(payload.status)
            ? (payload.status as ModerationAppeal['status'])
            : 'pending',
        summary: payload.summary,
    };
}

export function createModerationService(httpClient: AxiosInstance, manifest: ModuleManifestEntry) {
    const endpoints = manifest.endpoints ?? {};
    const queueEndpoint = endpoints.queue ?? '/moderation/reports';
    const bulkEndpoint = endpoints.bulk_action ?? '/moderation/reports/bulk';
    const appealsEndpoint = endpoints.appeals ?? '/moderation/appeals';

    return {
        async fetchQueue(cursor: string | null = null): Promise<PaginationResult<ModerationReport>> {
            const response = await httpClient.get<ApiEnvelope<ModerationReportPayload[]>>(queueEndpoint, {
                params: {
                    after: cursor ?? undefined,
                    page_size: 25,
                },
            });

            const payload = response.data;
            const items = Array.isArray(payload.data) ? payload.data.map(mapReport) : [];
            const meta = payload.meta ?? {};

            return {
                items,
                meta: {
                    nextCursor: meta.next_cursor ?? payload.links?.next ?? null,
                    total: meta.total,
                },
            };
        },

        async fetchAppeals(cursor: string | null = null): Promise<PaginationResult<ModerationAppeal>> {
            const response = await httpClient.get<ApiEnvelope<ModerationAppealPayload[]>>(appealsEndpoint, {
                params: {
                    after: cursor ?? undefined,
                    page_size: 25,
                },
            });

            const payload = response.data;
            const items = Array.isArray(payload.data) ? payload.data.map(mapAppeal) : [];
            const meta = payload.meta ?? {};

            return {
                items,
                meta: {
                    nextCursor: meta.next_cursor ?? payload.links?.next ?? null,
                    total: meta.total,
                },
            };
        },

        async applyBulkAction(input: ModerationBulkActionPayload): Promise<void> {
            await httpClient.post(bulkEndpoint, {
                report_ids: input.reportIds,
                action: input.action,
                notes: input.notes,
            });
        },
    };
}
