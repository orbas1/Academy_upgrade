<template>
    <div class="space-y-6">
        <UiCard>
            <template #header>
                <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
                    <div class="flex-1 space-y-2">
                        <h1 class="text-xl font-semibold text-slate-900">Communities</h1>
                        <p class="text-sm text-slate-500">{{ totalLabel }}</p>
                    </div>
                    <div class="flex flex-col gap-3 md:w-[360px]">
                        <UiTextInput
                            v-model="searchQuery"
                            type="search"
                            :label="$t('Search communities')"
                            placeholder="Search by name, slug, or owner"
                            @keyup.enter="onSearch"
                        />
                    </div>
                </div>
            </template>
            <div class="flex flex-wrap items-center gap-3">
                <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Visibility</label>
                <select
                    v-model="selectedVisibility"
                    class="rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                    @change="onVisibilityChanged"
                >
                    <option value="all">All</option>
                    <option value="public">Public</option>
                    <option value="private">Private</option>
                    <option value="unlisted">Unlisted</option>
                </select>
                <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Paywall</label>
                <select
                    v-model="selectedPaywall"
                    class="rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                    @change="onPaywallChanged"
                >
                    <option value="all">All</option>
                    <option value="enabled">Enabled</option>
                    <option value="disabled">Disabled</option>
                </select>
                <UiButton
                    class="ml-auto"
                    variant="secondary"
                    size="sm"
                    :loading="communitiesStore.loadingSummaries"
                    @click="refresh"
                >
                    Refresh
                </UiButton>
            </div>
        </UiCard>

        <UiCard>
            <div v-if="communitiesStore.loadingSummaries" class="p-6 text-center text-sm text-slate-500">
                Loading communities…
            </div>
            <div v-else-if="communitiesStore.summariesError" class="p-6 text-sm text-red-600">
                {{ communitiesStore.summariesError }}
            </div>
            <div v-else>
                <ul class="divide-y divide-slate-200">
                    <li v-for="community in communitiesStore.summaries" :key="community.id" class="py-4">
                        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                            <div>
                                <h3 class="text-lg font-semibold text-slate-900">{{ community.name }}</h3>
                                <p class="text-xs text-slate-500">
                                    {{ community.slug }} • {{ community.visibility }}
                                </p>
                                <div class="mt-3 flex flex-wrap gap-4 text-xs text-slate-600">
                                    <span>{{ community.membersCount.toLocaleString() }} members</span>
                                    <span class="text-emerald-600">{{ community.onlineCount.toLocaleString() }} online</span>
                                    <span>{{ community.postsPerDay.toLocaleString() }} posts / day</span>
                                    <span>{{ community.commentsPerDay.toLocaleString() }} comments / day</span>
                                </div>
                            </div>
                            <div class="flex flex-col items-start gap-3 md:items-end">
                                <UiBadge :variant="community.paywallEnabled ? 'default' : 'muted'">
                                    {{ community.paywallEnabled ? 'Paywall enabled' : 'Paywall disabled' }}
                                </UiBadge>
                                <p class="text-xs text-slate-500">
                                    Last activity
                                    <span v-if="community.lastActivityAt">{{ formatRelative(community.lastActivityAt) }}</span>
                                    <span v-else>—</span>
                                </p>
                                <RouterLink
                                    :to="{ name: 'communities.show', params: { id: community.id } }"
                                    class="text-sm font-semibold text-indigo-600 hover:text-indigo-500"
                                >
                                    Manage →
                                </RouterLink>
                            </div>
                        </div>
                    </li>
                </ul>

                <div class="border-t border-slate-200 pt-4 text-sm text-slate-600">
                    <UiButton
                        v-if="communitiesStore.hasMoreCommunities"
                        variant="secondary"
                        :loading="communitiesStore.loadingSummaries"
                        @click="loadMore"
                    >
                        Load more
                    </UiButton>
                    <p v-else class="text-xs text-slate-500">No additional communities.</p>
                </div>
            </div>
        </UiCard>
    </div>
</template>

<script setup lang="ts">
import { RouterLink } from 'vue-router';
import { computed, onMounted, ref } from 'vue';
import { useCommunityStore } from '@/modules/communities/stores/communityStore';
import { UiBadge, UiButton, UiCard, UiTextInput } from '@/admin/ui';

const communitiesStore = useCommunityStore();

const searchQuery = ref('');
const selectedVisibility = ref(communitiesStore.filters.visibility ?? 'all');
const selectedPaywall = ref(communitiesStore.filters.paywall ?? 'all');

const totalLabel = computed(() => {
    if (communitiesStore.totalCommunities === undefined) {
        return 'Realtime metrics for community health and performance.';
    }

    const total = communitiesStore.totalCommunities;
    return `${total?.toLocaleString() ?? '0'} communities in scope`;
});

function formatRelative(timestamp: string): string {
    const date = new Date(timestamp);
    return date.toLocaleString();
}

async function refresh() {
    await communitiesStore.refreshCommunities();
}

async function onSearch() {
    await communitiesStore.search(searchQuery.value);
}

async function onVisibilityChanged() {
    await communitiesStore.setVisibility(selectedVisibility.value as typeof communitiesStore.filters.visibility);
}

async function onPaywallChanged() {
    await communitiesStore.setPaywallFilter(selectedPaywall.value as typeof communitiesStore.filters.paywall);
}

async function loadMore() {
    await communitiesStore.fetchCommunities();
}

onMounted(async () => {
    if (!communitiesStore.summaries.length) {
        await communitiesStore.refreshCommunities();
    }
});
</script>
