<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { ref, reactive, onMounted, onUnmounted, watch } from 'vue';
import axios from 'axios';

const props = defineProps({
    history: Object,
});

const myHistory = ref(props.history?.data || []);

// Sync history on page navigation
watch(() => props.history?.data, (newData) => {
    myHistory.value = newData || [];
    selectedHistory.value = [];
});

// ── Form State ──────────────────────────────────────────────────────────────
const uuidVersion = ref('v4');
const uuidCount = ref(1);
const isLoading = ref(false);
const formError = ref('');
const resultUuids = ref([]);

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

// ── Actions ──────────────────────────────────────────────────────────────────
const generate = async () => {
    isLoading.value = true;
    formError.value = '';
    resultUuids.value = [];

    try {
        const resp = await axios.post(route('uuid.generate'), {
            version: uuidVersion.value,
            count: uuidCount.value,
        });
        resultUuids.value = resp.data.uuids;
        // Add to history
        resp.data.uuids.forEach(u => {
            myHistory.value.unshift(u);
        });
        if (myHistory.value.length > 10) myHistory.value = myHistory.value.slice(0, 10);
        
        // Update total count locally
        if (props.history) {
            props.history.total += resp.data.uuids.length;
        }
        
        showToast(`Generated ${resp.data.uuids.length} UUID(s)!`, 'success');
    } catch (err) {
        formError.value = err.response?.data?.message || 'Failed to generate UUIDs.';
        showToast(formError.value, 'error');
    } finally {
        isLoading.value = false;
    }
};

const copyToClipboard = async (text, label = 'UUID') => {
    try {
        await navigator.clipboard.writeText(text);
        showToast(`${label} copied!`, 'copy');
    } catch {
        showToast('Copy failed.', 'error');
    }
};

const copyAsJson = async () => {
    const json = JSON.stringify({ uuids: resultUuids.value.map(u => u.uuid) }, null, 2);
    await copyToClipboard(json, 'JSON Object');
};

const copyAsCsv = async () => {
    const csv = resultUuids.value.map(u => u.uuid).join(', ');
    await copyToClipboard(csv, 'CSV');
};

const copyAsList = async () => {
    const list = resultUuids.value.map(u => u.uuid).join('\n');
    await copyToClipboard(list, 'List');
};

// ── Selection & Bulk Actions ────────────────────────────────────────────────
const selectedHistory = ref([]);
const isBulkDeleting = ref(false);

const toggleSelectAll = () => {
    if (selectedHistory.value.length === myHistory.value.length) {
        selectedHistory.value = [];
    } else {
        selectedHistory.value = myHistory.value.map(r => r.id);
    }
};

const deleteSelected = () => {
    promptBulkDelete();
};

// ── Delete modal ─────────────────────────────────────────────────────────────
const deleteModal = reactive({ 
    open: false, 
    type: 'single', // 'single' | 'bulk'
    record: null, 
    count: 0,
    loading: false 
});

const promptDelete = (record) => {
    deleteModal.type = 'single';
    deleteModal.record = record;
    deleteModal.open = true;
};

const promptBulkDelete = () => {
    if (!selectedHistory.value.length) return;
    deleteModal.type = 'bulk';
    deleteModal.count = selectedHistory.value.length;
    deleteModal.open = true;
};

const cancelDelete = () => {
    deleteModal.open = false;
    deleteModal.record = null;
    deleteModal.count = 0;
    deleteModal.loading = false;
};

const confirmDelete = async () => {
    deleteModal.loading = true;
    
    const options = {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            showToast(deleteModal.type === 'single' ? 'Record deleted.' : `${deleteModal.count} records deleted.`, 'delete');
            cancelDelete();
        },
        onError: () => {
            showToast('Action failed. Try again.', 'error');
            deleteModal.loading = false;
        },
        onFinish: () => {
            deleteModal.loading = false;
        }
    };

    if (deleteModal.type === 'single') {
        router.delete(route('uuid.destroy', deleteModal.record.id), options);
    } else {
        router.delete(route('uuid.bulk-destroy'), {
            data: { ids: selectedHistory.value },
            ...options
        });
    }
};

// ── SEO & Layout ─────────────────────────────────────────────────────────────
let ldScript = null;
onMounted(() => {
    ldScript = document.createElement('script');
    ldScript.type = 'application/ld+json';
    ldScript.textContent = JSON.stringify({
        '@context': 'https://schema.org',
        '@type': 'WebApplication',
        name: 'FluxMedia UUID Generator',
        url: 'https://fluxmedia.space/tools/uuid-generator',
        description: 'Generate unique UUIDs (v1, v4) online. Bulk generation, custom versions, and instant copy.',
        applicationCategory: 'UtilityApplication',
        operatingSystem: 'Web',
    });
    document.head.appendChild(ldScript);
});
onUnmounted(() => { ldScript?.remove(); });

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
            <title>Bulk UUID Generator — Online v1 & v4 UUID/GUID Creator | FluxMedia</title>
            <meta name="description" content="Generate RFC-compliant UUIDs (v1 time-based & v4 random) in bulk. Secure, instant, and privacy-first. Copy as JSON, CSV, or list. The ultimate tool for developers." />
            <meta name="keywords" content="uuid generator, bulk uuid, guid generator, v4 uuid online, v1 uuid generator, unique id generator, rfc compliant uuid, developer tools" />
            
            <!-- Open Graph / Facebook -->
            <meta property="og:type" content="website" />
            <meta property="og:url" content="https://fluxmedia.space/tools/uuid-generator" />
            <meta property="og:title" content="Bulk UUID Generator — Online v1 & v4 UUID/GUID Creator" />
            <meta property="og:description" content="Securely generate up to 100 unique UUIDs at once. Privacy-focused, local generation, with bulk export options." />
            <meta property="og:image" content="https://fluxmedia.space/images/og-uuid.jpg" />

            <!-- Twitter -->
            <meta property="twitter:card" content="summary_large_image" />
            <meta property="twitter:url" content="https://fluxmedia.space/tools/uuid-generator" />
            <meta property="twitter:title" content="Bulk UUID Generator — Online v1 & v4 UUID/GUID Creator" />
            <meta property="twitter:description" content="Securely generate up to 100 unique UUIDs at once. Privacy-focused, local generation, with bulk export options." />
            <meta property="twitter:image" content="https://fluxmedia.space/images/og-uuid.jpg" />

            <link rel="canonical" href="https://fluxmedia.space/tools/uuid-generator" />
        </Head>

        <!-- Toast Teleport -->
        <Teleport to="body">
            <div class="fixed top-4 right-4 z-[300] flex flex-col gap-y-2.5 w-[calc(100vw-2rem)] sm:w-80 pointer-events-none">
                <TransitionGroup enter-active-class="transition-all duration-300 ease-out" enter-from-class="opacity-0 translate-y-[-12px] scale-95" enter-to-class="opacity-100 translate-y-0 scale-100" leave-active-class="transition-all duration-200 ease-in" leave-from-class="opacity-100 translate-y-0 scale-100" leave-to-class="opacity-0 translate-y-[-8px] scale-95">
                    <div v-for="toast in toasts" :key="toast.id" :class="['pointer-events-auto flex items-start gap-x-3 rounded-2xl border px-4 py-3 shadow-2xl backdrop-blur-xl', toastConfig[toast.type].bg]">
                        <svg :class="['h-4 w-4 mt-0.5 shrink-0', toastConfig[toast.type].text]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="toastConfig[toast.type].icon" /></svg>
                        <p :class="['flex-1 text-xs font-semibold leading-snug', toastConfig[toast.type].text]">{{ toast.message }}</p>
                        <button @click="dismissToast(toast.id)" class="shrink-0 text-gray-600 hover:text-gray-400 transition-colors">
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>
                </TransitionGroup>
            </div>
        </Teleport>

        <!-- Delete Modal Teleport -->
        <Teleport to="body">
            <Transition enter-active-class="transition-all duration-200 ease-out" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition-all duration-150 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0">
                <div v-if="deleteModal.open" class="fixed inset-0 z-[400] flex items-end sm:items-center justify-center p-4" @keydown.esc="cancelDelete" tabindex="-1" @click.self="cancelDelete">
                    <div class="absolute inset-0 bg-black/70 backdrop-blur-sm"></div>
                    <Transition enter-active-class="transition-all duration-200 ease-out" enter-from-class="opacity-0 scale-95 translate-y-4" enter-to-class="opacity-100 scale-100 translate-y-0" leave-active-class="transition-all duration-150 ease-in" leave-from-class="opacity-100 scale-100 translate-y-0" leave-to-class="opacity-0 scale-95 translate-y-2">
                        <div v-if="deleteModal.open" class="relative w-full max-w-sm rounded-3xl border border-red-500/20 bg-[#121826]/95 backdrop-blur-xl shadow-2xl p-6">
                            <div class="flex items-center justify-center h-12 w-12 rounded-2xl bg-red-500/10 border border-red-500/20 mx-auto mb-4">
                                <svg class="h-6 w-6 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                            </div>
                            <h3 class="text-base font-bold text-white text-center mb-1">
                                {{ deleteModal.type === 'single' ? 'Delete Record?' : `Delete ${deleteModal.count} Records?` }}
                            </h3>
                            <p class="text-xs text-gray-400 text-center mb-4">
                                {{ deleteModal.type === 'single' ? 'This will remove this UUID from your history.' : `This will permanently remove the ${deleteModal.count} selected records.` }} 
                                This action cannot be undone.
                            </p>
                            <div v-if="deleteModal.type === 'single' && deleteModal.record" class="rounded-xl bg-gray-900/60 border border-gray-800 px-3 py-2.5 mb-5">
                                <p class="text-[10px] font-black text-red-400 uppercase tracking-widest mb-1">UUID to delete</p>
                                <p class="text-xs font-bold text-white font-mono truncate">{{ deleteModal.record.uuid }}</p>
                            </div>
                            <div v-if="deleteModal.type === 'bulk'" class="rounded-xl bg-red-500/5 border border-red-500/10 px-4 py-4 mb-5 text-center">
                                <div class="flex items-center justify-center gap-x-3">
                                    <div class="h-10 w-10 rounded-xl bg-red-500/20 border border-red-500/20 flex items-center justify-center">
                                        <span class="text-lg font-black text-red-400">{{ deleteModal.count }}</span>
                                    </div>
                                    <div class="text-left">
                                        <p class="text-[10px] font-black text-red-400 uppercase tracking-widest leading-none">Records</p>
                                        <p class="text-[10px] font-bold text-gray-500 uppercase tracking-widest mt-1">Selected for deletion</p>
                                    </div>
                                </div>
                            </div>
                            <div class="flex gap-x-3">
                                <button @click="cancelDelete" :disabled="deleteModal.loading" class="flex-1 rounded-2xl border border-gray-700 bg-gray-800/60 hover:bg-gray-700/60 py-3 text-sm font-bold text-gray-300 transition-all active:scale-95 disabled:opacity-50">Cancel</button>
                                <button @click="confirmDelete" :disabled="deleteModal.loading" class="flex-1 inline-flex items-center justify-center gap-x-2 rounded-2xl bg-red-600 hover:bg-red-500 py-3 text-sm font-bold text-white shadow-lg shadow-red-600/20 transition-all active:scale-95 disabled:opacity-60">
                                    <svg v-if="deleteModal.loading" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                                    {{ deleteModal.loading ? 'Deleting...' : 'Delete' }}
                                </button>
                            </div>
                        </div>
                    </Transition>
                </div>
            </Transition>
        </Teleport>

        <!-- Hero -->
        <div class="relative overflow-hidden pt-8 pb-4 text-center">
            <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[200px] bg-gradient-to-tr from-emerald-600/15 via-teal-600/8 to-blue-600/8 blur-[100px] rounded-full pointer-events-none"></div>
            <div class="relative mx-auto max-w-4xl px-4 space-y-3">
                <div class="flex items-center justify-center">
                    <span class="inline-flex items-center gap-x-2 rounded-full bg-emerald-500/10 px-4 py-1.5 text-[10px] font-bold text-emerald-300 border border-emerald-500/20 uppercase tracking-[0.2em]">
                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                        UUID Generator
                    </span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-white leading-tight">
                    Unique. <span class="bg-gradient-to-r from-emerald-400 via-teal-400 to-blue-400 bg-clip-text text-transparent">Secure.</span> Instant.
                </h1>
                <p class="text-xs text-gray-400 max-w-md mx-auto leading-relaxed">
                    Generate RFC-compliant UUIDs v1 or v4 in bulk. Fast, secure, and no data leaves your browser.
                </p>
            </div>
        </div>

        <!-- Main Content -->
        <div class="mx-auto max-w-2xl px-3 sm:px-4 lg:px-6 pb-28 space-y-4 mt-2">
            <!-- Generator Card -->
            <div class="rounded-3xl border border-gray-800/80 bg-[#121826]/80 backdrop-blur-xl shadow-2xl p-4 sm:p-6 lg:p-8">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-2">Version</label>
                        <select v-model="uuidVersion" class="w-full bg-[#0B0F19] border border-gray-700 rounded-2xl py-3 px-4 text-white text-sm focus:border-emerald-500 focus:outline-none transition-colors">
                            <option value="v4">UUID v4 (Random)</option>
                            <option value="v1">UUID v1 (Time-based)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-2">Quantity (1-100)</label>
                        <input v-model.number="uuidCount" type="number" min="1" max="100" class="w-full bg-[#0B0F19] border border-gray-700 rounded-2xl py-3 px-4 text-white text-sm focus:border-emerald-500 focus:outline-none transition-colors" />
                    </div>
                </div>

                <button @click="generate" :disabled="isLoading" class="w-full inline-flex items-center justify-center gap-x-2 rounded-2xl bg-emerald-600 hover:bg-emerald-500 px-6 py-4 text-sm font-bold text-white shadow-lg shadow-emerald-600/20 transition-all active:scale-[0.98] disabled:opacity-50">
                    <svg v-if="isLoading" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                    <span>{{ isLoading ? 'Generating...' : 'Generate UUIDs' }}</span>
                </button>

                <!-- Results -->
                <div v-if="resultUuids.length > 0" class="mt-6 space-y-3">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="text-[10px] font-bold text-emerald-400 uppercase tracking-widest">Results</h3>
                        <div class="flex items-center gap-x-3">
                            <button @click="copyAsJson" class="text-[10px] font-bold text-gray-500 hover:text-white transition-colors uppercase tracking-widest">JSON</button>
                            <button @click="copyAsCsv" class="text-[10px] font-bold text-gray-500 hover:text-white transition-colors uppercase tracking-widest">CSV</button>
                            <button @click="copyAsList" class="text-[10px] font-bold text-gray-500 hover:text-white transition-colors uppercase tracking-widest">List</button>
                        </div>
                    </div>
                    <div class="max-h-60 overflow-y-auto space-y-2 custom-scrollbar pr-2">
                        <div v-for="u in resultUuids" :key="u.uuid" class="flex items-center gap-x-3 bg-[#0B0F19] border border-gray-800 rounded-xl px-4 py-3 group">
                            <code class="flex-1 text-xs font-mono text-emerald-300 truncate">{{ u.uuid }}</code>
                            <button @click="copyToClipboard(u.uuid)" class="text-gray-600 hover:text-emerald-400 transition-colors">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- History -->
            <div v-if="myHistory.length > 0" class="rounded-3xl border border-gray-800/80 bg-[#121826]/60 backdrop-blur-xl shadow-2xl overflow-hidden">
                <div class="px-4 sm:px-6 py-4 border-b border-gray-800/80 flex items-center justify-between">
                    <div class="flex items-center gap-x-4">
                        <label class="flex items-center cursor-pointer group">
                            <div class="relative flex items-center">
                                <input type="checkbox" :checked="selectedHistory.length === myHistory.length && myHistory.length > 0" @change="toggleSelectAll" class="peer sr-only" />
                                <div class="h-4 w-4 rounded border border-gray-700 bg-gray-900 group-hover:border-emerald-500/50 transition-colors peer-checked:bg-emerald-500 peer-checked:border-emerald-500"></div>
                                <svg class="absolute h-3 w-3 text-white scale-0 peer-checked:scale-100 transition-transform left-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" /></svg>
                            </div>
                        </label>
                        <span class="text-[10px] font-bold text-emerald-400 uppercase tracking-widest flex items-center gap-x-2">
                            <span class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
                            Generation History (Total: {{ history.total }})
                        </span>
                    </div>
                    <button v-if="selectedHistory.length > 0" @click="deleteSelected" :disabled="isBulkDeleting" class="text-[10px] font-bold text-red-400 hover:text-red-300 transition-colors uppercase tracking-widest flex items-center gap-x-1.5">
                        <svg v-if="isBulkDeleting" class="animate-spin h-3 w-3" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                        {{ isBulkDeleting ? 'Deleting...' : `Delete Selected (${selectedHistory.length})` }}
                    </button>
                </div>
                <div class="divide-y divide-gray-800/50">
                    <div v-for="record in myHistory" :key="record.id" class="flex items-center gap-x-4 px-4 sm:px-6 py-3.5 hover:bg-gray-800/20 transition-colors group">
                        <label class="flex items-center cursor-pointer shrink-0">
                            <div class="relative flex items-center">
                                <input type="checkbox" :value="record.id" v-model="selectedHistory" class="peer sr-only" />
                                <div class="h-4 w-4 rounded border border-gray-700 bg-gray-900 group-hover:border-emerald-500/50 transition-colors peer-checked:bg-emerald-500 peer-checked:border-emerald-500"></div>
                                <svg class="absolute h-3 w-3 text-white scale-0 peer-checked:scale-100 transition-transform left-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" /></svg>
                            </div>
                        </label>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-x-2">
                                <code class="text-xs font-mono text-gray-300 truncate">{{ record.uuid }}</code>
                                <span class="text-[9px] font-black text-emerald-400 bg-emerald-500/10 border border-emerald-500/20 rounded-full px-2 py-0.5 uppercase tracking-widest">{{ record.version }}</span>
                            </div>
                            <p class="text-[10px] text-gray-600 mt-0.5">{{ record.created_at }}</p>
                        </div>
                        <div class="flex items-center gap-x-1.5 shrink-0 sm:opacity-0 sm:group-hover:opacity-100 transition-opacity">
                            <button @click="copyToClipboard(record.uuid)" class="h-8 w-8 rounded-lg bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-400 flex items-center justify-center transition-colors"><svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg></button>
                            <button @click="promptDelete(record)" class="h-8 w-8 rounded-lg bg-red-500/10 hover:bg-red-500/20 text-red-400 flex items-center justify-center transition-colors"><svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg></button>
                        </div>
                    </div>
                </div>

                <!-- Pagination -->
                <div v-if="history.last_page > 1" class="px-4 sm:px-6 py-4 border-t border-gray-800/80 flex items-center justify-between bg-gray-900/20">
                    <p class="text-[10px] text-gray-500 font-bold uppercase tracking-widest">Page {{ history.current_page }} of {{ history.last_page }}</p>
                    <div class="flex items-center gap-x-2">
                        <Link v-if="history.prev_page_url" :href="history.prev_page_url" class="h-8 px-3 rounded-lg bg-gray-800 hover:bg-gray-700 text-[10px] font-bold text-white flex items-center justify-center transition-colors border border-gray-700">Prev</Link>
                        <Link v-if="history.next_page_url" :href="history.next_page_url" class="h-8 px-3 rounded-lg bg-gray-800 hover:bg-gray-700 text-[10px] font-bold text-white flex items-center justify-center transition-colors border border-gray-700">Next</Link>
                    </div>
                </div>
            </div>
        </div>
    </PublicLayout>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #1f2937; border-radius: 10px; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #374151; }
</style>
