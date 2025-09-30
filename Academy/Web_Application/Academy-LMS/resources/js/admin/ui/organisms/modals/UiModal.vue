<template>
    <Teleport to="body">
        <Transition name="fade">
            <div
                v-if="modelValue"
                class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 px-4 py-6"
                role="dialog"
                aria-modal="true"
                @keydown.esc.prevent.stop="close"
            >
                <div
                    ref="dialog"
                    class="relative w-full max-w-2xl rounded-2xl bg-white shadow-2xl focus:outline-none"
                    role="document"
                >
                    <button
                        type="button"
                        class="absolute right-4 top-4 text-slate-400 transition hover:text-slate-600 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500"
                        @click="close"
                    >
                        <span class="sr-only">Close dialog</span>
                        ✕
                    </button>
                    <header v-if="$slots.title" class="border-b border-slate-200 px-6 py-4">
                        <h2 class="text-lg font-semibold text-slate-900">
                            <slot name="title" />
                        </h2>
                        <p v-if="$slots.subtitle" class="mt-1 text-sm text-slate-500">
                            <slot name="subtitle" />
                        </p>
                    </header>
                    <div class="px-6 py-4">
                        <slot />
                    </div>
                    <footer v-if="$slots.footer" class="flex flex-wrap items-center justify-end gap-3 border-t border-slate-200 bg-slate-50 px-6 py-4">
                        <slot name="footer" />
                    </footer>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup lang="ts">
import { nextTick, onMounted, onUnmounted, ref, watch } from 'vue';
import { trapFocus } from '../../utils/focusTrap';

const props = defineProps<{
    modelValue: boolean;
}>();

const emit = defineEmits<{
    (e: 'update:modelValue', value: boolean): void;
    (e: 'close'): void;
}>();

const dialog = ref<HTMLElement | null>(null);
let releaseFocus: (() => void) | null = null;
let previousFocus: HTMLElement | null = null;

watch(
    () => props.modelValue,
    (isOpen) => {
        if (isOpen) {
            nextTick(() => {
                if (!dialog.value) return;
                previousFocus = document.activeElement as HTMLElement | null;
                releaseFocus = trapFocus(dialog.value);
            });
            document.body.classList.add('overflow-hidden');
        } else {
            document.body.classList.remove('overflow-hidden');
            releaseFocus?.();
            releaseFocus = null;
            previousFocus?.focus();
            previousFocus = null;
        }
    },
    { immediate: true },
);

onMounted(() => {
    if (props.modelValue && dialog.value) {
        previousFocus = document.activeElement as HTMLElement | null;
        releaseFocus = trapFocus(dialog.value);
    }
});

onUnmounted(() => {
    releaseFocus?.();
    document.body.classList.remove('overflow-hidden');
    previousFocus?.focus();
});

function close() {
    emit('update:modelValue', false);
    emit('close');
}
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 120ms ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
