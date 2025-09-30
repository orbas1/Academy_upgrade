import { defineConfig, splitVendorChunkPlugin } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import path from 'node:path';

export default defineConfig(({ mode }) => ({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/performance-hints.js',
                'resources/js/accessibility.js',
                'resources/js/admin/main.ts',
            ],
            refresh: true,
        }),
        vue({
            script: {
                defineModel: true,
            },
        }),
        splitVendorChunkPlugin(),
    ],
    resolve: {
        alias: {
            '@': path.resolve(__dirname, 'resources/js'),
        },
    },
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

                    if (id.includes('vue')) {
                        return 'vendor-vue';
                    }

                    if (id.includes('pinia')) {
                        return 'vendor-pinia';
                    }

                    if (id.includes('@vueuse')) {
                        return 'vendor-vueuse';
                    }

                    return 'vendor';
                },
            },
        },
    },
    optimizeDeps: {
        include: ['alpinejs', 'axios', 'vue', 'vue-router', 'pinia', '@vueuse/core'],
    },
}));
