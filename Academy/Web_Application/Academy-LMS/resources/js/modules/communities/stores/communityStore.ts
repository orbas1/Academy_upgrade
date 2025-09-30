import { isAxiosError } from 'axios';
import { defineStore } from 'pinia';
import { computed, reactive, ref } from 'vue';
import type { ModuleManifestEntry } from '@/core/modules/types';
import {
    createCommunityService,
    type CommunityListFilters,
} from '@/modules/communities/services/communityService';
import type {
    CommunityDetail,
    CommunityFeedItem,
    CommunityMetrics,
    CommunityMemberSummary,
    CommunitySummary,
} from '@/modules/communities/types';
import { httpClient } from '@/core/http/http-client';
import type { ApiEnvelope } from '@/core/http/types';

type FeedIssueKind =
    | 'offline'
    | 'forbidden'
    | 'paywall'
    | 'moderation_hold'
    | 'rate_limited'
    | 'error';

interface FeedIssue {
    kind: FeedIssueKind;
    message: string;
    details?: string | null;
    retryAt?: Date | null;
}

let manifest: ModuleManifestEntry | null = null;

export function bindCommunityManifest(entry: ModuleManifestEntry) {
    manifest = entry;
}

export const useCommunityStore = defineStore('community-admin.communities', () => {
    if (!manifest) {
        throw new Error('Community manifest must be bound before using the store.');
    }

    const service = createCommunityService(httpClient, manifest);

    const filters = reactive<CommunityListFilters>({
        visibility: 'all',
        paywall: 'all',
        pageSize: 25,
    });

    const summaries = ref<CommunitySummary[]>([]);
    const loadingSummaries = ref(false);
    const summariesError = ref<string | null>(null);
    const nextCursor = ref<string | null>(null);
    const totalCommunities = ref<number | undefined>(undefined);

    const activeCommunity = ref<CommunityDetail | null>(null);
    const metrics = ref<CommunityMetrics | null>(null);
    const members = ref<CommunityMemberSummary[]>([]);
    const membersCursor = ref<string | null>(null);
    const loadingMembers = ref(false);
    const membersError = ref<string | null>(null);

    const feedItems = ref<CommunityFeedItem[]>([]);
    const feedCursor = ref<string | null>(null);
    const feedFilter = ref('new');
    const loadingFeed = ref(false);
    const feedError = ref<string | null>(null);
    const feedIssue = ref<FeedIssue | null>(null);

    const hasMoreCommunities = computed(() => Boolean(nextCursor.value));
    const hasMoreFeed = computed(() => Boolean(feedCursor.value));

    async function fetchCommunities(reset = false): Promise<void> {
        loadingSummaries.value = true;
        summariesError.value = null;

        try {
            const result = await service.fetchCommunities({
                ...filters,
                cursor: reset ? null : nextCursor.value,
            });

            nextCursor.value = result.meta.nextCursor ?? null;
            totalCommunities.value = result.meta.total;

            summaries.value = reset ? result.items : [...summaries.value, ...result.items];
        } catch (error) {
            const message = error instanceof Error ? error.message : 'Unable to load communities';
            summariesError.value = message;
            throw error;
        } finally {
            loadingSummaries.value = false;
        }
    }

    async function refreshCommunities(): Promise<void> {
        nextCursor.value = null;
        await fetchCommunities(true);
    }

    async function search(query: string): Promise<void> {
        filters.query = query.trim() || undefined;
        await refreshCommunities();
    }

    async function setVisibility(visibility: CommunityListFilters['visibility']): Promise<void> {
        filters.visibility = visibility;
        await refreshCommunities();
    }

    async function setPaywallFilter(paywall: CommunityListFilters['paywall']): Promise<void> {
        filters.paywall = paywall;
        await refreshCommunities();
    }

    async function loadCommunity(idOrSlug: string | number): Promise<void> {
        activeCommunity.value = await service.fetchCommunity(idOrSlug);
        metrics.value = await service.fetchMetrics(activeCommunity.value.id);
        await loadMembers(activeCommunity.value.id, true);
        await loadFeed(activeCommunity.value.id, { reset: true });
    }

    async function loadMembers(idOrSlug: string | number, reset = false): Promise<void> {
        if (loadingMembers.value && !reset) {
            return;
        }

        loadingMembers.value = true;
        membersError.value = null;

        try {
            const result = await service.fetchMembers(idOrSlug, reset ? null : membersCursor.value);
            membersCursor.value = result.meta.nextCursor ?? null;
            members.value = reset ? result.items : [...members.value, ...result.items];
        } catch (error) {
            membersError.value = error instanceof Error ? error.message : 'Unable to load members';
            throw error;
        } finally {
            loadingMembers.value = false;
        }
    }

    async function loadFeed(
        idOrSlug: string | number,
        options: { filter?: string; reset?: boolean; pageSize?: number } = {},
    ): Promise<void> {
        if (loadingFeed.value && !options.reset) {
            return;
        }

        loadingFeed.value = true;
        feedError.value = null;

        try {
            const filter = options.filter ?? feedFilter.value;
            const response = await service.fetchFeed(idOrSlug, {
                filter,
                cursor: options.reset ? null : feedCursor.value,
                pageSize: options.pageSize,
            });

            feedFilter.value = filter;
            feedCursor.value = response.meta.nextCursor ?? null;
            feedItems.value = options.reset ? response.items : [...feedItems.value, ...response.items];
            feedIssue.value = null;
        } catch (error) {
            const issue = resolveFeedIssue(error);
            feedIssue.value = issue;
            feedError.value = issue.kind === 'error' ? issue.message : null;
            throw error;
        } finally {
            loadingFeed.value = false;
        }
    }

    async function refreshFeed(
        idOrSlug: string | number,
        filter = feedFilter.value,
    ): Promise<void> {
        feedCursor.value = null;
        await loadFeed(idOrSlug, { filter, reset: true });
    }

    async function createPost(
        idOrSlug: string | number,
        payload: {
            body: string;
            visibility?: 'public' | 'community' | 'paid';
            attachments?: File[];
            scheduledAt?: string | null;
            paywallTierId?: number | null;
        },
    ): Promise<void> {
        const item = await service.createPost(idOrSlug, payload);
        feedItems.value = [item, ...feedItems.value];
    }

    async function toggleReaction(
        idOrSlug: string | number,
        postId: number,
        reaction: string | null,
    ): Promise<void> {
        const item = await service.toggleReaction(idOrSlug, postId, reaction);
        feedItems.value = feedItems.value.map((existing) => (existing.id === item.id ? item : existing));
    }

    return {
        filters,
        summaries,
        loadingSummaries,
        summariesError,
        totalCommunities,
        hasMoreCommunities,
        fetchCommunities,
        refreshCommunities,
        search,
        setVisibility,
        setPaywallFilter,
        loadCommunity,
        activeCommunity,
        metrics,
        members,
        membersCursor,
        loadMembers,
        loadingMembers,
        membersError,
        feedItems,
        feedCursor,
        feedFilter,
        loadFeed,
        refreshFeed,
        loadingFeed,
        feedError,
        feedIssue,
        hasMoreFeed,
        createPost,
        toggleReaction,
    };
});

function resolveFeedIssue(error: unknown): FeedIssue {
    if (typeof navigator !== 'undefined' && !navigator.onLine) {
        return {
            kind: 'offline',
            message: 'You appear to be offline. Changes will sync once you reconnect.',
        };
    }

    if (isAxiosError(error)) {
        const status = error.response?.status;
        const envelope = (error.response?.data ?? {}) as ApiEnvelope<unknown>;
        const messageFromEnvelope =
            envelope.message || envelope.errors?.[0]?.message || error.message || 'Unable to load feed.';

        if (status === 403) {
            return {
                kind: 'forbidden',
                message: messageFromEnvelope || 'Membership is required to view this feed.',
            };
        }

        if (status === 402) {
            return {
                kind: 'paywall',
                message: messageFromEnvelope || 'Upgrade to a paid tier to unlock this feed.',
            };
        }

        if (status === 423) {
            return {
                kind: 'moderation_hold',
                message: messageFromEnvelope || 'This feed is temporarily locked while moderators review reports.',
            };
        }

        if (status === 429) {
            const retryHeader = error.response?.headers?.['retry-after'];
            const retrySeconds = typeof retryHeader === 'string' ? parseInt(retryHeader, 10) : NaN;
            const retryAt = Number.isFinite(retrySeconds)
                ? new Date(Date.now() + retrySeconds * 1000)
                : undefined;

            return {
                kind: 'rate_limited',
                message: messageFromEnvelope || 'Feed refresh is temporarily rate limited. We will try again shortly.',
                retryAt: retryAt ?? null,
            };
        }

        if (status && status >= 500) {
            return {
                kind: 'error',
                message: `Community services are unavailable (${status}). Please retry in a moment.`,
                details: messageFromEnvelope,
            };
        }

        return {
            kind: 'error',
            message: messageFromEnvelope,
        };
    }

    if (error instanceof Error) {
        return {
            kind: 'error',
            message: error.message,
        };
    }

    return {
        kind: 'error',
        message: 'Unable to load feed.',
    };
}
