const PREFETCHED_URLS = new Set();
const ANCHOR_CACHE = new WeakSet();

const isDataSaverEnabled = () => {
    if (!('connection' in navigator)) {
        return false;
    }

    const connection = navigator.connection;
    return Boolean(connection.saveData) || ['slow-2g', '2g'].includes(connection.effectiveType || '');
};

const normaliseUrl = (href) => {
    try {
        return new URL(href, window.location.href).toString();
    } catch (error) {
        if (import.meta?.env?.DEV) {
            // eslint-disable-next-line no-console
            console.warn('Skipping prefetch for invalid URL', href, error);
        }

        return null;
    }
};

const isSameOrigin = (url) => {
    try {
        const parsed = new URL(url);
        return parsed.origin === window.location.origin;
    } catch (error) {
        return false;
    }
};

const createPrefetchLink = (url) => {
    if (!url || PREFETCHED_URLS.has(url)) {
        return;
    }

    const link = document.createElement('link');
    link.rel = 'prefetch';
    link.href = url;
    link.as = 'document';
    link.crossOrigin = 'anonymous';
    link.fetchPriority = 'low';

    link.addEventListener(
        'error',
        () => {
            PREFETCHED_URLS.delete(url);
            link.remove();
        },
        { once: true }
    );

    document.head.appendChild(link);
    PREFETCHED_URLS.add(url);
};

const shouldPrefetch = (anchor) => {
    if (!(anchor instanceof HTMLAnchorElement)) {
        return false;
    }

    const href = anchor.getAttribute('href');

    if (!href || href.startsWith('#') || href.startsWith('javascript:')) {
        return false;
    }

    if (anchor.hasAttribute('download') || anchor.getAttribute('rel')?.includes('external')) {
        return false;
    }

    if (anchor.dataset.prefetch === 'off') {
        return false;
    }

    const url = normaliseUrl(href);

    if (!url || !isSameOrigin(url)) {
        return false;
    }

    if (url === window.location.href) {
        return false;
    }

    if (anchor.dataset.prefetch === 'on') {
        return true;
    }

    return anchor.closest('[data-prefetch-scope="off"]') === null;
};

const watchAnchor = (anchor, observer) => {
    if (ANCHOR_CACHE.has(anchor) || !shouldPrefetch(anchor)) {
        return;
    }

    ANCHOR_CACHE.add(anchor);

    const triggerPrefetch = () => {
        const url = normaliseUrl(anchor.href);
        createPrefetchLink(url);
    };

    anchor.addEventListener('mouseenter', triggerPrefetch, { passive: true, once: true });
    anchor.addEventListener('focus', triggerPrefetch, { passive: true, once: true });

    if (observer) {
        observer.observe(anchor);
    }
};

const bootstrapMutationObserver = (observer) => {
    if (!('MutationObserver' in window) || !document.body) {
        return null;
    }

    const mutationObserver = new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
            mutation.addedNodes.forEach((node) => {
                if (node instanceof HTMLAnchorElement) {
                    watchAnchor(node, observer);
                    return;
                }

                if (node instanceof HTMLElement) {
                    node.querySelectorAll('a[href]').forEach((anchor) => watchAnchor(anchor, observer));
                }
            });
        });
    });

    mutationObserver.observe(document.body, { childList: true, subtree: true });
    return mutationObserver;
};

const buildIntersectionObserver = () => {
    if (!('IntersectionObserver' in window)) {
        return null;
    }

    return new IntersectionObserver(
        (entries, observer) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting) {
                    return;
                }

                const target = entry.target;

                if (target instanceof HTMLAnchorElement) {
                    const url = normaliseUrl(target.href);
                    createPrefetchLink(url);
                }

                observer.unobserve(entry.target);
            });
        },
        { rootMargin: '20% 0px' }
    );
};

export const bootstrapPrefetching = () => {
    if (typeof window === 'undefined' || typeof document === 'undefined') {
        return;
    }

    if (isDataSaverEnabled()) {
        return;
    }

    const observer = buildIntersectionObserver();

    document.querySelectorAll('a[href]').forEach((anchor) => watchAnchor(anchor, observer));

    const mutationObserver = bootstrapMutationObserver(observer);

    if (import.meta?.hot) {
        import.meta.hot.dispose(() => {
            mutationObserver?.disconnect();
            observer?.disconnect?.();
            PREFETCHED_URLS.clear();
            ANCHOR_CACHE.clear();
        });
    }

    if (!observer) {
        window.setTimeout(() => {
            Array.from(document.querySelectorAll('a[href]'))
                .slice(0, 5)
                .forEach((anchor) => {
                    const url = normaliseUrl(anchor.href);
                    createPrefetchLink(url);
                });
        }, 2000);
    }
};

export default bootstrapPrefetching;
