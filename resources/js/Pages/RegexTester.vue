<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';

// --- State Variables ---
const regexPattern = ref('[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\\.[a-zA-Z]{2,}');
const testText = ref('Hello developer! You can test your regular expressions here.\n\nMy email addresses are: support@example.com and contact.us@example.com.\nOur old email was office-info@example.org.\n\nYou can also test numeric patterns, phone numbers like +1-202-555-0143 or simple words.');
const flagGlobal = ref(true);
const flagIgnoreCase = ref(true);
const flagMultiline = ref(true);

const matchError = ref(null);
const copiedRegex = ref(false);
const copiedText = ref(false);

// --- Regex Match Computation ---
const regexFlags = computed(() => {
    let flags = '';
    if (flagGlobal.value) flags += 'g';
    if (flagIgnoreCase.value) flags += 'i';
    if (flagMultiline.value) flags += 'm';
    return flags;
});

const regexObj = computed(() => {
    matchError.value = null;
    if (!regexPattern.value) return null;
    try {
        return new RegExp(regexPattern.value, regexFlags.value);
    } catch (e) {
        matchError.value = e.message;
        return null;
    }
});

const regexMatches = computed(() => {
    if (!regexObj.value || !testText.value) return [];
    
    const matches = [];
    const text = testText.value;
    
    try {
        if (flagGlobal.value) {
            let match;
            // Reset index just in case
            regexObj.value.lastIndex = 0;
            
            // Limit iterations to prevent infinite loop crash
            let safetyCounter = 0;
            while ((match = regexObj.value.exec(text)) !== null && safetyCounter < 1000) {
                safetyCounter++;
                
                // Save match information
                matches.push({
                    value: match[0],
                    index: match.index,
                    groups: match.slice(1),
                    length: match[0].length
                });
                
                // Zero-length match correction to prevent infinite loop
                if (match[0].length === 0) {
                    regexObj.value.lastIndex++;
                }
            }
        } else {
            const match = text.match(regexObj.value);
            if (match) {
                matches.push({
                    value: match[0],
                    index: match.index ?? 0,
                    groups: match.slice(1),
                    length: match[0].length
                });
            }
        }
    } catch (e) {
        matchError.value = e.message;
    }
    
    return matches;
});

// --- Match Highlight HTML Generation ---
const highlightedTextHtml = computed(() => {
    if (!testText.value) return '<span class="text-gray-500 italic">No text provided.</span>';
    if (!regexPattern.value || matchError.value || regexMatches.value.length === 0) {
        // Return escaped text
        return escapeHtml(testText.value).replace(/\n/g, '<br>');
    }
    
    const text = testText.value;
    const sortedMatches = [...regexMatches.value].sort((a, b) => b.index - a.index);
    
    let html = text;
    
    // Inject highlight spans from back to front to preserve string indexes
    for (const match of sortedMatches) {
        if (match.length === 0) continue;
        
        const before = html.substring(0, match.index);
        const matchVal = html.substring(match.index, match.index + match.length);
        const after = html.substring(match.index + match.length);
        
        html = before + `<mark class="bg-rose-500/20 text-rose-300 border-b border-rose-400 px-0.5 rounded-sm font-semibold shadow-[0_0_10px_rgba(244,63,94,0.15)]">${escapeHtml(matchVal)}</mark>` + after;
    }
    
    return html.replace(/\n/g, '<br>');
});

function escapeHtml(text) {
    return text
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

// --- Intelligent Pattern Explainer ---
const patternExplanation = computed(() => {
    if (!regexPattern.value) return [];
    
    const explanation = [];
    const pattern = regexPattern.value;
    
    // Simple tokens classification mapping
    const rules = [
        { regex: /\\d/, desc: 'Matches any digit (equivalent to [0-9])' },
        { regex: /\\D/, desc: 'Matches any non-digit character (equivalent to [^0-9])' },
        { regex: /\\w/, desc: 'Matches any word character (alphanumeric and underscore)' },
        { regex: /\\W/, desc: 'Matches any non-word character' },
        { regex: /\\s/, desc: 'Matches any whitespace character (space, tab, newline)' },
        { regex: /\\S/, desc: 'Matches any non-whitespace character' },
        { regex: /\\b/, desc: 'Asserts a word boundary position' },
        { regex: /\^/, desc: 'Asserts the start of the string/line' },
        { regex: /\$/, desc: 'Asserts the end of the string/line' },
        { regex: /\./, desc: 'Matches any character except line break' },
        { regex: /\*/, desc: 'Matches 0 or more of the preceding token (greedy quantifier)' },
        { regex: /\+/, desc: 'Matches 1 or more of the preceding token (greedy quantifier)' },
        { regex: /\?/, desc: 'Matches 0 or 1 of the preceding token, or makes quantifiers lazy' },
        { regex: /\{\d+\}/, desc: 'Matches exactly the specified number of occurrences' },
        { regex: /\{\d+,\}/, desc: 'Matches the specified number of occurrences or more' },
        { regex: /\{\d+,\d+\}/, desc: 'Matches a range of occurrences' },
        { regex: /\[.+?\]/, desc: 'Matches a single character defined in the brackets (character set)' },
        { regex: /\(.+?\)/, desc: 'Groups multiple tokens together to create a capturing group' },
        { regex: /\|/, desc: 'Alternation operator (acts like a boolean OR)' }
    ];
    
    // Scan pattern for matching rules
    rules.forEach(rule => {
        const matches = pattern.match(rule.regex);
        if (matches) {
            explanation.push({
                symbol: matches[0],
                desc: rule.desc
            });
        }
    });
    
    if (explanation.length === 0) {
        explanation.push({
            symbol: 'Literal characters',
            desc: 'Matches the exact sequence of letters/characters precisely as typed.'
        });
    }
    
    return explanation;
});

// --- Copy Helpers ---
const handleCopy = async (text, refFlag) => {
    if (!text) return;
    try {
        await navigator.clipboard.writeText(text);
        refFlag.value = true;
        setTimeout(() => {
            refFlag.value = false;
        }, 2000);
    } catch (err) {
        console.error('Failed to copy: ', err);
    }
};

const loadSample = (pattern, text) => {
    regexPattern.value = pattern;
    testText.value = text;
};

// --- Lifecycle & Persistence ---
const seoScripts = [];
onMounted(() => {
    const savedPattern = localStorage.getItem('fluxmedia_regex_pattern');
    const savedText = localStorage.getItem('fluxmedia_regex_text');
    if (savedPattern !== null) regexPattern.value = savedPattern;
    if (savedText !== null) testText.value = savedText;

    // SEO Structured schemas
    const schemas = [
        {
            '@context': 'https://schema.org',
            '@type': 'WebApplication',
            'name': 'Regex Tester & Visual Explainer — Free Online Tool',
            'url': 'https://fluxmedia.space/tools/regex',
            'description': 'Free online regular expression tester and visual explainer. Test regex patterns with instant match highlighting, capture group inspection, and built-in cheat sheet.',
            'applicationCategory': 'DeveloperApplication',
            'operatingSystem': 'Web, Windows, macOS, Linux, Android, iOS',
            'offers': { '@type': 'Offer', 'price': '0', 'priceCurrency': 'USD' },
            'featureList': ['Live Match Highlighting', 'Capture Group Inspector', 'Pattern Explainer', 'Regex Cheat Sheet', 'Global/Case/Multiline Flags', 'Local Storage Autosave'],
        },
        {
            '@context': 'https://schema.org',
            '@type': 'HowTo',
            'name': 'How to Test a Regular Expression Online',
            'description': 'Step-by-step guide to testing and debugging regex patterns using FluxMedia Regex Tester.',
            'totalTime': 'PT1M',
            'step': [
                { '@type': 'HowToStep', 'position': 1, 'name': 'Enter Your Pattern', 'text': 'Type or paste your regular expression into the Expression Canvas input field.' },
                { '@type': 'HowToStep', 'position': 2, 'name': 'Toggle Flags', 'text': 'Enable or disable Global (g), Case Insensitive (i), and Multiline (m) flags using the flag buttons.' },
                { '@type': 'HowToStep', 'position': 3, 'name': 'Paste Test String', 'text': 'Enter your test input string in the Test String textarea.' },
                { '@type': 'HowToStep', 'position': 4, 'name': 'View Matches', 'text': 'All matches are highlighted in the Live Matching Highlights panel. The Match List Inspector shows index and length of each match.' },
            ],
        },
        {
            '@context': 'https://schema.org',
            '@type': 'FAQPage',
            'mainEntity': [
                { '@type': 'Question', 'name': 'What regex engine does the tester use?', 'acceptedAnswer': { '@type': 'Answer', 'text': 'The regex tester uses the JavaScript native RegExp engine, which is compatible with most standard PCRE-like patterns.' } },
                { '@type': 'Question', 'name': 'Does the regex tester save my patterns?', 'acceptedAnswer': { '@type': 'Answer', 'text': 'Yes. Your pattern and test text are automatically saved to browser local storage, so your work is preserved across page refreshes.' } },
                { '@type': 'Question', 'name': 'Can I test email or phone number regex patterns?', 'acceptedAnswer': { '@type': 'Answer', 'text': 'Yes. Use the built-in Email Pattern or Phone Pattern sample buttons to load common patterns instantly, or enter your own custom pattern.' } },
            ],
        },
        {
            '@context': 'https://schema.org',
            '@type': 'BreadcrumbList',
            'itemListElement': [
                { '@type': 'ListItem', 'position': 1, 'name': 'Home', 'item': 'https://fluxmedia.space' },
                { '@type': 'ListItem', 'position': 2, 'name': 'Regex Tester', 'item': 'https://fluxmedia.space/tools/regex' },
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

watch([regexPattern, testText], () => {
    localStorage.setItem('fluxmedia_regex_pattern', regexPattern.value);
    localStorage.setItem('fluxmedia_regex_text', testText.value);
});
</script>

<template>
    <PublicLayout>
        <Head>
            <title>Premium Online Regex Tester & Visual Explainer | FluxMedia</title>
            <meta name="description" content="A premium regular expression builder and analyzer. Test regex matches dynamically, review captured groups, and check visual pattern explanations in real-time." />
            <meta name="keywords" content="regex tester, regex builder, regular expression explainer, match highlighter, live regex testing, regex checker, developer tools, free utilities" />
            <meta name="author" content="FluxMedia" />
            <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1" />
            <meta name="googlebot" content="index, follow, max-snippet:-1, max-image-preview:large" />
            <link rel="canonical" href="https://fluxmedia.space/tools/regex" />
        </Head>

        <!-- Ambient Backdrop Colors (Rose Accent) -->
        <div class="fixed inset-0 overflow-hidden pointer-events-none -z-10 bg-[#0B0F19]">
            <div class="absolute top-[-10%] left-[-10%] w-[40%] h-[40%] rounded-full bg-rose-600/10 blur-[120px]"></div>
            <div class="absolute bottom-[-10%] right-[-10%] w-[40%] h-[40%] rounded-full bg-violet-600/10 blur-[120px]"></div>
            <div class="absolute top-[30%] left-[60%] w-[35%] h-[35%] rounded-full bg-rose-600/5 blur-[110px]"></div>
        </div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 relative z-10 font-jakarta">
            <!-- Header Banner -->
            <div class="text-center space-y-4 mb-8">
                <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-gray-800/50 border border-gray-700/50 backdrop-blur-md">
                    <span class="w-2.5 h-2.5 rounded-full bg-rose-500 animate-pulse"></span>
                    <span class="text-xs font-semibold text-gray-300 uppercase tracking-wider">Developer & Design Suite</span>
                </div>
                
                <h1 class="text-3xl sm:text-4xl font-extrabold tracking-tight text-white">
                    Regex Tester & <span class="bg-gradient-to-r from-rose-400 via-pink-400 to-violet-400 bg-clip-text text-transparent">Visual Explainer</span>
                </h1>
                
                <p class="text-xs sm:text-sm text-gray-400 max-w-2xl mx-auto leading-relaxed">
                    Build, test, and master regular expressions with instant highlighting, match lists, and graphical rules mapping. 100% free and client-side sandbox execution.
                </p>
            </div>

            <!-- Main Workspace split -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
                
                <!-- Left panel - Editor inputs (Col 7/12) -->
                <div class="lg:col-span-7 space-y-6">
                    <div class="bg-gray-900/50 border border-white/[0.06] rounded-2xl p-6 backdrop-blur-xl shadow-2xl space-y-5">
                        
                        <!-- Header toolbar -->
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="p-2 bg-rose-500/10 rounded-lg text-rose-400">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                </span>
                                <h2 class="text-md font-bold text-white">Expression Canvas</h2>
                            </div>

                            <!-- Samples dropdown -->
                            <div class="flex gap-2">
                                <button 
                                    @click="loadSample('[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\\.[a-zA-Z]{2,}', 'Test emails: developer@example.com, support@site.org and test-123@sub.domain.net.')"
                                    class="text-[10px] font-bold px-2 py-1.5 rounded-lg bg-white/5 border border-white/10 text-gray-300 hover:bg-white/10 hover:text-white transition duration-200"
                                >
                                    Email Pattern
                                </button>
                                <button 
                                    @click="loadSample('\\+\\d{1,3}-\\d{3}-\\d{3}-\\d{4}', 'Test phones: international +1-202-555-0143 and +44-163-296-0081.')"
                                    class="text-[10px] font-bold px-2 py-1.5 rounded-lg bg-white/5 border border-white/10 text-gray-300 hover:bg-white/10 hover:text-white transition duration-200"
                                >
                                    Phone Pattern
                                </button>
                            </div>
                        </div>

                        <!-- Pattern Box -->
                        <div class="space-y-2">
                            <label class="text-[10px] font-black uppercase text-gray-500 tracking-widest">Regular Expression</label>
                            <div class="relative flex items-center bg-[#0B0F19]/80 border border-gray-800 rounded-xl px-4 py-3 group focus-within:border-rose-500/50 transition">
                                <span class="text-rose-500 font-mono text-sm select-none mr-2">/</span>
                                <input 
                                    v-model="regexPattern"
                                    type="text"
                                    placeholder="Enter your pattern..."
                                    class="flex-1 bg-transparent text-xs font-mono text-white placeholder-gray-650 focus:outline-none"
                                />
                                <span class="text-rose-500 font-mono text-sm select-none mx-2">/</span>
                                
                                <!-- Flags indicators -->
                                <div class="flex items-center gap-1 bg-[#121826]/90 border border-white/[0.03] p-1 rounded-md text-[10px] font-mono">
                                    <button 
                                        @click="flagGlobal = !flagGlobal" 
                                        :class="['px-1.5 py-0.5 rounded font-bold transition', flagGlobal ? 'bg-rose-500/20 text-rose-400' : 'text-gray-600 hover:text-gray-400']"
                                        title="Global (g)"
                                    >g</button>
                                    <button 
                                        @click="flagIgnoreCase = !flagIgnoreCase" 
                                        :class="['px-1.5 py-0.5 rounded font-bold transition', flagIgnoreCase ? 'bg-rose-500/20 text-rose-400' : 'text-gray-600 hover:text-gray-400']"
                                        title="Case Insensitive (i)"
                                    >i</button>
                                    <button 
                                        @click="flagMultiline = !flagMultiline" 
                                        :class="['px-1.5 py-0.5 rounded font-bold transition', flagMultiline ? 'bg-rose-500/20 text-rose-400' : 'text-gray-600 hover:text-gray-400']"
                                        title="Multiline (m)"
                                    >m</button>
                                </div>
                            </div>

                            <!-- Error indicator -->
                            <div v-if="matchError" class="p-3.5 bg-red-500/10 border border-red-500/20 rounded-xl text-xs text-red-400 flex items-start gap-2 animate-pulse">
                                <svg class="w-4 h-4 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                                <span class="font-mono">Invalid Regex: {{ matchError }}</span>
                            </div>
                        </div>

                        <!-- Test Text Area -->
                        <div class="space-y-2">
                            <div class="flex items-center justify-between">
                                <label class="text-[10px] font-black uppercase text-gray-500 tracking-widest">Test String / Inputs</label>
                                <div class="flex items-center gap-3">
                                    <button 
                                        @click="handleCopy(testText, copiedText)"
                                        class="text-[10px] font-bold text-gray-500 hover:text-white flex items-center gap-1 transition"
                                    >
                                        <svg v-if="!copiedText" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2" />
                                        </svg>
                                        <span>{{ copiedText ? 'Copied' : 'Copy Input' }}</span>
                                    </button>
                                    <button 
                                        @click="testText = ''"
                                        class="text-[10px] font-bold text-red-500/70 hover:text-red-400 transition"
                                    >
                                        Clear
                                    </button>
                                </div>
                            </div>
                            
                            <div class="relative">
                                <textarea
                                    v-model="testText"
                                    placeholder="Enter string values to match against..."
                                    class="w-full h-56 bg-[#0B0F19]/80 border border-gray-800 focus:border-rose-500/80 focus:ring-1 focus:ring-rose-500/30 rounded-xl p-4 text-xs font-mono text-gray-300 placeholder-gray-650 focus:outline-none transition duration-200 resize-none custom-scrollbar leading-relaxed"
                                ></textarea>
                                
                                <div class="absolute bottom-3 right-4 flex items-center gap-1.5 text-[9px] font-semibold text-gray-500 bg-gray-900/60 px-2 py-1 rounded-md border border-white/[0.04]">
                                    <span class="w-1.5 h-1.5 rounded-full bg-rose-500 animate-ping"></span>
                                    Autosaved Draft
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- Highlight Match View Board -->
                    <div class="bg-gray-900/50 border border-white/[0.06] rounded-2xl p-6 backdrop-blur-xl shadow-2xl space-y-4">
                        <div class="flex items-center justify-between border-b border-white/[0.04] pb-2">
                            <div class="flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span>
                                <h3 class="text-xs font-black uppercase tracking-widest text-gray-200">Live Matching Highlights</h3>
                            </div>
                            
                            <!-- Counter indicator -->
                            <span 
                                :class="[
                                    'text-[10px] font-black uppercase tracking-widest px-2.5 py-0.5 rounded-md border',
                                    regexMatches.length > 0 
                                        ? 'bg-rose-500/10 text-rose-400 border-rose-500/20 shadow-glow' 
                                        : 'bg-gray-800/40 text-gray-500 border-gray-700/30'
                                ]"
                            >
                                {{ regexMatches.length }} {{ regexMatches.length === 1 ? 'Match' : 'Matches' }} found
                            </span>
                        </div>

                        <!-- Render Frame -->
                        <div 
                            class="w-full min-h-36 max-h-80 bg-[#0B0F19]/80 border border-gray-800/60 rounded-xl p-4 text-xs font-mono text-gray-300 overflow-y-auto custom-scrollbar break-words leading-relaxed border-t-2 border-t-rose-500/30 select-text"
                            v-html="highlightedTextHtml"
                        ></div>
                    </div>
                </div>

                <!-- Right panel - Inspector, explainer, cheat sheet (Col 5/12) -->
                <div class="lg:col-span-5 space-y-6">
                    
                    <!-- Match details list -->
                    <div class="bg-gray-900/50 border border-white/[0.06] rounded-2xl p-6 backdrop-blur-xl shadow-2xl space-y-4">
                        <h3 class="text-xs font-black uppercase tracking-widest text-gray-200 border-b border-white/[0.04] pb-2">
                            Match List Inspector
                        </h3>

                        <div v-if="regexMatches.length === 0" class="text-center py-8 text-xs text-gray-500 italic">
                            No match results found. Adjust your pattern or input string.
                        </div>
                        <div v-else class="max-h-56 overflow-y-auto custom-scrollbar space-y-2 pr-1">
                            <div 
                                v-for="(m, idx) in regexMatches.slice(0, 100)" 
                                :key="idx" 
                                class="flex items-center justify-between bg-[#0B0F19]/45 border border-white/[0.02] p-2.5 rounded-xl text-xs hover:border-rose-500/20 transition group"
                            >
                                <div class="flex items-center gap-2.5 min-w-0">
                                    <span class="text-[9px] font-black text-rose-400/80 bg-rose-500/10 px-1.5 py-0.5 rounded font-mono">
                                        #{{ idx + 1 }}
                                    </span>
                                    <span class="font-mono text-white font-semibold truncate select-all">{{ m.value }}</span>
                                </div>
                                <span class="font-mono text-[9px] text-gray-500 font-medium shrink-0">
                                    index: {{ m.index }} (len: {{ m.length }})
                                </span>
                            </div>
                            <p v-if="regexMatches.length > 100" class="text-[10px] text-center text-gray-500 pt-1 font-semibold">
                                Showing top 100 matches...
                            </p>
                        </div>
                    </div>

                    <!-- Explainer card -->
                    <div class="bg-gray-900/50 border border-white/[0.06] rounded-2xl p-6 backdrop-blur-xl shadow-2xl space-y-4">
                        <h3 class="text-xs font-black uppercase tracking-widest text-gray-200 border-b border-white/[0.04] pb-2">
                            Pattern Explainer
                        </h3>

                        <div class="space-y-3">
                            <div v-for="(exp, i) in patternExplanation" :key="i" class="flex gap-3 text-xs">
                                <span class="font-mono text-rose-400 bg-rose-500/10 border border-rose-500/10 px-2 py-0.5 rounded-md text-[11px] font-bold h-fit shrink-0">
                                    {{ exp.symbol }}
                                </span>
                                <p class="text-gray-400 leading-normal font-medium pt-0.5">{{ exp.desc }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Regex Cheat Sheet -->
                    <div class="bg-gray-900/50 border border-white/[0.06] rounded-2xl p-6 backdrop-blur-xl shadow-2xl space-y-4">
                        <h3 class="text-xs font-black uppercase tracking-widest text-gray-200 border-b border-white/[0.04] pb-2">
                            Regex Quick Reference
                        </h3>
                        
                        <div class="grid grid-cols-2 gap-x-4 gap-y-3 text-[11px] leading-relaxed">
                            <div v-for="item in [
                                { label: 'Any digit', code: '\\d' },
                                { label: 'Any word char', code: '\\w' },
                                { label: 'Any space char', code: '\\s' },
                                { label: 'Word boundary', code: '\\b' },
                                { label: 'Start of line', code: '^' },
                                { label: 'End of line', code: '$' },
                                { label: '0 or more times', code: '*' },
                                { label: '1 or more times', code: '+' },
                                { label: '0 or 1 time', code: '?' },
                                { label: 'Set (e.g. A-Z)', code: '[A-Z]' },
                                { label: 'Not in set', code: '[^0-9]' },
                                { label: 'Alternative (OR)', code: 'a|b' },
                            ]" :key="item.code" class="flex justify-between items-center bg-[#0B0F19]/40 border border-white/[0.02] p-2 rounded-lg">
                                <span class="text-gray-400 font-medium">{{ item.label }}</span>
                                <code class="text-rose-400 font-mono font-bold bg-rose-500/5 px-1.5 py-0.5 rounded">{{ item.code }}</code>
                            </div>
                        </div>
                    </div>

                </div>

            </div>

        </div>
    </PublicLayout>
</template>

<style>
/* Glowing shadow accent */
.shadow-glow {
    box-shadow: 0 0 15px rgba(244, 63, 94, 0.15);
}

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
