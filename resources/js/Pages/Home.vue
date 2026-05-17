<script setup>
import { Head, Link } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';

defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
});

const categories = [
    {
        id: 'image-media',
        label: 'Image & Media',
        description: 'Transform, compress, and generate visual assets',
        color: 'purple',
        tools: [
            {
                id: 'image-studio',
                name: 'Image Studio',
                description: 'Convert, resize, crop & adjust images. Supports WebP, AVIF, PNG, JPEG.',
                route: 'tools.image',
                status: 'live',
                icon: `<svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>`,
            },
            {
                id: 'qr-generator',
                name: 'QR Generator',
                description: 'Create custom QR codes for URLs, WiFi, Email, SMS & more.',
                route: 'tools.qr',
                status: 'live',
                icon: `<svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4H6a2 2 0 00-2 2v6m8-8h6a2 2 0 012 2v6M4 12v6a2 2 0 002 2h6m2-8h6a2 2 0 012 2v6a2 2 0 01-2 2h-6a2 2 0 01-2-2v-6a2 2 0 012-2z" /></svg>`,
            },
        ],
    },
    {
        id: 'web-network',
        label: 'Web & Network',
        description: 'Inspect, shorten, and analyze web resources',
        color: 'blue',
        tools: [
            {
                id: 'url-shortener',
                name: 'URL Shortener',
                description: 'Shorten long URLs into clean, trackable links instantly.',
                route: 'tools.url-shortener',
                status: 'live',
                icon: `<svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" /></svg>`,
            },
            {
                id: 'ip-lookup',
                name: 'IP Lookup',
                description: 'Find geolocation, ISP, and threat data for any IP address.',
                route: 'tools.ip',
                status: 'live',
                icon: `<svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" /></svg>`,
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
                description: 'Generate RFC-compliant UUIDs v1 and v4 in bulk instantly.',
                route: 'tools.uuid-generator',
                status: 'live',
                icon: `<svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14" /></svg>`,
            },
            {
                id: 'password-generator',
                name: 'Password Generator',
                description: 'Create strong, secure passwords with custom character sets.',
                route: 'tools.password-generator',
                status: 'live',
                icon: `<svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>`,
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
                status: 'soon',
                icon: `<svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>`,
            },
            {
                id: 'hash-generator',
                name: 'Hash Generator',
                description: 'Generate MD5, SHA-1, SHA-256, and SHA-512 hashes instantly.',
                status: 'soon',
                icon: `<svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>`,
            },
        ],
    },
    {
        id: 'dev-tools',
        label: 'Developer Tools',
        description: 'Format, pick, and manipulate data and text',
        color: 'pink',
        tools: [
            {
                id: 'json-formatter',
                name: 'JSON Formatter',
                description: 'Beautify, minify, and validate JSON with syntax highlighting.',
                status: 'soon',
                icon: `<svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" /></svg>`,
            },
            {
                id: 'color-picker',
                name: 'Color Picker',
                description: 'Pick, convert, and generate palettes in HEX, RGB, HSL, and more.',
                status: 'soon',
                icon: `<svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01" /></svg>`,
            },
            {
                id: 'text-tools',
                name: 'Text Tools',
                description: 'Count words, change case, remove duplicates, and more.',
                status: 'soon',
                icon: `<svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 6h16M4 12h16M4 18h7" /></svg>`,
            },
        ],
    },
];

const colorMap = {
    purple: {
        badge: 'bg-purple-500/10 text-purple-400 border-purple-500/20',
        icon: 'bg-purple-500/10 text-purple-400',
        glow: 'shadow-purple-500/10',
        border: 'border-purple-500/30',
        hover: 'hover:border-purple-500/50 hover:shadow-purple-500/10',
        dot: 'bg-purple-500',
    },
    blue: {
        badge: 'bg-blue-500/10 text-blue-400 border-blue-500/20',
        icon: 'bg-blue-500/10 text-blue-400',
        glow: 'shadow-blue-500/10',
        border: 'border-blue-500/20',
        hover: 'hover:border-blue-500/40 hover:shadow-blue-500/10',
        dot: 'bg-blue-500',
    },
    emerald: {
        badge: 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20',
        icon: 'bg-emerald-500/10 text-emerald-400',
        glow: 'shadow-emerald-500/10',
        border: 'border-emerald-500/20',
        hover: 'hover:border-emerald-500/40 hover:shadow-emerald-500/10',
        dot: 'bg-emerald-500',
    },
    amber: {
        badge: 'bg-amber-500/10 text-amber-400 border-amber-500/20',
        icon: 'bg-amber-500/10 text-amber-400',
        glow: 'shadow-amber-500/10',
        border: 'border-amber-500/20',
        hover: 'hover:border-amber-500/40 hover:shadow-amber-500/10',
        dot: 'bg-amber-500',
    },
    pink: {
        badge: 'bg-pink-500/10 text-pink-400 border-pink-500/20',
        icon: 'bg-pink-500/10 text-pink-400',
        glow: 'shadow-pink-500/10',
        border: 'border-pink-500/20',
        hover: 'hover:border-pink-500/40 hover:shadow-pink-500/10',
        dot: 'bg-pink-500',
    },
};
</script>

<template>
    <PublicLayout>
        <Head>
            <title>FluxMedia · Free Online Tools — Image, QR, Dev Utilities</title>
            <meta name="description" content="FluxMedia is your all-in-one toolkit for image conversion, QR generation, URL shortening, password generation, JSON formatting, Base64 encoding, and more. All tools free, no signup required." />
            <meta name="keywords" content="online tools, image converter, qr code generator, url shortener, password generator, json formatter, base64 encoder, hash generator, free developer tools" />
            <meta property="og:title" content="FluxMedia · Free Online Tools — Image, QR, Dev Utilities" />
            <meta property="og:description" content="Your all-in-one creative and developer toolkit. Free, fast, no signup required." />
            <meta property="og:image" content="/assets/images/fluxmedia_main.webp" />
            <meta property="og:url" content="https://fluxmedia.space" />
            <meta name="twitter:title" content="FluxMedia · Free Online Tools" />
            <meta name="twitter:description" content="Free image, QR, and developer tools — all in one place." />
            <meta name="twitter:image" content="/assets/images/fluxmedia_main.webp" />
        </Head>

        <!-- Hero -->
        <div class="relative overflow-hidden pt-14 pb-10 text-center">
            <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[700px] h-[300px] bg-gradient-to-tr from-purple-600/15 via-indigo-600/8 to-pink-600/8 blur-[120px] rounded-full pointer-events-none"></div>

            <div class="relative mx-auto max-w-4xl px-6">
                <div class="flex items-center justify-center gap-x-3 mb-8">
                    <div class="h-px w-16 bg-gradient-to-r from-transparent to-purple-500/40"></div>
                    <span class="inline-flex items-center gap-x-2 rounded-full bg-purple-500/10 px-4 py-1.5 text-[10px] font-bold text-purple-300 border border-purple-500/20 uppercase tracking-[0.2em]">
                        <span class="h-1.5 w-1.5 rounded-full bg-purple-400 animate-pulse"></span>
                        All-in-One Toolkit
                    </span>
                    <div class="h-px w-16 bg-gradient-to-l from-transparent to-purple-500/40"></div>
                </div>

                <img src="/assets/images/fluxmedia_main.webp" class="mx-auto h-20 lg:h-28 object-contain mb-8 drop-shadow-[0_0_30px_rgba(168,85,247,0.35)]" alt="FluxMedia Logo" />

                <h1 class="text-4xl lg:text-6xl font-extrabold tracking-tight text-white mb-5 leading-tight">
                    Your Free <span class="bg-gradient-to-r from-purple-400 via-pink-400 to-indigo-400 bg-clip-text text-transparent">Creative</span> Toolkit
                </h1>
                <p class="text-sm lg:text-base text-gray-400 font-medium max-w-2xl mx-auto leading-relaxed">
                    Image processing, QR codes, developer utilities, and more — all free, no signup required, no limits.
                </p>

                <!-- Stats Strip -->
                <div class="mt-10 flex flex-wrap items-center justify-center gap-6 lg:gap-10">
                    <div v-for="stat in [
                        { value: '11', label: 'Tools' },
                        { value: '2', label: 'Live Now' },
                        { value: '100%', label: 'Free' },
                        { value: '0', label: 'Signup Required' },
                    ]" :key="stat.label" class="text-center">
                        <p class="text-2xl font-black text-white">{{ stat.value }}</p>
                        <p class="text-[10px] text-gray-500 font-bold uppercase tracking-widest mt-0.5">{{ stat.label }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tool Grid -->
        <div class="mx-auto max-w-6xl px-3 lg:px-6 pb-20 mt-2">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-8">

                <div v-for="category in categories" :key="category.id">
                    <!-- Category Label -->
                    <div class="flex items-center gap-x-2.5 mb-3">
                        <span :class="['h-2 w-2 rounded-full shrink-0', colorMap[category.color].dot]"></span>
                        <h2 class="text-[11px] font-black text-white uppercase tracking-[0.18em]">{{ category.label }}</h2>
                        <div class="flex-1 h-px bg-gray-800/60"></div>
                        <span class="text-[10px] text-gray-600 hidden sm:block">{{ category.description }}</span>
                    </div>

                    <!-- Tool Cards — stack vertically within each category column -->
                    <div class="space-y-2">
                        <component
                            v-for="tool in category.tools"
                            :key="tool.id"
                            :is="tool.status === 'live' ? Link : 'div'"
                            v-bind="tool.status === 'live' ? { href: route(tool.route) } : {}"
                            :class="[
                                'group flex items-center gap-x-4 rounded-xl border px-4 py-3.5 transition-all duration-200',
                                tool.status === 'live'
                                    ? [colorMap[category.color].border, 'bg-[#121826]/80 cursor-pointer hover:shadow-lg hover:shadow-black/30 hover:-translate-y-0.5', colorMap[category.color].hover]
                                    : 'border-gray-800/50 bg-[#0F1520]/60 opacity-55 cursor-default',
                            ]"
                        >
                            <!-- Icon -->
                            <div :class="[
                                'h-10 w-10 rounded-xl flex items-center justify-center shrink-0 transition-transform duration-200',
                                colorMap[category.color].icon,
                                tool.status === 'live' ? 'group-hover:scale-105' : '',
                            ]" v-html="tool.icon"></div>

                            <!-- Content -->
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-bold text-white leading-snug truncate">{{ tool.name }}</p>
                                <p class="text-[11px] text-gray-500 mt-0.5 leading-snug line-clamp-1">{{ tool.description }}</p>
                            </div>

                            <!-- Status -->
                            <div class="shrink-0 flex flex-col items-end gap-y-1">
                                <span v-if="tool.status === 'live'" :class="['inline-flex items-center gap-x-1.5 rounded-full px-2.5 py-1 text-[9px] font-black uppercase tracking-widest border', colorMap[category.color].badge]">
                                    <span :class="['h-1.5 w-1.5 rounded-full animate-pulse', colorMap[category.color].dot]"></span>
                                    Live
                                </span>
                                <span v-else class="inline-flex items-center gap-x-1.5 rounded-full bg-gray-800/60 px-2.5 py-1 text-[9px] font-bold uppercase tracking-widest text-gray-600 border border-gray-700/40">
                                    Soon
                                </span>
                                <!-- Arrow on hover for live -->
                                <svg v-if="tool.status === 'live'" :class="['h-3.5 w-3.5 opacity-0 group-hover:opacity-100 transition-all duration-200 group-hover:translate-x-0.5', `text-${category.color === 'pink' ? 'pink' : category.color}-400`]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" /></svg>
                            </div>
                        </component>
                    </div>
                </div>

            </div>
        </div>

        <!-- Footer -->
        <footer class="border-t border-gray-800/80 pt-8 pb-12 text-center mx-auto max-w-6xl px-6">
            <p class="text-[10px] text-gray-600 font-bold uppercase tracking-[0.2em]">FluxMedia Studio · Hub v1.0.0</p>
        </footer>
    </PublicLayout>
</template>
