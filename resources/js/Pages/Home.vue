<script setup>
import { Head, Link } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { onMounted, onUnmounted, ref } from 'vue';

defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
});

// FAQ accordion state
const openFaq = ref(null);
const toggleFaq = (idx) => {
    openFaq.value = openFaq.value === idx ? null : idx;
};

const faqs = [
    {
        q: 'Are all FluxMedia tools really free?',
        a: 'Yes — every tool on FluxMedia is 100% free with no hidden fees, no subscriptions, and no usage limits. We are committed to keeping all core tools permanently free.',
    },
    {
        q: 'Do I need to create an account to use the tools?',
        a: 'No account is needed. All tools work instantly in your browser. Creating an optional account lets you save history and access your files across sessions.',
    },
    {
        q: 'Is my data safe when using FluxMedia tools?',
        a: 'Yes. Most processing happens directly in your browser — nothing is uploaded to our servers unless required (e.g., image conversion). Uploaded files are automatically purged from secure storage after a short retention period.',
    },
    {
        q: 'What image formats does the Image Studio support?',
        a: 'The Image Studio supports JPEG, PNG, WebP, GIF, AVIF, and ICO. You can convert between any of these formats, resize, crop, adjust brightness and blur, and download the result instantly.',
    },
    {
        q: 'Can I use FluxMedia on mobile?',
        a: 'Absolutely. FluxMedia is fully responsive and optimized for mobile devices. All tools work on smartphones and tablets without any app installation required.',
    },
    {
        q: 'How many tools does FluxMedia offer?',
        a: 'FluxMedia currently offers 14 tools across 5 categories: Image & Media, Web & Network, Generators, Encoding & Crypto, and Developer Tools. New tools are added regularly.',
    },
];

// Inject JSON-LD structured data programmatically (can't use <script> inside Vue template)
const ldScripts = [];
onMounted(() => {
    // 1. WebSite Structured Data
    const webSiteScript = document.createElement('script');
    webSiteScript.type = 'application/ld+json';
    webSiteScript.textContent = JSON.stringify({
        '@context': 'https://schema.org',
        '@type': 'WebSite',
        'name': 'FluxMedia',
        'url': 'https://fluxmedia.space',
        'description': 'Free online tools for developers and creators. Convert images, generate QR codes, shorten URLs, create passwords, encode Base64, hash strings, format JSON, pick colors, and more.',
        'potentialAction': {
            '@type': 'SearchAction',
            'target': {
                '@type': 'EntryPoint',
                'urlTemplate': 'https://fluxmedia.space/search?q={search_term_string}',
            },
            'query-input': 'required name=search_term_string',
        },
    });
    document.head.appendChild(webSiteScript);
    ldScripts.push(webSiteScript);

    // 2. SoftwareApplication Structured Data (enhanced)
    const appScript = document.createElement('script');
    appScript.type = 'application/ld+json';
    appScript.textContent = JSON.stringify({
        '@context': 'https://schema.org',
        '@type': 'SoftwareApplication',
        'name': 'FluxMedia Toolkit',
        'applicationCategory': 'UtilitiesApplication',
        'applicationSubCategory': 'Developer Tools',
        'operatingSystem': 'Web, Windows, macOS, Linux, Android, iOS',
        'url': 'https://fluxmedia.space',
        'offers': {
            '@type': 'Offer',
            'price': '0',
            'priceCurrency': 'USD',
        },
        'description': '14 free online tools for developers and creators including image converter & compressor, QR code generator, URL shortener, password generator, UUID generator, Base64 encoder, SHA hash generator, IP lookup, JSON formatter, color picker, text tools, JWT debugger, regex tester, and CSV-to-JSON converter.',
        'featureList': [
            'Image Conversion (WebP, AVIF, PNG, JPEG, GIF, ICO)',
            'QR Code Generator (URL, WiFi, Email, SMS, Phone)',
            'URL Shortener',
            'IP Geolocation Lookup',
            'UUID Generator (v1, v4)',
            'Secure Password Generator',
            'Base64 Encoder & Decoder',
            'Hash Generator (MD5, SHA-1, SHA-256, SHA-512)',
            'JWT Debugger & Verifier',
            'JSON Formatter & Validator',
            'Color Picker (HEX, RGB, HSL)',
            'Text Tools (word count, case converter)',
            'Regex Tester & Explainer',
            'CSV to JSON Converter',
        ],
    });
    document.head.appendChild(appScript);
    ldScripts.push(appScript);

    // 3. ItemList — all live tools (enables carousel rich results in Google)
    const itemListScript = document.createElement('script');
    itemListScript.type = 'application/ld+json';
    itemListScript.textContent = JSON.stringify({
        '@context': 'https://schema.org',
        '@type': 'ItemList',
        'name': 'FluxMedia Free Online Tools',
        'description': 'A curated list of free online tools for developers and creators.',
        'url': 'https://fluxmedia.space',
        'numberOfItems': 15,
        'itemListElement': [
            { '@type': 'ListItem', 'position': 1, 'name': 'Image Studio — Convert, Resize & Compress Images', 'url': 'https://fluxmedia.space/tools/image', 'description': 'Free online image converter and compressor. Convert to WebP, AVIF, PNG, JPEG, GIF, ICO. Resize, crop, and adjust images instantly.' },
            { '@type': 'ListItem', 'position': 2, 'name': 'QR Code Generator', 'url': 'https://fluxmedia.space/tools/qr-code', 'description': 'Generate custom QR codes for URLs, WiFi, email, SMS, phone, and plain text. Download as PNG or SVG.' },
            { '@type': 'ListItem', 'position': 3, 'name': 'URL Shortener', 'url': 'https://fluxmedia.space/tools/url-shortener', 'description': 'Turn long URLs into short, trackable links. No account required.' },
            { '@type': 'ListItem', 'position': 4, 'name': 'IP Lookup & Geolocation', 'url': 'https://fluxmedia.space/tools/ip-lookup', 'description': 'Look up geolocation, ISP, ASN, and threat intelligence data for any IP address.' },
            { '@type': 'ListItem', 'position': 5, 'name': 'UUID Generator', 'url': 'https://fluxmedia.space/tools/uuid-generator', 'description': 'Generate RFC-compliant UUID v1 and v4 identifiers in bulk with one click.' },
            { '@type': 'ListItem', 'position': 6, 'name': 'Password Generator', 'url': 'https://fluxmedia.space/tools/password-generator', 'description': 'Generate strong, secure passwords with custom length and character sets.' },
            { '@type': 'ListItem', 'position': 7, 'name': 'Base64 Encoder & Decoder', 'url': 'https://fluxmedia.space/tools/base64', 'description': 'Encode or decode Base64 strings and files in real-time, directly in your browser.' },
            { '@type': 'ListItem', 'position': 8, 'name': 'Hash Generator — MD5, SHA-1, SHA-256, SHA-512', 'url': 'https://fluxmedia.space/tools/hash-generator', 'description': 'Instantly compute MD5, SHA-1, SHA-256, and SHA-512 cryptographic hashes for any text.' },
            { '@type': 'ListItem', 'position': 9, 'name': 'JWT Debugger & Verifier', 'url': 'https://fluxmedia.space/tools/jwt', 'description': 'Decode, encode, and verify JSON Web Tokens (JWT). Inspect claims and signatures locally.' },
            { '@type': 'ListItem', 'position': 10, 'name': 'JSON Formatter & Validator', 'url': 'https://fluxmedia.space/tools/json-formatter', 'description': 'Beautify, minify, and validate JSON with syntax highlighting and error detection.' },
            { '@type': 'ListItem', 'position': 11, 'name': 'Color Picker — HEX, RGB, HSL', 'url': 'https://fluxmedia.space/tools/color-picker', 'description': 'Pick colors and convert between HEX, RGB, and HSL formats. Save custom palettes.' },
            { '@type': 'ListItem', 'position': 12, 'name': 'Text Tools', 'url': 'https://fluxmedia.space/tools/text', 'description': 'Word count, character count, case converter, remove duplicates, and more text utilities.' },
            { '@type': 'ListItem', 'position': 13, 'name': 'Regex Tester & Explainer', 'url': 'https://fluxmedia.space/tools/regex', 'description': 'Test and visualize regular expression patterns with real-time match highlighting and explanations.' },
            { '@type': 'ListItem', 'position': 14, 'name': 'CSV to JSON Converter', 'url': 'https://fluxmedia.space/tools/csv-json', 'description': 'Convert CSV spreadsheet data to formatted JSON instantly. Supports custom delimiters.' },
            { '@type': 'ListItem', 'position': 15, 'name': 'AI Background Remover', 'url': 'https://fluxmedia.space/tools/ai-background-remover', 'description': 'Remove image backgrounds instantly with AI. Download transparent PNGs.' },
        ],
    });
    document.head.appendChild(itemListScript);
    ldScripts.push(itemListScript);

    // 4. FAQPage Structured Data (enables FAQ rich snippets)
    const faqScript = document.createElement('script');
    faqScript.type = 'application/ld+json';
    faqScript.textContent = JSON.stringify({
        '@context': 'https://schema.org',
        '@type': 'FAQPage',
        'mainEntity': faqs.map(f => ({
            '@type': 'Question',
            'name': f.q,
            'acceptedAnswer': {
                '@type': 'Answer',
                'text': f.a,
            },
        })),
    });
    document.head.appendChild(faqScript);
    ldScripts.push(faqScript);

    // 5. BreadcrumbList
    const breadcrumbScript = document.createElement('script');
    breadcrumbScript.type = 'application/ld+json';
    breadcrumbScript.textContent = JSON.stringify({
        '@context': 'https://schema.org',
        '@type': 'BreadcrumbList',
        'itemListElement': [
            { '@type': 'ListItem', 'position': 1, 'name': 'Home', 'item': 'https://fluxmedia.space' },
        ],
    });
    document.head.appendChild(breadcrumbScript);
    ldScripts.push(breadcrumbScript);
});
onUnmounted(() => {
    ldScripts.forEach(script => script.remove());
});


const siteUrl = 'https://fluxmedia.space';
const siteName = 'FluxMedia';
const siteTitle = 'FluxMedia — Free Online Tools for Creators & Developers';
const siteDescription = 'Convert images, generate QR codes, shorten URLs, create passwords, encode Base64, hash strings, and more. 15+ free tools, no account needed, no limits. Fast, private, and built for everyone.';
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
            {
                id: 'background-remover',
                name: 'AI Background Remover',
                description: 'Remove image backgrounds instantly with AI. Fast and transparent.',
                route: 'tools.background-remover',
                status: 'live',
                icon: 'sparkles',
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
            {
                id: 'jwt-debugger',
                name: 'JWT Debugger',
                description: 'Decode, encode, verify signatures, and analyze token claims locally.',
                route: 'tools.jwt',
                status: 'live',
                icon: 'key',
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
                route: 'tools.json-formatter',
                status: 'live',
                icon: 'braces',
            },
            {
                id: 'color-picker',
                name: 'Color Picker',
                description: 'Pick, convert, and generate palettes in HEX, RGB, and HSL.',
                route: 'tools.color-picker',
                status: 'live',
                icon: 'palette',
            },
            {
                id: 'text-tools',
                name: 'Text Tools',
                description: 'Count words, change case, remove duplicates, and more.',
                route: 'tools.text',
                status: 'live',
                icon: 'text-size',
            },
            {
                id: 'regex-tester',
                name: 'Regex Tester',
                description: 'Test, visualize, and explain regular expression matches in real-time.',
                route: 'tools.regex',
                status: 'live',
                icon: 'search',
            },
            {
                id: 'csv-json',
                name: 'CSV ⇆ JSON Transformer',
                description: 'Convert spreadsheet lists directly to clean, formatted JSON.',
                route: 'tools.csv-json',
                status: 'live',
                icon: 'arrows-right-left',
            },
            {
                id: 'svg-architect',
                name: 'SVG Path Architect',
                description: 'Optimize SVG icons, compress inline shapes, and clean layout properties.',
                route: 'tools.svg-architect',
                status: 'soon',
                icon: 'cube',
            },
            {
                id: 'epoch-translator',
                name: 'Epoch UNIX Translator',
                description: 'Decode UNIX timestamps into relative and human-readable dates.',
                route: 'tools.epoch',
                status: 'soon',
                icon: 'clock',
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
    key: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-.999.43-1.563A6 6 0 1121.75 8.25z"/>`,
    search: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>`,
    'arrows-right-left': `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5"/>`,
    cube: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9"/>`,
    clock: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/>`,
    sparkles: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.813 15.904L9 21L8.188 15.904L3 15L8.188 14.096L9 9L9.813 14.096L15 15L9.813 15.904ZM19.071 4.929L19.5 7.5L19.929 4.929L22.5 4.5L19.929 4.071L19.5 1.5L19.071 4.071L16.5 4.5L19.071 4.929ZM19.071 19.071L19.5 21.642L19.929 19.071L22.5 18.642L19.929 18.213L19.5 15.642L19.071 18.213L16.5 18.642L19.071 19.071Z"/>`,
};

const stats = [
    { value: '17', label: 'Total Tools' },
    { value: '15', label: 'Live Now' },
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
            <meta name="keywords" content="free online tools, developer tools, AI tools, image converter, webp converter, avif converter, qr code generator, url shortener, password generator, json formatter, base64 encoder, hash generator, sha256, md5, uuid generator, web utilities, ip lookup, geolocation, text tools, regex tester, csv to json, color picker, jwt decoder, no signup tools, browser tools, free developer utilities" />
            <meta name="author" content="FluxMedia" />
            <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1" />
            <meta name="googlebot" content="index, follow, max-snippet:-1, max-image-preview:large" />
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
                <div class="absolute left-1/2 top-0 h-[250px] w-[400px] md:h-[500px] md:w-[800px] -translate-x-1/2 -translate-y-1/3 rounded-full bg-violet-600/[0.07] md:blur-[100px] blur-[40px]"></div>
                <div class="absolute left-[20%] top-[40%] h-[150px] w-[150px] md:h-[300px] md:w-[300px] rounded-full bg-sky-600/[0.05] md:blur-[80px] blur-[30px]"></div>
                <div class="absolute right-[15%] top-[30%] h-[120px] w-[120px] md:h-[250px] md:w-[250px] rounded-full bg-rose-600/[0.05] md:blur-[80px] blur-[30px]"></div>
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
        <main class="mx-auto max-w-5xl px-4 pb-24 lg:px-6 tools-containment" id="tools" aria-label="Available tools">

            <!-- Section heading -->
            <div class="mb-10 flex items-center gap-4">
                <div class="h-px flex-1 bg-gradient-to-r from-transparent via-white/[0.08] to-transparent"></div>
                <h2 class="text-[11px] font-bold uppercase tracking-[0.25em] text-gray-600">Browse tools</h2>
                <div class="h-px flex-1 bg-gradient-to-r from-transparent via-white/[0.08] to-transparent"></div>
            </div>

            <div class="grid grid-cols-1 gap-x-6 gap-y-10 md:grid-cols-2">
                <div v-for="category in categories" :key="category.id" :class="[category.id === 'dev-tools' ? 'md:col-span-2' : '']">

                    <!-- Category header -->
                    <div class="mb-3 flex items-center gap-2.5">
                        <span :class="['h-2 w-2 shrink-0 rounded-full', colorConfig[category.color].dot]"></span>
                        <h3 class="text-[11px] font-black uppercase tracking-[0.2em] text-white">{{ category.label }}</h3>
                        <div class="h-px flex-1 bg-white/[0.06]"></div>
                    </div>

                    <!-- Tool cards -->
                    <ul :class="[category.id === 'dev-tools' ? 'grid grid-cols-1 md:grid-cols-2 gap-3 space-y-0' : 'space-y-2']" role="list">
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

        <!-- ─────────────── WHY FLUXMEDIA (Trust Signals) ─────────────── -->
        <section class="mx-auto max-w-5xl px-4 pb-16 lg:px-6" aria-label="Why FluxMedia">
            <div class="mb-8 flex items-center gap-4">
                <div class="h-px flex-1 bg-gradient-to-r from-transparent via-white/[0.08] to-transparent"></div>
                <h2 class="text-[11px] font-bold uppercase tracking-[0.25em] text-gray-600">Why FluxMedia</h2>
                <div class="h-px flex-1 bg-gradient-to-r from-transparent via-white/[0.08] to-transparent"></div>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <!-- Private & Secure -->
                <div class="rounded-xl border border-white/[0.06] bg-white/[0.025] p-5 text-center">
                    <div class="mx-auto mb-3 flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-400/10">
                        <svg class="h-5 w-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                        </svg>
                    </div>
                    <h3 class="mb-1.5 text-[13px] font-bold text-white">Private &amp; Secure</h3>
                    <p class="text-[11px] leading-relaxed text-gray-500">Files processed in your browser. Nothing stored without your permission. Uploads auto-purge from secure servers.</p>
                </div>

                <!-- Fast & Free -->
                <div class="rounded-xl border border-white/[0.06] bg-white/[0.025] p-5 text-center">
                    <div class="mx-auto mb-3 flex h-10 w-10 items-center justify-center rounded-xl bg-violet-400/10">
                        <svg class="h-5 w-5 text-violet-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>
                    <h3 class="mb-1.5 text-[13px] font-bold text-white">Fast &amp; Free Forever</h3>
                    <p class="text-[11px] leading-relaxed text-gray-500">No ads, no account required, no usage limits. Built for speed — most tools run instantly in your browser.</p>
                </div>

                <!-- 15 Tools in One -->
                <div class="rounded-xl border border-white/[0.06] bg-white/[0.025] p-5 text-center">
                    <div class="mx-auto mb-3 flex h-10 w-10 items-center justify-center rounded-xl bg-sky-400/10">
                        <svg class="h-5 w-5 text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11.42 15.17L17.25 21A2.652 2.652 0 0021 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 11-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 004.486-6.336l-3.276 3.277a3.004 3.004 0 01-2.25-2.25l3.276-3.276a4.5 4.5 0 00-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437l1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008z" />
                        </svg>
                    </div>
                    <h3 class="mb-1.5 text-[13px] font-bold text-white">15 Tools in One Place</h3>
                    <p class="text-[11px] leading-relaxed text-gray-500">Image processing, encoding, network utilities, generators, and developer tools — all under one roof, always expanding.</p>
                </div>
            </div>
        </section>

        <!-- ─────────────── FAQ SECTION ─────────────── -->
        <section class="mx-auto max-w-5xl px-4 pb-24 lg:px-6" aria-label="Frequently asked questions" id="faq">
            <div class="mb-8 flex items-center gap-4">
                <div class="h-px flex-1 bg-gradient-to-r from-transparent via-white/[0.08] to-transparent"></div>
                <h2 class="text-[11px] font-bold uppercase tracking-[0.25em] text-gray-600">FAQ</h2>
                <div class="h-px flex-1 bg-gradient-to-r from-transparent via-white/[0.08] to-transparent"></div>
            </div>

            <dl class="space-y-2">
                <div
                    v-for="(faq, idx) in faqs"
                    :key="idx"
                    class="rounded-xl border border-white/[0.06] bg-white/[0.02] overflow-hidden transition-all duration-200"
                    :class="openFaq === idx ? 'border-white/[0.1]' : ''"
                >
                    <dt>
                        <button
                            @click="toggleFaq(idx)"
                            class="flex w-full items-center justify-between gap-4 px-5 py-4 text-left cursor-pointer"
                            :aria-expanded="openFaq === idx"
                            :aria-controls="`faq-answer-${idx}`"
                            :id="`faq-btn-${idx}`"
                        >
                            <span class="text-[13px] font-semibold text-white">{{ faq.q }}</span>
                            <svg
                                :class="['h-4 w-4 shrink-0 text-gray-500 transition-transform duration-200', openFaq === idx ? 'rotate-180' : '']"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                    </dt>
                    <dd
                        v-show="openFaq === idx"
                        :id="`faq-answer-${idx}`"
                        :aria-labelledby="`faq-btn-${idx}`"
                        class="px-5 pb-4"
                    >
                        <p class="text-[12px] leading-relaxed text-gray-400">{{ faq.a }}</p>
                    </dd>
                </div>
            </dl>
        </section>
    </PublicLayout>
</template>

<style scoped>
.tools-containment {
    content-visibility: auto;
    contain-intrinsic-size: auto 1200px;
}
</style>