import '../css/app.css';
import './bootstrap';

// Register PWA service worker from the root (optimized non-blocking delay)
if ('serviceWorker' in navigator && import.meta.env.PROD) {
    window.addEventListener('load', () => {
        const registerSW = () => {
            navigator.serviceWorker.register('/sw.js').catch(err => {
                console.error('SW registration failed: ', err);
            });
        };
        // Defer SW registration to prevent massive PWA background precaching 
        // from clogging the network/CPU during critical page interactivity audits
        if ('requestIdleCallback' in window) {
            window.requestIdleCallback(() => setTimeout(registerSW, 2000));
        } else {
            setTimeout(registerSW, 4000);
        }
    });
}


// ─── PWA: capture beforeinstallprompt BEFORE Vue mounts ───────────────────────
// The event fires early (sometimes before Inertia/Vue is ready), so we store
// it globally. PWAInstallPrompt.vue reads from window.__pwaPrompt.
window.__pwaPrompt = null;
window.__pwaInstalled = false;

window.addEventListener('beforeinstallprompt', (e) => {
    e.preventDefault();
    window.__pwaPrompt = e;
    // Dispatch a custom event so any already-mounted component can react
    window.dispatchEvent(new CustomEvent('pwa-prompt-ready'));
});

window.addEventListener('appinstalled', () => {
    window.__pwaPrompt = null;
    window.__pwaInstalled = true;
    window.dispatchEvent(new CustomEvent('pwa-installed'));
});
// ──────────────────────────────────────────────────────────────────────────────

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});
