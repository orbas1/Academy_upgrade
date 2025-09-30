<template>
    <div v-if="!community" class="space-y-6">
        <UiCard>
            <div class="space-y-3">
                <div class="h-6 w-48 animate-pulse rounded bg-slate-200" />
                <div class="h-4 w-64 animate-pulse rounded bg-slate-100" />
            </div>
        </UiCard>
        <UiCard>
            <div class="h-40 animate-pulse rounded-xl bg-slate-100" />
        </UiCard>
    </div>
    <div v-else class="space-y-6">
        <UiCard>
            <template #header>
                <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h1 class="text-2xl font-semibold text-slate-900">{{ community.name }}</h1>
                        <p class="text-sm text-slate-500">
                            {{ community.slug }} • {{ community.visibility }} • Created {{ formatRelative(community.createdAt) }}
                        </p>
                    </div>
                    <UiBadge :variant="community.paywallEnabled ? 'default' : 'muted'">
                        {{ community.paywallEnabled ? 'Paywall enabled' : 'Paywall disabled' }}
                    </UiBadge>
                </div>
            </template>
            <p v-if="community.description" class="text-sm leading-6 text-slate-600">
                {{ community.description }}
            </p>
            <div class="mt-4 grid gap-4 md:grid-cols-3">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Members</p>
                    <p class="mt-1 text-3xl font-bold text-slate-900">{{ community.membersCount.toLocaleString() }}</p>
                    <p class="text-sm text-emerald-600">{{ community.onlineCount.toLocaleString() }} online right now</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Engagement</p>
                    <p class="mt-1 text-lg font-semibold text-slate-900">{{ community.postsPerDay.toLocaleString() }} posts / day</p>
                    <p class="text-sm text-slate-500">{{ community.commentsPerDay.toLocaleString() }} comments / day</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Last activity</p>
                    <p class="mt-1 text-lg font-semibold text-slate-900">
                        <span v-if="community.lastActivityAt">{{ formatRelative(community.lastActivityAt) }}</span>
                        <span v-else>—</span>
                    </p>
                </div>
            </div>
        </UiCard>

        <UiCard v-if="metrics">
            <template #header>
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">Health metrics</h2>
                    <p class="text-sm text-slate-500">Realtime signals for retention, monetization, and queue load.</p>
                </div>
            </template>
            <dl class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <UiMetricTile>
                    <template #label>DAU / WAU / MAU</template>
                    {{ metrics.dau }}/{{ metrics.wau }}/{{ metrics.mau }}
                </UiMetricTile>
                <UiMetricTile>
                    <template #label>Retention 7/28/90</template>
                    {{ toPercent(metrics.retention7) }} / {{ toPercent(metrics.retention28) }} /
                    {{ toPercent(metrics.retention90) }}
                </UiMetricTile>
                <UiMetricTile>
                    <template #label>Conversion to first post</template>
                    {{ toPercent(metrics.conversionToFirstPost) }}
                </UiMetricTile>
                <UiMetricTile>
                    <template #label>MRR / Churn</template>
                    {{ currency(metrics.mrr) }} / {{ toPercent(metrics.churnRate) }}
                </UiMetricTile>
                <UiMetricTile>
                    <template #label>ARPU / LTV</template>
                    {{ currency(metrics.arpu) }} / {{ currency(metrics.ltv) }}
                </UiMetricTile>
                <UiMetricTile>
                    <template #label>Posts per minute</template>
                    {{ metrics.postsPerMinute.toFixed(2) }}
                </UiMetricTile>
                <UiMetricTile>
                    <template #label>Moderation queue size</template>
                    {{ metrics.queueSize.toLocaleString() }}
                </UiMetricTile>
            </dl>
        </UiCard>

        <UiCard>
            <template #header>
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-semibold text-slate-900">Feed &amp; announcements</h2>
                        <p class="text-sm text-slate-500">Broadcast updates and monitor community sentiment.</p>
                    </div>
                    <UiButton
                        variant="secondary"
                        size="sm"
                        :loading="store.loadingFeed"
                        @click="refreshFeed"
                    >
                        Refresh feed
                    </UiButton>
                </div>
            </template>

            <FeedComposer :submitting="composerSubmitting" @submit="handleComposerSubmit" />
            <div
                v-if="composerError"
                class="rounded-lg border border-red-100 bg-red-50 p-3 text-sm text-red-600"
            >
                {{ composerError }}
            </div>

            <div v-if="feedError" class="rounded-lg border border-red-100 bg-red-50 p-4 text-sm text-red-600">
                {{ feedError }}
            </div>
            <div v-else-if="store.loadingFeed && !feed.length" class="text-sm text-slate-500">Loading feed…</div>
            <UiEmptyState v-else-if="!feed.length">
                <template #title>No posts yet</template>
                Encourage moderators to share a welcome note or scheduled update.
            </UiEmptyState>
            <div v-else class="space-y-4">
                <FeedItemCard
                    v-for="item in feed"
                    :key="item.id"
                    :item="item"
                    @toggle-reaction="onReactionToggled"
                    @toggle-comments="onCommentsRequested"
                    @show-breakdown="openReactionBreakdown"
                />
            </div>

            <template #footer>
                <div class="flex items-center justify-between">
                    <p class="text-xs text-slate-500">
                        Last refreshed {{ formatRelative(lastFeedUpdated) }}
                    </p>
                    <UiButton
                        v-if="store.hasMoreFeed"
                        variant="secondary"
                        size="sm"
                        :loading="store.loadingFeed"
                        @click="loadMoreFeed"
                    >
                        Load more
                    </UiButton>
                    <p v-else class="text-xs text-slate-500">All caught up.</p>
                </div>
            </template>
        </UiCard>

        <UiCard>
            <template #header>
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">Members</h2>
                    <p class="text-sm text-slate-500">Latest approved members with last seen timestamps.</p>
                </div>
            </template>
            <div v-if="store.loadingMembers" class="text-sm text-slate-500">Loading members…</div>
            <div v-else-if="store.membersError" class="rounded-lg border border-red-100 bg-red-50 p-4 text-sm text-red-600">
                {{ store.membersError }}
            </div>
            <div v-else class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th scope="col" class="px-4 py-3">Member</th>
                            <th scope="col" class="px-4 py-3">Role</th>
                            <th scope="col" class="px-4 py-3">Joined</th>
                            <th scope="col" class="px-4 py-3">Last active</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        <tr v-for="member in store.members" :key="member.id">
                            <td class="px-4 py-3 text-slate-700">{{ member.name }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ member.role }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ formatRelative(member.joinedAt) }}</td>
                            <td class="px-4 py-3 text-slate-600">
                                <span v-if="member.lastActiveAt">{{ formatRelative(member.lastActiveAt) }}</span>
                                <span v-else class="text-slate-400">Not seen</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <div class="border-t border-slate-200 px-4 py-3">
                    <UiButton
                        v-if="store.membersCursor"
                        variant="secondary"
                        size="sm"
                        :loading="store.loadingMembers"
                        @click="loadMoreMembers"
                    >
                        Load more members
                    </UiButton>
                    <p v-else class="text-xs text-slate-500">All members loaded.</p>
                </div>
            </div>
        </UiCard>

        <UiModal v-model="reactionModalOpen" @close="reactionModalItem = null">
            <template #title>Reaction breakdown</template>
            <template #subtitle>
                {{ reactionModalItem?.author.name }} • {{ formatRelative(reactionModalItem?.createdAt ?? new Date().toISOString()) }}
            </template>
            <div class="space-y-2">
                <div
                    v-for="entry in reactionEntries"
                    :key="entry.reaction"
                    class="flex items-center justify-between rounded-lg border border-slate-200 px-4 py-2"
                >
                    <span class="font-semibold text-slate-700">{{ entry.label }}</span>
                    <span class="text-sm text-slate-500">{{ entry.count.toLocaleString() }}</span>
                </div>
            </div>
            <template #footer>
                <UiButton variant="secondary" @click="reactionModalOpen = false">Close</UiButton>
            </template>
        </UiModal>
    </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { useRoute } from 'vue-router';
import {
    FeedComposer,
    FeedItemCard,
    UiBadge,
    UiButton,
    UiCard,
    UiEmptyState,
    UiMetricTile,
    UiModal,
} from '@/admin/ui';
import { useCommunityStore } from '@/modules/communities/stores/communityStore';
import type { CommunityFeedItem } from '@/modules/communities/types';

const route = useRoute();
const store = useCommunityStore();

const community = computed(() => store.activeCommunity);
const metrics = computed(() => store.metrics);
const feed = computed(() => store.feedItems);
const feedError = computed(() => store.feedError);
const lastFeedUpdated = computed(() => feed.value[0]?.createdAt ?? new Date().toISOString());

const composerSubmitting = ref(false);
const composerError = ref<string | null>(null);
const reactionModalOpen = ref(false);
const reactionModalItem = ref<CommunityFeedItem | null>(null);

const reactionEntries = computed(() => {
    if (!reactionModalItem.value) {
        return [] as Array<{ reaction: string; label: string; count: number }>;
    }

    return Object.entries(reactionModalItem.value.reactionBreakdown)
        .map(([key, count]) => ({ reaction: key, label: reactionLabel(key), count }))
        .filter((entry) => entry.count > 0)
        .sort((a, b) => b.count - a.count);
});

function reactionLabel(key: string): string {
    switch (key) {
        case 'celebrate':
            return '🎉 Celebrate';
        case 'insightful':
            return '💡 Insightful';
        case 'support':
            return '🤝 Support';
        case 'like':
        default:
            return '👍 Like';
    }
}

function toPercent(value: number): string {
    return `${(value * 100).toFixed(1)}%`;
}

function currency(value: number): string {
    return value.toLocaleString(undefined, { style: 'currency', currency: 'USD' });
}

function formatRelative(timestamp: string): string {
    const date = new Date(timestamp);
    return date.toLocaleString();
}

async function loadMoreMembers() {
    if (!community.value) {
        return;
    }

    await store.loadMembers(community.value.id);
}

async function refreshFeed() {
    if (!community.value) {
        return;
    }

    await store.refreshFeed(community.value.id);
}

async function loadMoreFeed() {
    if (!community.value) {
        return;
    }

    await store.loadFeed(community.value.id);
}

async function handleComposerSubmit(payload: {
    body: string;
    visibility: 'public' | 'community' | 'paid';
    attachments: File[];
    scheduledAt: string | null;
}) {
    if (!community.value) {
        return;
    }

    composerSubmitting.value = true;
    try {
        await store.createPost(community.value.id, {
            body: payload.body,
            visibility: payload.visibility,
            attachments: payload.attachments,
            scheduledAt: payload.scheduledAt,
        });
        composerError.value = null;
    } catch (error) {
        const message = error instanceof Error ? error.message : 'Unable to publish post. Please try again.';
        composerError.value = message;
    } finally {
        composerSubmitting.value = false;
    }
}

async function onReactionToggled(item: CommunityFeedItem, reaction: string | null) {
    if (!community.value) {
        return;
    }

    await store.toggleReaction(community.value.id, item.id, reaction);
}

function onCommentsRequested(item: CommunityFeedItem) {
    // Future enhancement: open moderation drawer with threaded comments.
    console.info('Open comments for post', item.id);
}

function openReactionBreakdown(item: CommunityFeedItem) {
    reactionModalItem.value = item;
    reactionModalOpen.value = true;
}

onMounted(async () => {
    const identifier = route.params.id as string;
    await store.loadCommunity(identifier);
});
</script>
