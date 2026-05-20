<script setup>
import { Head } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';
</script>

<template>
    <PublicLayout>
        <Head>
            <title>Privacy Policy — Data Protection & Client-Side Privacy | FluxMedia</title>

            <!-- Primary SEO -->
            <meta name="description" content="Read the FluxMedia Privacy Policy. Learn about our commitment to secure, client-side data processing and transparent telemetry practices." />
            <meta name="keywords" content="privacy policy, data protection, secure tools, client side processing, telemetry disclosure, cookie policy" />
            <meta name="author" content="FluxMedia" />
            <meta name="robots" content="index, follow" />
            <link rel="canonical" href="https://fluxmedia.space/privacy" />

            <!-- Open Graph / Facebook -->
            <meta property="og:type" content="website" />
            <meta property="og:title" content="Privacy Policy — Data Protection & Client-Side Privacy | FluxMedia" />
            <meta property="og:description" content="Read the FluxMedia Privacy Policy. Learn about our commitment to secure, client-side data processing and transparent telemetry practices." />
            <meta property="og:image" content="https://fluxmedia.space/assets/images/fluxmedia_main.webp" />
            <meta property="og:url" content="https://fluxmedia.space/privacy" />
            <meta property="og:site_name" content="FluxMedia" />

            <!-- Twitter Card -->
            <meta name="twitter:card" content="summary_large_image" />
            <meta name="twitter:site" content="@fluxmedia" />
            <meta name="twitter:creator" content="@fluxmedia" />
            <meta name="twitter:title" content="Privacy Policy — Data Protection & Client-Side Privacy" />
            <meta name="twitter:description" content="Read the FluxMedia Privacy Policy. Learn about our commitment to secure, client-side data processing." />
            <meta name="twitter:image" content="https://fluxmedia.space/assets/images/fluxmedia_main.webp" />
        </Head>

        <!-- Ambient Glow Background Background -->
        <div class="relative min-h-screen">
            <div class="absolute top-0 left-1/2 -translate-x-1/2 w-[800px] h-[350px] bg-gradient-to-br from-purple-600/10 via-indigo-600/5 to-transparent blur-[120px] rounded-full pointer-events-none"></div>

            <!-- Hero Section -->
            <div class="relative overflow-hidden pt-12 lg:pt-16 pb-6 text-center">
                <div class="relative mx-auto max-w-4xl px-4 space-y-3">
                    <div class="flex items-center justify-center">
                        <span class="inline-flex items-center gap-x-2 rounded-full bg-purple-500/10 px-4 py-1.5 text-[10px] font-bold text-purple-300 border border-purple-500/20 uppercase tracking-[0.2em]">
                            <svg class="h-3 w-3 text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                            Secure Ecosystem
                        </span>
                    </div>

                    <h1 class="text-3xl lg:text-5xl font-extrabold tracking-tight text-white leading-tight uppercase">
                        Privacy <span class="bg-gradient-to-r from-purple-400 via-pink-400 to-indigo-400 bg-clip-text text-transparent">Policy</span>
                    </h1>
                    <p class="text-xs lg:text-sm font-bold uppercase tracking-widest text-gray-500 max-w-md mx-auto">
                        Simple, Safe & Transparent Data Policies
                    </p>
                </div>
            </div>

            <!-- Content Area -->
            <div class="mx-auto max-w-4xl px-4 sm:px-6 mt-6 pb-24">
                <div class="rounded-3xl border border-gray-800/80 bg-[#121826]/80 backdrop-blur-xl shadow-2xl p-6 lg:p-10 space-y-10">
                    
                    <!-- Metadata block -->
                    <div class="flex flex-wrap items-center justify-between gap-4 border-b border-gray-800/80 pb-6 text-xs text-gray-400">
                        <div class="flex items-center gap-x-2">
                            <span class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
                            <span class="font-semibold uppercase tracking-wider">Active Policy Suite v1.0.0</span>
                        </div>
                        <span class="font-mono">Last Updated: May 20, 2026</span>
                    </div>

                    <!-- Client-side Disclosure Highlight Box -->
                    <div class="bg-purple-950/10 border border-purple-500/20 p-6 rounded-2xl space-y-3">
                        <h3 class="text-xs font-black uppercase tracking-wider text-purple-300 flex items-center gap-x-2">
                            <svg class="h-4 w-4 text-purple-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                            Core Privacy Principle: Client-Side Core
                        </h3>
                        <p class="text-xs lg:text-sm text-gray-300 leading-relaxed">
                            FluxMedia values your data security. The majority of utilities in our developer workspace (including base64 parsing, text alterations, regular expression computations, and JSON/CSV formatting) execute <strong class="text-white">entirely in your browser memory</strong>. None of your input strings, credentials, or keys are uploaded to our host server architecture.
                        </p>
                    </div>

                    <!-- Section: 100% Client-Side Processing -->
                    <section class="space-y-4">
                        <div class="flex items-center gap-x-3">
                            <div class="h-7 w-7 rounded-lg bg-purple-500/10 border border-purple-500/25 flex items-center justify-center text-purple-400 font-bold text-xs">
                                01
                            </div>
                            <h2 class="text-lg font-black uppercase tracking-tight text-white">Client-Side Isolation</h2>
                        </div>
                        <div class="pl-10 space-y-3 text-xs lg:text-sm text-gray-400 leading-relaxed">
                            <p>
                                Unlike legacy tool portals that dispatch payloads back-and-forth across API endpoints, FluxMedia processes your data inputs locally.
                            </p>
                            <ul class="list-disc pl-5 space-y-2 text-gray-400">
                                <li>All input parsing, string transformations, base64 conversions, and JWT evaluations take place in-memory within your sandboxed browser runtime.</li>
                                <li>Any files or logs processed locally never touch our persistent hard drives.</li>
                                <li>This design model isolates sensitive application data, passwords, and tokens from internet intercepts and data leakage breaches.</li>
                            </ul>
                        </div>
                    </section>

                    <!-- Section: Autosave Drafts -->
                    <section class="space-y-4">
                        <div class="flex items-center gap-x-3">
                            <div class="h-7 w-7 rounded-lg bg-purple-500/10 border border-purple-500/25 flex items-center justify-center text-purple-400 font-bold text-xs">
                                02
                            </div>
                            <h2 class="text-lg font-black uppercase tracking-tight text-white">Local Storage & Draft Autosaves</h2>
                        </div>
                        <div class="pl-10 space-y-3 text-xs lg:text-sm text-gray-400 leading-relaxed">
                            <p>
                                To prevent content loss during accidental navigation, page refreshes, or browser crashes, select tools utilize the HTML5 Web Storage API (`localStorage`).
                            </p>
                            <ul class="list-disc pl-5 space-y-2 text-gray-400">
                                <li>Draft text data, configuration states, and interface settings are persisted strictly in your local device browser profile.</li>
                                <li>These settings are never synchronized with any remote cloud accounts.</li>
                                <li>You remain in absolute command: clearing your browser history or site settings instantly purges all preserved configurations and text drafts.</li>
                            </ul>
                        </div>
                    </section>

                    <!-- Section: System Telemetry Logs -->
                    <section class="space-y-4">
                        <div class="flex items-center gap-x-3">
                            <div class="h-7 w-7 rounded-lg bg-purple-500/10 border border-purple-500/25 flex items-center justify-center text-purple-400 font-bold text-xs">
                                03
                            </div>
                            <h2 class="text-lg font-black uppercase tracking-tight text-white">Server Connection Logs</h2>
                        </div>
                        <div class="pl-10 space-y-3 text-xs lg:text-sm text-gray-400 leading-relaxed">
                            <p>
                                When navigating to, loading, or requesting specific network operations (such as processing complex multi-format images, fetching remote API payloads, or utilizing our URL shortener redirects), standard server request metadata is logged.
                            </p>
                            <p>
                                These telemetry logs include your <span class="text-gray-200">Internet Protocol (IP) address</span>, browser user-agent details, access timestamp, and the targeted resource URL.
                            </p>
                            <ul class="list-disc pl-5 space-y-2 text-gray-400">
                                <li><strong class="text-gray-300">Security & Integrity:</strong> These logs are used to counter Denial-of-Service (DDoS) attempts, track API abuse, enforce reasonable rate-limits, and verify system status.</li>
                                <li><strong class="text-gray-300">Dashboard Metrics:</strong> Connection logs are processed to aggregate usage frequency metrics inside the administrator dashboard.</li>
                                <li><strong class="text-gray-300">Retention Limits:</strong> Server log cycles are subject to automatic purge rules, keeping data strictly for security mitigation before permanent deletion.</li>
                            </ul>
                        </div>
                    </section>

                    <!-- Section: No Third-Party Tracking -->
                    <section class="space-y-4">
                        <div class="flex items-center gap-x-3">
                            <div class="h-7 w-7 rounded-lg bg-purple-500/10 border border-purple-500/25 flex items-center justify-center text-purple-400 font-bold text-xs">
                                04
                            </div>
                            <h2 class="text-lg font-black uppercase tracking-tight text-white">No Monitored Ads or Selling</h2>
                        </div>
                        <div class="pl-10 space-y-3 text-xs lg:text-sm text-gray-400 leading-relaxed">
                            <p>
                                Our business model is focused on delivering a high-quality, open developer toolkit without compromising user privacy.
                            </p>
                            <ul class="list-disc pl-5 space-y-2 text-gray-400">
                                <li>We do not sell, rent, or lease your connection metadata or text content to external data brokers or advertisers.</li>
                                <li>No invasive behavioral tracking tags, advertisement tracking scripts, or analytics systems (such as Facebook Pixel or third-party ad networks) are installed in this application.</li>
                                <li>Your development environment remains entirely clear of commercial tracking algorithms.</li>
                            </ul>
                        </div>
                    </section>

                    <!-- Section: Cookies -->
                    <section class="space-y-4">
                        <div class="flex items-center gap-x-3">
                            <div class="h-7 w-7 rounded-lg bg-purple-500/10 border border-purple-500/25 flex items-center justify-center text-purple-400 font-bold text-xs">
                                05
                            </div>
                            <h2 class="text-lg font-black uppercase tracking-tight text-white">Essential Cookies Only</h2>
                        </div>
                        <div class="pl-10 space-y-3 text-xs lg:text-sm text-gray-400 leading-relaxed">
                            <p>
                                FluxMedia utilizes cookies strictly for secure session functionality and application integrity.
                            </p>
                            <ul class="list-disc pl-5 space-y-2 text-gray-400">
                                <li><strong class="text-gray-300">Authentication Cookies:</strong> Standard secure cookies are set when you sign up or log in to preserve your authenticated access session.</li>
                                <li><strong class="text-gray-300">CSRF Integrity:</strong> Cross-Site Request Forgery tokens are stored securely to prevent cross-site request attacks.</li>
                                <li><strong class="text-gray-300">User Configuration:</strong> Cookies preserve interface values (such as light/dark mode overrides or panel layout states) that cannot be managed solely via localStorage.</li>
                            </ul>
                        </div>
                    </section>

                    <!-- Support Inquiries -->
                    <div class="border-t border-gray-800/80 pt-8 flex flex-col md:flex-row items-center justify-between gap-6">
                        <div class="space-y-1 text-center md:text-left">
                            <h4 class="text-xs font-black uppercase tracking-widest text-white">Need Further Privacy Clarification?</h4>
                            <p class="text-xs text-gray-500">Contact our privacy compliance mailbox with any data security questions.</p>
                        </div>
                        <a href="mailto:gilbert@fluxmetrics.site" class="inline-flex items-center gap-x-2 rounded-2xl bg-[#0B0F19] border border-gray-800 hover:border-purple-500/40 py-2.5 px-5 text-xs font-bold text-gray-300 hover:text-white transition-all shadow-lg active:scale-95">
                            <svg class="h-4 w-4 text-purple-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                            gilbert@fluxmetrics.site
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </PublicLayout>
</template>
