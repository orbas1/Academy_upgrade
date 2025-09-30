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
        } catch (error) {
            feedError.value = error instanceof Error ? error.message : 'Unable to load feed';
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
        hasMoreFeed,
        createPost,
        toggleReaction,
    };
});
