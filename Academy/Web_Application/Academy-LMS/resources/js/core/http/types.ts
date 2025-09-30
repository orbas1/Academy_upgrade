export interface ApiError {
    code?: string;
    message: string;
}

export interface PaginationMeta {
    total?: number;
    per_page?: number;
    current_page?: number;
    next_cursor?: string | null;
    prev_cursor?: string | null;
}

export interface ApiEnvelope<T> {
    data: T;
    meta?: PaginationMeta;
    links?: Record<string, string | null | undefined>;
    errors?: ApiError[];
    message?: string;
}
