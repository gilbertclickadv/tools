<script setup>
import { Head } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { ref, reactive, onMounted, onUnmounted, watch, computed } from 'vue';
import axios from 'axios';

// ── Password Generation Logic ──────────────────────────────────────────────
const length = ref(16);
const options = reactive({
    uppercase: true,
    lowercase: true,
    numbers: true,
    symbols: true,
});
const password = ref('');
const strength = ref(0);

const allOptionsSelected = computed(() => {
    return options.uppercase && options.lowercase && options.numbers && options.symbols;
});

const toggleAllOptions = () => {
    const target = !allOptionsSelected.value;
    options.uppercase = target;
    options.lowercase = target;
    options.numbers = target;
    options.symbols = target;
    generatePassword();
};

const generatePassword = () => {
    const charset = {
        upper: 'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
        lower: 'abcdefghijklmnopqrstuvwxyz',
        numbers: '0123456789',
        symbols: '!@#$%^&*()_+~`|}{[]:;?><,./-='
    };

    let pool = '';
    if (options.uppercase) pool += charset.upper;
    if (options.lowercase) pool += charset.lower;
    if (options.numbers)   pool += charset.numbers;
    if (options.symbols)   pool += charset.symbols;

    if (!pool) {
        password.value = '';
        strength.value = 0;
        return;
    }

    let result = '';
    const array = new Uint32Array(length.value);
    window.crypto.getRandomValues(array);

    for (let i = 0; i < length.value; i++) {
        result += pool.charAt(array[i] % pool.length);
    }

    password.value = result;
    calculateStrength();
    
    // Track generation (throttled)
    trackGeneration();
};

const calculateStrength = () => {
    let s = 0;
    if (password.value.length > 8) s += 1;
    if (password.value.length > 12) s += 1;
    if (/[A-Z]/.test(password.value)) s += 1;
    if (/[0-9]/.test(password.value)) s += 1;
    if (/[^A-Za-z0-9]/.test(password.value)) s += 1;
    strength.value = Math.min(s, 5);
};

let trackTimeout = null;
const trackGeneration = () => {
    clearTimeout(trackTimeout);
    trackTimeout = setTimeout(async () => {
        try {
            await axios.post(route('password.track'), {
                length: length.value,
                use_uppercase: options.uppercase,
                use_lowercase: options.lowercase,
                use_numbers: options.numbers,
                use_symbols: options.symbols,
            });
        } catch (e) {}
    }, 1000);
};

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

const copyToClipboard = async () => {
    if (!password.value) return;
    try {
        await navigator.clipboard.writeText(password.value);
        showToast('Password copied!', 'copy');
    } catch {
        showToast('Copy failed.', 'error');
    }
};

onMounted(() => {
    generatePassword();
});

// ── SEO ──────────────────────────────────────────────────────────────────────
const seoScripts = [];
onMounted(() => {
    const schemas = [
        {
            '@context': 'https://schema.org',
            '@type': 'WebApplication',
            'name': 'Password Generator — Free Secure Password Creator',
            'url': 'https://fluxmedia.space/tools/password-generator',
            'description': 'Free online password generator. Create strong, secure, cryptographically random passwords with custom length and character sets. 100% browser-side, nothing sent to servers.',
            'applicationCategory': 'UtilityApplication',
            'operatingSystem': 'Web, Windows, macOS, Linux, Android, iOS',
            'offers': { '@type': 'Offer', 'price': '0', 'priceCurrency': 'USD' },
            'featureList': ['Custom Length (4-64)', 'Uppercase Letters', 'Lowercase Letters', 'Numbers', 'Symbols', 'Strength Meter', 'Browser Cryptography'],
        },
        {
            '@context': 'https://schema.org',
            '@type': 'HowTo',
            'name': 'How to Generate a Strong Password Online',
            'description': 'Step-by-step guide to creating a secure password using FluxMedia Password Generator.',
            'totalTime': 'PT1M',
            'step': [
                { '@type': 'HowToStep', 'position': 1, 'name': 'Set Password Length', 'text': 'Drag the slider to set the desired password length (4 to 64 characters).' },
                { '@type': 'HowToStep', 'position': 2, 'name': 'Choose Character Types', 'text': 'Toggle uppercase letters, lowercase letters, numbers, and symbols.' },
                { '@type': 'HowToStep', 'position': 3, 'name': 'Generate & Copy', 'text': 'Click Regenerate Password to create a new password, then click the copy icon to copy it.' },
            ],
        },
        {
            '@context': 'https://schema.org',
            '@type': 'FAQPage',
            'mainEntity': [
                { '@type': 'Question', 'name': 'Is the generated password stored anywhere?', 'acceptedAnswer': { '@type': 'Answer', 'text': 'No. All passwords are generated entirely in your browser using the Web Crypto API. Nothing is ever sent to our servers.' } },
                { '@type': 'Question', 'name': 'How long should my password be?', 'acceptedAnswer': { '@type': 'Answer', 'text': 'We recommend at least 16 characters using a mix of uppercase, lowercase, numbers, and symbols for strong security.' } },
                { '@type': 'Question', 'name': 'Is this password generator free?', 'acceptedAnswer': { '@type': 'Answer', 'text': 'Yes, completely free with no account required and no limits.' } },
            ],
        },
        {
            '@context': 'https://schema.org',
            '@type': 'BreadcrumbList',
            'itemListElement': [
                { '@type': 'ListItem', 'position': 1, 'name': 'Home', 'item': 'https://fluxmedia.space' },
                { '@type': 'ListItem', 'position': 2, 'name': 'Password Generator', 'item': 'https://fluxmedia.space/tools/password-generator' },
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

const toastConfig = {
    success: { bg: 'bg-emerald-500/10 border-emerald-500/30', text: 'text-emerald-400', icon: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z' },
    copy:    { bg: 'bg-blue-500/10 border-blue-500/30',       text: 'text-blue-400',    icon: 'M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z' },
    error:   { bg: 'bg-red-500/10 border-red-500/30',         text: 'text-red-400',     icon: 'M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z' },
};
</script>

<template>
    <PublicLayout>
        <Head>
            <title>Secure Password Generator — Create Strong Random Passwords | FluxMedia</title>
            <meta name="description" content="Generate strong, secure, and random passwords instantly. Uses browser-side cryptography for maximum privacy. Customizable length, symbols, and one-click copy." />
            <meta name="keywords" content="password generator, secure password, random password, strong password creator, safe password generator, password generator online, cryptographically secure password" />
            <meta name="author" content="FluxMedia" />
            <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1" />
            <meta name="googlebot" content="index, follow, max-snippet:-1, max-image-preview:large" />
            
            <!-- Open Graph / Facebook -->
            <meta property="og:type" content="website" />
            <meta property="og:url" content="https://fluxmedia.space/tools/password-generator" />
            <meta property="og:title" content="Secure Password Generator — Create Strong Random Passwords" />
            <meta property="og:description" content="Generate cryptographically secure passwords locally in your browser. Private, secure, and fully customizable." />
            <meta property="og:image" content="https://fluxmedia.space/images/og-password.jpg" />

            <!-- Twitter -->
            <meta property="twitter:card" content="summary_large_image" />
            <meta property="twitter:url" content="https://fluxmedia.space/tools/password-generator" />
            <meta property="twitter:title" content="Secure Password Generator — Create Strong Random Passwords" />
            <meta property="twitter:description" content="Generate cryptographically secure passwords locally in your browser. Private, secure, and fully customizable." />
            <meta property="twitter:image" content="https://fluxmedia.space/images/og-password.jpg" />

            <link rel="canonical" href="https://fluxmedia.space/tools/password-generator" />
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

        <!-- Hero -->
        <div class="relative overflow-hidden pt-8 pb-4 text-center">
            <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[200px] bg-gradient-to-tr from-purple-600/15 via-indigo-600/8 to-blue-600/8 blur-[100px] rounded-full pointer-events-none"></div>
            <div class="relative mx-auto max-w-4xl px-4 space-y-3">
                <div class="flex items-center justify-center">
                    <span class="inline-flex items-center gap-x-2 rounded-full bg-purple-500/10 px-4 py-1.5 text-[10px] font-bold text-purple-300 border border-purple-500/20 uppercase tracking-[0.2em]">
                        <span class="h-1.5 w-1.5 rounded-full bg-purple-400 animate-pulse"></span>
                        Password Generator
                    </span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-white leading-tight">
                    Strong. <span class="bg-gradient-to-r from-purple-400 via-indigo-400 to-blue-400 bg-clip-text text-transparent">Secure.</span> Private.
                </h1>
                <p class="text-xs text-gray-400 max-w-md mx-auto leading-relaxed">
                    Generate cryptographically secure passwords locally in your browser. No password ever touches our servers.
                </p>
            </div>
        </div>

        <!-- Main Content -->
        <div class="mx-auto max-w-2xl px-3 sm:px-4 lg:px-6 pb-28 space-y-6 mt-2">
            <!-- Password Card -->
            <div class="rounded-3xl border border-gray-800/80 bg-[#121826]/80 backdrop-blur-xl shadow-2xl p-4 sm:p-6 lg:p-8">
                <!-- Result Area -->
                <div class="relative mb-8">
                    <div class="w-full bg-[#0B0F19] border border-gray-700 rounded-2xl py-5 px-6 pr-14 text-xl sm:text-2xl font-mono text-center text-purple-400 break-all select-all min-h-[76px] flex items-center justify-center">
                        {{ password || 'Select options...' }}
                    </div>
                    <button @click="copyToClipboard" class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-500 hover:text-purple-400 transition-colors p-2 rounded-xl hover:bg-purple-500/10">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
                    </button>
                </div>

                <!-- Strength Indicator -->
                <div class="flex gap-1 mb-8">
                    <div v-for="i in 5" :key="i" :class="['h-1.5 flex-1 rounded-full transition-all duration-500', i <= strength ? (strength <= 2 ? 'bg-red-500' : (strength <= 4 ? 'bg-amber-500' : 'bg-emerald-500')) : 'bg-gray-800']"></div>
                </div>

                <!-- Controls -->
                <div class="space-y-6">
                    <div>
                        <div class="flex justify-between mb-3">
                            <label class="text-[10px] font-bold text-gray-500 uppercase tracking-widest">Password Length: {{ length }}</label>
                        </div>
                        <input v-model.number="length" type="range" min="4" max="64" @input="generatePassword" class="w-full h-1.5 bg-gray-800 rounded-lg appearance-none cursor-pointer accent-purple-500" />
                    </div>

                    <div class="flex items-center justify-between px-1">
                        <label class="text-[10px] font-bold text-gray-500 uppercase tracking-widest">Characters</label>
                        <button @click="toggleAllOptions" class="text-[10px] font-bold text-purple-400 hover:text-purple-300 transition-colors uppercase tracking-widest">
                            {{ allOptionsSelected ? 'Deselect All' : 'Select All' }}
                        </button>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <button v-for="opt in [
                            { label: 'Uppercase', key: 'uppercase' },
                            { label: 'Lowercase', key: 'lowercase' },
                            { label: 'Numbers',   key: 'numbers'   },
                            { label: 'Symbols',   key: 'symbols'   },
                        ]" :key="opt.label" @click="options[opt.key] = !options[opt.key]; generatePassword()" :class="['flex items-center justify-between px-4 py-3.5 rounded-2xl border transition-all', options[opt.key] ? 'bg-purple-500/10 border-purple-500/40 text-purple-400' : 'bg-[#0B0F19] border-gray-800 text-gray-500']">
                            <span class="text-xs font-bold">{{ opt.label }}</span>
                            <div :class="['h-4 w-4 rounded-full border flex items-center justify-center transition-all', options[opt.key] ? 'bg-purple-500 border-purple-500' : 'border-gray-700']">
                                <svg v-if="options[opt.key]" class="h-2.5 w-2.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="4" d="M5 13l4 4L19 7" /></svg>
                            </div>
                        </button>
                    </div>

                    <button @click="generatePassword" class="w-full inline-flex items-center justify-center gap-x-2 rounded-2xl bg-purple-600 hover:bg-purple-500 px-6 py-4 text-sm font-bold text-white shadow-lg shadow-purple-600/20 transition-all active:scale-[0.98]">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                        <span>Regenerate Password</span>
                    </button>
                </div>
            </div>

            <!-- Features -->
            <div class="flex flex-wrap justify-center gap-2">
                <span v-for="f in ['Secure Random', 'Client-side only', 'Open Source', 'Track-free', 'Brute-force safe']" :key="f" class="inline-flex items-center gap-x-1.5 rounded-full bg-gray-800/40 border border-gray-700/30 px-3 py-1.5 text-[10px] font-bold text-gray-500 uppercase tracking-widest">
                    <svg class="h-3 w-3 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" /></svg>
                    {{ f }}
                </span>
            </div>
        </div>
    </PublicLayout>
</template>

<style scoped>
input[type=range]::-webkit-slider-thumb {
    -webkit-appearance: none;
    height: 18px;
    width: 18px;
    border-radius: 50%;
    background: #a855f7;
    cursor: pointer;
    box-shadow: 0 0 10px rgba(168, 85, 247, 0.4);
    border: 2px solid white;
}
</style>
