import { createRouter, createWebHistory } from 'vue-router';
import { appContext } from '@/core/context/app-context';
import { moduleRoutes } from '@/core/modules/module-registry';

const fallbackRoute = moduleRoutes[0]?.path ?? '/communities';

const router = createRouter({
    history: createWebHistory(appContext.spaBasePath),
    routes: [
        ...moduleRoutes,
        {
            path: '/',
            redirect: fallbackRoute,
        },
        {
            path: '/:pathMatch(.*)*',
            name: 'not-found',
            component: () => import('@/admin/views/NotFoundView.vue'),
            meta: {
                title: 'Not found',
            },
        },
    ],
});

router.afterEach((to) => {
    const title = (to.meta?.title as string | undefined) ?? 'Communities admin';
    document.title = `${title} • Communities Control Center`;
});

export default router;
