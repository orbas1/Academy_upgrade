import type { AxiosInstance } from 'axios';
import type { ApiEnvelope } from '@/core/http/types';
import type { ModuleManifestEntry } from '@/core/modules/types';
import type {
    CommunityDetail,
    CommunityFeedAttachment,
    CommunityFeedItem,
    CommunityMetrics,
    CommunityMemberSummary,
    CommunitySummary,
    PaginationResult,
} from '@/modules/communities/types';

interface CommunitySummaryPayload {
    id: number;
    name: string;
    slug: string;
    visibility: string;
    members_count: number;
    online_count: number;
    posts_per_day: number;
    comments_per_day: number;
    paywall_enabled: boolean;
    last_activity_at?: string | null;
}

interface CommunityDetailPayload extends CommunitySummaryPayload {
    description?: string | null;
    category?: string | null;
    created_at: string;
    owners: Array<{
        id: number;
        name: string;
        avatar_url?: string | null;
    }>;
}

interface CommunityMetricsPayload {
    dau: number;
    wau: number;
    mau: number;
    retention_7: number;
    retention_28: number;
    retention_90: number;
    conversion_first_post: number;
    mrr: number;
    churn_rate: number;
    arpu: number;
    ltv: number;
    posts_per_minute: number;
    queue_size: number;
}

interface CommunityMemberPayload {
    id: number;
    name: string;
    avatar_url?: string | null;
    role: string;
    joined_at: string;
    last_active_at?: string | null;
}

interface CommunityFeedAttachmentPayload {
    id: number;
    type: string;
    url: string;
    thumbnail_url?: string | null;
    mime_type?: string | null;
    title?: string | null;
    description?: string | null;
}

interface CommunityFeedItemPayload {
    id: number;
    author: {
        id: number;
        name: string;
        avatar_url?: string | null;
        role?: string | null;
    };
    created_at: string;
    visibility: string;
    body: string;
    body_html?: string | null;
    like_count: number;
    comment_count: number;
    viewer_reaction?: string | null;
    reaction_breakdown?: Record<string, number> | Array<{ key: string; value: number }>;
    attachments?: CommunityFeedAttachmentPayload[];
    paywall_tier_id?: number | null;
}

export interface CommunityListFilters {
    query?: string;
    visibility?: 'all' | 'public' | 'private' | 'unlisted';
    paywall?: 'all' | 'enabled' | 'disabled';
    pageSize?: number;
    cursor?: string | null;
}

export interface CommunityServiceContract {
    fetchCommunities(filters?: CommunityListFilters): Promise<PaginationResult<CommunitySummary>>;
    fetchCommunity(idOrSlug: string | number): Promise<CommunityDetail>;
    fetchMetrics(idOrSlug: string | number): Promise<CommunityMetrics>;
    fetchMembers(idOrSlug: string | number, cursor?: string | null): Promise<PaginationResult<CommunityMemberSummary>>;
    fetchFeed(
        idOrSlug: string | number,
        options?: { filter?: string; cursor?: string | null; pageSize?: number },
    ): Promise<PaginationResult<CommunityFeedItem>>;
    createPost(
        idOrSlug: string | number,
        payload: {
            body: string;
            visibility?: 'public' | 'community' | 'paid';
            attachments?: File[];
            scheduledAt?: string | null;
            paywallTierId?: number | null;
        },
    ): Promise<CommunityFeedItem>;
    toggleReaction(
        idOrSlug: string | number,
        postId: number,
        reaction: string | null,
    ): Promise<CommunityFeedItem>;
}

function compileEndpoint(template: string, parameters: Record<string, string | number>): string {
    return template.replace(/\{(.*?)\}/g, (_, key: string) => {
        const value = parameters[key];

        if (value === undefined || value === null) {
            throw new Error(`Missing parameter "${key}" for endpoint ${template}`);
        }

        return encodeURIComponent(String(value));
    });
}

function mapSummary(payload: CommunitySummaryPayload): CommunitySummary {
    return {
        id: payload.id,
        name: payload.name,
        slug: payload.slug,
        visibility: ['public', 'private', 'unlisted'].includes(payload.visibility)
            ? (payload.visibility as CommunitySummary['visibility'])
            : 'public',
        membersCount: payload.members_count,
        onlineCount: payload.online_count,
        postsPerDay: payload.posts_per_day,
        commentsPerDay: payload.comments_per_day,
        paywallEnabled: Boolean(payload.paywall_enabled),
        lastActivityAt: payload.last_activity_at ?? null,
    };
}

function mapDetail(payload: CommunityDetailPayload): CommunityDetail {
    return {
        ...mapSummary(payload),
        description: payload.description ?? null,
        category: payload.category ?? null,
        createdAt: payload.created_at,
        owners: (payload.owners ?? []).map((owner) => ({
            id: owner.id,
            name: owner.name,
            avatarUrl: owner.avatar_url ?? null,
        })),
    };
}

function mapMetrics(payload: CommunityMetricsPayload): CommunityMetrics {
    return {
        dau: payload.dau,
        wau: payload.wau,
        mau: payload.mau,
        retention7: payload.retention_7,
        retention28: payload.retention_28,
        retention90: payload.retention_90,
        conversionToFirstPost: payload.conversion_first_post,
        mrr: payload.mrr,
        churnRate: payload.churn_rate,
        arpu: payload.arpu,
        ltv: payload.ltv,
        postsPerMinute: payload.posts_per_minute,
        queueSize: payload.queue_size,
    };
}

function mapMember(payload: CommunityMemberPayload): CommunityMemberSummary {
    return {
        id: payload.id,
        name: payload.name,
        avatarUrl: payload.avatar_url ?? null,
        role: payload.role,
        joinedAt: payload.joined_at,
        lastActiveAt: payload.last_active_at ?? null,
    };
}

function mapAttachment(payload: CommunityFeedAttachmentPayload): CommunityFeedAttachment {
    return {
        id: payload.id,
        type: ['image', 'video', 'link', 'document'].includes(payload.type)
            ? (payload.type as CommunityFeedAttachment['type'])
            : 'link',
        url: payload.url,
        thumbnailUrl: payload.thumbnail_url ?? null,
        mimeType: payload.mime_type ?? null,
        title: payload.title ?? null,
        description: payload.description ?? null,
    };
}

function mapFeedItem(payload: CommunityFeedItemPayload): CommunityFeedItem {
    const breakdown = Array.isArray(payload.reaction_breakdown)
        ? payload.reaction_breakdown.reduce<Record<string, number>>((carry, entry) => {
              carry[entry.key] = entry.value;
              return carry;
          }, {})
        : payload.reaction_breakdown ?? {};

    return {
        id: payload.id,
        author: {
            id: payload.author.id,
            name: payload.author.name,
            avatarUrl: payload.author.avatar_url ?? null,
            role: payload.author.role ?? null,
        },
        createdAt: payload.created_at,
        visibility: ['public', 'community', 'paid'].includes(payload.visibility)
            ? (payload.visibility as CommunityFeedItem['visibility'])
            : 'community',
        body: payload.body,
        bodyHtml: payload.body_html ?? null,
        likeCount: payload.like_count,
        commentCount: payload.comment_count,
        viewerReaction: payload.viewer_reaction ?? null,
        reactionBreakdown: breakdown,
        attachments: (payload.attachments ?? []).map(mapAttachment),
        paywallTierId: payload.paywall_tier_id ?? null,
    };
}

export function createCommunityService(httpClient: AxiosInstance, manifest: ModuleManifestEntry): CommunityServiceContract {
    const endpoints = manifest.endpoints ?? {};

    const indexEndpoint = endpoints.index ?? '/communities';
    const detailEndpoint = endpoints.show ?? '/communities/{id}';
    const metricsEndpoint = endpoints.metrics ?? '/communities/{id}/metrics';
    const membersEndpoint = endpoints.members ?? '/communities/{id}/members';
    const feedEndpoint = endpoints.feed ?? '/communities/{id}/feed';
    const createPostEndpoint = endpoints.create_post ?? '/communities/{id}/posts';
    const toggleReactionEndpoint = endpoints.toggle_reaction ?? '/communities/{id}/posts/{post}/reactions';

    return {
        async fetchCommunities(filters = {}): Promise<PaginationResult<CommunitySummary>> {
            const response = await httpClient.get<ApiEnvelope<CommunitySummaryPayload[]>>(
                indexEndpoint,
                {
                    params: {
                        query: filters.query,
                        visibility: filters.visibility,
                        paywall: filters.paywall,
                        page_size: filters.pageSize ?? 25,
                        after: filters.cursor ?? undefined,
                    },
                },
            );

            const payload = response.data;
            const items = Array.isArray(payload.data) ? payload.data.map(mapSummary) : [];
            const meta = payload.meta ?? {};

            return {
                items,
                meta: {
                    total: meta.total,
                    nextCursor: meta.next_cursor ?? payload.links?.next ?? null,
                },
            };
        },

        async fetchCommunity(idOrSlug: string | number): Promise<CommunityDetail> {
            const endpoint = compileEndpoint(detailEndpoint, { id: idOrSlug });
            const response = await httpClient.get<ApiEnvelope<CommunityDetailPayload>>(endpoint);
            const payload = response.data;

            return mapDetail(Array.isArray(payload.data) ? payload.data[0] : (payload.data as CommunityDetailPayload));
        },

        async fetchMetrics(idOrSlug: string | number): Promise<CommunityMetrics> {
            const endpoint = compileEndpoint(metricsEndpoint, { id: idOrSlug });
            const response = await httpClient.get<ApiEnvelope<CommunityMetricsPayload>>(endpoint);
            const payload = response.data;

            return mapMetrics(Array.isArray(payload.data) ? payload.data[0] : (payload.data as CommunityMetricsPayload));
        },

        async fetchMembers(idOrSlug: string | number, cursor: string | null = null): Promise<PaginationResult<CommunityMemberSummary>> {
            const endpoint = compileEndpoint(membersEndpoint, { id: idOrSlug });
            const response = await httpClient.get<ApiEnvelope<CommunityMemberPayload[]>>(
                endpoint,
                {
                    params: {
                        after: cursor ?? undefined,
                        page_size: 25,
                    },
                },
            );

            const payload = response.data;
            const items = Array.isArray(payload.data) ? payload.data.map(mapMember) : [];
            const meta = payload.meta ?? {};

            return {
                items,
                meta: {
                    total: meta.total,
                    nextCursor: meta.next_cursor ?? payload.links?.next ?? null,
                },
            };
        },

        async fetchFeed(
            idOrSlug: string | number,
            options: { filter?: string; cursor?: string | null; pageSize?: number } = {},
        ): Promise<PaginationResult<CommunityFeedItem>> {
            const endpoint = compileEndpoint(feedEndpoint, { id: idOrSlug });
            const response = await httpClient.get<ApiEnvelope<CommunityFeedItemPayload[]>>(
                endpoint,
                {
                    params: {
                        filter: options.filter ?? 'new',
                        after: options.cursor ?? undefined,
                        page_size: options.pageSize ?? 25,
                    },
                },
            );

            const payload = response.data;
            const items = Array.isArray(payload.data) ? payload.data.map(mapFeedItem) : [];
            const meta = payload.meta ?? {};

            return {
                items,
                meta: {
                    total: meta.total,
                    nextCursor: meta.next_cursor ?? payload.links?.next ?? null,
                },
            };
        },

        async createPost(
            idOrSlug: string | number,
            payload,
        ): Promise<CommunityFeedItem> {
            const endpoint = compileEndpoint(createPostEndpoint, { id: idOrSlug });
            const formData = new FormData();
            formData.set('body', payload.body);
            formData.set('visibility', payload.visibility ?? 'community');
            if (payload.scheduledAt) {
                formData.set('scheduled_at', payload.scheduledAt);
            }
            if (payload.paywallTierId) {
                formData.set('paywall_tier_id', String(payload.paywallTierId));
            }

            (payload.attachments ?? []).forEach((file, index) => {
                formData.append(`attachments[${index}]`, file);
            });

            const response = await httpClient.post<ApiEnvelope<CommunityFeedItemPayload>>(endpoint, formData, {
                headers: { 'Content-Type': 'multipart/form-data' },
            });

            const data = Array.isArray(response.data.data)
                ? (response.data.data[0] as CommunityFeedItemPayload)
                : (response.data.data as CommunityFeedItemPayload);

            return mapFeedItem(data);
        },

        async toggleReaction(
            idOrSlug: string | number,
            postId: number,
            reaction: string | null,
        ): Promise<CommunityFeedItem> {
            const endpoint = compileEndpoint(toggleReactionEndpoint, { id: idOrSlug, post: postId });
            const response = await httpClient.post<ApiEnvelope<CommunityFeedItemPayload>>(endpoint, {
                reaction: reaction ?? 'none',
            });
            const data = Array.isArray(response.data.data)
                ? (response.data.data[0] as CommunityFeedItemPayload)
                : (response.data.data as CommunityFeedItemPayload);

            return mapFeedItem(data);
        },
    };
}
