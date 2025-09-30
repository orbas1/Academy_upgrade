import { nanoid } from 'nanoid';

export type ToastIntent = 'info' | 'success' | 'warning' | 'error';

export interface ToastMessage {
    id?: string;
    intent?: ToastIntent;
    title?: string;
    message: string;
    durationMs?: number;
    actionLabel?: string;
    onAction?: () => void;
}

type ToastListener = (toast: Required<ToastMessage>) => void;

const listeners = new Set<ToastListener>();

export function pushToast(toast: ToastMessage): string {
    const payload: Required<ToastMessage> = {
        id: toast.id ?? nanoid(),
        intent: toast.intent ?? 'info',
        title: toast.title ?? '',
        message: toast.message,
        durationMs: toast.durationMs ?? 5000,
        actionLabel: toast.actionLabel ?? '',
        onAction: toast.onAction ?? (() => undefined),
    };

    listeners.forEach((listener) => listener(payload));
    return payload.id;
}

export function subscribeToToasts(listener: ToastListener): () => void {
    listeners.add(listener);
    return () => {
        listeners.delete(listener);
    };
}
