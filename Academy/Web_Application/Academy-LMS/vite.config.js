import { defineConfig, splitVendorChunkPlugin } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig(({ mode }) => ({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/performance-hints.js',
            ],
            refresh: true,
        }),
        splitVendorChunkPlugin(),
    ],
    build: {
        target: 'es2018',
        sourcemap: mode !== 'production',
        cssCodeSplit: true,
        chunkSizeWarningLimit: 768,
        rollupOptions: {
            output: {
                manualChunks: (id) => {
                    if (!id.includes('node_modules')) {
                        return undefined;
                    }

                    if (id.includes('alpinejs')) {
                        return 'vendor-alpine';
                    }

                    if (id.includes('axios')) {
                        return 'vendor-axios';
                    }

                    return 'vendor';
                },
            },
        },
    },
    optimizeDeps: {
        include: ['alpinejs', 'axios'],
    },
}));
