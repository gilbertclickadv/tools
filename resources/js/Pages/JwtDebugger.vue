<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';

// --- State Variables ---
const tokenInput = ref('');
const secretInput = ref('your-256-bit-secret');
const isSignatureValid = ref(null);
const activeTab = ref('decoder'); // 'decoder', 'architect'

// Builder/Architect state
const builderHeader = ref('{\n  "alg": "HS256",\n  "typ": "JWT"\n}');
const builderPayload = ref('{\n  "sub": "1234567890",\n  "name": "John Doe",\n  "admin": true,\n  "iat": 1516239022\n}');
const builderSecret = ref('your-256-bit-secret');
const generatedToken = ref('');

// Copy notifications
const copiedToken = ref(false);
const copiedHeader = ref(false);
const copiedPayload = ref(false);
const copiedGenerated = ref(false);

// --- Base64URL Helper Functions ---
function base64urlDecode(str) {
    try {
        let base64 = str.replace(/-/g, '+').replace(/_/g, '/');
        while (base64.length % 4) {
            base64 += '=';
        }
        const decoded = atob(base64);
        return decodeURIComponent(decoded.split('').map(function(c) {
            return '%' + ('00' + c.charCodeAt(0).toString(16)).slice(-2);
        }).join(''));
    } catch (e) {
        return null;
    }
}

function base64urlEncode(str) {
    try {
        const bytes = new TextEncoder().encode(str);
        const binString = String.fromCharCode(...bytes);
        const encoded = btoa(binString);
        return encoded.replace(/=+$/, '').replace(/\+/g, '-').replace(/\//g, '_');
    } catch (e) {
        return '';
    }
}

function arrayBufferToBase64url(buffer) {
    const bytes = new Uint8Array(buffer);
    let binary = '';
    for (let i = 0; i < bytes.byteLength; i++) {
        binary += String.fromCharCode(bytes[i]);
    }
    const base64 = btoa(binary);
    return base64.replace(/=+$/, '').replace(/\+/g, '-').replace(/\//g, '_');
}

// --- Date Claim Translators ---
function translateTimestamp(timestamp) {
    if (!timestamp || isNaN(timestamp)) return null;
    const date = new Date(timestamp * 1000);
    const now = new Date();
    const diffMs = date.getTime() - now.getTime();
    const diffMin = Math.round(diffMs / 60000);
    const diffHours = Math.round(diffMs / 3600000);
    const diffDays = Math.round(diffMs / 86400000);

    const formattedDate = date.toLocaleString();
    let relative = '';

    if (Math.abs(diffMin) < 60) {
        relative = diffMin >= 0 ? `in ${diffMin} min` : `${Math.abs(diffMin)} min ago`;
    } else if (Math.abs(diffHours) < 24) {
        relative = diffHours >= 0 ? `in ${diffHours} hours` : `${Math.abs(diffHours)} hours ago`;
    } else {
        relative = diffDays >= 0 ? `in ${diffDays} days` : `${Math.abs(diffDays)} days ago`;
    }

    return {
        formatted: formattedDate,
        relative: relative,
        isExpired: diffMs < 0
    };
}

// --- Token Parsing Computation ---
const parsedToken = computed(() => {
    if (!tokenInput.value) return null;
    const parts = tokenInput.value.trim().split('.');
    
    return {
        headerRaw: parts[0] || '',
        payloadRaw: parts[1] || '',
        signatureRaw: parts[2] || '',
        partsCount: parts.length,
        headerDecoded: parts[0] ? base64urlDecode(parts[0]) : null,
        payloadDecoded: parts[1] ? base64urlDecode(parts[1]) : null
    };
});

const headerJson = computed(() => {
    if (!parsedToken.value || !parsedToken.value.headerDecoded) return null;
    try {
        return JSON.parse(parsedToken.value.headerDecoded);
    } catch (e) {
        return null;
    }
});

const payloadJson = computed(() => {
    if (!parsedToken.value || !parsedToken.value.payloadDecoded) return null;
    try {
        return JSON.parse(parsedToken.value.payloadDecoded);
    } catch (e) {
        return null;
    }
});

// Dynamic Claims relative time translator
const payloadClaimsList = computed(() => {
    if (!payloadJson.value) return [];
    return Object.entries(payloadJson.value).map(([key, val]) => {
        let meta = null;
        if (['exp', 'iat', 'nbf'].includes(key) && typeof val === 'number') {
            meta = translateTimestamp(val);
        }
        return { key, value: val, meta };
    });
});

// --- Client-Side HS256 HMAC Verification ---
async function verifySignature() {
    if (!parsedToken.value || parsedToken.value.partsCount !== 3) {
        isSignatureValid.value = false;
        return;
    }
    const { headerRaw, payloadRaw, signatureRaw } = parsedToken.value;
    const secret = secretInput.value;

    try {
        const encoder = new TextEncoder();
        const keyData = encoder.encode(secret);
        const data = encoder.encode(`${headerRaw}.${payloadRaw}`);

        const key = await window.crypto.subtle.importKey(
            "raw",
            keyData,
            { name: "HMAC", hash: { name: "SHA-256" } },
            false,
            ["sign"]
        );

        const signatureBuffer = await window.crypto.subtle.sign(
            "HMAC",
            key,
            data
        );

        const generatedSig = arrayBufferToBase64url(signatureBuffer);
        isSignatureValid.value = (generatedSig === signatureRaw);
    } catch (e) {
        console.error("Signature calculation error", e);
        isSignatureValid.value = false;
    }
}

// --- Dynamic Architect (Encoder) Generator ---
async function generateArchitectToken() {
    try {
        // Clean and parse Header & Payload
        const hJson = JSON.parse(builderHeader.value);
        const pJson = JSON.parse(builderPayload.value);

        const headerStr = JSON.stringify(hJson);
        const payloadStr = JSON.stringify(pJson);

        const headerB64 = base64urlEncode(headerStr);
        const payloadB64 = base64urlEncode(payloadStr);

        const secret = builderSecret.value;
        const encoder = new TextEncoder();
        const keyData = encoder.encode(secret);
        const data = encoder.encode(`${headerB64}.${payloadB64}`);

        const key = await window.crypto.subtle.importKey(
            "raw",
            keyData,
            { name: "HMAC", hash: { name: "SHA-256" } },
            false,
            ["sign"]
        );

        const signatureBuffer = await window.crypto.subtle.sign(
            "HMAC",
            key,
            data
        );

        const signatureB64 = arrayBufferToBase64url(signatureBuffer);
        generatedToken.value = `${headerB64}.${payloadB64}.${signatureB64}`;
    } catch (e) {
        generatedToken.value = 'Error: Invalid JSON elements or secret syntax.';
    }
}

// --- Watchers & Interactions ---
watch([tokenInput, secretInput], () => {
    if (tokenInput.value) {
        verifySignature();
    } else {
        isSignatureValid.value = null;
    }
});

watch([builderHeader, builderPayload, builderSecret], () => {
    generateArchitectToken();
});

// Copy to Clipboard Helpers
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

const loadSampleToken = () => {
    // A standard valid HS256 token signed with "secret"
    tokenInput.value = "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxMjM0NTY3ODkwIiwibmFtZSI6Ik1pZGFzIEFwaSIsImFkbWluIjp0cnVlLCJleHAiOjE3NzkyNzM2MDAsImlhdCI6MTUxNjIzOTAyMn0.51G-h9u0DqG78Wexd6gX6Q6nQ-lF044uWpP2-s-m_3w";
    secretInput.value = "secret";
};

// --- Lifecycle ---
const seoScripts = [];
onMounted(() => {
    // Local storage persistence
    const savedToken = localStorage.getItem('fluxmedia_jwt_token');
    if (savedToken) {
        tokenInput.value = savedToken;
    } else {
        loadSampleToken();
    }

    generateArchitectToken();

    // SEO structured schemas
    const schemas = [
        {
            '@context': 'https://schema.org',
            '@type': 'WebApplication',
            'name': 'JWT Debugger & Token Inspector — Free Online Tool',
            'url': 'https://fluxmedia.space/tools/jwt',
            'description': 'Free online JWT debugger and token inspector. Decode JWT headers and payloads, verify HS256 signatures, and generate new tokens entirely in your browser.',
            'applicationCategory': 'DeveloperApplication',
            'operatingSystem': 'Web, Windows, macOS, Linux, Android, iOS',
            'offers': { '@type': 'Offer', 'price': '0', 'priceCurrency': 'USD' },
            'featureList': ['JWT Decode', 'HS256 Signature Verification', 'JWT Encoder/Architect', 'Claim Timestamp Translator', 'Browser-side Only'],
        },
        {
            '@context': 'https://schema.org',
            '@type': 'HowTo',
            'name': 'How to Decode and Verify a JWT Token Online',
            'description': 'Step-by-step guide to decoding and verifying a JSON Web Token using FluxMedia JWT Debugger.',
            'totalTime': 'PT1M',
            'step': [
                { '@type': 'HowToStep', 'position': 1, 'name': 'Paste Your JWT', 'text': 'Paste your encoded JWT string into the Token Decoder panel.' },
                { '@type': 'HowToStep', 'position': 2, 'name': 'View Decoded Claims', 'text': 'The header and payload are decoded instantly with claim translations for exp, iat, and nbf timestamps.' },
                { '@type': 'HowToStep', 'position': 3, 'name': 'Verify Signature', 'text': 'Enter your HMAC secret to verify the HS256 signature directly in your browser.' },
            ],
        },
        {
            '@context': 'https://schema.org',
            '@type': 'FAQPage',
            'mainEntity': [
                { '@type': 'Question', 'name': 'Is JWT decoding safe?', 'acceptedAnswer': { '@type': 'Answer', 'text': 'Yes. All JWT decoding and verification happens entirely in your browser. No token data or secrets are sent to any server.' } },
                { '@type': 'Question', 'name': 'What algorithms does the JWT Debugger support?', 'acceptedAnswer': { '@type': 'Answer', 'text': 'The JWT Debugger supports HS256 signature verification and encoding. Decoding works for all standard JWT formats regardless of algorithm.' } },
                { '@type': 'Question', 'name': 'Can I generate a JWT token?', 'acceptedAnswer': { '@type': 'Answer', 'text': 'Yes. Use the Architect (Encoder) tab to edit the header and payload JSON and generate a signed HS256 JWT token instantly.' } },
            ],
        },
        {
            '@context': 'https://schema.org',
            '@type': 'BreadcrumbList',
            'itemListElement': [
                { '@type': 'ListItem', 'position': 1, 'name': 'Home', 'item': 'https://fluxmedia.space' },
                { '@type': 'ListItem', 'position': 2, 'name': 'JWT Debugger', 'item': 'https://fluxmedia.space/tools/jwt' },
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

watch(tokenInput, (newVal) => {
    if (newVal) {
        localStorage.setItem('fluxmedia_jwt_token', newVal);
    } else {
        localStorage.removeItem('fluxmedia_jwt_token');
    }
});
</script>

<template>
    <PublicLayout>
        <Head>
            <title>Premium Online JWT Debugger & Token Inspector | FluxMedia</title>
            <meta name="description" content="A state-of-the-art JSON Web Token utility. Decode header and payload claims, translate timestamps dynamically, verify signatures, and sign tokens client-side." />
            <meta name="keywords" content="jwt debugger, json web token, jwt decoder, jwt encoder, verify jwt signature, token architect, hs256 signing, claims translator, epoch expiration, developer tools" />
            <meta name="author" content="FluxMedia" />
            <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1" />
            <meta name="googlebot" content="index, follow, max-snippet:-1, max-image-preview:large" />
            <link rel="canonical" href="https://fluxmedia.space/tools/jwt" />
        </Head>

        <!-- Ambient Backdrop Colors matching brand guidelines (Amber Accent) -->
        <div class="fixed inset-0 overflow-hidden pointer-events-none -z-10 bg-[#0B0F19]">
            <div class="absolute top-[-10%] left-[-10%] w-[40%] h-[40%] rounded-full bg-amber-600/10 blur-[120px]"></div>
            <div class="absolute bottom-[-10%] right-[-10%] w-[40%] h-[40%] rounded-full bg-indigo-600/10 blur-[120px]"></div>
            <div class="absolute top-[30%] left-[60%] w-[35%] h-[35%] rounded-full bg-amber-600/5 blur-[110px]"></div>
        </div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 relative z-10">
            <!-- Header Banner -->
            <div class="text-center space-y-4 mb-8">
                <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-gray-800/50 border border-gray-700/50 backdrop-blur-md">
                    <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                    <span class="text-xs font-semibold text-gray-300 uppercase tracking-wider">Encoding & Crypto Suite</span>
                </div>
                
                <h1 class="text-3xl sm:text-4xl font-extrabold tracking-tight text-white">
                    JWT Debugger & <span class="bg-gradient-to-r from-amber-400 via-yellow-400 to-indigo-400 bg-clip-text text-transparent">Token Inspector</span>
                </h1>
                
                <p class="text-xs sm:text-sm text-gray-400 max-w-2xl mx-auto leading-relaxed">
                    Decode, verify, and architect JSON Web Tokens in real-time. Fully sandboxed and executed in-browser, ensuring complete privacy of keys and claims.
                </p>
            </div>

            <!-- Workspace Tabs -->
            <div class="flex gap-2 max-w-xs mx-auto mb-8 bg-gray-950/80 p-1.5 rounded-xl border border-white/[0.04] backdrop-blur-md shadow-lg">
                <button 
                    @click="activeTab = 'decoder'"
                    :class="[
                        'flex-1 text-center py-2.5 rounded-lg text-xs font-bold uppercase tracking-wider transition duration-200 border',
                        activeTab === 'decoder' ? 'bg-amber-600/10 text-amber-400 border-amber-500/20' : 'border-transparent text-gray-500 hover:text-gray-300'
                    ]"
                >
                    Token Decoder
                </button>
                <button 
                    @click="activeTab = 'architect'"
                    :class="[
                        'flex-1 text-center py-2.5 rounded-lg text-xs font-bold uppercase tracking-wider transition duration-200 border',
                        activeTab === 'architect' ? 'bg-amber-600/10 text-amber-400 border-amber-500/20' : 'border-transparent text-gray-500 hover:text-gray-300'
                    ]"
                >
                    Architect (Encoder)
                </button>
            </div>

            <!-- ─── TAB 1: DECODER SUITE ─── -->
            <div v-if="activeTab === 'decoder'" class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
                
                <!-- Input Canvas (Col 5/12) -->
                <div class="lg:col-span-5 space-y-6">
                    <div class="relative bg-gray-900/50 border border-white/[0.06] rounded-2xl p-6 backdrop-blur-xl shadow-2xl">
                        
                        <!-- Toolbar -->
                        <div class="flex items-center justify-between gap-4 mb-4">
                            <div class="flex items-center gap-2">
                                <span class="p-2 bg-amber-500/10 rounded-lg text-amber-400">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                    </svg>
                                </span>
                                <h2 class="text-md font-bold text-white">Paste Encoded Token</h2>
                            </div>
                            
                            <div class="flex items-center gap-2">
                                <button 
                                    @click="loadSampleToken"
                                    class="text-xs px-3 py-1.5 rounded-lg bg-white/5 border border-white/10 text-gray-300 hover:bg-white/10 hover:text-white transition duration-200"
                                >
                                    Sample Token
                                </button>
                                <button 
                                    @click="tokenInput = ''"
                                    class="text-xs px-3 py-1.5 rounded-lg bg-red-500/10 border border-red-500/20 text-red-400 hover:bg-red-500/20 transition duration-200"
                                >
                                    Clear
                                </button>
                            </div>
                        </div>

                        <!-- Token Textarea -->
                        <div class="relative">
                            <textarea
                                v-model="tokenInput"
                                placeholder="Paste your encoded JWT string here..."
                                class="w-full h-80 sm:h-96 bg-[#0B0F19]/80 border border-gray-800 focus:border-amber-500/80 focus:ring-1 focus:ring-amber-500/30 rounded-xl p-4 text-xs font-mono text-gray-300 placeholder-gray-650 focus:outline-none transition duration-200 resize-none custom-scrollbar leading-relaxed"
                            ></textarea>
                            
                            <!-- Status Overlay -->
                            <div class="absolute bottom-3 right-4 flex items-center gap-1.5 text-[10px] font-semibold text-gray-500 bg-gray-900/60 px-2 py-1 rounded-md border border-white/[0.04] pointer-events-none">
                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-ping"></span>
                                Autosaved Draft
                            </div>
                        </div>

                        <!-- Visual segments indicator -->
                        <div v-if="parsedToken && parsedToken.partsCount === 3" class="mt-4 space-y-2 pt-4 border-t border-white/[0.04]">
                            <h4 class="text-[10px] font-black uppercase tracking-wider text-gray-500">Segment Layout</h4>
                            <div class="w-full h-4 bg-[#0B0F19]/80 rounded-md overflow-hidden flex border border-white/[0.02] text-[8px] font-mono font-bold text-center leading-4 text-white">
                                <div class="bg-rose-500/80 text-white h-full" style="width: 25%" title="Header Segment">Header</div>
                                <div class="bg-sky-500/80 text-white h-full" style="width: 50%" title="Payload Claims">Payload</div>
                                <div class="bg-emerald-500/80 text-white h-full" style="width: 25%" title="Signature Check">Signature</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Decoded claims board (Col 7/12) -->
                <div class="lg:col-span-7 space-y-6">
                    <div class="bg-gray-900/50 border border-white/[0.06] rounded-2xl p-6 backdrop-blur-xl shadow-2xl">
                        
                        <!-- Main tabs: Header & Payload Decoded -->
                        <div class="space-y-6">
                            
                            <!-- Header Segment Decoded -->
                            <div class="space-y-3">
                                <div class="flex items-center justify-between border-b border-white/[0.04] pb-2">
                                    <div class="flex items-center gap-2">
                                        <span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span>
                                        <h3 class="text-xs font-black uppercase tracking-widest text-gray-200">Header: Meta & Algorithm</h3>
                                    </div>
                                    <button 
                                        v-if="parsedToken && parsedToken.headerDecoded"
                                        @click="handleCopy(parsedToken.headerDecoded, copiedHeader)"
                                        class="text-xs text-gray-500 hover:text-white flex items-center gap-1 transition"
                                    >
                                        <svg v-if="!copiedHeader" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2" />
                                        </svg>
                                        <span class="text-[10px] font-bold">{{ copiedHeader ? 'Copied' : 'Copy' }}</span>
                                    </button>
                                </div>

                                <div v-if="!headerJson" class="text-center py-6 text-xs text-gray-500 leading-normal">
                                    Paste a valid JWT string on the left panel to inspect the metadata.
                                </div>
                                <pre v-else class="w-full bg-[#0B0F19]/60 border border-rose-500/10 p-4 rounded-xl text-xs font-mono text-rose-300 leading-relaxed overflow-x-auto custom-scrollbar">{{ JSON.stringify(headerJson, null, 2) }}</pre>
                            </div>

                            <!-- Payload Segment Decoded -->
                            <div class="space-y-3">
                                <div class="flex items-center justify-between border-b border-white/[0.04] pb-2">
                                    <div class="flex items-center gap-2">
                                        <span class="w-2.5 h-2.5 rounded-full bg-sky-500"></span>
                                        <h3 class="text-xs font-black uppercase tracking-widest text-gray-200">Payload: Data Claims</h3>
                                    </div>
                                    <button 
                                        v-if="parsedToken && parsedToken.payloadDecoded"
                                        @click="handleCopy(parsedToken.payloadDecoded, copiedPayload)"
                                        class="text-xs text-gray-500 hover:text-white flex items-center gap-1 transition"
                                    >
                                        <svg v-if="!copiedPayload" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2" />
                                        </svg>
                                        <span class="text-[10px] font-bold">{{ copiedPayload ? 'Copied' : 'Copy' }}</span>
                                    </button>
                                </div>

                                <div v-if="!payloadJson" class="text-center py-6 text-xs text-gray-500 leading-normal">
                                    Parsed JWT payload will render here in a dynamic claim inspector.
                                </div>
                                <div v-else class="space-y-4">
                                    <pre class="w-full bg-[#0B0F19]/60 border border-sky-500/10 p-4 rounded-xl text-xs font-mono text-sky-300 leading-relaxed overflow-x-auto custom-scrollbar">{{ JSON.stringify(payloadJson, null, 2) }}</pre>

                                    <!-- Claim translations list -->
                                    <div class="bg-[#0B0F19]/30 rounded-xl p-4 border border-white/[0.03] space-y-2.5">
                                        <h4 class="text-[10px] font-bold uppercase tracking-wider text-gray-500">Claim Interpretations</h4>
                                        <div class="space-y-2">
                                            <div v-for="claim in payloadClaimsList" :key="claim.key" class="flex flex-wrap items-center justify-between text-xs py-1 border-b border-white/[0.02] last:border-0">
                                                <span class="font-mono text-gray-400 font-medium">{{ claim.key }}:</span>
                                                <div class="flex items-center gap-2">
                                                    <span class="font-mono text-white">{{ claim.value }}</span>
                                                    <span 
                                                        v-if="claim.meta" 
                                                        :class="[
                                                            'text-[10px] font-semibold px-2 py-0.5 rounded',
                                                            claim.meta.isExpired ? 'bg-red-500/10 text-red-400 border border-red-500/20' : 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20'
                                                        ]"
                                                    >
                                                        {{ claim.meta.formatted }} ({{ claim.meta.relative }})
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Verification Secret / Check segment -->
                            <div class="space-y-3 pt-4 border-t border-white/[0.04]">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                                        <h3 class="text-xs font-black uppercase tracking-widest text-gray-200">Signature Verification (HS256)</h3>
                                    </div>
                                    
                                    <!-- Verified Badge indicator -->
                                    <div v-if="isSignatureValid !== null" class="flex items-center">
                                        <span 
                                            v-if="isSignatureValid" 
                                            class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-black uppercase tracking-widest bg-emerald-500/10 text-emerald-400 ring-1 ring-emerald-500/20 shadow-glow"
                                        >
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-ping"></span>
                                            Signature Verified
                                        </span>
                                        <span 
                                            v-else 
                                            class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-black uppercase tracking-widest bg-red-500/10 text-red-400 ring-1 ring-red-500/20"
                                        >
                                            Invalid Signature
                                        </span>
                                    </div>
                                </div>

                                <div class="space-y-2.5">
                                    <p class="text-[11px] text-gray-400">
                                        Verify signatures locally in real-time. Enter the token secret below:
                                    </p>
                                    <input 
                                        v-model="secretInput"
                                        type="text"
                                        placeholder="Enter signature secret key..."
                                        class="w-full bg-[#0B0F19] border border-gray-800 focus:border-amber-500/80 focus:ring-1 focus:ring-amber-500/30 rounded-xl px-4 py-3 text-xs text-white placeholder-gray-600 focus:outline-none transition duration-200 font-mono"
                                    />
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

            </div>

            <!-- ─── TAB 2: TOKEN ARCHITECT (ENCODER) ─── -->
            <div v-if="activeTab === 'architect'" class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
                
                <!-- JSON editors block (Col 7/12) -->
                <div class="lg:col-span-7 space-y-6">
                    <div class="bg-gray-900/50 border border-white/[0.06] rounded-2xl p-6 backdrop-blur-xl shadow-2xl space-y-6">
                        
                        <div class="flex items-center justify-between border-b border-white/[0.04] pb-2">
                            <div class="flex items-center gap-2">
                                <span class="p-2 bg-amber-500/10 rounded-lg text-amber-400">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </span>
                                <h2 class="text-md font-bold text-white">Interactive Token Architect</h2>
                            </div>
                            <span class="text-[10px] font-black uppercase text-gray-500 tracking-wider">Encode Client-Side</span>
                        </div>

                        <!-- Header Builder Input -->
                        <div class="space-y-2">
                            <label class="text-[10px] font-bold text-gray-500 uppercase tracking-widest">Edit Header JSON</label>
                            <textarea 
                                v-model="builderHeader"
                                class="w-full h-32 bg-[#0B0F19]/80 border border-gray-800 focus:border-amber-500/80 focus:ring-1 focus:ring-amber-500/30 rounded-xl p-3.5 text-xs font-mono text-rose-300 focus:outline-none transition leading-relaxed resize-y custom-scrollbar"
                            ></textarea>
                        </div>

                        <!-- Payload Builder Input -->
                        <div class="space-y-2">
                            <label class="text-[10px] font-bold text-gray-500 uppercase tracking-widest">Edit Payload (Claims) JSON</label>
                            <textarea 
                                v-model="builderPayload"
                                class="w-full h-64 bg-[#0B0F19]/80 border border-gray-800 focus:border-amber-500/80 focus:ring-1 focus:ring-amber-500/30 rounded-xl p-3.5 text-xs font-mono text-sky-300 focus:outline-none transition leading-relaxed resize-y custom-scrollbar"
                            ></textarea>
                        </div>

                        <!-- Secret Key -->
                        <div class="space-y-2">
                            <label class="text-[10px] font-bold text-gray-500 uppercase tracking-widest">Signature Secret Key</label>
                            <input 
                                v-model="builderSecret"
                                type="text"
                                placeholder="Enter signing secret..."
                                class="w-full bg-[#0B0F19] border border-gray-800 focus:border-amber-500/80 focus:ring-1 focus:ring-amber-500/30 rounded-xl px-4 py-3 text-xs text-white placeholder-gray-600 focus:outline-none transition font-mono"
                            />
                        </div>

                    </div>
                </div>

                <!-- Generated Token block (Col 5/12) -->
                <div class="lg:col-span-5 space-y-6">
                    <div class="bg-gray-900/50 border border-white/[0.06] rounded-2xl p-6 backdrop-blur-xl shadow-2xl space-y-4">
                        
                        <div class="flex items-center justify-between border-b border-white/[0.04] pb-2">
                            <h3 class="text-sm font-bold text-gray-200 uppercase tracking-wider">
                                Generated Output
                            </h3>
                            
                            <button 
                                @click="handleCopy(generatedToken, copiedGenerated)"
                                :class="[
                                    'text-xs px-3 py-1.5 rounded-lg transition duration-200 font-medium flex items-center gap-1',
                                    copiedGenerated 
                                        ? 'bg-emerald-500/10 border border-emerald-500/20 text-emerald-400' 
                                        : 'bg-amber-600 hover:bg-amber-500 text-white'
                                ]"
                            >
                                <svg v-if="!copiedGenerated" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3" />
                                </svg>
                                <svg v-else class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                {{ copiedGenerated ? 'Copied' : 'Copy Token' }}
                            </button>
                        </div>

                        <!-- Generated token textarea (readonly) -->
                        <div class="relative">
                            <div 
                                class="w-full min-h-64 bg-[#0B0F19]/90 border border-gray-900 rounded-xl p-4 text-xs font-mono text-gray-300 overflow-y-auto custom-scrollbar break-all select-all leading-relaxed border-t-2 border-t-amber-500/30"
                            >
                                {{ generatedToken }}
                            </div>
                        </div>

                        <!-- Informational note -->
                        <div class="p-4 bg-amber-600/5 rounded-xl border border-amber-500/10 text-xs text-gray-400 leading-relaxed">
                            <p class="font-semibold text-amber-300 mb-1">In-Browser Cryptography</p>
                            All token payload parameters and keys are serialized directly in your browser using standard JavaScript engine codecs. No key data is sent to external servers or API handlers.
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
    box-shadow: 0 0 15px rgba(245, 158, 11, 0.15);
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
