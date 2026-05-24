<script setup>
import { ref, watch, onMounted, onUnmounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';

// Programmatic JSON-LD Injection
const seoScripts = [];
onMounted(() => {
    const schemas = [
        {
            '@context': 'https://schema.org',
            '@type': 'WebApplication',
            'name': 'Base64 Encoder & Decoder — Free Online Tool',
            'url': 'https://fluxmedia.space/tools/base64',
            'description': 'Free online Base64 encoder and decoder. Encode text to Base64 or decode Base64 strings back to readable text instantly, 100% in your browser.',
            'applicationCategory': 'UtilityApplication',
            'operatingSystem': 'Web, Windows, macOS, Linux, Android, iOS',
            'offers': { '@type': 'Offer', 'price': '0', 'priceCurrency': 'USD' },
            'featureList': ['Base64 Encode', 'Base64 Decode', 'Unicode Support', 'Real-time Conversion', 'Privacy-First (100% Browser-Side)'],
        },
        {
            '@context': 'https://schema.org',
            '@type': 'HowTo',
            'name': 'How to Encode Text to Base64 Online',
            'description': 'Step-by-step guide to encoding or decoding Base64 strings using FluxMedia Base64 Converter.',
            'totalTime': 'PT1M',
            'step': [
                { '@type': 'HowToStep', 'position': 1, 'name': 'Select Mode', 'text': 'Choose "Encode" to convert text to Base64, or "Decode" to convert Base64 back to text.' },
                { '@type': 'HowToStep', 'position': 2, 'name': 'Enter Your Text', 'text': 'Type or paste your input text into the input field.' },
                { '@type': 'HowToStep', 'position': 3, 'name': 'Copy the Result', 'text': 'The converted output appears instantly. Click Copy to copy it to your clipboard.' },
            ],
        },
        {
            '@context': 'https://schema.org',
            '@type': 'FAQPage',
            'mainEntity': [
                { '@type': 'Question', 'name': 'What is Base64 encoding?', 'acceptedAnswer': { '@type': 'Answer', 'text': 'Base64 is a binary-to-text encoding scheme that converts binary data to ASCII text using 64 characters. It is commonly used in email attachments, data URLs, and API authentication.' } },
                { '@type': 'Question', 'name': 'Is the Base64 conversion private?', 'acceptedAnswer': { '@type': 'Answer', 'text': 'Yes. All encoding and decoding happens entirely in your browser using JavaScript. Nothing is sent to any server.' } },
                { '@type': 'Question', 'name': 'Does it support Unicode characters?', 'acceptedAnswer': { '@type': 'Answer', 'text': 'Yes. FluxMedia Base64 Converter correctly handles Unicode and multi-byte characters.' } },
            ],
        },
        {
            '@context': 'https://schema.org',
            '@type': 'BreadcrumbList',
            'itemListElement': [
                { '@type': 'ListItem', 'position': 1, 'name': 'Home', 'item': 'https://fluxmedia.space' },
                { '@type': 'ListItem', 'position': 2, 'name': 'Base64 Encoder & Decoder', 'item': 'https://fluxmedia.space/tools/base64' },
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

const mode = ref('encode'); // 'encode' or 'decode'
const inputText = ref('');
const outputText = ref('');
const errorMessage = ref('');
const copied = ref(false);

const processText = () => {
    errorMessage.value = '';
    if (!inputText.value) {
        outputText.value = '';
        return;
    }

    try {
        if (mode.value === 'encode') {
            // Encode: support unicode by escaping string first
            outputText.value = btoa(unescape(encodeURIComponent(inputText.value)));
        } else {
            // Decode: support unicode
            outputText.value = decodeURIComponent(escape(atob(inputText.value)));
        }
    } catch (e) {
        outputText.value = '';
        if (mode.value === 'decode') {
            errorMessage.value = 'Invalid Base64 string. Please ensure the input is properly formatted.';
        } else {
            errorMessage.value = 'Failed to encode input.';
        }
    }
};

// Watchers for real-time conversion
watch(inputText, processText);
watch(mode, () => {
    // Swap input and output when switching modes if desired, or just clear/reprocess
    const temp = outputText.value;
    inputText.value = temp && !errorMessage.value ? temp : '';
    processText();
});

const copyToClipboard = async () => {
    if (!outputText.value) return;
    try {
        await navigator.clipboard.writeText(outputText.value);
        copied.value = true;
        setTimeout(() => { copied.value = false; }, 2000);
    } catch (err) {
        console.error('Failed to copy text: ', err);
    }
};

const clearAll = () => {
    inputText.value = '';
    outputText.value = '';
    errorMessage.value = '';
};
</script>

<template>
    <PublicLayout>
        <Head>
            <title>Free Base64 Encoder & Decoder Online — Fast & Secure | FluxMedia</title>

            <!-- Primary SEO -->
            <meta name="description" content="Instantly encode or decode text to Base64 formatting right in your browser. Fast, secure, and privacy-focused utility with 100% client-side execution." />
            <meta name="keywords" content="base64 encoder, base64 decoder, base64 convert, base64 encode online, base64 decode online, base64 translator, free utility, client-side base64" />
            <meta name="author" content="FluxMedia" />
            <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1" />
            <meta name="googlebot" content="index, follow, max-snippet:-1, max-image-preview:large" />
            <link rel="canonical" href="https://fluxmedia.space/tools/base64" />

            <!-- Open Graph / Facebook -->
            <meta property="og:type" content="website" />
            <meta property="og:title" content="Free Base64 Encoder & Decoder Online — Fast & Secure | FluxMedia" />
            <meta property="og:description" content="Instantly encode or decode text to Base64 formatting right in your browser. Completely private and browser-based." />
            <meta property="og:image" content="https://fluxmedia.space/assets/images/fluxmedia_main.webp" />
            <meta property="og:url" content="https://fluxmedia.space/tools/base64" />
            <meta property="og:site_name" content="FluxMedia" />

            <!-- Twitter Card -->
            <meta name="twitter:card" content="summary_large_image" />
            <meta name="twitter:site" content="@fluxmedia" />
            <meta name="twitter:creator" content="@fluxmedia" />
            <meta name="twitter:title" content="Free Base64 Encoder & Decoder Online — Fast & Secure" />
            <meta name="twitter:description" content="Instantly encode or decode text to Base64 formatting right in your browser. Completely private and browser-based." />
            <meta name="twitter:image" content="https://fluxmedia.space/assets/images/fluxmedia_main.webp" />
        </Head>

        <!-- Background Accents -->
        <div class="fixed inset-0 overflow-hidden pointer-events-none -z-10 bg-[#0B0F19]">
            <div class="absolute top-[-10%] left-[-10%] w-[40%] h-[40%] rounded-full bg-blue-600/10 blur-[120px]"></div>
            <div class="absolute bottom-[-10%] right-[-10%] w-[40%] h-[40%] rounded-full bg-indigo-600/10 blur-[120px]"></div>
            <div class="absolute top-[40%] left-[60%] w-[30%] h-[30%] rounded-full bg-purple-600/10 blur-[100px]"></div>
        </div>

        <!-- Header -->
        <div class="relative pt-8 pb-4 text-center px-4 overflow-hidden z-10">
            <div class="absolute inset-0 bg-[url('/assets/images/grid-pattern.svg')] opacity-5 mask-image-gradient-b"></div>
            <div class="relative max-w-3xl mx-auto space-y-3">
                <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-gray-800/50 border border-gray-700/50 backdrop-blur-md">
                    <span class="w-2 h-2 rounded-full bg-blue-400 animate-pulse"></span>
                    <span class="text-xs font-semibold text-gray-300 uppercase tracking-wider">Client-Side Engine</span>
                </div>
                
                <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-white leading-tight">
                    Base64 <span class="bg-gradient-to-r from-blue-400 via-indigo-400 to-purple-400 bg-clip-text text-transparent">Converter</span>
                </h1>
                <p class="text-xs text-gray-400 max-w-xl mx-auto leading-relaxed">
                    Instantly encode and decode strings. 100% browser-based execution ensures your sensitive data never touches our servers.
                </p>
            </div>
        </div>

        <!-- Tool Container -->
        <div class="mx-auto max-w-5xl px-4 pb-24 relative z-10">
            <div class="bg-[#121826]/80 backdrop-blur-xl rounded-3xl border border-gray-800 shadow-2xl p-6 sm:p-8">
                
                <!-- Mode Toggle -->
                <div class="flex justify-center mb-8">
                    <div class="inline-flex rounded-xl p-1 bg-[#0B0F19] border border-gray-800 shadow-inner">
                        <button 
                            @click="mode = 'encode'"
                            :class="[
                                'px-6 py-2.5 rounded-lg text-sm font-bold transition-all duration-300',
                                mode === 'encode' ? 'bg-blue-600 text-white shadow-lg shadow-blue-900/30' : 'text-gray-500 hover:text-gray-300'
                            ]"
                        >
                            Encode
                        </button>
                        <button 
                            @click="mode = 'decode'"
                            :class="[
                                'px-6 py-2.5 rounded-lg text-sm font-bold transition-all duration-300',
                                mode === 'decode' ? 'bg-purple-600 text-white shadow-lg shadow-purple-900/30' : 'text-gray-500 hover:text-gray-300'
                            ]"
                        >
                            Decode
                        </button>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 lg:gap-8">
                    <!-- Input Section -->
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <label class="text-sm font-black text-gray-300 uppercase tracking-widest">Input String</label>
                            <button @click="clearAll" class="text-xs font-bold text-gray-500 hover:text-red-400 transition-colors uppercase tracking-wider">Clear</button>
                        </div>
                        <textarea
                            v-model="inputText"
                            placeholder="Type or paste your text here..."
                            class="w-full h-[300px] bg-[#0B0F19] border border-gray-700/80 rounded-2xl p-5 text-gray-100 placeholder-gray-600 focus:border-blue-500 focus:ring-1 focus:ring-blue-500/50 transition-all outline-none resize-none font-mono text-sm shadow-inner custom-scrollbar"
                            spellcheck="false"
                        ></textarea>
                    </div>

                    <!-- Output Section -->
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <label class="text-sm font-black text-gray-300 uppercase tracking-widest">Output {{ mode === 'encode' ? 'Base64' : 'String' }}</label>
                            
                            <!-- Copy Button -->
                            <button 
                                @click="copyToClipboard"
                                :disabled="!outputText"
                                :class="[
                                    'inline-flex items-center gap-1.5 px-3 py-1 rounded-lg text-xs font-bold uppercase tracking-wider transition-all duration-200',
                                    outputText ? 'bg-emerald-500/10 text-emerald-400 hover:bg-emerald-500/20 cursor-pointer' : 'bg-gray-800 text-gray-600 cursor-not-allowed'
                                ]"
                            >
                                <svg v-if="copied" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" /></svg>
                                <svg v-else class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3" /></svg>
                                {{ copied ? 'Copied!' : 'Copy' }}
                            </button>
                        </div>
                        
                        <!-- Output Area -->
                        <div class="relative">
                            <textarea
                                v-model="outputText"
                                readonly
                                placeholder="Result will appear here..."
                                :class="[
                                    'w-full h-[300px] bg-[#0B0F19]/50 border border-gray-800 rounded-2xl p-5 placeholder-gray-700 outline-none resize-none font-mono text-sm custom-scrollbar transition-all',
                                    errorMessage ? 'border-red-500/50 text-red-400' : 'text-blue-200'
                                ]"
                            ></textarea>

                            <!-- Error Overlay -->
                            <div v-if="errorMessage" class="absolute inset-0 bg-[#0B0F19]/90 backdrop-blur-sm rounded-2xl flex items-center justify-center p-6 border border-red-500/30">
                                <div class="text-center space-y-3">
                                    <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-red-500/10 text-red-500 mb-2">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                                    </div>
                                    <p class="text-red-400 text-sm font-medium">{{ errorMessage }}</p>
                                </div>
                            </div>
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
