import '../css/app.css';
import './bootstrap';

// Register PWA service worker from the root (highly optimized interaction-based loading)
if ('serviceWorker' in navigator && import.meta.env.PROD) {
    let swRegistered = false;
    
    const registerSW = () => {
        if (swRegistered) return;
        swRegistered = true;
        
        // Remove event listeners once registered
        removeInteractionListeners();
        
        navigator.serviceWorker.register('/sw.js').catch(err => {
            console.error('SW registration failed: ', err);
        });
    };

    const interactionEvents = ['mousedown', 'keydown', 'scroll', 'touchstart', 'mousemove'];
    
    const removeInteractionListeners = () => {
        interactionEvents.forEach(event => {
            window.removeEventListener(event, registerSW);
        });
    };

    // Register on first interaction
    interactionEvents.forEach(event => {
        window.addEventListener(event, registerSW, { passive: true });
    });

    // Fallback: register after 8 seconds of page load to ensure registration 
    // even if the user is completely idle
    window.addEventListener('load', () => {
        setTimeout(registerSW, 8000);
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
