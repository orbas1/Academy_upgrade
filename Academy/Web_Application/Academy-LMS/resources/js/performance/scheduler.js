const PREFETCH_FLAG = '__academy_prefetch_initialized__';

const queuePrefetchBootstrap = () =>
    import('./prefetch')
        .then(({ bootstrapPrefetching }) => bootstrapPrefetching())
        .catch((error) => {
            if (import.meta?.env?.DEV) {
                // eslint-disable-next-line no-console
                console.warn('Deferred prefetch initialisation failed', error);
            }
        });

const scheduleBootstrap = () => {
    if ('requestIdleCallback' in window) {
        window.requestIdleCallback(queuePrefetchBootstrap, { timeout: 3000 });
        return;
    }

    window.setTimeout(queuePrefetchBootstrap, 1500);
};

export const schedulePrefetching = () => {
    if (typeof window === 'undefined' || typeof document === 'undefined') {
        return;
    }

    if (window[PREFETCH_FLAG]) {
        return;
    }

    window[PREFETCH_FLAG] = true;

    const start = () => scheduleBootstrap();

    if (document.readyState === 'complete') {
        start();
        return;
    }

    window.addEventListener('load', start, { once: true });
};

export default schedulePrefetching;
