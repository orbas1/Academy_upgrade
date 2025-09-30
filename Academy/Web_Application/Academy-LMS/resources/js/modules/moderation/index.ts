import type { ModuleFactory } from '@/core/modules/types';
import { bindModerationManifest } from '@/modules/moderation/stores/moderationQueueStore';

export const createModerationModule: ModuleFactory = ({ manifest }) => {
    bindModerationManifest(manifest);

    const queueRoute = manifest.routes.find((route) => route.name === 'moderation.queue');
    const appealsRoute = manifest.routes.find((route) => route.name === 'moderation.appeals');

    return {
        key: manifest.key,
        navigation: manifest.navigation.map((entry) => ({
            label: entry.label,
            route: entry.route,
            icon: entry.icon ?? 'ph-shield-check',
        })),
        routes: [
            {
                name: queueRoute?.name ?? 'moderation.queue',
                path: queueRoute?.path ?? '/moderation/queue',
                component: () => import('@/modules/moderation/views/ModerationQueueView.vue'),
                meta: {
                    title: 'Moderation queue',
                },
            },
            {
                name: appealsRoute?.name ?? 'moderation.appeals',
                path: appealsRoute?.path ?? '/moderation/appeals',
                component: () => import('@/modules/moderation/views/ModerationAppealsView.vue'),
                meta: {
                    title: 'Appeals',
                },
            },
        ],
    };
};
