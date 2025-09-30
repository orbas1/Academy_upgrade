import type { ModuleFactory } from '@/core/modules/types';
import { bindCommunityManifest } from '@/modules/communities/stores/communityStore';

export const createCommunitiesModule: ModuleFactory = ({ manifest }) => {
    bindCommunityManifest(manifest);

    const indexRoute = manifest.routes.find((route) => route.name === 'communities.index');
    const detailRoute = manifest.routes.find((route) => route.name === 'communities.show');
    const insightsRoute = manifest.routes.find((route) => route.name === 'communities.insights');

    return {
        key: manifest.key,
        navigation: manifest.navigation.map((entry) => ({
            label: entry.label,
            route: entry.route,
            icon: entry.icon ?? 'ph-users-three',
        })),
        routes: [
            {
                name: indexRoute?.name ?? 'communities.index',
                path: indexRoute?.path ?? '/communities',
                component: () => import('@/modules/communities/views/CommunitiesIndexView.vue'),
                meta: {
                    title: 'Communities',
                },
            },
            {
                name: detailRoute?.name ?? 'communities.show',
                path: detailRoute?.path ?? '/communities/:id',
                component: () => import('@/modules/communities/views/CommunityDetailView.vue'),
                meta: {
                    title: 'Community detail',
                },
            },
            {
                name: insightsRoute?.name ?? 'communities.insights',
                path: insightsRoute?.path ?? '/communities/:id/insights',
                component: () => import('@/modules/communities/views/CommunityInsightsView.vue'),
                meta: {
                    title: 'Community insights',
                },
            },
        ],
    };
};
