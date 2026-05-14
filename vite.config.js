import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import { VitePWA } from 'vite-plugin-pwa';

export default defineConfig({
    plugins: [
        laravel({
            input: 'resources/js/app.js',
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
        VitePWA({
            registerType: 'autoUpdate',
            injectRegister: false, // We handle SW registration manually in app.js

            // Workbox config — cache the app shell + assets
            workbox: {
                globPatterns: ['**/*.{js,css,html,ico,png,webp,svg,woff2}'],
                cleanupOutdatedCaches: true,
                clientsClaim: true,
                skipWaiting: true,
                // Don't try to precache Laravel's server-rendered routes
                navigateFallback: null,
                runtimeCaching: [
                    {
                        urlPattern: /^https:\/\/fonts\.(bunny|googleapis)\.net\/.*/i,
                        handler: 'CacheFirst',
                        options: {
                            cacheName: 'google-fonts-cache',
                            expiration: { maxEntries: 10, maxAgeSeconds: 60 * 60 * 24 * 365 },
                            cacheableResponse: { statuses: [0, 200] },
                        },
                    },
                    {
                        urlPattern: /\/assets\/images\/.*/i,
                        handler: 'CacheFirst',
                        options: {
                            cacheName: 'static-image-cache',
                            expiration: { maxEntries: 30, maxAgeSeconds: 60 * 60 * 24 * 30 },
                            cacheableResponse: { statuses: [0, 200] },
                        },
                    },
                ],
            },

            manifest: {
                name: 'FluxMedia Creative Suite',
                short_name: 'FluxMedia',
                description: 'Professional Image Transformation & QR Intelligence Studio',
                theme_color: '#0B0F19',
                background_color: '#0B0F19',
                display: 'standalone',
                start_url: '/',
                scope: '/',
                orientation: 'portrait-primary',
                icons: [
                    {
                        src: '/assets/images/pwa-192.webp',
                        sizes: '192x192',
                        type: 'image/webp',
                    },
                    {
                        src: '/assets/images/pwa-512.webp',
                        sizes: '512x512',
                        type: 'image/webp',
                    },
                    {
                        src: '/assets/images/pwa-512.webp',
                        sizes: '512x512',
                        type: 'image/webp',
                        purpose: 'maskable',
                    },
                ],
            },
        }),
    ],
});
