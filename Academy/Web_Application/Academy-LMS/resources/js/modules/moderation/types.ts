export interface ModerationReporter {
    id: number;
    name: string;
    avatarUrl: string | null;
}

export interface ModerationTarget {
    id: number;
    type: 'post' | 'comment' | 'member';
    summary: string;
    author: string;
    permalink: string;
}

export interface ModerationReport {
    id: number;
    reason: string;
    status: 'open' | 'snoozed' | 'resolved';
    severity: 'low' | 'medium' | 'high';
    reportedAt: string;
    reporter: ModerationReporter;
    target: ModerationTarget;
    evidenceUrls: string[];
}

export interface ModerationAppeal {
    id: number;
    submittedAt: string;
    memberName: string;
    status: 'pending' | 'approved' | 'denied';
    summary: string;
}

export interface PaginationResult<T> {
    items: T[];
    meta: {
        nextCursor?: string | null;
        total?: number;
    };
}
