<script setup>
import { ref, watch, onMounted, onUnmounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';

// Local state
const inputText = ref('');
const outputText = ref('');
const errorMessage = ref('');
const indentSize = ref('2'); // '2', '4', 'tab'
const copied = ref(false);
const autoFormat = ref(true);

// Analytics state
const metrics = ref(null);
const fileSize = ref('');

// Toast notification state
const toastMessage = ref('');
const toastType = ref('success'); // 'success', 'error', 'copy'
const toastVisible = ref(false);

const showToast = (message, type = 'success') => {
    toastMessage.value = message;
    toastType.value = type;
    toastVisible.value = true;
    setTimeout(() => {
        toastVisible.value = false;
    }, 2800);
};

// Inject JSON-LD structured data programmatically
const seoScripts = [];
onMounted(() => {
    const schemas = [
        {
            '@context': 'https://schema.org',
            '@type': 'WebApplication',
            'name': 'JSON Formatter & Validator — Free Online Tool',
            'url': 'https://fluxmedia.space/tools/json-formatter',
            'description': 'Free online JSON formatter and validator. Beautify, minify, and validate JSON data in real-time with structural analysis. 100% client-side and private.',
            'applicationCategory': 'UtilityApplication',
            'operatingSystem': 'Web, Windows, macOS, Linux, Android, iOS',
            'offers': { '@type': 'Offer', 'price': '0', 'priceCurrency': 'USD' },
            'featureList': ['JSON Beautify', 'JSON Minify', 'JSON Validate', 'Structure Analysis', 'File Upload', 'Download JSON', 'Custom Indentation'],
        },
        {
            '@context': 'https://schema.org',
            '@type': 'HowTo',
            'name': 'How to Format and Validate JSON Online',
            'description': 'Step-by-step guide to formatting and validating JSON using FluxMedia JSON Formatter.',
            'totalTime': 'PT1M',
            'step': [
                { '@type': 'HowToStep', 'position': 1, 'name': 'Paste Your JSON', 'text': 'Paste your raw JSON string into the Input Raw JSON textarea.' },
                { '@type': 'HowToStep', 'position': 2, 'name': 'View Formatted Output', 'text': 'The formatted JSON appears in real-time in the output panel with syntax highlighting.' },
                { '@type': 'HowToStep', 'position': 3, 'name': 'Validate or Minify', 'text': 'Click Validate JSON to check for syntax errors, or Minify JSON to compress it.' },
                { '@type': 'HowToStep', 'position': 4, 'name': 'Copy or Download', 'text': 'Copy the formatted JSON to clipboard or download it as a .json file.' },
            ],
        },
        {
            '@context': 'https://schema.org',
            '@type': 'FAQPage',
            'mainEntity': [
                { '@type': 'Question', 'name': 'Is the JSON formatter private?', 'acceptedAnswer': { '@type': 'Answer', 'text': 'Yes. All JSON processing is performed entirely in your browser using JavaScript. Your data is never sent to any server.' } },
                { '@type': 'Question', 'name': 'Can I upload a JSON file?', 'acceptedAnswer': { '@type': 'Answer', 'text': 'Yes. Click the Upload File button to load a .json file from your computer directly into the formatter.' } },
                { '@type': 'Question', 'name': 'Does it show JSON validation errors?', 'acceptedAnswer': { '@type': 'Answer', 'text': 'Yes. If your JSON contains syntax errors, an error message with the exact error is displayed to help you fix it.' } },
            ],
        },
        {
            '@context': 'https://schema.org',
            '@type': 'BreadcrumbList',
            'itemListElement': [
                { '@type': 'ListItem', 'position': 1, 'name': 'Home', 'item': 'https://fluxmedia.space' },
                { '@type': 'ListItem', 'position': 2, 'name': 'JSON Formatter', 'item': 'https://fluxmedia.space/tools/json-formatter' },
            ],
        },
    ];
    schemas.forEach(schema => {
        const s = document.createElement('script');
        s.type = 'application/ld+json';
        s.textContent = JSON.stringify(schema);
        document.head.appendChild(s);
        seoScripts.push(s);
    });
});

onUnmounted(() => seoScripts.forEach(s => s.remove()));

// JSON Helper Functions
const getJsonMetrics = (obj) => {
    let keysCount = 0;
    let arraysCount = 0;
    let objectsCount = 0;
    let maxDepth = 0;

    const traverse = (current, depth) => {
        if (depth > maxDepth) maxDepth = depth;
        if (Array.isArray(current)) {
            arraysCount++;
            current.forEach(item => traverse(item, depth + 1));
        } else if (current !== null && typeof current === 'object') {
            objectsCount++;
            for (const key in current) {
                if (Object.prototype.hasOwnProperty.call(current, key)) {
                    keysCount++;
                    traverse(current[key], depth + 1);
                }
            }
        }
    };

    traverse(obj, 1);
    return { keysCount, arraysCount, objectsCount, maxDepth };
};

const formatSize = (bytes) => {
    if (bytes === 0) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
};

// Main processing logic
const processJson = (mode = 'format') => {
    errorMessage.value = '';
    metrics.value = null;
    fileSize.value = '';

    if (!inputText.value.trim()) {
        outputText.value = '';
        return;
    }

    try {
        const raw = inputText.value.trim();
        const parsed = JSON.parse(raw);

        // Calculate Metrics
        metrics.value = getJsonMetrics(parsed);
        fileSize.value = formatSize(new Blob([raw]).size);

        if (mode === 'format') {
            const space = indentSize.value === 'tab' ? '\t' : parseInt(indentSize.value);
            outputText.value = JSON.stringify(parsed, null, space);
        } else if (mode === 'minify') {
            outputText.value = JSON.stringify(parsed);
        }
    } catch (e) {
        outputText.value = '';
        errorMessage.value = e.message || 'Invalid JSON syntax. Please check for missing brackets, quotes, or commas.';
    }
};

// Live watchers for real-time mode
watch([inputText, indentSize], () => {
    if (autoFormat.value) {
        processJson('format');
    }
});

// Action Handlers
const handleFormat = () => {
    processJson('format');
    if (!errorMessage.value && inputText.value) {
        showToast('JSON beautified successfully!', 'success');
    } else if (errorMessage.value) {
        showToast('Invalid JSON. Check validation errors.', 'error');
    }
};

const handleMinify = () => {
    processJson('minify');
    if (!errorMessage.value && inputText.value) {
        showToast('JSON minified successfully!', 'success');
    } else if (errorMessage.value) {
        showToast('Invalid JSON. Check validation errors.', 'error');
    }
};

const handleValidate = () => {
    processJson('format');
    if (!errorMessage.value && inputText.value) {
        showToast('Valid JSON structure!', 'success');
    } else if (errorMessage.value) {
        showToast('Validation failed. Check syntax error.', 'error');
    } else {
        showToast('Please enter some JSON to validate.', 'error');
    }
};

const loadSample = () => {
    const sample = {
        appName: "FluxMedia Toolkit",
        version: "1.0.0",
        releaseDate: "2026-05-20",
        features: [
            "Image Studio",
            "QR Generator",
            "URL Shortener",
            "UUID Generator",
            "Base64 Encoder",
            "Hash Generator",
            "JSON Formatter"
        ],
        active: true,
        stats: {
            totalTools: 11,
            liveTools: 8,
            uptime: 0.9999,
            system: {
                node: "v20.11.0",
                vue: "^3.4.0",
                inertia: "^1.0.0"
            }
        },
        meta: null
    };
    inputText.value = JSON.stringify(sample, null, 2);
    processJson('format');
    showToast('Loaded nested sample JSON!', 'success');
};

const clearAll = () => {
    inputText.value = '';
    outputText.value = '';
    errorMessage.value = '';
    metrics.value = null;
    fileSize.value = '';
    showToast('Cleared workspace!', 'success');
};

const copyToClipboard = async () => {
    if (!outputText.value) return;
    try {
        await navigator.clipboard.writeText(outputText.value);
        copied.value = true;
        showToast('Copied output to clipboard!', 'copy');
        setTimeout(() => { copied.value = false; }, 2000);
    } catch (err) {
        console.error('Failed to copy JSON: ', err);
    }
};

const downloadJSON = () => {
    if (!outputText.value) return;
    try {
        const blob = new Blob([outputText.value], { type: 'application/json' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `fluxmedia_${Date.now()}.json`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
        showToast('JSON file downloaded successfully!', 'success');
    } catch (err) {
        console.error('Failed to download JSON: ', err);
    }
};

const handleFileUpload = (event) => {
    const file = event.target.files[0];
    if (!file) return;

    const reader = new FileReader();
    reader.onload = (e) => {
        inputText.value = e.target.result;
        processJson('format');
        showToast(`Loaded ${file.name}!`, 'success');
    };
    reader.readAsText(file);
};
</script>

<template>
    <PublicLayout>
        <Head>
            <title>Free JSON Formatter & Validator — Beautify & Minify JSON | FluxMedia</title>

            <!-- Primary SEO -->
            <meta name="description" content="Free online JSON Formatter and Validator. Beautify, format, validate, and minify your JSON data in real-time. Features structural analysis, file uploads, and full data privacy." />
            <meta name="keywords" content="json formatter, json validator, beautify json, minify json, format json, online json formatter, validate json, syntax checker, drag and drop json, parse json, copy json, format json online, free json tools" />
            <meta name="author" content="FluxMedia" />
            <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1" />
            <meta name="googlebot" content="index, follow, max-snippet:-1, max-image-preview:large" />
            <link rel="canonical" href="https://fluxmedia.space/tools/json-formatter" />

            <!-- Open Graph / Facebook -->
            <meta property="og:type" content="website" />
            <meta property="og:title" content="Free JSON Formatter & Validator — Format & Validate JSON | FluxMedia" />
            <meta property="og:description" content="Instantly format, minify, and validate JSON structures in real-time. Client-side processing ensures complete data privacy." />
            <meta property="og:image" content="https://fluxmedia.space/assets/images/fluxmedia_main.webp" />
            <meta property="og:url" content="https://fluxmedia.space/tools/json-formatter" />
            <meta property="og:site_name" content="FluxMedia" />

            <!-- Twitter Card -->
            <meta name="twitter:card" content="summary_large_image" />
            <meta name="twitter:title" content="Free JSON Formatter & Validator — FluxMedia" />
            <meta name="twitter:description" content="Format, minify, and validate JSON structures instantly online. Free, safe, and entirely browser-based." />
            <meta name="twitter:image" content="https://fluxmedia.space/assets/images/fluxmedia_main.webp" />
        </Head>

        <!-- Background Accents -->
        <div class="fixed inset-0 overflow-hidden pointer-events-none -z-10 bg-[#0B0F19]">
            <div class="absolute top-[-10%] left-[-10%] w-[40%] h-[40%] rounded-full bg-rose-600/10 blur-[120px]"></div>
            <div class="absolute bottom-[-10%] right-[-10%] w-[40%] h-[40%] rounded-full bg-purple-600/10 blur-[120px]"></div>
            <div class="absolute top-[40%] left-[60%] w-[30%] h-[30%] rounded-full bg-pink-600/5 blur-[100px]"></div>
        </div>

        <!-- Toast Notifications -->
        <Teleport to="body">
            <div class="fixed top-4 right-4 z-[300] flex flex-col gap-y-2.5 w-[calc(100vw-2rem)] sm:w-80 pointer-events-none">
                <Transition
                    enter-active-class="transition-all duration-300 ease-out"
                    enter-from-class="opacity-0 translate-y-[-12px] scale-95"
                    enter-to-class="opacity-100 translate-y-0 scale-100"
                    leave-active-class="transition-all duration-200 ease-in"
                    leave-from-class="opacity-100 translate-y-0 scale-100"
                    leave-to-class="opacity-0 translate-y-[-8px] scale-95"
                >
                    <div
                        v-if="toastVisible"
                        :class="[
                            'pointer-events-auto flex items-start gap-x-3 rounded-2xl border px-4 py-3 shadow-2xl backdrop-blur-xl transition-all duration-200',
                            toastType === 'success' ? 'bg-emerald-500/10 border-emerald-500/30 text-emerald-400' :
                            toastType === 'copy' ? 'bg-rose-500/10 border-rose-500/30 text-rose-400' :
                            'bg-red-500/10 border-red-500/30 text-red-400'
                        ]"
                    >
                        <svg class="h-4 w-4 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path v-if="toastType === 'success'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            <path v-else-if="toastType === 'copy'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                            <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <p class="flex-1 text-xs font-semibold leading-snug">{{ toastMessage }}</p>
                    </div>
                </Transition>
            </div>
        </Teleport>

        <!-- Header -->
        <div class="relative pt-8 pb-4 text-center px-4 overflow-hidden z-10">
            <div class="relative max-w-3xl mx-auto space-y-3">
                <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-gray-800/50 border border-gray-700/50 backdrop-blur-md">
                    <span class="w-2 h-2 rounded-full bg-rose-400 animate-pulse"></span>
                    <span class="text-xs font-semibold text-gray-300 uppercase tracking-wider">Free Dev Utility</span>
                </div>
                
                <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-white leading-tight">
                    JSON <span class="bg-gradient-to-r from-rose-400 via-purple-400 to-indigo-400 bg-clip-text text-transparent">Formatter</span> & Validator
                </h1>
                <p class="text-xs text-gray-400 max-w-xl mx-auto leading-relaxed">
                    Beautify, validate, minify, and inspect JSON in real-time. Secure, client-side formatting safeguards your sensitive API payloads.
                </p>
            </div>
        </div>

        <!-- Tool Container -->
        <div class="mx-auto max-w-6xl px-4 pb-24 relative z-10">
            <div class="bg-[#121826]/80 backdrop-blur-xl rounded-3xl border border-gray-800 shadow-2xl p-6 sm:p-8 space-y-6">
                
                <!-- Options / Actions Bar -->
                <div class="flex flex-col sm:flex-row gap-4 justify-between items-center bg-[#0B0F19]/50 border border-gray-800 p-4 rounded-2xl">
                    <!-- Left: Controls -->
                    <div class="flex flex-wrap items-center gap-4">
                        <!-- Indentation Size -->
                        <div class="flex items-center gap-2">
                            <span class="text-[10px] font-black text-gray-500 uppercase tracking-widest">Tab Size:</span>
                            <div class="inline-flex rounded-lg p-0.5 bg-[#0B0F19] border border-gray-800">
                                <button 
                                    v-for="size in ['2', '4', 'tab']" 
                                    :key="size"
                                    @click="indentSize = size"
                                    :class="[
                                        'px-3 py-1 text-xs font-bold rounded-md transition-all duration-200 uppercase tracking-wider',
                                        indentSize === size ? 'bg-rose-600 text-white shadow-md' : 'text-gray-500 hover:text-gray-300'
                                    ]"
                                >
                                    {{ size }}
                                </button>
                            </div>
                        </div>

                        <!-- Real-time toggle -->
                        <label class="relative inline-flex items-center cursor-pointer select-none">
                            <input type="checkbox" v-model="autoFormat" class="sr-only peer">
                            <div class="w-8 h-4 bg-gray-800 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-gray-400 after:border-gray-300 after:border after:rounded-full after:h-3 after:w-3 after:transition-all peer-checked:bg-rose-600 peer-checked:after:bg-white"></div>
                            <span class="ml-2 text-[10px] font-black text-gray-500 uppercase tracking-widest">Auto Format</span>
                        </label>
                    </div>

                    <!-- Right: Utilities -->
                    <div class="flex flex-wrap items-center gap-3">
                        <button 
                            @click="loadSample" 
                            class="px-4 py-2 rounded-xl text-xs font-bold bg-white/[0.03] border border-white/[0.06] text-gray-300 hover:bg-white/[0.08] hover:border-white/[0.12] transition-all duration-200"
                        >
                            Load Sample
                        </button>
                        
                        <!-- File Upload input link -->
                        <label class="px-4 py-2 rounded-xl text-xs font-bold bg-white/[0.03] border border-white/[0.06] text-gray-300 hover:bg-white/[0.08] hover:border-white/[0.12] transition-all duration-200 cursor-pointer">
                            Upload File
                            <input type="file" accept=".json" @change="handleFileUpload" class="hidden">
                        </label>
                    </div>
                </div>

                <!-- Textarea Editors -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 lg:gap-8">
                    <!-- Input Section -->
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <label class="text-sm font-black text-gray-300 uppercase tracking-widest">Input Raw JSON</label>
                            </div>
                            <button @click="clearAll" class="text-xs font-bold text-gray-500 hover:text-red-400 transition-colors uppercase tracking-wider">Clear</button>
                        </div>
                        <textarea
                            v-model="inputText"
                            placeholder="Paste your raw JSON string here..."
                            class="w-full h-[380px] bg-[#0B0F19] border border-gray-700/80 rounded-2xl p-5 text-gray-100 placeholder-gray-600 focus:border-rose-500 focus:ring-1 focus:ring-rose-500/50 transition-all outline-none resize-none font-mono text-sm shadow-inner custom-scrollbar"
                            spellcheck="false"
                        ></textarea>
                    </div>

                    <!-- Output Section -->
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <label class="text-sm font-black text-gray-300 uppercase tracking-widest">Formatted Output</label>
                            
                            <!-- Output Action Group -->
                            <div class="flex items-center gap-2">
                                <button 
                                    @click="downloadJSON"
                                    :disabled="!outputText"
                                    :class="[
                                        'inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold uppercase tracking-wider transition-all duration-200',
                                        outputText ? 'bg-white/[0.04] border border-white/[0.08] text-gray-300 hover:bg-white/[0.08] hover:border-white/[0.12] cursor-pointer' : 'bg-gray-800 text-gray-600 cursor-not-allowed'
                                    ]"
                                    title="Download formatted JSON"
                                >
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                                    Download
                                </button>
                                
                                <button 
                                    @click="copyToClipboard"
                                    :disabled="!outputText"
                                    :class="[
                                        'inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold uppercase tracking-wider transition-all duration-200',
                                        outputText ? 'bg-emerald-500/10 text-emerald-400 hover:bg-emerald-500/20 cursor-pointer' : 'bg-gray-800 text-gray-600 cursor-not-allowed'
                                    ]"
                                >
                                    <svg v-if="copied" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" /></svg>
                                    <svg v-else class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3" /></svg>
                                    {{ copied ? 'Copied!' : 'Copy' }}
                                </button>
                            </div>
                        </div>
                        
                        <!-- Output Area / Validation error Overlay -->
                        <div class="relative">
                            <textarea
                                v-model="outputText"
                                readonly
                                placeholder="Formatted and validated JSON output will appear here..."
                                :class="[
                                    'w-full h-[380px] bg-[#0B0F19]/50 border border-gray-800 rounded-2xl p-5 placeholder-gray-700 outline-none resize-none font-mono text-sm custom-scrollbar transition-all',
                                    errorMessage ? 'border-red-500/50 text-red-400' : 'text-rose-200'
                                ]"
                            ></textarea>

                            <!-- Error Overlay -->
                            <div v-if="errorMessage" class="absolute inset-0 bg-[#0B0F19]/90 backdrop-blur-sm rounded-2xl flex items-center justify-center p-6 border border-red-500/30">
                                <div class="text-center space-y-3 max-w-md">
                                    <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-red-500/10 text-red-500 mb-2">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                                    </div>
                                    <p class="text-red-400 text-sm font-bold uppercase tracking-widest">JSON Syntax Error</p>
                                    <p class="text-red-300 text-xs font-mono leading-relaxed bg-[#0B0F19] border border-red-500/20 p-4 rounded-xl text-left select-text custom-scrollbar overflow-x-auto max-h-[160px]">
                                        {{ errorMessage }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer Operations / Interactive Buttons -->
                <div class="flex flex-wrap items-center gap-3 pt-2">
                    <button 
                        @click="handleFormat"
                        class="flex-1 min-w-[140px] inline-flex items-center justify-center gap-x-2 rounded-2xl bg-rose-600 hover:bg-rose-500 px-6 py-4 text-sm font-bold text-white shadow-lg shadow-rose-600/20 transition-all active:scale-[0.98]"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7" /></svg>
                        Beautify / Format
                    </button>
                    <button 
                        @click="handleMinify"
                        class="flex-1 min-w-[140px] inline-flex items-center justify-center gap-x-2 rounded-2xl bg-[#0B0F19] hover:bg-gray-900 border border-gray-800 px-6 py-4 text-sm font-bold text-white transition-all active:scale-[0.98]"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                        Minify JSON
                    </button>
                    <button 
                        @click="handleValidate"
                        class="flex-1 min-w-[140px] inline-flex items-center justify-center gap-x-2 rounded-2xl bg-emerald-600 hover:bg-emerald-500 px-6 py-4 text-sm font-bold text-white shadow-lg shadow-emerald-600/20 transition-all active:scale-[0.98]"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        Validate JSON
                    </button>
                </div>

                <!-- Rich Analytics Display -->
                <div v-if="metrics" class="border-t border-gray-800 pt-6">
                    <h3 class="text-xs font-black text-gray-500 uppercase tracking-[0.2em] mb-4 flex items-center gap-2">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                        Structure Analysis
                    </h3>
                    <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                        <div class="bg-[#0B0F19]/50 border border-gray-800 rounded-2xl p-4 text-center">
                            <p class="text-xs font-bold text-gray-500 uppercase tracking-widest">Total Size</p>
                            <p class="text-xl font-black text-white mt-1 font-mono leading-none">{{ fileSize }}</p>
                        </div>
                        <div class="bg-[#0B0F19]/50 border border-gray-800 rounded-2xl p-4 text-center">
                            <p class="text-xs font-bold text-gray-500 uppercase tracking-widest">Object Keys</p>
                            <p class="text-xl font-black text-white mt-1 font-mono leading-none">{{ metrics.keysCount }}</p>
                        </div>
                        <div class="bg-[#0B0F19]/50 border border-gray-800 rounded-2xl p-4 text-center">
                            <p class="text-xs font-bold text-gray-500 uppercase tracking-widest">Nested Objects</p>
                            <p class="text-xl font-black text-white mt-1 font-mono leading-none">{{ metrics.objectsCount }}</p>
                        </div>
                        <div class="bg-[#0B0F19]/50 border border-gray-800 rounded-2xl p-4 text-center">
                            <p class="text-xs font-bold text-gray-500 uppercase tracking-widest">Total Arrays</p>
                            <p class="text-xl font-black text-white mt-1 font-mono leading-none">{{ metrics.arraysCount }}</p>
                        </div>
                        <div class="bg-[#0B0F19]/50 border border-gray-800 rounded-2xl p-4 text-center col-span-2 md:col-span-1">
                            <p class="text-xs font-bold text-gray-500 uppercase tracking-widest">Max Depth</p>
                            <p class="text-xl font-black text-white mt-1 font-mono leading-none">{{ metrics.maxDepth }}</p>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </PublicLayout>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 8px;
    height: 8px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: rgba(11, 15, 25, 0.5);
    border-radius: 8px;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: rgba(55, 65, 81, 0.8);
    border-radius: 8px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: rgba(75, 85, 99, 1);
}
</style>
