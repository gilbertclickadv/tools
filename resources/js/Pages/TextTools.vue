<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';

// --- Text State ---
const text = ref('');
const originalText = ref('');
const copiedFormat = ref(false);
const showStopWords = ref(false);
const activeTab = ref('analytics'); // 'analytics', 'transform', 'find-replace', 'comparison'

// Find & Replace State
const findQuery = ref('');
const replaceQuery = ref('');
const isRegex = ref(false);
const isCaseSensitive = ref(false);

// Common English Stop Words
const stopWords = new Set([
    'the', 'a', 'an', 'and', 'or', 'but', 'is', 'are', 'was', 'were', 
    'to', 'for', 'in', 'on', 'at', 'by', 'of', 'with', 'about', 'as', 
    'it', 'this', 'that', 'these', 'those', 'i', 'you', 'he', 'she', 
    'they', 'we', 'me', 'him', 'her', 'them', 'us', 'my', 'your', 'his', 
    'their', 'our', 'be', 'been', 'have', 'has', 'had', 'do', 'does', 
    'did', 'will', 'would', 'shall', 'should', 'can', 'could', 'may', 
    'might', 'must', 'so', 'if', 'then', 'else', 'no', 'not', 'very'
]);

// --- Real-time Metrics ---
const charCountWithSpaces = computed(() => text.value.length);

const charCountNoSpaces = computed(() => {
    return text.value.replace(/\s/g, '').length;
});

const wordCount = computed(() => {
    const cleaned = text.value.trim();
    if (!cleaned) return 0;
    return cleaned.split(/\s+/).length;
});

const sentenceCount = computed(() => {
    const cleaned = text.value.trim();
    if (!cleaned) return 0;
    const matches = cleaned.match(/[.!?]+(\s|$)/g);
    return matches ? matches.length : 1;
});

const paragraphCount = computed(() => {
    const cleaned = text.value.trim();
    if (!cleaned) return 0;
    return cleaned.split(/\n\s*\n+/).filter(p => p.trim().length > 0).length;
});

const readingTime = computed(() => {
    // Average reading speed: 225 WPM
    return Math.max(1, Math.ceil(wordCount.value / 225));
});

const speakingTime = computed(() => {
    // Average speaking speed: 140 WPM
    return Math.max(1, Math.ceil(wordCount.value / 140));
});

// --- Word Frequency & Density Analyzer ---
const wordDensity = computed(() => {
    const cleaned = text.value.toLowerCase().trim();
    if (!cleaned) return [];
    
    // Split into words, matching only alphanumeric segments
    const words = cleaned.split(/[^a-z0-9']+/).filter(w => w.length > 1);
    const totalWords = words.length;
    if (totalWords === 0) return [];
    
    const freqMap = {};
    words.forEach(w => {
        if (!showStopWords.value && stopWords.has(w)) return;
        freqMap[w] = (freqMap[w] || 0) + 1;
    });
    
    const densityList = Object.entries(freqMap).map(([word, count]) => {
        return {
            word,
            count,
            percentage: ((count / totalWords) * 100).toFixed(1)
        };
    });
    
    // Sort by count descending
    return densityList.sort((a, b) => b.count - a.count).slice(0, 8);
});

// --- Find & Replace Live Match Count ---
const liveMatches = computed(() => {
    if (!findQuery.value || !text.value) return 0;
    try {
        let flags = 'g';
        if (!isCaseSensitive.value) flags += 'i';
        
        let regex;
        if (isRegex.value) {
            regex = new RegExp(findQuery.value, flags);
        } else {
            const escaped = findQuery.value.replace(/[-\/\\^$*+?.()|[\]{}]/g, '\\$&');
            regex = new RegExp(escaped, flags);
        }
        
        const matches = text.value.match(regex);
        return matches ? matches.length : 0;
    } catch (e) {
        return 0; // invalid regex
    }
});

// --- Text Transformation Operations ---
const applyTransformation = (type) => {
    if (!text.value) return;
    
    // Save original for comparison before first transform
    if (!originalText.value) {
        originalText.value = text.value;
    }
    
    switch (type) {
        case 'upper':
            text.value = text.value.toUpperCase();
            break;
        case 'lower':
            text.value = text.value.toLowerCase();
            break;
        case 'title':
            text.value = text.value.replace(/\b\w+/g, s => s.charAt(0).toUpperCase() + s.substring(1).toLowerCase());
            break;
        case 'sentence':
            text.value = text.value.toLowerCase().replace(/(^\s*|[.!?]\s+)(\w)/g, s => s.toUpperCase());
            break;
        case 'slugify':
            text.value = text.value.toLowerCase().trim()
                .replace(/[^a-z0-9\s-]/g, '')
                .replace(/\s+/g, '-')
                .replace(/-+/g, '-');
            break;
        case 'snake':
            text.value = text.value.toLowerCase().trim()
                .replace(/[^a-z0-9\s-]/g, '')
                .replace(/[\s-]+/g, '_');
            break;
        case 'camel':
            {
                const words = text.value.toLowerCase().replace(/[^a-z0-9\s-_]/g, '').split(/[\s-_]+/);
                text.value = words.map((w, idx) => idx === 0 ? w : w.charAt(0).toUpperCase() + w.slice(1)).join('');
            }
            break;
        case 'pascal':
            {
                const words = text.value.toLowerCase().replace(/[^a-z0-9\s-_]/g, '').split(/[\s-_]+/);
                text.value = words.map(w => w.charAt(0).toUpperCase() + w.slice(1)).join('');
            }
            break;
        case 'trim':
            text.value = text.value.split('\n').map(line => line.trim()).join('\n');
            break;
        case 'collapse':
            text.value = text.value.replace(/[ \t]+/g, ' ');
            break;
        case 'strip-html':
            text.value = text.value.replace(/<\/?[^>]+(>|$)/g, "");
            break;
        case 'remove-empty':
            text.value = text.value.split('\n').filter(line => line.trim() !== '').join('\n');
            break;
        case 'reverse':
            text.value = text.value.split('').reverse().join('');
            break;
    }
};

// --- Find & Replace Execution ---
const handleReplace = () => {
    if (!findQuery.value || !text.value) return;
    try {
        if (!originalText.value) {
            originalText.value = text.value;
        }
        
        let flags = 'g';
        if (!isCaseSensitive.value) flags += 'i';
        
        let regex;
        if (isRegex.value) {
            regex = new RegExp(findQuery.value, flags);
        } else {
            const escaped = findQuery.value.replace(/[-\/\\^$*+?.()|[\]{}]/g, '\\$&');
            regex = new RegExp(escaped, flags);
        }
        
        text.value = text.value.replace(regex, replaceQuery.value);
    } catch (e) {
        alert('Invalid Regular Expression pattern.');
    }
};

// --- Clipboard Actions ---
const handleCopy = async () => {
    if (!text.value) return;
    try {
        await navigator.clipboard.writeText(text.value);
        copiedFormat.value = true;
        setTimeout(() => {
            copiedFormat.value = false;
        }, 2000);
    } catch (err) {
        console.error('Failed to copy text: ', err);
    }
};

const handlePaste = async () => {
    try {
        const clipboard = await navigator.clipboard.readText();
        if (clipboard) {
            if (!originalText.value && text.value) {
                originalText.value = text.value;
            }
            text.value = clipboard;
        }
    } catch (err) {
        alert('Could not read clipboard data. Please paste manually.');
    }
};

const clearAll = () => {
    if (text.value && !originalText.value) {
        originalText.value = text.value;
    }
    text.value = '';
    localStorage.removeItem('fluxmedia_text_draft');
};

const loadSample = () => {
    originalText.value = text.value;
    text.value = `FluxMedia provides high-fidelity, premium developer tools designed for fluid, client-side execution. 

A beautiful dark theme, glassmorphic card overlays, and fine-tuned HSL borders ensure the user is wowed at first glance. These Text Tools allow you to count character sizes, strip <b>HTML</b> tags, generate slug keys, check word density, and apply custom Regex (Regular Expressions) in real-time.

Try replacing the word "FluxMedia" with "Antigravity", or toggle stop words to analyze word distribution. Drafts are safely preserved in your browser's local storage automatically!`;
};

const resetToOriginal = () => {
    if (originalText.value) {
        text.value = originalText.value;
    }
};

// --- Lifecycle & Persistence ---
const seoScripts = [];

onMounted(() => {
    // Load local storage draft
    const draft = localStorage.getItem('fluxmedia_text_draft');
    if (draft) {
        text.value = draft;
    }

    // Inject SEO structured schemas
    const schemas = [
        {
            '@context': 'https://schema.org',
            '@type': 'WebApplication',
            'name': 'Text Tools & Analysis Suite — Free Online Text Editor',
            'url': 'https://fluxmedia.space/tools/text',
            'description': 'Free online text tools suite. Count words, characters, sentences, and paragraphs, convert cases, strip HTML, run regex find & replace, and analyze word density in real-time.',
            'applicationCategory': 'UtilityApplication',
            'operatingSystem': 'Web, Windows, macOS, Linux, Android, iOS',
            'offers': { '@type': 'Offer', 'price': '0', 'priceCurrency': 'USD' },
            'featureList': ['Word Count', 'Character Count', 'Sentence & Paragraph Count', 'Case Conversion (UPPER, lower, Title, Sentence, camelCase, PascalCase)', 'Slugify & snake_case', 'Strip HTML Tags', 'Word Density Analysis', 'Regex Find & Replace', 'Local Storage Autosave'],
        },
        {
            '@context': 'https://schema.org',
            '@type': 'HowTo',
            'name': 'How to Use Online Text Analysis and Transformation Tools',
            'description': 'Step-by-step guide to using FluxMedia Text Tools Suite.',
            'totalTime': 'PT1M',
            'step': [
                { '@type': 'HowToStep', 'position': 1, 'name': 'Paste or Type Text', 'text': 'Paste or type your text into the interactive editor. It is autosaved to your browser automatically.' },
                { '@type': 'HowToStep', 'position': 2, 'name': 'View Real-time Metrics', 'text': 'See your character count, word count, sentence count, reading time, and more update live.' },
                { '@type': 'HowToStep', 'position': 3, 'name': 'Transform Text', 'text': 'Use the Modifiers tab to convert case, slugify, strip HTML tags, or reverse text.' },
                { '@type': 'HowToStep', 'position': 4, 'name': 'Find & Replace with Regex', 'text': 'Switch to the Regex tab to run pattern-based find & replace with optional case sensitivity.' },
            ],
        },
        {
            '@context': 'https://schema.org',
            '@type': 'FAQPage',
            'mainEntity': [
                { '@type': 'Question', 'name': 'Is my text stored on a server?', 'acceptedAnswer': { '@type': 'Answer', 'text': 'No. All text processing happens in your browser. Drafts are saved to your browser local storage only, and never sent to any server.' } },
                { '@type': 'Question', 'name': 'What case conversions are available?', 'acceptedAnswer': { '@type': 'Answer', 'text': 'UPPERCASE, lowercase, Title Case, Sentence case, slugify-text, snake_case, camelCase, and PascalCase.' } },
                { '@type': 'Question', 'name': 'Can I use regular expressions in Find & Replace?', 'acceptedAnswer': { '@type': 'Answer', 'text': 'Yes. Toggle the Use Regex option in the Regex tab to use full JavaScript regular expression patterns with optional case sensitivity.' } },
            ],
        },
        {
            '@context': 'https://schema.org',
            '@type': 'BreadcrumbList',
            'itemListElement': [
                { '@type': 'ListItem', 'position': 1, 'name': 'Home', 'item': 'https://fluxmedia.space' },
                { '@type': 'ListItem', 'position': 2, 'name': 'Text Tools', 'item': 'https://fluxmedia.space/tools/text' },
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

watch(text, (newVal) => {
    if (newVal) {
        localStorage.setItem('fluxmedia_text_draft', newVal);
    } else {
        localStorage.removeItem('fluxmedia_text_draft');
    }
});
</script>

<template>
    <PublicLayout>
        <Head>
            <title>Free Online Text Tool Suite & Word Count Analyzer | FluxMedia</title>
            <meta name="description" content="A premium real-time text manipulation dashboard. Count characters, convert cases, strip HTML tags, run regex find & replace, and analyze word density in browser." />
            <meta name="keywords" content="text tool, word count, character counter, regex find replace, title case, slugify, HTML stripper, duplicate lines, string editor, side-by-side diff" />
            <meta name="author" content="FluxMedia" />
            <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1" />
            <meta name="googlebot" content="index, follow, max-snippet:-1, max-image-preview:large" />
            <link rel="canonical" href="https://fluxmedia.space/tools/text" />

            <!-- Open Graph -->
            <meta property="og:type" content="website" />
            <meta property="og:title" content="Free Online Text Tool Suite & Word Count Analyzer | FluxMedia" />
            <meta property="og:description" content="A premium real-time text editing, case conversion, word density analysis, and regex find & replace dashboard." />
            <meta property="og:image" content="https://fluxmedia.space/assets/images/fluxmedia_main.webp" />
            <meta property="og:url" content="https://fluxmedia.space/tools/text" />
            <meta property="og:site_name" content="FluxMedia" />

            <!-- Twitter -->
            <meta name="twitter:card" content="summary_large_image" />
            <meta name="twitter:title" content="Free Online Text Tool Suite & Word Count Analyzer" />
            <meta name="twitter:description" content="A premium real-time text editing, case conversion, word density analysis, and regex find & replace dashboard." />
            <meta name="twitter:image" content="https://fluxmedia.space/assets/images/fluxmedia_main.webp" />
        </Head>

        <!-- Ambient Glow Backdrops -->
        <div class="fixed inset-0 overflow-hidden pointer-events-none -z-10 bg-[#0B0F19]">
            <div class="absolute top-[-10%] left-[-10%] w-[40%] h-[40%] rounded-full bg-rose-600/10 blur-[120px]"></div>
            <div class="absolute bottom-[-10%] right-[-10%] w-[40%] h-[40%] rounded-full bg-indigo-600/10 blur-[120px]"></div>
            <div class="absolute top-[30%] left-[60%] w-[35%] h-[35%] rounded-full bg-pink-600/5 blur-[110px]"></div>
        </div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 relative z-10">
            <!-- Header Banner -->
            <div class="text-center space-y-4 mb-8">
                <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-gray-800/50 border border-gray-700/50 backdrop-blur-md">
                    <span class="w-2 h-2 rounded-full bg-rose-500 animate-pulse"></span>
                    <span class="text-xs font-semibold text-gray-300 uppercase tracking-wider">Content & Code Utilities</span>
                </div>
                
                <h1 class="text-3xl sm:text-4xl font-extrabold tracking-tight text-white">
                    Text Tools & <span class="bg-gradient-to-r from-rose-400 via-pink-400 to-indigo-400 bg-clip-text text-transparent">Analysis Suite</span>
                </h1>
                
                <p class="text-xs sm:text-sm text-gray-400 max-w-2xl mx-auto leading-relaxed">
                    A beautiful client-side workspace featuring live density indexing, case conversions, smart spacing cleanups, regex matching, and comparative differences instantly.
                </p>
            </div>

            <!-- Workspace Layout -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
                
                <!-- Main Editor Area (Col 7/12) -->
                <div class="lg:col-span-8 space-y-6">
                    <div class="relative bg-gray-900/50 border border-white/[0.06] rounded-2xl p-6 backdrop-blur-xl shadow-2xl">
                        
                        <!-- Toolbar Header -->
                        <div class="flex flex-wrap items-center justify-between gap-4 mb-4">
                            <div class="flex items-center gap-2">
                                <span class="p-2 bg-rose-500/10 rounded-lg text-rose-400">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                </span>
                                <h2 class="text-lg font-bold text-white">Interactive Editor</h2>
                            </div>
                            
                            <!-- Editor Control Buttons -->
                            <div class="flex items-center gap-2">
                                <button 
                                    @click="loadSample"
                                    class="text-xs px-3 py-1.5 rounded-lg bg-white/5 border border-white/10 text-gray-300 hover:bg-white/10 hover:text-white transition duration-200"
                                >
                                    Sample Text
                                </button>
                                <button 
                                    v-if="originalText"
                                    @click="resetToOriginal"
                                    class="text-xs px-3 py-1.5 rounded-lg bg-[#3B82F6]/10 border border-[#3B82F6]/20 text-[#60A5FA] hover:bg-[#3B82F6]/20 transition duration-200"
                                    title="Restore back to before transformations were applied"
                                >
                                    Restore Original
                                </button>
                                <button 
                                    @click="clearAll"
                                    class="text-xs px-3 py-1.5 rounded-lg bg-red-500/10 border border-red-500/20 text-red-400 hover:bg-red-500/20 transition duration-200"
                                >
                                    Clear
                                </button>
                            </div>
                        </div>

                        <!-- Editor Textarea Wrapper -->
                        <div class="relative">
                            <textarea
                                v-model="text"
                                placeholder="Paste or type your text here to begin analysis..."
                                class="w-full h-80 sm:h-96 bg-[#0B0F19]/80 border border-gray-800 focus:border-rose-500/80 focus:ring-1 focus:ring-rose-500/30 rounded-xl p-4 text-sm text-gray-200 placeholder-gray-600 focus:outline-none transition duration-200 resize-none custom-scrollbar"
                            ></textarea>
                            
                            <!-- Autosave Badge overlay -->
                            <div class="absolute bottom-3 right-4 flex items-center gap-1.5 text-[10px] font-semibold text-gray-500 bg-gray-900/60 px-2 py-1 rounded-md border border-white/[0.04] pointer-events-none">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-ping"></span>
                                Autosaved
                            </div>
                        </div>

                        <!-- Quick Copy/Paste Footer -->
                        <div class="flex items-center justify-between gap-4 mt-4 pt-4 border-t border-white/[0.04]">
                            <p class="text-[11px] text-gray-500">
                                Powered entirely inside browser sandbox. Data never leaves your computer.
                            </p>
                            
                            <div class="flex items-center gap-2">
                                <button 
                                    @click="handlePaste"
                                    class="flex items-center gap-1.5 text-xs px-3 py-2 rounded-lg bg-gray-800/80 hover:bg-gray-700 text-gray-300 hover:text-white transition duration-200"
                                >
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                    </svg>
                                    Paste
                                </button>
                                
                                <button 
                                    @click="handleCopy"
                                    :class="[
                                        'flex items-center gap-1.5 text-xs px-4 py-2 rounded-lg transition duration-200 font-medium',
                                        copiedFormat 
                                            ? 'bg-emerald-500/10 border border-emerald-500/20 text-emerald-400' 
                                            : 'bg-rose-600 hover:bg-rose-500 text-white'
                                    ]"
                                >
                                    <svg v-if="!copiedFormat" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3" />
                                    </svg>
                                    <svg v-else class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                    {{ copiedFormat ? 'Copied!' : 'Copy Result' }}
                                </button>
                            </div>
                        </div>

                    </div>

                    <!-- Split Diff / Comparison Section (Visible only when original and modified differ) -->
                    <div v-if="originalText && originalText !== text" class="bg-gray-900/50 border border-white/[0.06] rounded-2xl p-6 backdrop-blur-xl shadow-2xl">
                        <div class="flex items-center justify-between gap-4 mb-4">
                            <div class="flex items-center gap-2">
                                <span class="p-2 bg-indigo-500/10 rounded-lg text-indigo-400">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                                    </svg>
                                </span>
                                <h3 class="text-md font-bold text-white">Compare Dynamic Differences</h3>
                            </div>
                            
                            <button 
                                @click="originalText = ''" 
                                class="text-[10px] text-gray-500 hover:text-gray-300 font-bold uppercase tracking-widest transition"
                            >
                                Clear Diff History
                            </button>
                        </div>
                        
                        <!-- Comparison Columns -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <h4 class="text-xs font-semibold text-gray-500 mb-2 uppercase tracking-wider">Before Transformations</h4>
                                <div class="w-full h-48 bg-[#0B0F19]/40 border border-gray-950/60 rounded-xl p-3 text-xs text-gray-500 overflow-y-auto custom-scrollbar whitespace-pre-wrap leading-relaxed">
                                    {{ originalText }}
                                </div>
                            </div>
                            <div>
                                <h4 class="text-xs font-semibold text-rose-400/80 mb-2 uppercase tracking-wider">Current Modified State</h4>
                                <div class="w-full h-48 bg-[#0B0F19]/60 border border-rose-950/30 rounded-xl p-3 text-xs text-gray-300 overflow-y-auto custom-scrollbar whitespace-pre-wrap leading-relaxed">
                                    {{ text }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Control Panels & Analytics Sidebar (Col 4/12) -->
                <div class="lg:col-span-4 space-y-6">
                    
                    <!-- Advanced Real-time Metrics Card -->
                    <div class="bg-gray-900/50 border border-white/[0.06] rounded-2xl p-5 backdrop-blur-xl shadow-2xl">
                        <h3 class="text-sm font-bold text-gray-200 mb-4 uppercase tracking-wider border-b border-white/[0.04] pb-2">
                            Advanced Metrics
                        </h3>
                        
                        <!-- Grid layout for numbers -->
                        <div class="grid grid-cols-2 gap-4">
                            <div class="bg-[#0B0F19]/60 border border-white/[0.02] p-3.5 rounded-xl text-center">
                                <span class="block text-2xl font-black text-white leading-none mb-1">
                                    {{ charCountWithSpaces }}
                                </span>
                                <span class="text-[10px] font-medium text-gray-500 uppercase tracking-widest">Chars (With space)</span>
                            </div>
                            
                            <div class="bg-[#0B0F19]/60 border border-white/[0.02] p-3.5 rounded-xl text-center">
                                <span class="block text-2xl font-black text-white leading-none mb-1">
                                    {{ charCountNoSpaces }}
                                </span>
                                <span class="text-[10px] font-medium text-gray-500 uppercase tracking-widest">Chars (No space)</span>
                            </div>
                            
                            <div class="bg-[#0B0F19]/60 border border-white/[0.02] p-3.5 rounded-xl text-center">
                                <span class="block text-2xl font-black text-white leading-none mb-1">
                                    {{ wordCount }}
                                </span>
                                <span class="text-[10px] font-medium text-gray-500 uppercase tracking-widest">Word Count</span>
                            </div>

                            <div class="bg-[#0B0F19]/60 border border-white/[0.02] p-3.5 rounded-xl text-center">
                                <span class="block text-2xl font-black text-white leading-none mb-1">
                                    {{ sentenceCount }}
                                </span>
                                <span class="text-[10px] font-medium text-gray-500 uppercase tracking-widest">Sentences</span>
                            </div>

                            <div class="bg-[#0B0F19]/60 border border-white/[0.02] p-3.5 rounded-xl text-center">
                                <span class="block text-2xl font-black text-white leading-none mb-1">
                                    {{ paragraphCount }}
                                </span>
                                <span class="text-[10px] font-medium text-gray-500 uppercase tracking-widest">Paragraphs</span>
                            </div>

                            <div class="bg-[#0B0F19]/60 border border-white/[0.02] p-3.5 rounded-xl text-center">
                                <span class="block text-2xl font-black text-white leading-none mb-1">
                                    {{ readingTime }}m
                                </span>
                                <span class="text-[10px] font-medium text-gray-500 uppercase tracking-widest">Reading time</span>
                            </div>
                        </div>

                        <!-- Speaking speed indicator -->
                        <div class="mt-4 flex items-center justify-between text-xs px-3 py-2 bg-[#0B0F19]/30 rounded-lg border border-white/[0.03]">
                            <span class="text-gray-500 font-medium">Estimated Speech Duration:</span>
                            <span class="text-indigo-400 font-bold">{{ speakingTime }} min</span>
                        </div>
                    </div>

                    <!-- Toolbar Tabs selector -->
                    <div class="flex bg-gray-950/80 p-1 rounded-xl border border-white/[0.04]">
                        <button 
                            @click="activeTab = 'analytics'"
                            :class="[
                                'flex-1 text-center py-2 rounded-lg text-xs font-semibold transition',
                                activeTab === 'analytics' ? 'bg-rose-600/10 text-rose-400 border border-rose-500/20' : 'text-gray-500 hover:text-gray-300'
                            ]"
                        >
                            Density
                        </button>
                        <button 
                            @click="activeTab = 'transform'"
                            :class="[
                                'flex-1 text-center py-2 rounded-lg text-xs font-semibold transition',
                                activeTab === 'transform' ? 'bg-rose-600/10 text-rose-400 border border-rose-500/20' : 'text-gray-500 hover:text-gray-300'
                            ]"
                        >
                            Modifiers
                        </button>
                        <button 
                            @click="activeTab = 'find-replace'"
                            :class="[
                                'flex-1 text-center py-2 rounded-lg text-xs font-semibold transition',
                                activeTab === 'find-replace' ? 'bg-rose-600/10 text-rose-400 border border-rose-500/20' : 'text-gray-500 hover:text-gray-300'
                            ]"
                        >
                            Regex
                        </button>
                    </div>

                    <!-- Tab Panel 1: Word Density Analyzer -->
                    <div v-if="activeTab === 'analytics'" class="bg-gray-900/50 border border-white/[0.06] rounded-2xl p-5 backdrop-blur-xl shadow-2xl">
                        <div class="flex items-center justify-between mb-4 border-b border-white/[0.04] pb-2">
                            <h3 class="text-sm font-bold text-gray-200 uppercase tracking-wider">
                                Word Distribution
                            </h3>
                            <button 
                                @click="showStopWords = !showStopWords"
                                class="text-[10px] font-bold uppercase text-gray-500 hover:text-rose-400 transition"
                            >
                                {{ showStopWords ? 'Hide Stop Words' : 'Show Stop Words' }}
                            </button>
                        </div>
                        
                        <div v-if="wordDensity.length === 0" class="text-center py-6 text-xs text-gray-600 leading-normal">
                            Start typing in the text canvas to view structural word frequencies.
                        </div>

                        <div v-else class="space-y-3.5">
                            <div v-for="item in wordDensity" :key="item.word" class="space-y-1">
                                <div class="flex items-center justify-between text-xs font-semibold">
                                    <span class="text-gray-300 font-mono bg-white/[0.03] px-1.5 py-0.5 rounded border border-white/[0.04]">
                                        {{ item.word }}
                                    </span>
                                    <span class="text-gray-500">
                                        {{ item.count }} ({{ item.percentage }}%)
                                    </span>
                                </div>
                                
                                <div class="w-full h-1.5 bg-gray-950/80 rounded-full overflow-hidden border border-white/[0.02]">
                                    <div 
                                        class="h-full bg-gradient-to-r from-rose-500 to-pink-500 rounded-full"
                                        :style="{ width: item.percentage + '%' }"
                                    ></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tab Panel 2: Case Conversions & Cleaners -->
                    <div v-if="activeTab === 'transform'" class="bg-gray-900/50 border border-white/[0.06] rounded-2xl p-5 backdrop-blur-xl shadow-2xl space-y-6">
                        
                        <!-- Case Transformations -->
                        <div>
                            <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Case Convert</h3>
                            <div class="grid grid-cols-2 gap-2">
                                <button 
                                    @click="applyTransformation('upper')"
                                    class="text-xs px-3 py-2 bg-[#0B0F19]/60 hover:bg-[#0B0F19] text-gray-300 hover:text-white border border-white/[0.04] rounded-xl text-left transition"
                                >
                                    UPPERCASE
                                </button>
                                <button 
                                    @click="applyTransformation('lower')"
                                    class="text-xs px-3 py-2 bg-[#0B0F19]/60 hover:bg-[#0B0F19] text-gray-300 hover:text-white border border-white/[0.04] rounded-xl text-left transition"
                                >
                                    lowercase
                                </button>
                                <button 
                                    @click="applyTransformation('title')"
                                    class="text-xs px-3 py-2 bg-[#0B0F19]/60 hover:bg-[#0B0F19] text-gray-300 hover:text-white border border-white/[0.04] rounded-xl text-left transition"
                                >
                                    Title Case
                                </button>
                                <button 
                                    @click="applyTransformation('sentence')"
                                    class="text-xs px-3 py-2 bg-[#0B0F19]/60 hover:bg-[#0B0F19] text-gray-300 hover:text-white border border-white/[0.04] rounded-xl text-left transition"
                                >
                                    Sentence case
                                </button>
                                <button 
                                    @click="applyTransformation('slugify')"
                                    class="text-xs px-3 py-2 bg-[#0B0F19]/60 hover:bg-[#0B0F19] text-gray-300 hover:text-white border border-white/[0.04] rounded-xl text-left transition"
                                >
                                    Slugify-text
                                </button>
                                <button 
                                    @click="applyTransformation('snake')"
                                    class="text-xs px-3 py-2 bg-[#0B0F19]/60 hover:bg-[#0B0F19] text-gray-300 hover:text-white border border-white/[0.04] rounded-xl text-left transition"
                                >
                                    snake_case
                                </button>
                                <button 
                                    @click="applyTransformation('camel')"
                                    class="text-xs px-3 py-2 bg-[#0B0F19]/60 hover:bg-[#0B0F19] text-gray-300 hover:text-white border border-white/[0.04] rounded-xl text-left transition"
                                >
                                    camelCase
                                </button>
                                <button 
                                    @click="applyTransformation('pascal')"
                                    class="text-xs px-3 py-2 bg-[#0B0F19]/60 hover:bg-[#0B0F19] text-gray-300 hover:text-white border border-white/[0.04] rounded-xl text-left transition"
                                >
                                    PascalCase
                                </button>
                            </div>
                        </div>

                        <!-- String Cleaner utilities -->
                        <div>
                            <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Clean Strings</h3>
                            <div class="grid grid-cols-2 gap-2">
                                <button 
                                    @click="applyTransformation('trim')"
                                    class="text-xs px-3 py-2 bg-[#0B0F19]/60 hover:bg-[#0B0F19] text-gray-300 hover:text-white border border-white/[0.04] rounded-xl text-left transition"
                                >
                                    Trim Lines
                                </button>
                                <button 
                                    @click="applyTransformation('collapse')"
                                    class="text-xs px-3 py-2 bg-[#0B0F19]/60 hover:bg-[#0B0F19] text-gray-300 hover:text-white border border-white/[0.04] rounded-xl text-left transition"
                                >
                                    Remove Double Spaces
                                </button>
                                <button 
                                    @click="applyTransformation('remove-empty')"
                                    class="text-xs px-3 py-2 bg-[#0B0F19]/60 hover:bg-[#0B0F19] text-gray-300 hover:text-white border border-white/[0.04] rounded-xl text-left transition"
                                >
                                    Strip Blank Lines
                                </button>
                                <button 
                                    @click="applyTransformation('strip-html')"
                                    class="text-xs px-3 py-2 bg-[#0B0F19]/60 hover:bg-[#0B0F19] text-gray-300 hover:text-white border border-white/[0.04] rounded-xl text-left transition"
                                    title="Strips raw HTML angle tags"
                                >
                                    Strip HTML Tags
                                </button>
                                <button 
                                    @click="applyTransformation('reverse')"
                                    class="text-xs px-3 py-2 bg-[#0B0F19]/60 hover:bg-[#0B0F19] text-gray-300 hover:text-white border border-white/[0.04] rounded-xl text-left transition"
                                >
                                    Reverse Text
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Tab Panel 3: Regex Find & Replace -->
                    <div v-if="activeTab === 'find-replace'" class="bg-gray-900/50 border border-white/[0.06] rounded-2xl p-5 backdrop-blur-xl shadow-2xl space-y-4">
                        <h3 class="text-sm font-bold text-gray-200 border-b border-white/[0.04] pb-2 uppercase tracking-wider">
                            Find & Replace
                        </h3>

                        <!-- Find Field -->
                        <div class="space-y-1.5">
                            <label class="text-[10px] font-bold text-gray-500 uppercase tracking-widest">Find Pattern</label>
                            <input 
                                v-model="findQuery"
                                type="text"
                                placeholder="Target word or regex..."
                                class="w-full bg-[#0B0F19] border border-gray-800 focus:border-rose-500/80 focus:ring-1 focus:ring-rose-500/30 rounded-xl px-3 py-2 text-xs text-white focus:outline-none transition duration-200"
                            />
                        </div>

                        <!-- Replace Field -->
                        <div class="space-y-1.5">
                            <label class="text-[10px] font-bold text-gray-500 uppercase tracking-widest">Replace With</label>
                            <input 
                                v-model="replaceQuery"
                                type="text"
                                placeholder="New string value..."
                                class="w-full bg-[#0B0F19] border border-gray-800 focus:border-rose-500/80 focus:ring-1 focus:ring-rose-500/30 rounded-xl px-3 py-2 text-xs text-white focus:outline-none transition duration-200"
                            />
                        </div>

                        <!-- Flag Options -->
                        <div class="flex items-center justify-between gap-4 pt-1.5">
                            <label class="flex items-center gap-1.5 text-xs text-gray-400 cursor-pointer select-none">
                                <input 
                                    v-model="isRegex"
                                    type="checkbox"
                                    class="rounded bg-[#0B0F19] border-gray-800 text-rose-600 focus:ring-0"
                                />
                                Use Regex (\d+)
                            </label>
                            <label class="flex items-center gap-1.5 text-xs text-gray-400 cursor-pointer select-none">
                                <input 
                                    v-model="isCaseSensitive"
                                    type="checkbox"
                                    class="rounded bg-[#0B0F19] border-gray-800 text-rose-600 focus:ring-0"
                                />
                                Case Sensitive
                            </label>
                        </div>

                        <!-- Matches and Execution -->
                        <div class="flex items-center justify-between pt-2 border-t border-white/[0.04]">
                            <span class="text-[10px] text-gray-500 font-semibold uppercase tracking-wider">
                                Matches: <span class="text-rose-400 font-black">{{ liveMatches }}</span>
                            </span>
                            
                            <button
                                @click="handleReplace"
                                :disabled="!findQuery"
                                class="text-xs px-4 py-2 rounded-xl font-medium bg-rose-600 hover:bg-rose-500 disabled:bg-gray-800 text-white disabled:text-gray-650 transition cursor-pointer"
                            >
                                Replace Matches
                            </button>
                        </div>
                    </div>

                </div>

            </div>
        </div>
    </PublicLayout>
</template>

<style>
/* Custom styled vertical scrollbars to match premium dark canvas */
.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
    height: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.08);
    border-radius: 99px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: rgba(255, 255, 255, 0.16);
}
</style>
