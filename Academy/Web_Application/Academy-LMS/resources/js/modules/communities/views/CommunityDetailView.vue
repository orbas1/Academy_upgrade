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

            <FeedComposer
                :submitting="composerSubmitting"
                :prefill="composerPrefill"
                @submit="handleComposerSubmit"
            />
            <div
                v-if="composerError"
                class="rounded-lg border border-red-100 bg-red-50 p-3 text-sm text-red-600"
            >
                {{ composerError }}
            </div>

            <div
                v-if="feedIssue"
                class="mt-4 rounded-lg border p-4 text-sm"
                :class="issueClasses(feedIssue.kind)"
            >
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="space-y-1">
                        <p class="text-sm font-semibold text-slate-900">{{ issueTitle(feedIssue.kind) }}</p>
                        <p class="text-sm text-slate-600">{{ feedIssue.message }}</p>
                        <p v-if="feedIssue.details" class="text-xs text-slate-500">{{ feedIssue.details }}</p>
                        <p v-if="feedIssue.retryAt" class="text-xs text-slate-500">
                            Retry scheduled {{ formatRelative(feedIssue.retryAt) }}
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <UiButton
                            v-if="feedIssue.kind === 'offline'"
                            size="sm"
                            variant="secondary"
                            :disabled="!isOnline"
                            @click="retryFeed"
                        >
                            Retry now
                        </UiButton>
                        <UiButton
                            v-else-if="feedIssue.kind === 'forbidden'"
                            size="sm"
                            variant="secondary"
                            @click="() => openInsightsPanel('members')"
                        >
                            Review member access
                        </UiButton>
                        <UiButton
                            v-else-if="feedIssue.kind === 'paywall'"
                            size="sm"
                            variant="secondary"
                            @click="() => openInsightsPanel('paywalls')"
                        >
                            Manage tiers
                        </UiButton>
                        <UiButton
                            v-else-if="feedIssue.kind === 'moderation_hold'"
                            size="sm"
                            variant="secondary"
                            @click="() => openInsightsPanel('moderation')"
                        >
                            Open moderation queue
                        </UiButton>
                        <UiButton
                            v-else-if="feedIssue.kind === 'rate_limited'"
                            size="sm"
                            variant="secondary"
                            :disabled="!canRetryRateLimited"
                            @click="retryFeed"
                        >
                            Retry now
                        </UiButton>
                        <UiButton
                            v-else-if="feedIssue.kind === 'error'"
                            size="sm"
                            variant="secondary"
                            @click="retryFeed"
                        >
                            Retry feed
                        </UiButton>
                    </div>
                </div>
            </div>

            <div v-if="feedError" class="mt-4 rounded-lg border border-red-100 bg-red-50 p-4 text-sm text-red-600">
                {{ feedError }}
            </div>

            <div v-if="store.loadingFeed && !feed.length" class="mt-4 text-sm text-slate-500">Loading feed…</div>
            <UiEmptyState v-else-if="!feed.length">
                <template #title>No posts yet</template>
                Encourage moderators to share a welcome note or scheduled update.
                <template #actions>
                    <UiButton
                        v-for="starter in smartStarters"
                        :key="starter.label"
                        size="sm"
                        variant="secondary"
                        @click="() => applyStarter(starter.body)"
                    >
                        {{ starter.label }}
                    </UiButton>
                </template>
            </UiEmptyState>
            <div v-else class="mt-4 space-y-4">
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
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
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
import { pushToast } from '@/core/feedback/toast-bus';
import { useNetworkStatus } from '@/core/composables/useNetworkStatus';

type FeedIssueKind = 'offline' | 'forbidden' | 'paywall' | 'moderation_hold' | 'rate_limited' | 'error';
type FeedIssueState = {
    kind: FeedIssueKind;
    message: string;
    details?: string | null;
    retryAt?: Date | null;
};

const route = useRoute();
const router = useRouter();
const store = useCommunityStore();

const community = computed(() => store.activeCommunity);
const metrics = computed(() => store.metrics);
const feed = computed(() => store.feedItems);
const feedError = computed(() => store.feedError);
const feedIssue = computed<FeedIssueState | null>(() => store.feedIssue as FeedIssueState | null);
const lastFeedUpdated = computed(() => feed.value[0]?.createdAt ?? new Date().toISOString());

const composerSubmitting = ref(false);
const composerError = ref<string | null>(null);
const composerPrefill = ref<string | null>(null);
const reactionModalOpen = ref(false);
const reactionModalItem = ref<CommunityFeedItem | null>(null);
const smartStarters = [
    {
        label: 'Post a welcome note',
        body: '🎉 Welcome to our community! Introduce yourself below and share what you are hoping to learn this week.',
    },
    {
        label: 'Share weekly goals',
        body: '📅 Weekly focus: 1) Ship one resource 2) Celebrate a member win 3) Ask a thoughtful question. What will you tackle?',
    },
    {
        label: 'Kick off a discussion',
        body: '💬 Question of the day: What is one process in your workflow that became dramatically easier after joining this group?',
    },
];
const { isOnline } = useNetworkStatus();

const canRetryRateLimited = computed(() => {
    if (!feedIssue.value || feedIssue.value.kind !== 'rate_limited') {
        return true;
    }

    if (!feedIssue.value.retryAt) {
        return true;
    }

    return feedIssue.value.retryAt.getTime() <= Date.now();
});

let rateLimitTimer: number | null = null;

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

function formatRelative(timestamp: string | Date): string {
    const date = typeof timestamp === 'string' ? new Date(timestamp) : new Date(timestamp);
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

async function retryFeed() {
    if (!community.value) {
        return;
    }

    await refreshFeed();
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
        pushToast({ intent: 'success', message: 'Update published to the community feed.' });
    } catch (error) {
        const message = error instanceof Error ? error.message : 'Unable to publish post. Please try again.';
        composerError.value = message;
        pushToast({ intent: 'error', title: 'Publish failed', message });
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

function issueTitle(kind: FeedIssueKind): string {
    switch (kind) {
        case 'offline':
            return 'Working offline';
        case 'forbidden':
            return 'Membership required';
        case 'paywall':
            return 'Paywall protected feed';
        case 'moderation_hold':
            return 'Feed locked for moderation';
        case 'rate_limited':
            return 'Rate limited';
        case 'error':
        default:
            return 'Feed unavailable';
    }
}

function issueClasses(kind: FeedIssueKind): string {
    switch (kind) {
        case 'offline':
            return 'border-amber-200 bg-amber-50';
        case 'forbidden':
        case 'paywall':
            return 'border-indigo-200 bg-indigo-50';
        case 'moderation_hold':
            return 'border-rose-200 bg-rose-50';
        case 'rate_limited':
            return 'border-emerald-200 bg-emerald-50';
        case 'error':
        default:
            return 'border-rose-200 bg-rose-50';
    }
}

async function openInsightsPanel(panel: string) {
    if (!community.value) {
        return;
    }

    await router.push({
        name: 'communities.insights',
        params: { id: community.value.id },
        query: { panel },
    });
}

function applyStarter(body: string) {
    composerPrefill.value = body;
    void nextTick(() => {
        composerPrefill.value = null;
    });
}

onMounted(async () => {
    const identifier = route.params.id as string;
    await store.loadCommunity(identifier);
});

watch(
    () => isOnline.value,
    async (online, previous) => {
        if (previous === undefined) {
            return;
        }
        if (online && feedIssue.value?.kind === 'offline') {
            pushToast({ intent: 'info', message: 'Connection restored. Refreshing feed…' });
            await refreshFeed();
        }
    },
);

watch(
    () => feedIssue.value,
    (issue, previous) => {
        if (rateLimitTimer !== null) {
            window.clearTimeout(rateLimitTimer);
            rateLimitTimer = null;
        }

        if (issue?.kind === 'rate_limited' && issue.retryAt && community.value) {
            const delay = issue.retryAt.getTime() - Date.now();
            if (delay > 0) {
                rateLimitTimer = window.setTimeout(async () => {
                    pushToast({ intent: 'info', message: 'Retrying community feed after rate limit window.' });
                    await refreshFeed();
                }, delay);
            }
        }

        if (issue?.kind === 'error' && issue.message !== previous?.message) {
            pushToast({ intent: 'error', title: 'Feed error', message: issue.message });
        }
    },
);

onBeforeUnmount(() => {
    if (rateLimitTimer !== null) {
        window.clearTimeout(rateLimitTimer);
    }
});
</script>
