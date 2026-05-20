<script setup>
import { Head, usePage } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { ref } from 'vue';

const props = defineProps({
    history: {
        type: Array,
        default: () => [],
    },
});

const userHistory = ref(props.history || []);

const formatBytes = (bytes) => {
    if (bytes === 0) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
};
</script>

<template>
    <PublicLayout>
        <Head>
            <title>My Asset History Hub — Optimize & Manage Assets | FluxMedia</title>

            <!-- Primary SEO -->
            <meta name="description" content="Access and manage your processed image assets. Review optimization metrics and download transformed files from your secure session history." />
            <meta name="keywords" content="asset history, image conversion history, compressed images, my assets, fluxmedia history, session archive" />
            <meta name="author" content="FluxMedia" />
            <meta name="robots" content="noindex, nofollow" />
            <link rel="canonical" href="https://fluxmedia.space/history" />

            <!-- Open Graph / Facebook -->
            <meta property="og:type" content="website" />
            <meta property="og:title" content="My Asset History Hub — Optimize & Manage Assets | FluxMedia" />
            <meta property="og:description" content="Access and manage your processed image assets. Review optimization metrics and download transformed files from your secure session history." />
            <meta property="og:image" content="https://fluxmedia.space/assets/images/fluxmedia_main.webp" />
            <meta property="og:url" content="https://fluxmedia.space/history" />
            <meta property="og:site_name" content="FluxMedia" />

            <!-- Twitter Card -->
            <meta name="twitter:card" content="summary_large_image" />
            <meta name="twitter:site" content="@fluxmedia" />
            <meta name="twitter:creator" content="@fluxmedia" />
            <meta name="twitter:title" content="My Asset History Hub — Optimize & Manage Assets" />
            <meta name="twitter:description" content="Access and manage your processed image assets. Review optimization metrics and download transformed files from your secure session history." />
            <meta name="twitter:image" content="https://fluxmedia.space/assets/images/fluxmedia_main.webp" />
        </Head>

        <!-- Hero Section (Desktop Only) -->
        <div class="hidden lg:block relative overflow-hidden pt-10 lg:pt-12 pb-6 lg:pb-8 text-center">
            <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[500px] h-[250px] bg-gradient-to-tr from-purple-600/20 via-indigo-600/10 to-pink-600/10 blur-[100px] rounded-full pointer-events-none"></div>

            <div class="relative mx-auto max-w-4xl px-6">
                <span class="inline-flex items-center gap-x-2 rounded-full bg-purple-500/10 px-4 py-1.5 text-[10px] font-bold text-purple-300 border border-purple-500/20 mb-6 uppercase tracking-widest">
                    <span class="h-1.5 w-1.5 rounded-full bg-purple-400 animate-pulse"></span>
                    Asset Archive
                </span>

                <img src="/assets/images/fluxmedia_main.webp" class="mx-auto h-16 lg:h-24 object-contain mb-8 drop-shadow-[0_0_25px_rgba(168,85,247,0.35)]" alt="FluxMedia Logo" />

                <h1 class="text-3xl lg:text-5xl font-extrabold tracking-tight text-white max-w-3xl mx-auto leading-tight uppercase">
                    History <br class="lg:hidden" />
                    <span class="bg-gradient-to-r from-purple-400 via-pink-400 to-indigo-400 bg-clip-text text-transparent">
                        Hub
                    </span>
                </h1>
            </div>
        </div>

        <!-- Main History Area -->
        <div class="mx-auto max-w-7xl px-3 lg:px-6 mt-4 lg:mt-8 mb-20">
            <div class="rounded-3xl border border-gray-800/80 bg-[#121826]/80 backdrop-blur-xl shadow-2xl p-5 lg:p-8">
                
                <div class="flex items-center justify-between mb-8 pb-4 border-b border-gray-800/80">
                    <div>
                        <h2 class="text-xl lg:text-2xl font-black text-white uppercase tracking-tight">Recent Cycles</h2>
                        <p class="text-[10px] text-gray-500 font-bold uppercase tracking-widest mt-1">
                            Assets are purged every {{ $page.props.auth?.user ? $page.props.appSettings.authRetentionDays + ' days' : $page.props.appSettings.guestRetentionHours + ' hours' }}
                        </p>
                    </div>
                    <div class="bg-purple-500/10 border border-purple-500/20 px-4 py-2 rounded-2xl">
                        <span class="text-xs font-black text-purple-400">{{ userHistory.length }} Total Assets</span>
                    </div>
                </div>

                <div v-if="userHistory.length === 0" class="py-32 text-center">
                    <div class="h-20 w-20 bg-gray-900 rounded-3xl flex items-center justify-center text-gray-700 mx-auto mb-6 border border-gray-800">
                        <svg class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">Archive Empty</h3>
                    <p class="text-sm text-gray-500 max-w-xs mx-auto">No processed assets found in your current session context. Start refining images to see them here.</p>
                </div>

                <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                    <div v-for="item in userHistory" :key="item.id" class="group relative bg-[#0B0F19] rounded-3xl border border-gray-800/80 overflow-hidden hover:border-purple-500/50 transition-all duration-300 hover:shadow-2xl hover:shadow-purple-500/10">
                        <!-- Preview Box -->
                        <div class="aspect-video relative overflow-hidden bg-black flex items-center justify-center p-2">
                            <img :src="item.output_url" class="max-h-full max-w-full object-contain transition-transform duration-500 group-hover:scale-110" alt="Processed Asset" />
                            <div class="absolute top-3 right-3 px-2.5 py-1 rounded-lg bg-black/60 backdrop-blur-md border border-white/10 text-[9px] font-black text-purple-400 uppercase tracking-widest">
                                {{ item.format }}
                            </div>
                        </div>

                        <!-- Meta Info -->
                        <div class="p-5">
                            <div class="flex items-center justify-between mb-4">
                                <div class="flex-1 min-w-0 pr-3">
                                    <h4 class="text-xs font-black text-white truncate uppercase tracking-tight">{{ item.original_name }}</h4>
                                    <p class="text-[9px] text-gray-500 font-bold uppercase tracking-widest mt-0.5">{{ item.created_at }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-[10px] font-black text-emerald-400">{{ formatBytes(item.processed_size) }}</p>
                                    <p class="text-[8px] text-gray-600 font-bold uppercase tracking-tighter">{{ item.dimensions }}</p>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <a 
                                    :href="item.output_url" 
                                    target="_blank"
                                    class="py-2.5 rounded-xl bg-gray-800 hover:bg-gray-700 text-white text-[9px] font-black uppercase tracking-widest text-center transition-all flex items-center justify-center gap-x-2"
                                >
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                    Preview
                                </a>
                                <a 
                                    :href="item.download_url" 
                                    class="py-2.5 rounded-xl bg-purple-600 hover:bg-purple-500 text-white text-[9px] font-black uppercase tracking-widest text-center transition-all flex items-center justify-center gap-x-2 shadow-lg shadow-purple-600/20"
                                >
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                                    Download
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </PublicLayout>
</template>

<style scoped>
.no-scrollbar::-webkit-scrollbar {
    display: none;
}
.no-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
</style>
