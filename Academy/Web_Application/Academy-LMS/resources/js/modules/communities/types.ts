export interface CommunitySummary {
    id: number;
    name: string;
    slug: string;
    visibility: 'public' | 'private' | 'unlisted';
    membersCount: number;
    onlineCount: number;
    postsPerDay: number;
    commentsPerDay: number;
    paywallEnabled: boolean;
    lastActivityAt: string | null;
}

export interface CommunityDetail extends CommunitySummary {
    description: string | null;
    category: string | null;
    createdAt: string;
    owners: Array<{
        id: number;
        name: string;
        avatarUrl: string | null;
    }>;
}

export interface CommunityMetrics {
    dau: number;
    wau: number;
    mau: number;
    retention7: number;
    retention28: number;
    retention90: number;
    conversionToFirstPost: number;
    mrr: number;
    churnRate: number;
    arpu: number;
    ltv: number;
    postsPerMinute: number;
    queueSize: number;
}

export interface CommunityMemberSummary {
    id: number;
    name: string;
    avatarUrl: string | null;
    role: string;
    joinedAt: string;
    lastActiveAt: string | null;
}

export interface CommunityFeedAttachment {
    id: number;
    type: 'image' | 'video' | 'link' | 'document';
    url: string;
    thumbnailUrl?: string | null;
    mimeType?: string | null;
    title?: string | null;
    description?: string | null;
}

export interface CommunityFeedItem {
    id: number;
    author: {
        id: number;
        name: string;
        avatarUrl: string | null;
        role: string | null;
    };
    createdAt: string;
    visibility: 'public' | 'community' | 'paid';
    body: string;
    bodyHtml: string | null;
    likeCount: number;
    commentCount: number;
    viewerReaction: string | null;
    reactionBreakdown: Record<string, number>;
    attachments: CommunityFeedAttachment[];
    paywallTierId: number | null;
}

export interface PaginationResult<T> {
    items: T[];
    meta: {
        total?: number;
        nextCursor?: string | null;
    };
}
