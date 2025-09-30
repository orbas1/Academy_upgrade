<template>
    <div class="flex flex-wrap items-center gap-2 text-sm text-slate-600">
        <div class="flex flex-wrap items-center gap-1">
            <button
                v-for="option in options"
                :key="option"
                type="button"
                class="inline-flex items-center gap-1 rounded-full border px-3 py-1 text-xs font-semibold transition"
                :class="[
                    activeReaction === option
                        ? 'border-indigo-500 bg-indigo-50 text-indigo-700'
                        : 'border-slate-200 bg-white hover:border-indigo-300 hover:text-indigo-600',
                ]"
                @click="toggle(option)"
            >
                <span>{{ emoji(option) }}</span>
                <span>{{ optionLabel(option) }}</span>
                <span class="text-slate-400" aria-hidden="true">•</span>
                <span>{{ (reactions[option] ?? 0).toLocaleString() }}</span>
            </button>
        </div>
        <UiButton
            v-if="totalReactions > 0"
            variant="ghost"
            size="sm"
            class="rounded-full"
            @click="emit('showBreakdown')"
        >
            View all {{ totalReactions.toLocaleString() }}
        </UiButton>
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import UiButton from '../../atoms/UiButton.vue';

const props = defineProps<{
    reactions: Record<string, number>;
    activeReaction: string | null;
    options?: string[];
}>();

const emit = defineEmits<{
    (e: 'update:activeReaction', value: string | null): void;
    (e: 'showBreakdown'): void;
}>();

const options = computed(() => props.options ?? ['like', 'celebrate', 'insightful', 'support']);

const totalReactions = computed(() =>
    options.value.reduce((carry, key) => carry + (props.reactions[key] ?? 0), 0),
);

function toggle(option: string) {
    const nextValue = props.activeReaction === option ? null : option;
    emit('update:activeReaction', nextValue);
}

function emoji(option: string): string {
    switch (option) {
        case 'celebrate':
            return '🎉';
        case 'insightful':
            return '💡';
        case 'support':
            return '🤝';
        case 'like':
        default:
            return '👍';
    }
}

function optionLabel(option: string): string {
    switch (option) {
        case 'celebrate':
            return 'Celebrate';
        case 'insightful':
            return 'Insightful';
        case 'support':
            return 'Support';
        case 'like':
        default:
            return 'Like';
    }
}
</script>
