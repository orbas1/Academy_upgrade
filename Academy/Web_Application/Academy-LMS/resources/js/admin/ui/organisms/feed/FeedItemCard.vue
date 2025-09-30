<template>
    <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition hover:shadow-md">
        <header class="flex items-start gap-3">
            <UiAvatar :name="item.author.name" :src="item.author.avatarUrl" size="sm" />
            <div class="flex-1">
                <div class="flex flex-wrap items-center gap-x-2 gap-y-1 text-sm">
                    <span class="font-semibold text-slate-900">{{ item.author.name }}</span>
                    <span v-if="item.author.role" class="rounded-full bg-slate-100 px-2 py-0.5 text-xs text-slate-500">
                        {{ item.author.role }}
                    </span>
                    <UiBadge :variant="visibilityBadge.variant">{{ visibilityBadge.label }}</UiBadge>
                </div>
                <p class="text-xs text-slate-500">{{ relativeCreatedAt }}</p>
            </div>
            <slot name="menu" />
        </header>
        <section class="mt-4 space-y-4 text-sm leading-6 text-slate-700">
            <div v-if="sanitizedHtml" v-html="sanitizedHtml" class="prose prose-sm max-w-none" />
            <p v-else class="whitespace-pre-line">{{ item.body }}</p>

            <div v-if="item.attachments.length" class="grid gap-3 sm:grid-cols-2">
                <figure
                    v-for="attachment in item.attachments"
                    :key="attachment.id"
                    class="overflow-hidden rounded-xl border border-slate-200"
                >
                    <img
                        v-if="attachment.type === 'image'"
                        :src="attachment.thumbnailUrl ?? attachment.url"
                        :alt="attachment.title ?? 'Attachment'"
                        class="h-40 w-full object-cover"
                    />
                    <video v-else-if="attachment.type === 'video'" class="h-40 w-full object-cover" controls>
                        <source :src="attachment.url" :type="attachment.mimeType ?? 'video/mp4'" />
                    </video>
                    <div v-else class="space-y-2 p-4">
                        <p class="text-sm font-semibold text-slate-800">{{ attachment.title ?? attachment.url }}</p>
                        <p v-if="attachment.description" class="text-xs text-slate-500">{{ attachment.description }}</p>
                        <a
                            :href="attachment.url"
                            class="inline-flex items-center gap-1 text-xs font-semibold text-indigo-600 hover:text-indigo-500"
                            rel="noopener noreferrer"
                            target="_blank"
                        >
                            Open link →
                        </a>
                    </div>
                </figure>
            </div>
        </section>
        <footer class="mt-6 space-y-4">
            <ReactionBar
                :reactions="item.reactionBreakdown"
                :active-reaction="activeReaction"
                @update:active-reaction="onReactionChanged"
                @show-breakdown="emit('showBreakdown', item)"
            />
            <div class="flex flex-wrap items-center gap-3 text-xs text-slate-500">
                <button type="button" class="hover:text-indigo-600" @click="emit('toggleComments', item)">
                    {{ item.commentCount.toLocaleString() }} comments
                </button>
                <span aria-hidden="true">•</span>
                <span>{{ item.likeCount.toLocaleString() }} reactions</span>
            </div>
        </footer>
    </article>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import UiAvatar from '../../atoms/UiAvatar.vue';
import UiBadge from '../../atoms/UiBadge.vue';
import ReactionBar from './ReactionBar.vue';
import type { CommunityFeedItem } from '@/modules/communities/types';

const props = defineProps<{
    item: CommunityFeedItem;
}>();

const emit = defineEmits<{
    (e: 'toggleReaction', item: CommunityFeedItem, reaction: string | null): void;
    (e: 'toggleComments', item: CommunityFeedItem): void;
    (e: 'showBreakdown', item: CommunityFeedItem): void;
}>();

const relativeCreatedAt = computed(() => {
    const created = new Date(props.item.createdAt);
    const now = new Date();
    const diffMs = created.getTime() - now.getTime();
    const diffMinutes = Math.round(diffMs / 60000);
    const formatter = new Intl.RelativeTimeFormat('en', { numeric: 'auto' });

    if (Math.abs(diffMinutes) < 60) {
        return formatter.format(diffMinutes, 'minute');
    }
    const diffHours = Math.round(diffMinutes / 60);
    if (Math.abs(diffHours) < 24) {
        return formatter.format(diffHours, 'hour');
    }
    const diffDays = Math.round(diffHours / 24);
    return formatter.format(diffDays, 'day');
});

const visibilityBadge = computed(() => {
    switch (props.item.visibility) {
        case 'public':
            return { variant: 'success', label: 'Public' } as const;
        case 'paid':
            return { variant: 'warning', label: 'Paid tier' } as const;
        case 'community':
        default:
            return { variant: 'muted', label: 'Members' } as const;
    }
});

const activeReaction = computed(() => props.item.viewerReaction);

function onReactionChanged(value: string | null) {
    emit('toggleReaction', props.item, value);
}

const sanitizedHtml = computed(() => {
    if (!props.item.bodyHtml) {
        return null;
    }

    if (typeof window === 'undefined') {
        return props.item.bodyHtml;
    }

    const parser = new DOMParser();
    const doc = parser.parseFromString(props.item.bodyHtml, 'text/html');
    const allowed = new Set(['A', 'P', 'EM', 'STRONG', 'UL', 'OL', 'LI', 'CODE', 'PRE', 'BLOCKQUOTE', 'SPAN', 'BR']);

    const walker = document.createTreeWalker(doc.body, NodeFilter.SHOW_ELEMENT, null);
    const toRemove: Element[] = [];

    let node = walker.nextNode() as Element | null;
    while (node) {
        if (!allowed.has(node.tagName)) {
            toRemove.push(node);
        } else {
            for (const attr of Array.from(node.attributes)) {
                if (!['href', 'target', 'rel', 'class'].includes(attr.name)) {
                    node.removeAttribute(attr.name);
                }
                if (attr.name === 'href' && attr.value.startsWith('javascript:')) {
                    node.setAttribute('href', '#');
                }
            }
        }
        node = walker.nextNode() as Element | null;
    }

    toRemove.forEach((element) => {
        const textNode = doc.createTextNode(element.textContent ?? '');
        element.replaceWith(textNode);
    });

    return doc.body.innerHTML;
});
</script>
