<script setup>
import { Head, Link } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { onMounted, onUnmounted } from 'vue';

defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
});

// Inject JSON-LD structured data programmatically (can't use <script> inside Vue template)
const ldScripts = [];
onMounted(() => {
    // 1. WebSite Structured Data
    const webSiteScript = document.createElement('script');
    webSiteScript.type = 'application/ld+json';
    webSiteScript.textContent = JSON.stringify({
        "@context": "https://schema.org",
        "@type": "WebSite",
        "name": "FluxMedia",
        "url": "https://fluxmedia.space",
        "description": "Free online tools for image conversion, QR generation, URL shortening, password creation, Base64 encoding, hash generation, and more.",
        "potentialAction": {
            "@type": "SearchAction",
            "target": {
                "@type": "EntryPoint",
                "urlTemplate": "https://fluxmedia.space/search?q={search_term_string}"
            },
            "query-input": "required name=search_term_string"
        }
    });
    document.head.appendChild(webSiteScript);
    ldScripts.push(webSiteScript);

    // 2. SoftwareApplication Structured Data
    const appScript = document.createElement('script');
    appScript.type = 'application/ld+json';
    appScript.textContent = JSON.stringify({
        "@context": "https://schema.org",
        "@type": "SoftwareApplication",
        "name": "FluxMedia Toolkit",
        "applicationCategory": "UtilitiesApplication",
        "operatingSystem": "Web",
        "url": "https://fluxmedia.space",
        "offers": {
            "@type": "Offer",
            "price": "0",
            "priceCurrency": "USD"
        },
        "description": "A collection of 11 free online tools including image converter, QR code generator, URL shortener, password generator, UUID generator, Base64 encoder, and SHA hash generator.",
        "featureList": ["Image Conversion", "QR Code Generation", "URL Shortening", "Password Generation", "UUID Generation", "Base64 Encoding", "Hash Generation", "IP Lookup"]
    });
    document.head.appendChild(appScript);
    ldScripts.push(appScript);
});
onUnmounted(() => {
    ldScripts.forEach(script => script.remove());
});


const siteUrl = 'https://fluxmedia.space';
const siteName = 'FluxMedia';
const siteTitle = 'FluxMedia — Free Online Tools for Creators & Developers';
const siteDescription = 'Convert images, generate QR codes, shorten URLs, create passwords, encode Base64, hash strings, and more. 8+ free tools, no account needed, no limits. Fast, private, and built for everyone.';
const ogImage = `${siteUrl}/assets/images/fluxmedia_og.webp`;

const categories = [
    {
        id: 'image-media',
        label: 'Image & Media',
        description: 'Transform, compress, and generate visual assets',
        color: 'violet',
        tools: [
            {
                id: 'image-studio',
                name: 'Image Studio',
                description: 'Convert, resize, crop & adjust. Supports WebP, AVIF, PNG, JPEG.',
                route: 'tools.image',
                status: 'live',
                icon: 'photo',
            },
            {
                id: 'qr-generator',
                name: 'QR Generator',
                description: 'Custom QR codes for URLs, WiFi, email, SMS, vCards & more.',
                route: 'tools.qr',
                status: 'live',
                icon: 'qrcode',
            },
        ],
    },
    {
        id: 'web-network',
        label: 'Web & Network',
        description: 'Inspect, shorten, and analyze web resources',
        color: 'sky',
        tools: [
            {
                id: 'url-shortener',
                name: 'URL Shortener',
                description: 'Turn long URLs into short, clean, trackable links instantly.',
                route: 'tools.url-shortener',
                status: 'live',
                icon: 'link',
            },
            {
                id: 'ip-lookup',
                name: 'IP Lookup',
                description: 'Geolocation, ISP, and threat intelligence for any IP address.',
                route: 'tools.ip',
                status: 'live',
                icon: 'world',
            },
        ],
    },
    {
        id: 'generators',
        label: 'Generators',
        description: 'Generate random, secure, and unique values',
        color: 'emerald',
        tools: [
            {
                id: 'uuid-generator',
                name: 'UUID Generator',
                description: 'Bulk-generate RFC-compliant UUIDs v1 and v4 in one click.',
                route: 'tools.uuid-generator',
                status: 'live',
                icon: 'fingerprint',
            },
            {
                id: 'password-generator',
                name: 'Password Generator',
                description: 'Strong, secure passwords with custom length and character sets.',
                route: 'tools.password-generator',
                status: 'live',
                icon: 'lock',
            },
        ],
    },
    {
        id: 'encoding-crypto',
        label: 'Encoding & Crypto',
        description: 'Encode, decode, and hash your data',
        color: 'amber',
        tools: [
            {
                id: 'base64',
                name: 'Base64 Encoder',
                description: 'Encode or decode Base64 strings and files in real-time.',
                route: 'tools.base64',
                status: 'live',
                icon: 'code',
            },
            {
                id: 'hash-generator',
                name: 'Hash Generator',
                description: 'Instant MD5, SHA-1, SHA-256, and SHA-512 hash generation.',
                route: 'tools.hash-generator',
                status: 'live',
                icon: 'shield-check',
            },
        ],
    },
    {
        id: 'dev-tools',
        label: 'Developer Tools',
        description: 'Format, pick, and manipulate data and text',
        color: 'rose',
        tools: [
            {
                id: 'json-formatter',
                name: 'JSON Formatter',
                description: 'Beautify, minify, and validate JSON with syntax highlighting.',
                status: 'soon',
                icon: 'braces',
            },
            {
                id: 'color-picker',
                name: 'Color Picker',
                description: 'Pick, convert, and generate palettes in HEX, RGB, and HSL.',
                status: 'soon',
                icon: 'palette',
            },
            {
                id: 'text-tools',
                name: 'Text Tools',
                description: 'Count words, change case, remove duplicates, and more.',
                status: 'soon',
                icon: 'text-size',
            },
        ],
    },
];

const colorConfig = {
    violet: {
        dot: 'bg-violet-400',
        badge: 'bg-violet-400/10 text-violet-300 ring-violet-400/20',
        icon: 'bg-violet-400/10 text-violet-300',
        card: 'border-white/[0.06] hover:border-violet-400/30 hover:bg-white/[0.04]',
        arrow: 'text-violet-400',
        pulse: 'bg-violet-400',
    },
    sky: {
        dot: 'bg-sky-400',
        badge: 'bg-sky-400/10 text-sky-300 ring-sky-400/20',
        icon: 'bg-sky-400/10 text-sky-300',
        card: 'border-white/[0.06] hover:border-sky-400/30 hover:bg-white/[0.04]',
        arrow: 'text-sky-400',
        pulse: 'bg-sky-400',
    },
    emerald: {
        dot: 'bg-emerald-400',
        badge: 'bg-emerald-400/10 text-emerald-300 ring-emerald-400/20',
        icon: 'bg-emerald-400/10 text-emerald-300',
        card: 'border-white/[0.06] hover:border-emerald-400/30 hover:bg-white/[0.04]',
        arrow: 'text-emerald-400',
        pulse: 'bg-emerald-400',
    },
    amber: {
        dot: 'bg-amber-400',
        badge: 'bg-amber-400/10 text-amber-300 ring-amber-400/20',
        icon: 'bg-amber-400/10 text-amber-300',
        card: 'border-white/[0.06] hover:border-amber-400/30 hover:bg-white/[0.04]',
        arrow: 'text-amber-400',
        pulse: 'bg-amber-400',
    },
    rose: {
        dot: 'bg-rose-400',
        badge: 'bg-rose-400/10 text-rose-300 ring-rose-400/20',
        icon: 'bg-rose-400/10 text-rose-300',
        card: 'border-white/[0.06] hover:border-rose-400/30 hover:bg-white/[0.04]',
        arrow: 'text-rose-400',
        pulse: 'bg-rose-400',
    },
};

const iconPaths = {
    photo: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"/>`,
    qrcode: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3.75 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 013.75 9.375v-4.5zM3.75 14.625c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 01-1.125-1.125v-4.5zM13.5 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0113.5 9.375v-4.5z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6.75 6.75h.75v.75h-.75v-.75zM6.75 16.5h.75v.75h-.75v-.75zM16.5 6.75h.75v.75h-.75v-.75zM13.5 13.5h.75v.75h-.75v-.75zM13.5 19.5h.75v.75h-.75v-.75zM19.5 13.5h.75v.75h-.75v-.75zM19.5 19.5h.75v.75h-.75v-.75zM16.5 16.5h.75v.75h-.75v-.75z"/>`,
    link: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m13.35-.622l1.757-1.757a4.5 4.5 0 00-6.364-6.364l-4.5 4.5a4.5 4.5 0 001.242 7.244"/>`,
    world: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0121 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0112 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 013 12c0-1.605.42-3.113 1.157-4.418"/>`,
    fingerprint: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7.864 4.243A7.5 7.5 0 0119.5 10.5c0 2.92-.556 5.709-1.568 8.268M5.742 6.364A7.465 7.465 0 004.5 10.5a7.464 7.464 0 01-1.15 3.993m1.989 3.559A11.209 11.209 0 008.25 10.5a3.75 3.75 0 117.5 0c0 .527-.021 1.049-.064 1.565M12 10.5a14.94 14.94 0 01-3.6 9.75m6.633-4.596a18.666 18.666 0 01-2.485 5.33"/>`,
    lock: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/>`,
    code: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.25 6.75L22.5 12l-5.25 5.25m-10.5 0L1.5 12l5.25-5.25m7.5-3l-4.5 16.5"/>`,
    'shield-check': `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/>`,
    braces: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6.75 7.5l3 2.25-3 2.25m4.5 0h3m-9 8.25h13.5A2.25 2.25 0 0021 18V6a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 003 6v12a2.25 2.25 0 002.25 2.25z"/>`,
    palette: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4.098 19.902a3.75 3.75 0 005.304 0l6.401-6.402M6.75 21A3.75 3.75 0 013 17.25V4.125C3 3.504 3.504 3 4.125 3h5.25c.621 0 1.125.504 1.125 1.125v4.072M6.75 21a3.75 3.75 0 003.75-3.75V8.197M6.75 21h13.125c.621 0 1.125-.504 1.125-1.125v-5.25c0-.621-.504-1.125-1.125-1.125h-4.072M10.5 8.197l2.88-2.88c.438-.439 1.15-.439 1.59 0l3.712 3.713c.44.44.44 1.152 0 1.59l-2.879 2.88M6.75 17.25h.008v.008H6.75v-.008z"/>`,
    'text-size': `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25H12"/>`,
};

const stats = [
    { value: '11', label: 'Total Tools' },
    { value: '8', label: 'Live Now' },
    { value: '100%', label: 'Free Forever' },
    { value: '0', label: 'Sign-up Needed' },
];
</script>

<template>
    <PublicLayout>
        <Head>
            <!-- Primary Meta -->
            <title>{{ siteTitle }}</title>
            <meta name="description" :content="siteDescription" />
            <meta name="keywords" content="online tools, free tools, image converter, webp converter, avif converter, qr code generator, url shortener, password generator, json formatter, base64 encoder, hash generator, sha256, md5, uuid generator, developer tools, web utilities, ip lookup, geolocation" />
            <meta name="author" content="FluxMedia" />
            <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1" />
            <link rel="canonical" :href="siteUrl" />

            <!-- Open Graph / Facebook / LinkedIn -->
            <meta property="og:type" content="website" />
            <meta property="og:site_name" :content="siteName" />
            <meta property="og:url" :content="siteUrl" />
            <meta property="og:title" :content="siteTitle" />
            <meta property="og:description" :content="siteDescription" />
            <meta property="og:image" :content="ogImage" />
            <meta property="og:image:width" content="1200" />
            <meta property="og:image:height" content="630" />
            <meta property="og:image:alt" content="FluxMedia — Free Online Tools for Creators & Developers" />
            <meta property="og:locale" content="en_US" />

            <!-- Twitter / X Card -->
            <meta name="twitter:card" content="summary_large_image" />
            <meta name="twitter:site" content="@fluxmedia" />
            <meta name="twitter:creator" content="@fluxmedia" />
            <meta name="twitter:url" :content="siteUrl" />
            <meta name="twitter:title" :content="siteTitle" />
            <meta name="twitter:description" :content="siteDescription" />
            <meta name="twitter:image" :content="ogImage" />
            <meta name="twitter:image:alt" content="FluxMedia — Free Online Tools for Creators & Developers" />

            <!-- WhatsApp / iMessage / Telegram preview -->
            <meta property="og:image:secure_url" :content="ogImage" />

            <!-- Favicon / PWA -->
            <link rel="icon" type="image/webp" href="/assets/images/fluxmedia_main.webp" />
            <link rel="apple-touch-icon" href="/assets/images/fluxmedia_main.webp" />
            <meta name="theme-color" content="#0a0d14" />
            <meta name="apple-mobile-web-app-capable" content="yes" />
            <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent" />
            <meta name="apple-mobile-web-app-title" content="FluxMedia" />

            <!-- JSON-LD injected via onMounted in script setup -->
        </Head>

        <!-- ─────────────── HERO ─────────────── -->
        <section class="relative overflow-hidden px-6 pb-12 pt-16 text-center" aria-label="FluxMedia homepage hero">

            <!-- Ambient background glow -->
            <div class="pointer-events-none absolute inset-0 overflow-hidden" aria-hidden="true">
                <div class="absolute left-1/2 top-0 h-[500px] w-[800px] -translate-x-1/2 -translate-y-1/3 rounded-full bg-violet-600/[0.07] blur-[100px]"></div>
                <div class="absolute left-[20%] top-[40%] h-[300px] w-[300px] rounded-full bg-sky-600/[0.05] blur-[80px]"></div>
                <div class="absolute right-[15%] top-[30%] h-[250px] w-[250px] rounded-full bg-rose-600/[0.05] blur-[80px]"></div>
                <!-- Subtle grid -->
                <div class="absolute inset-0 bg-[linear-gradient(rgba(255,255,255,0.015)_1px,transparent_1px),linear-gradient(90deg,rgba(255,255,255,0.015)_1px,transparent_1px)] bg-[size:60px_60px]"></div>
            </div>

            <div class="relative mx-auto max-w-3xl">
                <!-- Eyebrow pill -->
                <div class="mb-7 flex items-center justify-center">
                    <span class="inline-flex items-center gap-2 rounded-full border border-white/[0.08] bg-white/[0.04] px-4 py-1.5 text-[11px] font-semibold uppercase tracking-widest text-gray-400 backdrop-blur-sm">
                        <span class="inline-block h-1.5 w-1.5 rounded-full bg-emerald-400 ring-2 ring-emerald-400/30 animate-pulse"></span>
                        All-in-one free toolkit
                    </span>
                </div>

                <!-- Logo -->
                <img
                    src="/assets/images/fluxmedia_main.webp"
                    alt="FluxMedia logo"
                    width="112"
                    height="112"
                    class="mx-auto mb-7 h-24 w-24 object-contain drop-shadow-[0_0_40px_rgba(139,92,246,0.4)]"
                    loading="eager"
                    fetchpriority="high"
                />

                <!-- Headline -->
                <h1 class="mb-4 text-[2.6rem] font-extrabold leading-[1.1] tracking-tight text-white lg:text-6xl">
                    Every tool you need,<br>
                    <span class="bg-gradient-to-r from-violet-400 via-sky-400 to-emerald-400 bg-clip-text text-transparent">completely free</span>
                </h1>

                <!-- Sub -->
                <p class="mx-auto max-w-xl text-[15px] leading-relaxed text-gray-400">
                    Image conversion, QR codes, URL shortening, password generation, Base64 encoding, hashing, and more — no account, no limits, no nonsense.
                </p>

                <!-- Stat strip -->
                <div class="mt-10 flex flex-wrap items-center justify-center gap-8">
                    <div v-for="stat in stats" :key="stat.label" class="text-center">
                        <p class="text-2xl font-black tabular-nums text-white">{{ stat.value }}</p>
                        <p class="mt-0.5 text-[10px] font-bold uppercase tracking-[0.18em] text-gray-600">{{ stat.label }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ─────────────── TOOLS GRID ─────────────── -->
        <main class="mx-auto max-w-5xl px-4 pb-24 lg:px-6" id="tools" aria-label="Available tools">

            <!-- Section heading -->
            <div class="mb-10 flex items-center gap-4">
                <div class="h-px flex-1 bg-gradient-to-r from-transparent via-white/[0.08] to-transparent"></div>
                <h2 class="text-[11px] font-bold uppercase tracking-[0.25em] text-gray-600">Browse tools</h2>
                <div class="h-px flex-1 bg-gradient-to-r from-transparent via-white/[0.08] to-transparent"></div>
            </div>

            <div class="grid grid-cols-1 gap-x-6 gap-y-10 md:grid-cols-2">
                <div v-for="category in categories" :key="category.id">

                    <!-- Category header -->
                    <div class="mb-3 flex items-center gap-2.5">
                        <span :class="['h-2 w-2 shrink-0 rounded-full', colorConfig[category.color].dot]"></span>
                        <h3 class="text-[11px] font-black uppercase tracking-[0.2em] text-white">{{ category.label }}</h3>
                        <div class="h-px flex-1 bg-white/[0.06]"></div>
                    </div>

                    <!-- Tool cards -->
                    <ul class="space-y-2" role="list">
                        <li v-for="tool in category.tools" :key="tool.id">
                            <component
                                :is="tool.status === 'live' ? Link : 'div'"
                                v-bind="tool.status === 'live' ? { href: route(tool.route) } : {}"
                                :class="[
                                    'group flex items-center gap-4 rounded-xl border px-4 py-3.5 transition-all duration-150',
                                    tool.status === 'live'
                                        ? [colorConfig[category.color].card, 'bg-white/[0.025] cursor-pointer hover:shadow-xl hover:shadow-black/20 hover:-translate-y-px']
                                        : 'cursor-default border-white/[0.04] bg-white/[0.01] opacity-40',
                                ]"
                                :aria-label="tool.status === 'live' ? `Open ${tool.name}` : `${tool.name} — coming soon`"
                            >
                                <!-- Icon box -->
                                <div
                                    :class="['flex h-10 w-10 shrink-0 items-center justify-center rounded-xl transition-transform duration-150', colorConfig[category.color].icon, tool.status === 'live' ? 'group-hover:scale-110' : '']"
                                    aria-hidden="true"
                                >
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <g v-html="iconPaths[tool.icon]"></g>
                                    </svg>
                                </div>

                                <!-- Text -->
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-[13px] font-bold leading-snug text-white">{{ tool.name }}</p>
                                    <p class="mt-0.5 line-clamp-1 text-[11px] leading-snug text-gray-500">{{ tool.description }}</p>
                                </div>

                                <!-- Badge + chevron -->
                                <div class="flex shrink-0 flex-col items-end gap-1.5">
                                    <span
                                        v-if="tool.status === 'live'"
                                        :class="['inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[9px] font-black uppercase tracking-widest ring-1', colorConfig[category.color].badge]"
                                    >
                                        <span :class="['h-1.5 w-1.5 rounded-full animate-pulse', colorConfig[category.color].pulse]" aria-hidden="true"></span>
                                        Live
                                    </span>
                                    <span
                                        v-else
                                        class="inline-flex items-center rounded-full bg-white/[0.05] px-2.5 py-1 text-[9px] font-bold uppercase tracking-widest text-gray-600 ring-1 ring-white/[0.06]"
                                    >
                                        Soon
                                    </span>
                                    <svg
                                        v-if="tool.status === 'live'"
                                        :class="['h-3.5 w-3.5 translate-x-0 opacity-0 transition-all duration-150 group-hover:translate-x-0.5 group-hover:opacity-100', colorConfig[category.color].arrow]"
                                        fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                        aria-hidden="true"
                                    >
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" />
                                    </svg>
                                </div>
                            </component>
                        </li>
                    </ul>
                </div>
            </div>
        </main>

        <!-- ─────────────── FOOTER ─────────────── -->
        <footer class="border-t border-white/[0.05] pb-12 pt-8 text-center" role="contentinfo">
            <p class="text-[10px] font-bold uppercase tracking-[0.25em] text-gray-700">
                FluxMedia Studio &middot; Hub v1.0.0
            </p>
        </footer>
    </PublicLayout>
</template>