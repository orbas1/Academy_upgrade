<template>
    <teleport to="body">
        <div
            class="pointer-events-none fixed inset-x-0 top-4 z-[1200] flex flex-col items-center gap-3 px-4 sm:items-end sm:px-6"
            role="region"
            aria-live="polite"
        >
            <TransitionGroup name="toast">
                <div
                    v-for="toast in toasts"
                    :key="toast.id"
                    class="pointer-events-auto w-full max-w-sm rounded-xl border border-slate-200 bg-white/95 p-4 shadow-lg backdrop-blur"
                    :class="intentClasses(toast.intent)"
                >
                    <div class="flex items-start gap-3">
                        <span class="text-xl" aria-hidden="true">{{ intentIcon(toast.intent) }}</span>
                        <div class="flex-1 space-y-1">
                            <p v-if="toast.title" class="text-sm font-semibold text-slate-900">{{ toast.title }}</p>
                            <p class="text-sm text-slate-600">{{ toast.message }}</p>
                            <button
                                v-if="toast.actionLabel"
                                type="button"
                                class="text-sm font-semibold text-indigo-600 hover:text-indigo-500"
                                @click="() => handleAction(toast)"
                            >
                                {{ toast.actionLabel }}
                            </button>
                        </div>
                        <button
                            type="button"
                            class="rounded-full p-1 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600"
                            @click="() => dismiss(toast.id)"
                        >
                            <span class="sr-only">Dismiss</span>
                            ✕
                        </button>
                    </div>
                </div>
            </TransitionGroup>
        </div>
    </teleport>
</template>

<script setup lang="ts">
import { onBeforeUnmount, onMounted, reactive } from 'vue';
import { subscribeToToasts, type ToastIntent, type ToastMessage } from '@/core/feedback/toast-bus';

type ActiveToast = Required<ToastMessage> & { timeoutId?: number };

const toasts = reactive<ActiveToast[]>([]);

function intentClasses(intent: ToastIntent): string {
    switch (intent) {
        case 'success':
            return 'border-emerald-200 bg-emerald-50/95';
        case 'warning':
            return 'border-amber-200 bg-amber-50/95';
        case 'error':
            return 'border-rose-200 bg-rose-50/95';
        default:
            return 'border-slate-200 bg-white/95';
    }
}

function intentIcon(intent: ToastIntent): string {
    switch (intent) {
        case 'success':
            return '✅';
        case 'warning':
            return '⚠️';
        case 'error':
            return '⛔️';
        default:
            return '💬';
    }
}

function push(toast: Required<ToastMessage>) {
    const existingIndex = toasts.findIndex((entry) => entry.id === toast.id);
    const payload: ActiveToast = { ...toast };

    if (existingIndex >= 0) {
        const existing = toasts[existingIndex];
        if (existing.timeoutId) {
            window.clearTimeout(existing.timeoutId);
        }
        toasts.splice(existingIndex, 1, payload);
    } else {
        toasts.push(payload);
    }

    payload.timeoutId = window.setTimeout(() => dismiss(payload.id), toast.durationMs);
}

function dismiss(id: string) {
    const index = toasts.findIndex((entry) => entry.id === id);
    if (index >= 0) {
        const toast = toasts[index];
        if (toast.timeoutId) {
            window.clearTimeout(toast.timeoutId);
        }
        toasts.splice(index, 1);
    }
}

function handleAction(toast: ActiveToast) {
    toast.onAction();
    dismiss(toast.id);
}

let unsubscribe: (() => void) | null = null;

onMounted(() => {
    unsubscribe = subscribeToToasts((toast) => push(toast));
});

onBeforeUnmount(() => {
    toasts.forEach((toast) => {
        if (toast.timeoutId) {
            window.clearTimeout(toast.timeoutId);
        }
    });
    if (unsubscribe) {
        unsubscribe();
        unsubscribe = null;
    }
});
</script>

<style scoped>
.toast-enter-active,
.toast-leave-active {
    transition: all 0.2s ease;
}

.toast-enter-from,
.toast-leave-to {
    opacity: 0;
    transform: translateY(-10px) scale(0.95);
}
</style>
