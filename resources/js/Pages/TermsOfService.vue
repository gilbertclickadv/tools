<script setup>
import { Head } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';
</script>

<template>
    <PublicLayout>
        <Head>
            <title>Terms of Service — Fair Use & Platform License Agreement | FluxMedia</title>

            <!-- Primary SEO -->
            <meta name="description" content="Read the FluxMedia Terms of Service. Understand our fair use restrictions, free-tier warranty disclaimers, and open developer utility usage permissions." />
            <meta name="keywords" content="terms of service, user agreement, fair use guidelines, open source tools, liability waiver, developer terms" />
            <meta name="author" content="FluxMedia" />
            <meta name="robots" content="index, follow" />
            <link rel="canonical" href="https://fluxmedia.space/terms" />

            <!-- Open Graph / Facebook -->
            <meta property="og:type" content="website" />
            <meta property="og:title" content="Terms of Service — Fair Use & Platform License Agreement | FluxMedia" />
            <meta property="og:description" content="Read the FluxMedia Terms of Service. Understand our fair use restrictions, free-tier warranty disclaimers, and open developer utility usage permissions." />
            <meta property="og:image" content="https://fluxmedia.space/assets/images/fluxmedia_main.webp" />
            <meta property="og:url" content="https://fluxmedia.space/terms" />
            <meta property="og:site_name" content="FluxMedia" />

            <!-- Twitter Card -->
            <meta name="twitter:card" content="summary_large_image" />
            <meta name="twitter:site" content="@fluxmedia" />
            <meta name="twitter:creator" content="@fluxmedia" />
            <meta name="twitter:title" content="Terms of Service — Fair Use & Platform License Agreement" />
            <meta name="twitter:description" content="Read the FluxMedia Terms of Service. Understand our fair use restrictions, free-tier warranty disclaimers, and open developer utility usage permissions." />
            <meta name="twitter:image" content="https://fluxmedia.space/assets/images/fluxmedia_main.webp" />
        </Head>

        <!-- Ambient Glow Background -->
        <div class="relative min-h-screen">
            <div class="absolute top-0 left-1/2 -translate-x-1/2 w-[800px] h-[350px] bg-gradient-to-br from-purple-600/10 via-indigo-600/5 to-transparent blur-[120px] rounded-full pointer-events-none"></div>

            <!-- Hero Section -->
            <div class="relative overflow-hidden pt-12 lg:pt-16 pb-6 text-center">
                <div class="relative mx-auto max-w-4xl px-4 space-y-3">
                    <div class="flex items-center justify-center">
                        <span class="inline-flex items-center gap-x-2 rounded-full bg-purple-500/10 px-4 py-1.5 text-[10px] font-bold text-purple-300 border border-purple-500/20 uppercase tracking-[0.2em]">
                            <svg class="h-3 w-3 text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            Usage Framework
                        </span>
                    </div>

                    <h1 class="text-3xl lg:text-5xl font-extrabold tracking-tight text-white leading-tight uppercase">
                        Terms of <span class="bg-gradient-to-r from-purple-400 via-pink-400 to-indigo-400 bg-clip-text text-transparent">Service</span>
                    </h1>
                    <p class="text-xs lg:text-sm font-bold uppercase tracking-widest text-gray-500 max-w-md mx-auto">
                        Fair Use Guidelines & Platform Licenses
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
                            <span class="font-semibold uppercase tracking-wider">Active Agreement Suite v1.0.0</span>
                        </div>
                        <span class="font-mono">Last Updated: May 20, 2026</span>
                    </div>

                    <!-- Fair Use Highlight Box -->
                    <div class="bg-purple-950/10 border border-purple-500/20 p-6 rounded-2xl space-y-3">
                        <h3 class="text-xs font-black uppercase tracking-wider text-purple-300 flex items-center gap-x-2">
                            <svg class="h-4 w-4 text-purple-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c0 3.517-1.009 6.799-2.753 9.571m-3.44-2.04l.054-.09A13.916 13.916 0 009 11V7a2 2 0 00-2-2H6a2 2 0 00-2 2v2a4 4 0 00.293 2.154L5 18" />
                            </svg>
                            Essential Purpose: Fair & Open Ecosystem
                        </h3>
                        <p class="text-xs lg:text-sm text-gray-300 leading-relaxed">
                            FluxMedia provides modern, robust developer utilities without commercial access barriers. By visiting, using, or loading any content on our domain, you consent to these Terms of Service. These rules ensure that all web developers and creators can utilize our platforms reliably and securely.
                        </p>
                    </div>

                    <!-- Section: Acceptance of Terms -->
                    <section class="space-y-4">
                        <div class="flex items-center gap-x-3">
                            <div class="h-7 w-7 rounded-lg bg-purple-500/10 border border-purple-500/25 flex items-center justify-center text-purple-400 font-bold text-xs">
                                01
                            </div>
                            <h2 class="text-lg font-black uppercase tracking-tight text-white">Acceptance & Compliance</h2>
                        </div>
                        <div class="pl-10 space-y-3 text-xs lg:text-sm text-gray-400 leading-relaxed">
                            <p>
                                These Terms constitute a binding legal agreement between you ("User", "Developer", "Visitor") and FluxMedia.
                            </p>
                            <ul class="list-disc pl-5 space-y-2 text-gray-400">
                                <li>You represent that you have the authority to agree to these terms, whether on your own behalf or as an authorized representative of a business entity.</li>
                                <li>If you disagree with any segment of these provisions, you must terminate your access to the FluxMedia portal immediately.</li>
                                <li>We reserve the right to revise or adjust these terms at our sole discretion to preserve compliance, protect system security, or align with tool upgrades.</li>
                            </ul>
                        </div>
                    </section>

                    <!-- Section: Free & Unlimited Access -->
                    <section class="space-y-4">
                        <div class="flex items-center gap-x-3">
                            <div class="h-7 w-7 rounded-lg bg-purple-500/10 border border-purple-500/25 flex items-center justify-center text-purple-400 font-bold text-xs">
                                02
                            </div>
                            <h2 class="text-lg font-black uppercase tracking-tight text-white">Free-Forever Utility Model</h2>
                        </div>
                        <div class="pl-10 space-y-3 text-xs lg:text-sm text-gray-400 leading-relaxed">
                            <p>
                                All digital conversion, formatting, compression, analysis, and validation utilities deployed on FluxMedia are provided 100% free of charge.
                            </p>
                            <ul class="list-disc pl-5 space-y-2 text-gray-400">
                                <li>There are no subscription fees, premium paywall constraints, hidden quotas, or required credit card credentials.</li>
                                <li>FluxMedia does not restrict your access frequencies, payload sizes, or concurrent browser operations unless standard server rate-limits are breached to maintain service availability.</li>
                                <li>We grant you a non-exclusive, worldwide, royalty-free, non-transferable personal license to run and interact with these utilities natively in your browser.</li>
                            </ul>
                        </div>
                    </section>

                    <!-- Section: Permitted Usage -->
                    <section class="space-y-4">
                        <div class="flex items-center gap-x-3">
                            <div class="h-7 w-7 rounded-lg bg-purple-500/10 border border-purple-500/25 flex items-center justify-center text-purple-400 font-bold text-xs">
                                03
                            </div>
                            <h2 class="text-lg font-black uppercase tracking-tight text-white">Permitted Commercial & Personal Output Usage</h2>
                        </div>
                        <div class="pl-10 space-y-3 text-xs lg:text-sm text-gray-400 leading-relaxed">
                            <p>
                                FluxMedia claims absolute <strong class="text-purple-300">zero ownership</strong> over any text inputs, generated code strings, compiled JSON datasets, or optimized image assets produced using our utilities.
                            </p>
                            <ul class="list-disc pl-5 space-y-2 text-gray-400">
                                <li>You retain complete intellectual property and copyright over all files, formats, and text outputs produced using FluxMedia.</li>
                                <li>You are fully permitted to integrate, deploy, distribute, or license these output assets in both non-commercial projects and commercial software, games, websites, or enterprise systems.</li>
                                <li>You are solely responsible for verifying the integrity and legal ownership of any input data you process on this site.</li>
                            </ul>
                        </div>
                    </section>

                    <!-- Section: Limitation of Liability -->
                    <section class="space-y-4">
                        <div class="flex items-center gap-x-3">
                            <div class="h-7 w-7 rounded-lg bg-purple-500/10 border border-purple-500/25 flex items-center justify-center text-purple-400 font-bold text-xs">
                                04
                            </div>
                            <h2 class="text-lg font-black uppercase tracking-tight text-white">Disclaimer of Warranties & Liability Limitation</h2>
                        </div>
                        <div class="pl-10 space-y-3 text-xs lg:text-sm text-gray-400 leading-relaxed">
                            <p class="uppercase font-bold text-gray-300 text-xs">
                                IMPORTANT TECHNICAL DISCLAIMER: ALL UTILITIES ON FLUXMEDIA ARE DEPLOYED "AS-IS" AND "AS-AVAILABLE".
                            </p>
                            <p>
                                While we strive to maintain the absolute accuracy, high performance, and error-free execution of all conversion, validation, and encoding engines, we cannot guarantee perfection.
                            </p>
                            <ul class="list-disc pl-5 space-y-2 text-gray-400">
                                <li>We make no representations or warranties of any kind (express, implied, or statutory) regarding the accuracy, completeness, or suitability of generated tool outputs.</li>
                                <li>You assume all risks regarding data loss, structural inaccuracies, parsing glitches, or file corruptions that may occur during local conversions.</li>
                                <li>FluxMedia shall not be held liable for any direct, indirect, incidental, special, or consequential damages resulting from the use of, or inability to use, our platform. Developers are strongly encouraged to independently verify outputs before using them in production databases.</li>
                            </ul>
                        </div>
                    </section>

                    <!-- Section: Fair Use Restrictions -->
                    <section class="space-y-4">
                        <div class="flex items-center gap-x-3">
                            <div class="h-7 w-7 rounded-lg bg-purple-500/10 border border-purple-500/25 flex items-center justify-center text-purple-400 font-bold text-xs">
                                05
                            </div>
                            <h2 class="text-lg font-black uppercase tracking-tight text-white">System Protection & Fair Use Restrictions</h2>
                        </div>
                        <div class="pl-10 space-y-3 text-xs lg:text-sm text-gray-400 leading-relaxed">
                            <p>
                                To preserve server reliability, safeguard security, and maintain equitable speed for all developers, you agree to refrain from the following prohibited activities:
                            </p>
                            <ul class="list-disc pl-5 space-y-2 text-gray-400">
                                <li>Deploying automated scraper bots, mass request scripts, or automated tools to crawl our application files or endpoints without authorization.</li>
                                <li>Initiating Denial-of-Service (DoS) or Distributed Denial-of-Service (DDoS) campaigns, or flooding the host server with high-volume request streams.</li>
                                <li>Attempting to bypass security blocks, manipulate administrative dashboards, inject malicious payloads, or intercept connection streams.</li>
                                <li>Redistributing or repackaging FluxMedia's core code or styling systems to create cloned utility services for financial gain.</li>
                            </ul>
                        </div>
                    </section>

                    <!-- Support Inquiries -->
                    <div class="border-t border-gray-800/80 pt-8 flex flex-col md:flex-row items-center justify-between gap-6">
                        <div class="space-y-1 text-center md:text-left">
                            <h4 class="text-xs font-black uppercase tracking-widest text-white">Have Questions About Permitted Usage?</h4>
                            <p class="text-xs text-gray-500">Reach out to our platform support mailbox with any licensing inquiries.</p>
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
