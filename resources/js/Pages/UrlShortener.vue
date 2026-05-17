<script setup>
import { Head, Link } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { ref, reactive, onMounted, onUnmounted } from 'vue';
import axios from 'axios';
import { usePage } from '@inertiajs/vue3';

defineProps({ canLogin: Boolean, canRegister: Boolean });

// Inject JSON-LD structured data programmatically (can't use <script> inside Vue template)
let ldScript = null;
onMounted(() => {
    ldScript = document.createElement('script');
    ldScript.type = 'application/ld+json';
    ldScript.textContent = JSON.stringify({
        '@context': 'https://schema.org',
        '@type': 'WebApplication',
        name: 'FluxMedia URL Shortener',
        url: 'https://fluxmedia.space/tools/url-shortener',
        description: 'Free URL shortener tool to shorten long links, create custom aliases, and track click analytics.',
        applicationCategory: 'UtilityApplication',
        operatingSystem: 'Web',
        offers: { '@type': 'Offer', price: '0', priceCurrency: 'USD' },
    });
    document.head.appendChild(ldScript);
});
onUnmounted(() => { ldScript?.remove(); });

const page    = usePage();
const myLinks = ref(page.props.myLinks || []);
const baseUrl = 'https://fluxmedia.space/s/';

// ── Form ─────────────────────────────────────────────────────────────────────
const longUrl     = ref('');
const customAlias = ref('');
const title       = ref('');
const isLoading   = ref(false);
const formError   = ref('');
const result      = ref(null);

// ── Toast system ─────────────────────────────────────────────────────────────
const toasts = reactive([]);
let toastId = 0;

const showToast = (message, type = 'success') => {
    const id = ++toastId;
    toasts.push({ id, message, type });
    setTimeout(() => {
        const idx = toasts.findIndex(t => t.id === id);
        if (idx !== -1) toasts.splice(idx, 1);
    }, 3200);
};

const dismissToast = (id) => {
    const idx = toasts.findIndex(t => t.id === id);
    if (idx !== -1) toasts.splice(idx, 1);
};

// ── Actions ───────────────────────────────────────────────────────────────────
const shorten = async () => {
    if (!longUrl.value.trim()) return;
    isLoading.value = true;
    formError.value = '';
    result.value    = null;

    try {
        const resp = await axios.post('/api/shorten', {
            url:   longUrl.value.trim(),
            alias: customAlias.value.trim() || null,
            title: title.value.trim() || null,
        });
        result.value = resp.data;
        myLinks.value.unshift(resp.data);
        if (myLinks.value.length > 20) myLinks.value.pop();
        longUrl.value     = '';
        customAlias.value = '';
        title.value       = '';
        showToast('Short link created! Copy it below.', 'success');
    } catch (err) {
        const msgs = err.response?.data?.errors;
        formError.value = msgs
            ? Object.values(msgs).flat().join(' ')
            : (err.response?.data?.message || 'Something went wrong.');
        showToast(formError.value, 'error');
    } finally {
        isLoading.value = false;
    }
};

const copyToClipboard = async (url, label = 'Link') => {
    try {
        await navigator.clipboard.writeText(url);
        showToast(`${label} copied to clipboard!`, 'copy');
    } catch {
        showToast('Copy failed — please copy manually.', 'error');
    }
};

// ── Delete modal ─────────────────────────────────────────────────────────────
const deleteModal = reactive({ open: false, link: null, loading: false });

const promptDelete = (link) => {
    deleteModal.link = link;
    deleteModal.open = true;
};

const cancelDelete = () => {
    deleteModal.open   = false;
    deleteModal.link   = null;
    deleteModal.loading = false;
};

const confirmDelete = async () => {
    if (!deleteModal.link) return;
    // Capture ID locally — reactive object will be cleared after delete
    const linkId = deleteModal.link.id;
    deleteModal.loading = true;
    try {
        await axios.delete(`/api/shorten/${linkId}`);
        // Update list immediately in-place
        myLinks.value = myLinks.value.filter(l => l.id !== linkId);
        if (result.value?.id === linkId) result.value = null;
        // Close modal explicitly
        deleteModal.open    = false;
        deleteModal.link    = null;
        deleteModal.loading = false;
        showToast('Link deleted successfully.', 'delete');
    } catch (err) {
        deleteModal.loading = false;
        const msg = err.response?.data?.message || 'Failed to delete link. Try again.';
        showToast(msg, 'error');
    }
};

// ── Toast icon helper ─────────────────────────────────────────────────────────
const toastConfig = {
    success: { bg: 'bg-emerald-500/10 border-emerald-500/30', text: 'text-emerald-400', icon: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z' },
    copy:    { bg: 'bg-blue-500/10 border-blue-500/30',       text: 'text-blue-400',    icon: 'M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z' },
    delete:  { bg: 'bg-red-500/10 border-red-500/30',         text: 'text-red-400',     icon: 'M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16' },
    error:   { bg: 'bg-red-500/10 border-red-500/30',         text: 'text-red-400',     icon: 'M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z' },
};
</script>

<template>
    <PublicLayout>
        <Head>
            <title>Free URL Shortener — Shorten Links Online | FluxMedia</title>

            <!-- Primary SEO -->
            <meta name="description" content="Free URL shortener tool. Shorten long links instantly, create custom branded aliases, and track click analytics. No signup or account required. Fast, simple, and 100% free." />
            <meta name="keywords" content="url shortener, free url shortener, shorten link, link shortener, custom short url, bitly alternative, short link generator, url shortener no signup, create short link, link tracker, short url creator, tiny url, free link shortener online" />
            <meta name="author" content="FluxMedia" />
            <meta name="robots" content="index, follow" />
            <link rel="canonical" href="https://fluxmedia.space/tools/url-shortener" />

            <!-- Open Graph -->
            <meta property="og:type" content="website" />
            <meta property="og:title" content="Free URL Shortener — Shorten & Track Links | FluxMedia" />
            <meta property="og:description" content="Shorten any URL in seconds. Create custom aliases like fluxmedia.space/s/my-link. Track clicks. 100% free, no account needed." />
            <meta property="og:image" content="https://fluxmedia.space/assets/images/fluxmedia_main.webp" />
            <meta property="og:url" content="https://fluxmedia.space/tools/url-shortener" />
            <meta property="og:site_name" content="FluxMedia" />

            <!-- Twitter Card -->
            <meta name="twitter:card" content="summary_large_image" />
            <meta name="twitter:title" content="Free URL Shortener — FluxMedia" />
            <meta name="twitter:description" content="Shorten URLs, create custom short links, track clicks. Free and instant — no signup required." />
            <meta name="twitter:image" content="https://fluxmedia.space/assets/images/fluxmedia_main.webp" />

            <!-- JSON-LD injected via onMounted in script setup -->
        </Head>

        <!-- ── Delete Confirmation Modal ────────────────────────────────── -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition-all duration-200 ease-out"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition-all duration-150 ease-in"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div
                    v-if="deleteModal.open"
                    class="fixed inset-0 z-[400] flex items-end sm:items-center justify-center p-4"
                    @keydown.esc="cancelDelete"
                    tabindex="-1"
                    @click.self="cancelDelete"
                >
                    <!-- Backdrop -->
                    <div class="absolute inset-0 bg-black/70 backdrop-blur-sm"></div>

                    <!-- Modal panel -->
                    <Transition
                        enter-active-class="transition-all duration-200 ease-out"
                        enter-from-class="opacity-0 scale-95 translate-y-4"
                        enter-to-class="opacity-100 scale-100 translate-y-0"
                        leave-active-class="transition-all duration-150 ease-in"
                        leave-from-class="opacity-100 scale-100 translate-y-0"
                        leave-to-class="opacity-0 scale-95 translate-y-2"
                    >
                        <div
                            v-if="deleteModal.open"
                            class="relative w-full max-w-sm rounded-3xl border border-red-500/20 bg-[#121826]/95 backdrop-blur-xl shadow-2xl p-6"
                        >
                            <!-- Icon -->
                            <div class="flex items-center justify-center h-12 w-12 rounded-2xl bg-red-500/10 border border-red-500/20 mx-auto mb-4">
                                <svg class="h-6 w-6 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </div>

                            <!-- Text -->
                            <h3 class="text-base font-bold text-white text-center mb-1">Delete Short Link?</h3>
                            <p class="text-xs text-gray-400 text-center mb-4">This action cannot be undone. The short link and all its click data will be permanently removed.</p>

                            <!-- Link preview -->
                            <div v-if="deleteModal.link" class="rounded-xl bg-gray-900/60 border border-gray-800 px-3 py-2.5 mb-5">
                                <p class="text-[10px] font-black text-red-400 uppercase tracking-widest mb-1">Link to delete</p>
                                <p class="text-xs font-bold text-white font-mono truncate">fluxmedia.space/s/{{ deleteModal.link.slug }}</p>
                                <p class="text-[10px] text-gray-600 truncate mt-0.5">{{ deleteModal.link.original_url }}</p>
                            </div>

                            <!-- Actions -->
                            <div class="flex gap-x-3">
                                <button
                                    @click="cancelDelete"
                                    :disabled="deleteModal.loading"
                                    class="flex-1 rounded-2xl border border-gray-700 bg-gray-800/60 hover:bg-gray-700/60 py-3 text-sm font-bold text-gray-300 transition-all active:scale-95 disabled:opacity-50"
                                >
                                    Cancel
                                </button>
                                <button
                                    @click="confirmDelete"
                                    :disabled="deleteModal.loading"
                                    class="flex-1 inline-flex items-center justify-center gap-x-2 rounded-2xl bg-red-600 hover:bg-red-500 py-3 text-sm font-bold text-white shadow-lg shadow-red-600/20 transition-all active:scale-95 disabled:opacity-60"
                                >
                                    <svg v-if="deleteModal.loading" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                                    {{ deleteModal.loading ? 'Deleting...' : 'Delete Link' }}
                                </button>
                            </div>
                        </div>
                    </Transition>
                </div>
            </Transition>
        </Teleport>

        <!-- ── Toast Container ─────────────────────────────────────────────── -->
        <Teleport to="body">
            <div class="fixed top-4 right-4 z-[300] flex flex-col gap-y-2.5 w-[calc(100vw-2rem)] sm:w-80 pointer-events-none">
                <TransitionGroup
                    enter-active-class="transition-all duration-300 ease-out"
                    enter-from-class="opacity-0 translate-y-[-12px] scale-95"
                    enter-to-class="opacity-100 translate-y-0 scale-100"
                    leave-active-class="transition-all duration-200 ease-in"
                    leave-from-class="opacity-100 translate-y-0 scale-100"
                    leave-to-class="opacity-0 translate-y-[-8px] scale-95"
                >
                    <div
                        v-for="toast in toasts"
                        :key="toast.id"
                        :class="[
                            'pointer-events-auto flex items-start gap-x-3 rounded-2xl border px-4 py-3 shadow-2xl backdrop-blur-xl',
                            toastConfig[toast.type].bg,
                        ]"
                    >
                        <svg :class="['h-4 w-4 mt-0.5 shrink-0', toastConfig[toast.type].text]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="toastConfig[toast.type].icon" />
                        </svg>
                        <p :class="['flex-1 text-xs font-semibold leading-snug', toastConfig[toast.type].text]">{{ toast.message }}</p>
                        <button @click="dismissToast(toast.id)" class="shrink-0 text-gray-600 hover:text-gray-400 transition-colors">
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>
                </TransitionGroup>
            </div>
        </Teleport>

        <!-- ── Hero ───────────────────────────────────────────────────────── -->
        <div class="relative overflow-hidden pt-8 pb-4 text-center">
            <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[200px] bg-gradient-to-tr from-blue-600/15 via-indigo-600/8 to-purple-600/8 blur-[100px] rounded-full pointer-events-none"></div>
            <div class="relative mx-auto max-w-4xl px-4 space-y-3">
                <div class="flex items-center justify-center">
                    <span class="inline-flex items-center gap-x-2 rounded-full bg-blue-500/10 px-4 py-1.5 text-[10px] font-bold text-blue-300 border border-blue-500/20 uppercase tracking-[0.2em]">
                        <span class="h-1.5 w-1.5 rounded-full bg-blue-400 animate-pulse"></span>
                        URL Shortener
                    </span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-white leading-tight">
                    Shorten. <span class="bg-gradient-to-r from-blue-400 via-indigo-400 to-purple-400 bg-clip-text text-transparent">Share.</span> Track.
                </h1>
                <p class="text-xs text-gray-400 max-w-md mx-auto leading-relaxed">
                    Turn long URLs into clean short links. Custom aliases, click tracking, completely free.
                </p>
            </div>
        </div>

        <!-- ── Main Content ────────────────────────────────────────────────── -->
        <div class="mx-auto max-w-2xl px-3 sm:px-4 lg:px-6 pb-28 lg:pb-16 space-y-4 mt-2">

            <!-- Shortener Card -->
            <div class="rounded-3xl border border-gray-800/80 bg-[#121826]/80 backdrop-blur-xl shadow-2xl p-4 sm:p-6 lg:p-8">
                <h2 class="text-[10px] font-bold text-gray-500 uppercase tracking-widest flex items-center gap-x-2 mb-4">
                    <svg class="h-3.5 w-3.5 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" /></svg>
                    Paste Your Long URL
                </h2>

                <!-- URL Input — stacks on mobile, side-by-side on sm+ -->
                <div class="flex flex-col sm:flex-row gap-2">
                    <input
                        v-model="longUrl"
                        type="url"
                        id="url-input"
                        placeholder="https://example.com/very/long/url/that/needs/shortening"
                        class="flex-1 min-w-0 bg-[#0B0F19] border border-gray-700 rounded-2xl py-3.5 px-4 text-white text-sm placeholder-gray-600 focus:border-blue-500 focus:outline-none transition-colors"
                        @keyup.enter="shorten"
                        autocomplete="url"
                    />
                    <button
                        @click="shorten"
                        :disabled="isLoading || !longUrl.trim()"
                        class="inline-flex items-center justify-center gap-x-2 rounded-2xl bg-blue-600 hover:bg-blue-500 px-6 py-3.5 text-sm font-bold text-white shadow-lg shadow-blue-600/20 transition-all disabled:opacity-50 active:scale-95 whitespace-nowrap"
                    >
                        <svg v-if="isLoading" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                        <svg v-else class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" /></svg>
                        {{ isLoading ? 'Shortening...' : 'Shorten' }}
                    </button>
                </div>

                <!-- Optional fields -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-3">
                    <div>
                        <label for="alias-input" class="block text-[10px] font-bold text-gray-600 uppercase tracking-wider mb-1.5">Custom Alias <span class="text-gray-700 normal-case font-normal">(optional)</span></label>
                        <div class="flex items-center bg-[#0B0F19] border border-gray-700 rounded-xl overflow-hidden focus-within:border-blue-500 transition-colors">
                            <span class="text-[10px] text-gray-600 pl-3 pr-1 shrink-0 font-mono leading-none">fluxmedia.space/s/</span>
                            <input id="alias-input" v-model="customAlias" type="text" placeholder="my-link" class="flex-1 min-w-0 bg-transparent py-2.5 pr-3 text-white text-sm focus:outline-none font-mono" />
                        </div>
                    </div>
                    <div>
                        <label for="label-input" class="block text-[10px] font-bold text-gray-600 uppercase tracking-wider mb-1.5">Label <span class="text-gray-700 normal-case font-normal">(optional)</span></label>
                        <input id="label-input" v-model="title" type="text" placeholder="e.g. My campaign link" class="w-full bg-[#0B0F19] border border-gray-700 rounded-xl py-2.5 px-3 text-white text-sm focus:border-blue-500 focus:outline-none transition-colors" />
                    </div>
                </div>

                <!-- Inline error (also fires toast) -->
                <div v-if="formError" class="mt-3 rounded-xl bg-red-500/10 border border-red-500/20 p-3 text-xs text-red-400 flex items-center gap-x-2">
                    <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    <span>{{ formError }}</span>
                </div>

                <!-- Result Box -->
                <div v-if="result" class="mt-4 rounded-2xl border border-blue-500/30 bg-blue-500/5 p-4">
                    <p class="text-[10px] font-bold text-blue-400 uppercase tracking-widest mb-2.5 flex items-center gap-x-1.5">
                        <span class="h-1.5 w-1.5 rounded-full bg-blue-400 animate-pulse"></span>
                        Link Ready
                    </p>
                    <!-- Short URL row -->
                    <div class="flex items-center gap-x-2">
                        <span class="flex-1 min-w-0 text-sm font-bold text-white font-mono truncate">{{ result.short_url }}</span>
                        <button
                            @click="copyToClipboard(result.short_url, 'Short URL')"
                            class="shrink-0 inline-flex items-center gap-x-1.5 rounded-xl bg-blue-600 hover:bg-blue-500 px-4 py-2 text-[10px] font-black text-white uppercase tracking-widest transition-all active:scale-95"
                        >
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
                            Copy
                        </button>
                        <a :href="result.short_url" target="_blank" rel="noopener" class="shrink-0 h-8 w-8 rounded-xl bg-gray-800 hover:bg-gray-700 flex items-center justify-center text-gray-400 hover:text-white transition-colors">
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" /></svg>
                        </a>
                    </div>
                    <p class="mt-1.5 text-[10px] text-gray-600 truncate">↗ {{ result.original_url }}</p>
                </div>
            </div>

            <!-- My Links -->
            <div v-if="myLinks.length > 0" class="rounded-3xl border border-gray-800/80 bg-[#121826]/60 backdrop-blur-xl shadow-2xl overflow-hidden">
                <!-- Header -->
                <div class="flex items-center justify-between px-4 sm:px-6 py-3.5 border-b border-gray-800/80">
                    <span class="text-[10px] font-bold text-blue-400 uppercase tracking-widest flex items-center gap-x-2">
                        <span class="h-2 w-2 rounded-full bg-blue-500 animate-pulse"></span>
                        Your Links ({{ myLinks.length }})
                    </span>
                    <span class="text-[10px] text-gray-600">Session history</span>
                </div>

                <!-- Link rows -->
                <div class="divide-y divide-gray-800/50">
                    <div
                        v-for="link in myLinks"
                        :key="link.id"
                        class="flex items-center gap-x-3 px-4 sm:px-6 py-3.5 hover:bg-gray-800/20 transition-colors group"
                    >
                        <!-- Icon -->
                        <div class="h-8 w-8 rounded-lg bg-blue-500/10 flex items-center justify-center text-blue-400 shrink-0">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" /></svg>
                        </div>

                        <!-- Content -->
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-x-2 flex-wrap">
                                <p class="text-sm font-bold text-white font-mono truncate">{{ baseUrl }}{{ link.slug }}</p>
                                <span class="text-[9px] font-black text-blue-400 bg-blue-500/10 border border-blue-500/20 rounded-full px-2 py-0.5 uppercase tracking-widest shrink-0">
                                    {{ link.click_count }} clicks
                                </span>
                            </div>
                            <p class="text-[10px] text-gray-600 truncate mt-0.5">{{ link.original_url }}</p>
                        </div>

                        <!-- Action buttons — always visible on mobile, hover on desktop -->
                        <div class="flex items-center gap-x-1.5 shrink-0 sm:opacity-0 sm:group-hover:opacity-100 transition-opacity">
                            <button
                                @click="copyToClipboard(baseUrl + link.slug, 'Link')"
                                class="h-8 w-8 rounded-lg bg-blue-500/10 hover:bg-blue-500/20 text-blue-400 flex items-center justify-center transition-colors"
                                title="Copy"
                            >
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
                            </button>
                            <button
                                @click="promptDelete(link)"
                                class="h-8 w-8 rounded-lg bg-red-500/10 hover:bg-red-500/20 text-red-400 flex items-center justify-center transition-colors"
                                title="Delete"
                            >
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty state hint -->
            <div v-else class="rounded-2xl border border-dashed border-gray-800/60 py-8 text-center">
                <svg class="h-8 w-8 text-gray-700 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" /></svg>
                <p class="text-xs text-gray-600 font-medium">Paste a URL above to create your first short link</p>
            </div>

            <!-- Feature pills -->
            <div class="flex flex-wrap justify-center gap-2 pt-2">
                <span v-for="f in ['No signup needed', 'Custom aliases', 'Click tracking', '301 redirect', '100% Free']" :key="f"
                    class="inline-flex items-center gap-x-1.5 rounded-full bg-gray-800/60 border border-gray-700/40 px-3 py-1.5 text-[10px] font-bold text-gray-500 uppercase tracking-widest">
                    <svg class="h-3 w-3 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" /></svg>
                    {{ f }}
                </span>
            </div>
        </div>
    </PublicLayout>
</template>
