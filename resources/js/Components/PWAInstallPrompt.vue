<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue';

// ── State ────────────────────────────────────────────────────────────────────
const showPrompt   = ref(false);
const showIOS      = ref(false);
const isInstalling = ref(false);

// ── Helpers ───────────────────────────────────────────────────────────────────
const DISMISS_KEY     = 'pwa_dismissed_at';
const DISMISS_COOLDOWN = 30 * 24 * 60 * 60 * 1000; // 30 days

function wasDismissedRecently() {
    const ts = localStorage.getItem(DISMISS_KEY);
    if (!ts) return false;
    return Date.now() - parseInt(ts, 10) < DISMISS_COOLDOWN;
}

function isIOS() {
    return /iphone|ipad|ipod/i.test(navigator.userAgent) && !window.MSStream;
}

function isInStandaloneMode() {
    return window.matchMedia('(display-mode: standalone)').matches
        || window.navigator.standalone === true;
}

// ── Handlers ─────────────────────────────────────────────────────────────────
function onPromptReady() {
    if (wasDismissedRecently() || isInStandaloneMode() || window.__pwaInstalled) return;
    showPrompt.value = true;
}

function onInstalled() {
    showPrompt.value = false;
    showIOS.value    = false;
}

// ── Lifecycle ─────────────────────────────────────────────────────────────────
onMounted(() => {
    // Already installed → bail
    if (isInStandaloneMode() || window.__pwaInstalled) return;
    if (wasDismissedRecently()) return;

    // iOS Safari — no beforeinstallprompt, show manual guidance
    if (isIOS()) {
        showIOS.value = true;
        return;
    }

    // Chrome/Edge/Android — prompt may have already fired (stored globally)
    if (window.__pwaPrompt) {
        showPrompt.value = true;
    }

    // Or it fires later — listen for our custom event
    window.addEventListener('pwa-prompt-ready', onPromptReady);
    window.addEventListener('pwa-installed',    onInstalled);
});

onUnmounted(() => {
    window.removeEventListener('pwa-prompt-ready', onPromptReady);
    window.removeEventListener('pwa-installed',    onInstalled);
});

// ── Actions ───────────────────────────────────────────────────────────────────
async function installPWA() {
    const prompt = window.__pwaPrompt;
    if (!prompt) return;

    isInstalling.value = true;
    prompt.prompt();

    const { outcome } = await prompt.userChoice;

    window.__pwaPrompt  = null;
    isInstalling.value  = false;
    showPrompt.value    = false;

    if (outcome === 'accepted') {
        localStorage.removeItem(DISMISS_KEY);
    }
}

function dismiss() {
    localStorage.setItem(DISMISS_KEY, String(Date.now()));
    showPrompt.value = false;
    showIOS.value    = false;
}
</script>

<template>
    <!-- ── Chrome / Edge / Android prompt ──────────────────────────────────── -->
    <Transition
        enter-active-class="transform transition ease-out duration-500"
        enter-from-class="translate-y-4 opacity-0"
        enter-to-class="translate-y-0 opacity-100"
        leave-active-class="transform transition ease-in duration-300"
        leave-from-class="translate-y-0 opacity-100"
        leave-to-class="translate-y-4 opacity-0"
    >
        <div
            v-if="showPrompt"
            class="fixed bottom-24 lg:bottom-6 left-4 right-4 sm:left-auto sm:right-6 sm:w-96 z-[200]"
        >
            <div class="bg-[#121826]/95 backdrop-blur-2xl border border-purple-500/30 rounded-3xl p-5 shadow-2xl shadow-purple-500/10 overflow-hidden relative">
                <!-- Glow -->
                <div class="absolute -top-12 -right-12 w-28 h-28 bg-purple-600/20 blur-3xl rounded-full pointer-events-none"></div>
                <div class="absolute -bottom-8 -left-8 w-20 h-20 bg-indigo-600/15 blur-2xl rounded-full pointer-events-none"></div>

                <div class="relative z-10 flex items-start gap-x-4">
                    <!-- Icon -->
                    <div class="h-12 w-12 bg-gradient-to-tr from-purple-600 to-indigo-600 flex items-center justify-center shrink-0 shadow-lg shadow-purple-900/40">
                        <img src="/assets/images/pwa-192.webp" class="h-8 w-8 object-contain" alt="App Icon" />
                    </div>

                    <div class="flex-1 min-w-0">
                        <h3 class="text-sm font-bold text-white tracking-tight">Install FluxMedia Studio</h3>
                        <p class="text-[11px] text-gray-400 mt-1 leading-relaxed">
                            Add to your home screen for lightning-fast image processing &amp; instant QR access — works offline too.
                        </p>

                        <div class="mt-4 flex items-center gap-x-3">
                            <button
                                @click="installPWA"
                                :disabled="isInstalling"
                                class="flex-1 bg-purple-600 hover:bg-purple-500 disabled:opacity-60 text-white text-[11px] font-extrabold uppercase tracking-widest py-2.5 rounded-xl transition-all shadow-lg shadow-purple-600/20 flex items-center justify-center gap-x-2"
                            >
                                <svg v-if="isInstalling" class="animate-spin h-3 w-3" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                                </svg>
                                {{ isInstalling ? 'Installing…' : 'Install Now' }}
                            </button>
                            <button
                                @click="dismiss"
                                class="px-4 py-2.5 text-[11px] font-bold text-gray-500 hover:text-gray-300 transition-colors"
                            >
                                Not Now
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </Transition>

    <!-- ── iOS Safari manual guidance ──────────────────────────────────────── -->
    <Transition
        enter-active-class="transform transition ease-out duration-500"
        enter-from-class="translate-y-4 opacity-0"
        enter-to-class="translate-y-0 opacity-100"
        leave-active-class="transform transition ease-in duration-300"
        leave-from-class="translate-y-0 opacity-100"
        leave-to-class="translate-y-4 opacity-0"
    >
        <div
            v-if="showIOS"
            class="fixed bottom-24 lg:bottom-6 left-4 right-4 sm:left-auto sm:right-6 sm:w-96 z-[200]"
        >
            <div class="bg-[#121826]/95 backdrop-blur-2xl border border-indigo-500/30 rounded-3xl p-5 shadow-2xl shadow-indigo-500/10 overflow-hidden relative">
                <div class="absolute -top-10 -right-10 w-24 h-24 bg-indigo-600/20 blur-3xl rounded-full pointer-events-none"></div>

                <div class="relative z-10">
                    <div class="flex items-center gap-x-3 mb-3">
                        <div class="h-10 w-10 bg-gradient-to-tr from-indigo-600 to-purple-600 flex items-center justify-center shrink-0 shadow-lg shadow-indigo-900/40">
                            <img src="/assets/images/pwa-192.webp" class="h-7 w-7 object-contain" alt="App Icon" />
                        </div>
                        <h3 class="text-sm font-bold text-white tracking-tight">Install FluxMedia Studio</h3>
                    </div>

                    <p class="text-[11px] text-gray-400 leading-relaxed mb-3">
                        Add to your Home Screen in two taps:
                    </p>

                    <ol class="space-y-2 mb-4">
                        <li class="flex items-center gap-x-2.5 text-[11px] text-gray-300">
                            <span class="h-5 w-5 rounded-full bg-purple-600/30 text-purple-400 flex items-center justify-center font-bold text-[10px] shrink-0">1</span>
                            Tap the <strong class="text-white mx-1">Share</strong>
                            <!-- iOS share icon inline -->
                            <svg class="h-4 w-4 text-blue-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/>
                            </svg>
                            button at the bottom
                        </li>
                        <li class="flex items-center gap-x-2.5 text-[11px] text-gray-300">
                            <span class="h-5 w-5 rounded-full bg-purple-600/30 text-purple-400 flex items-center justify-center font-bold text-[10px] shrink-0">2</span>
                            Select <strong class="text-white mx-1">"Add to Home Screen"</strong>
                        </li>
                    </ol>

                    <button
                        @click="dismiss"
                        class="w-full py-2 text-[11px] font-bold text-gray-500 hover:text-gray-300 transition-colors text-center border border-gray-800/60 rounded-xl"
                    >
                        Dismiss
                    </button>
                </div>
            </div>
        </div>
    </Transition>
</template>
