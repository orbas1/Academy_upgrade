import './bootstrap';
import { schedulePrefetching } from './performance/scheduler';

const bootAlpine = async () => {
    const { default: Alpine } = await import('alpinejs');

    window.Alpine = Alpine;
    Alpine.start();
};

const mountAlpineOnce = () => {
    bootAlpine().catch((error) => {
        if (import.meta.env.DEV) {
            // eslint-disable-next-line no-console
            console.warn('Unable to bootstrap Alpine.js', error);
        }
    });
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', mountAlpineOnce, { once: true });
} else {
    mountAlpineOnce();
}

schedulePrefetching();
