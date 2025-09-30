import type { RouteComponent, RouteRecordNormalized, Router } from 'vue-router';

type RouteLoader = () => Promise<unknown>;

const PREFETCH_ATTRIBUTE = 'data-route-prefetch';
const PREFETCHED_TARGETS = new Set<string>();
const OBSERVED_ELEMENTS = new WeakSet<Element>();

const isDataSaverEnabled = (): boolean => {
    const connection = (navigator as Navigator & { connection?: { saveData?: boolean; effectiveType?: string } }).connection;
    if (!connection) {
        return false;
    }

    if (connection.saveData) {
        return true;
    }

    const effectiveType = connection.effectiveType ?? '';
    return effectiveType === 'slow-2g' || effectiveType === '2g';
};

const sanitiseTargets = (raw: string | null): string[] => {
    if (!raw) {
        return [];
    }

    return raw
        .split(/[\s,]+/u)
        .map((value) => value.trim())
        .filter((value) => value.length > 0);
};

const resolveLoaderFromComponent = (component: RouteComponent | undefined): RouteLoader | null => {
    if (!component) {
        return null;
    }

    if (typeof component === 'function') {
        return () => Promise.resolve((component as () => Promise<unknown> | unknown)());
    }

    const asyncLoader = (component as { __asyncLoader?: () => Promise<unknown> }).__asyncLoader;

    if (typeof asyncLoader === 'function') {
        return () => asyncLoader();
    }

    return null;
};

const buildLoaderRegistry = (router: Router) => {
    let map = new Map<string, RouteLoader>();

    const registerRoute = (route: RouteRecordNormalized) => {
        const component = route.components?.default ?? route.component;
        const loader = resolveLoaderFromComponent(component);

        if (!loader) {
            return;
        }

        if (route.name) {
            map.set(String(route.name), loader);
        }

        if (route.path) {
            map.set(route.path, loader);
        }
    };

    const rebuild = () => {
        map = new Map<string, RouteLoader>();
        router.getRoutes().forEach(registerRoute);
    };

    rebuild();

    return {
        get: (target: string): RouteLoader | undefined => map.get(target),
        refresh: rebuild,
    };
};

const prefetchTarget = (target: string, loader: RouteLoader | undefined) => {
    if (!loader || PREFETCHED_TARGETS.has(target) || isDataSaverEnabled()) {
        return;
    }

    PREFETCHED_TARGETS.add(target);

    loader()
        .catch((error) => {
            PREFETCHED_TARGETS.delete(target);
            if (import.meta?.env?.DEV) {
                // eslint-disable-next-line no-console
                console.warn(`Route prefetch failed for "${target}"`, error);
            }
        })
        .finally(() => {
            window.setTimeout(() => PREFETCHED_TARGETS.delete(target), 60_000);
        });
};

const createIntersectionObserver = (
    callback: (element: Element) => void,
): IntersectionObserver | null => {
    if (!('IntersectionObserver' in window)) {
        return null;
    }

    return new IntersectionObserver(
        (entries, observer) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting) {
                    return;
                }

                callback(entry.target);
                observer.unobserve(entry.target);
            });
        },
        { rootMargin: '160px 0px' },
    );
};

const bindEventPrefetch = (
    documentRef: Document,
    trigger: (element: Element) => void,
): (() => void) => {
    const handler = (event: Event) => {
        const element = (event.target as Element | null)?.closest?.(`[${PREFETCH_ATTRIBUTE}]`);
        if (!element) {
            return;
        }

        trigger(element);
    };

    const options: AddEventListenerOptions & EventListenerOptions = {
        capture: true,
        passive: true,
    };

    documentRef.addEventListener('mouseenter', handler, options);
    documentRef.addEventListener('touchstart', handler, options);
    documentRef.addEventListener('focusin', handler, true);

    return () => {
        documentRef.removeEventListener('mouseenter', handler, options);
        documentRef.removeEventListener('touchstart', handler, options);
        documentRef.removeEventListener('focusin', handler, true);
    };
};

const observeRouteTargets = (
    observer: IntersectionObserver | null,
    trigger: (element: Element) => void,
) => {
    if (!observer) {
        return trigger;
    }

    return (element: Element) => {
        if (OBSERVED_ELEMENTS.has(element)) {
            return;
        }

        OBSERVED_ELEMENTS.add(element);
        observer.observe(element);
    };
};

const scanForTargets = (trigger: (element: Element) => void) => {
    document.querySelectorAll(`[${PREFETCH_ATTRIBUTE}]`).forEach((element) => trigger(element));
};

const triggerPrefetch = (
    registry: ReturnType<typeof buildLoaderRegistry>,
    element: Element,
) => {
    const targets = sanitiseTargets(element.getAttribute(PREFETCH_ATTRIBUTE));
    targets.forEach((target) => {
        const loader = registry.get(target) ?? registry.get(target.split(':')[0] ?? '');
        prefetchTarget(target, loader);
    });
};

export const setupRouteComponentPrefetch = (router: Router) => {
    if (typeof window === 'undefined' || typeof document === 'undefined') {
        return;
    }

    const registry = buildLoaderRegistry(router);
    const observer = createIntersectionObserver((element) => triggerPrefetch(registry, element));
    const trigger = observeRouteTargets(observer, (element) => triggerPrefetch(registry, element));
    const unbindEvents = bindEventPrefetch(document, (element) => triggerPrefetch(registry, element));

    const mutationObserver = new MutationObserver(() => scanForTargets(trigger));

    const initialise = () => {
        scanForTargets(trigger);
        mutationObserver.observe(document.body, { childList: true, subtree: true });
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initialise, { once: true });
    } else {
        initialise();
    }

    router.afterEach(() => registry.refresh());

    const cleanup = () => {
        mutationObserver.disconnect();
        observer?.disconnect();
        unbindEvents();
        PREFETCHED_TARGETS.clear();
    };

    if (import.meta?.hot) {
        import.meta.hot.dispose(cleanup);
    }
};

export default setupRouteComponentPrefetch;
