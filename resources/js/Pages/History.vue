<script setup>
import { Head, Link } from '@inertiajs/vue3';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import VerificationAlert from '@/Components/VerificationAlert.vue';
import PWAInstallPrompt from '@/Components/PWAInstallPrompt.vue';
import { ref, computed } from 'vue';
import QrcodeVue from 'qrcode.vue';

const mobileMenuOpen = ref(false);

const props = defineProps({
    canLogin: {
        type: Boolean,
        default: true,
    },
    canRegister: {
        type: Boolean,
        default: true,
    },
    historyFeed: {
        type: Array,
        default: () => [],
    },
});

// View Filtering State
const activeFilter = ref('all'); // all | images | qrs

const filteredFeed = computed(() => {
    if (activeFilter.value === 'images') {
        return props.historyFeed.filter(item => item.type === 'image');
    }
    if (activeFilter.value === 'qrs') {
        return props.historyFeed.filter(item => item.type === 'qr');
    }
    return props.historyFeed;
});

// Formatter Helpers
const formatBytes = (bytes) => {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
};

const getProfileBadge = (type) => {
    switch (type) {
        case 'url': return '🔗 Link';
        case 'text': return '📝 Text';
        case 'email': return '✉️ Email';
        case 'phone': return '📞 Call';
        case 'wifi': return '📶 WiFi';
        case 'sms': return '💬 SMS';
        default: return '📐 Profile';
    }
};
</script>

<template>
    <Head>
        <title>FluxMedia Studio · Comprehensive Execution History</title>
        <meta name="description" content="Centralized cloud portal aggregating active user session caches. Download optimized Image streams and inspect live customizable vector QR codes securely." />
    </Head>

    <div class="min-h-screen bg-[#0B0F19] text-gray-100 font-jakarta selection:bg-purple-500 selection:text-white pb-32 lg:pb-10 overflow-x-hidden">
        <VerificationAlert />
        <PWAInstallPrompt />

        <!-- App Header (Unified Style) -->
        <header class="border-b border-gray-800/60 bg-[#0B0F19]/80 backdrop-blur-md sticky top-0 z-[100]">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 flex h-16 lg:h-20 items-center justify-between">
                <!-- Brand Logo -->
                <Link href="/" class="flex items-center gap-x-3 shrink-0 group">
                    <img src="/assets/images/icon_only.webp" class="h-8 w-8 lg:h-10 lg:w-10 object-cover group-hover:scale-105 transition-transform" alt="FluxMedia Icon" />
                    <span class="text-lg lg:text-xl font-bold tracking-tight bg-gradient-to-r from-white via-gray-200 to-purple-300 bg-clip-text text-transparent select-none">
                        FluxMedia
                    </span>
                </Link>

                <!-- Desktop Navigation (Centered) -->
                <nav class="hidden lg:flex items-center absolute left-1/2 -translate-x-1/2 gap-x-6 text-sm font-medium">
                    <Link href="/" class="px-4 py-2 rounded-xl transition-all duration-200 text-gray-400 hover:text-white hover:bg-gray-800/50">
                        Image Studio
                    </Link>
                    <Link href="/qr-code-generator" class="px-4 py-2 rounded-xl transition-all duration-200 text-gray-400 hover:text-white hover:bg-gray-800/50">
                        QR Generator
                    </Link>
                    <Link href="/history" class="px-4 py-2 rounded-xl transition-all duration-200 font-semibold bg-purple-500/10 text-purple-400 border border-purple-500/20">
                        History Hub
                    </Link>
                </nav>

                <!-- Desktop Auth (Right) -->
                <div class="hidden lg:flex items-center gap-x-4">
                    <a href="mailto:gilbert@fluxmetrics.site" class="h-10 w-10 flex items-center justify-center rounded-xl border border-gray-800 bg-[#121826]/50 hover:bg-gray-800/80 text-gray-400 hover:text-white transition-all shadow-lg" title="Contact Support">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" class="h-5 w-5" fill="currentColor"><path d="M320 128C241 128 175.3 185.3 162.3 260.7C171.6 257.7 181.6 256 192 256L208 256C234.5 256 256 277.5 256 304L256 400C256 426.5 234.5 448 208 448L192 448C139 448 96 405 96 352L96 288C96 164.3 196.3 64 320 64C443.7 64 544 164.3 544 288L544 456.1C544 522.4 490.2 576.1 423.9 576.1L336 576L304 576C277.5 576 256 554.5 256 528C256 501.5 277.5 480 304 480L336 480C362.5 480 384 501.5 384 528L384 528L424 528C463.8 528 496 495.8 496 456L496 435.1C481.9 443.3 465.5 447.9 448 447.9L432 447.9C405.5 447.9 384 426.4 384 399.9L384 303.9C384 277.4 405.5 255.9 432 255.9L448 255.9C458.4 255.9 468.3 257.5 477.7 260.6C464.7 185.3 399.1 127.9 320 127.9z"/></svg>
                    </a>
                    <template v-if="$page.props.auth?.user">
                        <Dropdown align="right" width="56">
                            <template #trigger>
                                <button class="flex items-center gap-x-2.5 px-3 py-1.5 rounded-xl border border-gray-800 bg-[#121826]/50 hover:bg-gray-800/80 transition-all group">
                                    <div class="h-7 w-7 rounded-lg bg-gradient-to-tr from-purple-600 to-indigo-600 flex items-center justify-center font-bold text-[11px] text-white">
                                        {{ $page.props.auth.user.name[0] }}
                                    </div>
                                    <span class="text-sm font-semibold text-gray-300 group-hover:text-white transition-colors">
                                        {{ $page.props.auth.user.name.split(' ')[0] }}
                                    </span>
                                    <svg class="h-4 w-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>
                            </template>
                            <template #content>
                                <div class="px-4 py-2 border-b border-gray-800/80 mb-1">
                                    <p class="text-xs font-bold text-white truncate">{{ $page.props.auth.user.name }}</p>
                                    <p class="text-[10px] text-gray-500 truncate">{{ $page.props.auth.user.email }}</p>
                                </div>
                                <DropdownLink :href="route('profile.edit')" class="flex items-center gap-x-2">
                                    Profile Settings
                                </DropdownLink>
                                <DropdownLink v-if="$page.props.auth?.user?.is_admin" :href="route('admin.dashboard')" class="text-purple-400 font-bold">
                                    Admin Dashboard
                                </DropdownLink>
                                <div class="h-px bg-gray-800/60 my-1"></div>
                                <DropdownLink :href="route('logout')" method="post" as="button" class="text-red-400">
                                    Logout Session
                                </DropdownLink>
                            </template>
                        </Dropdown>
                    </template>
                    <template v-else>
                        <Link :href="route('login')" class="text-sm font-medium text-gray-400 hover:text-white transition-colors">Log in</Link>
                        <Link v-if="canRegister" :href="route('register')" class="rounded-lg px-4 py-2 bg-purple-600 hover:bg-purple-500 text-white font-semibold text-sm shadow-md shadow-purple-600/20">Register Now</Link>
                    </template>
                </div>

                <!-- Mobile Actions -->
                <div class="lg:hidden flex items-center gap-x-3">
                    <a href="mailto:gilbert@fluxmetrics.site" class="h-9 w-9 flex items-center justify-center rounded-xl border border-gray-800 bg-[#121826]/50 text-gray-400 active:scale-95 transition-all">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" class="h-4 w-4" fill="currentColor"><path d="M320 128C241 128 175.3 185.3 162.3 260.7C171.6 257.7 181.6 256 192 256L208 256C234.5 256 256 277.5 256 304L256 400C256 426.5 234.5 448 208 448L192 448C139 448 96 405 96 352L96 288C96 164.3 196.3 64 320 64C443.7 64 544 164.3 544 288L544 456.1C544 522.4 490.2 576.1 423.9 576.1L336 576L304 576C277.5 576 256 554.5 256 528C256 501.5 277.5 480 304 480L336 480C362.5 480 384 501.5 384 528L384 528L424 528C463.8 528 496 495.8 496 456L496 435.1C481.9 443.3 465.5 447.9 448 447.9L432 447.9C405.5 447.9 384 426.4 384 399.9L384 303.9C384 277.4 405.5 255.9 432 255.9L448 255.9C458.4 255.9 468.3 257.5 477.7 260.6C464.7 185.3 399.1 127.9 320 127.9z"/></svg>
                    </a>
                    <template v-if="$page.props.auth?.user">
                        <Link :href="route('profile.edit')" class="h-9 w-9 rounded-xl bg-gradient-to-tr from-purple-600 to-indigo-600 flex items-center justify-center font-bold text-white text-xs border border-purple-400/30">
                            {{ $page.props.auth.user.name[0] }}
                        </Link>
                    </template>
                    <template v-else>
                        <Link :href="route('login')" class="text-xs font-bold text-purple-400 uppercase tracking-widest bg-purple-500/10 px-3 py-1.5 rounded-lg border border-purple-500/20">Login</Link>
                    </template>
                </div>
            </div>
        </header>

        <!-- Bottom Navigation for Mobile -->
        <nav class="lg:hidden fixed bottom-0 left-0 right-0 bg-[#0B0F19]/90 backdrop-blur-xl border-t border-gray-800/60 z-[100] px-6 py-3 flex items-center justify-between pb-[calc(12px+env(safe-area-inset-bottom))] shadow-[0_-10px_40px_rgba(0,0,0,0.4)]">
            <Link href="/" class="flex flex-col items-center gap-y-1 transition-all text-gray-500 hover:text-gray-300">
                <div class="h-6 w-6 flex items-center justify-center">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="currentColor"><path d="M1.5 6a2.25 2.25 0 0 1 2.25-2.25h16.5A2.25 2.25 0 0 1 22.5 6v12a2.25 2.25 0 0 1-2.25 2.25H3.75A2.25 2.25 0 0 1 1.5 18V6ZM3 16.06V18c0 .414.336.75.75.75h16.5A.75.75 0 0 0 21 18v-1.94l-2.69-2.689a1.5 1.5 0 0 0-2.12 0l-.88.879.97.97a.75.75 0 1 1-1.06 1.06l-5.16-5.159a1.5 1.5 0 0 0-2.12 0L3 16.061Zm10.125-7.81a1.125 1.125 0 1 0 0 2.25 1.125 1.125 0 0 0 0-2.25Z" /></svg>
                </div>
                <span class="text-[10px] font-bold uppercase tracking-widest">Image</span>
            </Link>
            
            <Link href="/qr-code-generator" class="flex flex-col items-center gap-y-1 transition-all text-gray-500 hover:text-gray-300">
                <div class="h-6 w-6 flex items-center justify-center">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="currentColor"><path d="M3.75 3A.75.75 0 0 0 3 3.75v3a.75.75 0 0 0 .75.75h3A.75.75 0 0 0 7.5 6.75v-3A.75.75 0 0 0 6.75 3h-3ZM4.5 4.5h1.5v1.5H4.5v-1.5ZM3.75 16.5a.75.75 0 0 0-.75.75v3a.75.75 0 0 0 .75.75h3a.75.75 0 0 0 .75-.75v-3a.75.75 0 0 0-.75-.75h-3ZM4.5 18h1.5v1.5H4.5V18ZM16.5 3a.75.75 0 0 0-.75.75v3a.75.75 0 0 0 .75.75h3a.75.75 0 0 0 .75-.75v-3a.75.75 0 0 0-.75-.75h-3ZM18 4.5h1.5v1.5H18v-1.5ZM10.5 3a.75.75 0 0 0-.75.75v3a.75.75 0 0 0 .75.75h3a.75.75 0 0 0 .75-.75v-3a.75.75 0 0 0-.75-.75h-3ZM12 4.5h1.5v1.5H12v-1.5ZM10.5 10.5a.75.75 0 0 0-.75.75v3a.75.75 0 0 0 .75.75h3a.75.75 0 0 0 .75-.75v-3a.75.75 0 0 0-.75-.75h-3ZM12 12h1.5v1.5H12V12ZM16.5 10.5a.75.75 0 0 0-.75.75v3a.75.75 0 0 0 .75.75h3a.75.75 0 0 0 .75-.75v-3a.75.75 0 0 0-.75-.75h-3ZM18 12h1.5v1.5H18V12ZM16.5 16.5a.75.75 0 0 0-.75.75v3a.75.75 0 0 0 .75.75h3a.75.75 0 0 0 .75-.75v-3a.75.75 0 0 0-.75-.75h-3ZM18 18h1.5v1.5H18V18ZM10.5 16.5a.75.75 0 0 0-.75.75v3a.75.75 0 0 0 .75.75h3a.75.75 0 0 0 .75-.75v-3a.75.75 0 0 0-.75-.75h-3ZM12 18h1.5v1.5H12V18ZM3.75 10.5a.75.75 0 0 0-.75.75v3a.75.75 0 0 0 .75.75h3a.75.75 0 0 0 .75-.75v-3a.75.75 0 0 0-.75-.75h-3ZM4.5 12h1.5v1.5H4.5V12Z" /></svg>
                </div>
                <span class="text-[10px] font-bold uppercase tracking-widest">QR Code</span>
            </Link>


            <Link href="/history" class="flex flex-col items-center gap-y-1 transition-all text-emerald-400 scale-110">
                <div class="h-6 w-6 flex items-center justify-center">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25ZM12.75 6a.75.75 0 0 0-1.5 0v6c0 .414.336.75.75.75h4.5a.75.75 0 0 0 0-1.5h-3.75V6Z" /></svg>
                </div>
                <span class="text-[10px] font-bold uppercase tracking-widest">History</span>
            </Link>

            <Link :href="$page.props.auth?.user ? route('profile.edit') : route('login')" class="flex flex-col items-center gap-y-1 transition-all text-gray-500 hover:text-gray-300">
                <div class="h-6 w-6 flex items-center justify-center">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                </div>
                <span class="text-[10px] font-bold uppercase tracking-widest">{{ $page.props.auth?.user ? 'Profile' : 'Login' }}</span>
            </Link>
        </nav>

        <!-- Hero Section (Desktop Only) -->
        <div class="hidden lg:block relative overflow-hidden pt-10 lg:pt-12 pb-6 lg:pb-8 text-center">
            <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[400px] h-[150px] bg-gradient-to-tr from-purple-600/10 via-pink-600/10 to-indigo-600/10 blur-[90px] rounded-full pointer-events-none"></div>
            
            <div class="relative mx-auto max-w-4xl px-6">
                <span class="inline-flex items-center gap-x-2 rounded-full bg-purple-500/10 px-3.5 py-1 text-[10px] font-bold text-purple-300 border border-purple-500/20 mb-4 uppercase tracking-widest">
                    Execution Log
                </span>

                <img src="/assets/images/fluxmedia_main.webp" class="mx-auto h-16 lg:h-20 object-contain mb-6 drop-shadow-[0_0_20px_rgba(168,85,247,0.3)]" alt="FluxMedia Logo" />
                <h1 class="text-3xl lg:text-4xl font-extrabold tracking-tight text-white uppercase tracking-tighter">
                    History Hub
                </h1>
            </div>
        </div>

        <!-- Filter Hub -->
        <div class="mx-auto max-w-6xl px-3 lg:px-6 mt-4">
            <div class="flex overflow-x-auto no-scrollbar items-center gap-x-2 pb-6 border-b border-gray-800/60">
                <button 
                    @click="activeFilter = 'all'"
                    :class="['whitespace-nowrap px-4 py-2 rounded-xl text-[10px] font-bold uppercase tracking-widest transition-all duration-200', activeFilter === 'all' ? 'bg-purple-600 text-white shadow-md' : 'bg-[#121826]/80 text-gray-500 border border-gray-800/80']"
                >
                    All Activities ({{ historyFeed.length }})
                </button>
                <button 
                    @click="activeFilter = 'images'"
                    :class="['whitespace-nowrap px-4 py-2 rounded-xl text-[10px] font-bold uppercase tracking-widest transition-all duration-200', activeFilter === 'images' ? 'bg-purple-600 text-white shadow-md' : 'bg-[#121826]/80 text-gray-500 border border-gray-800/80']"
                >
                    Images ({{ historyFeed.filter(i => i.type === 'image').length }})
                </button>
                <button 
                    @click="activeFilter = 'qrs'"
                    :class="['whitespace-nowrap px-4 py-2 rounded-xl text-[10px] font-bold uppercase tracking-widest transition-all duration-200', activeFilter === 'qrs' ? 'bg-purple-600 text-white shadow-md' : 'bg-[#121826]/80 text-gray-500 border border-gray-800/80']"
                >
                    QR Matrices ({{ historyFeed.filter(i => i.type === 'qr').length }})
                </button>

                <div class="ml-auto shrink-0 group relative">
                    <button class="flex items-center gap-x-1.5 rounded-full bg-amber-500/10 px-3 py-1.5 text-[9px] font-bold text-amber-500 border border-amber-500/20 hover:bg-amber-500/20 transition-colors shadow-lg shadow-amber-900/20">
                        <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        Purge: {{ $page.props.auth?.user ? '24h' : '1h' }}
                    </button>
                    <!-- Tooltip Card - Positioned Below -->
                    <div class="absolute top-full right-0 mt-3 w-56 p-3 bg-[#1A2133] border border-gray-700 rounded-2xl shadow-2xl opacity-0 group-hover:opacity-100 transition-all transform -translate-y-2 group-hover:translate-y-0 pointer-events-none z-[100]">
                        <div class="absolute -top-1 right-6 w-2 h-2 bg-[#1A2133] border-t border-l border-gray-700 rotate-45"></div>
                        <p class="text-[9px] leading-relaxed text-gray-300 font-medium">
                            <span class="text-amber-400 font-bold">Privacy Note:</span> Authenticated sessions retain assets for <span class="text-white font-bold">24 hours</span>, while guest assets are purged after <span class="text-white font-bold">1 hour</span>.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Feed Grid -->
            <div class="mt-8">
                <div v-if="filteredFeed.length === 0" class="py-20 text-center text-[10px] font-bold uppercase tracking-[0.2em] text-gray-600 border border-dashed border-gray-800 rounded-3xl bg-[#121826]/20">
                    No matching streams retained
                </div>

                <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 lg:gap-6">
                    <div 
                        v-for="item in filteredFeed" 
                        :key="item.type + '_' + item.id"
                        class="rounded-3xl border border-gray-800 bg-[#121826]/60 backdrop-blur-xl hover:border-purple-500/30 transition-all p-5 flex flex-col justify-between shadow-xl relative overflow-hidden"
                    >
                        <!-- Header Meta -->
                        <div class="flex items-center justify-between gap-x-2 mb-4">
                            <span class="px-2 py-0.5 rounded-lg text-[9px] font-black uppercase tracking-[0.15em]" :class="item.type === 'image' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-purple-500/10 text-purple-400 border border-purple-500/20'">
                                {{ item.type === 'image' ? 'Optimized Asset' : 'Matrix Profile' }}
                            </span>
                            <span class="text-[10px] text-gray-600 font-mono">{{ item.created_at }}</span>
                        </div>

                        <!-- Content -->
                        <div class="flex-1">
                            <template v-if="item.type === 'image'">
                                <h3 class="text-sm font-bold text-gray-200 truncate mb-3">{{ item.original_name }}</h3>
                                <div class="flex flex-wrap gap-2 text-[10px] font-bold uppercase tracking-widest">
                                    <span class="bg-[#0B0F19] py-1 px-2.5 rounded-lg border border-gray-800 text-gray-500">
                                        <strong class="text-white">{{ item.format }}</strong>
                                    </span>
                                    <span class="bg-[#0B0F19] py-1 px-2.5 rounded-lg border border-gray-800 text-gray-500">
                                        <strong class="text-teal-400">{{ formatBytes(item.size_bytes) }}</strong>
                                    </span>
                                </div>
                            </template>

                            <template v-else>
                                <div class="flex items-start justify-between gap-x-4">
                                    <div class="flex-1 min-w-0">
                                        <span class="text-[10px] font-bold text-gray-300 block mb-1 uppercase tracking-wider">{{ getProfileBadge(item.profile_type) }}</span>
                                        <span class="text-[11px] text-gray-500 font-mono truncate block" :title="item.summary_payload">
                                            {{ item.summary_payload }}
                                        </span>
                                    </div>
                                    <div class="p-1.5 rounded-xl bg-white shrink-0 border border-gray-200">
                                        <QrcodeVue :value="item.summary_payload" :size="48" render-as="svg" :foreground="item.foreground_color" :background="item.background_color" :level="item.redundancy_level" class="block" />
                                    </div>
                                </div>
                                <div class="mt-4 flex flex-wrap gap-2 text-[9px] font-bold uppercase tracking-widest">
                                    <div class="flex items-center gap-x-1 bg-[#0B0F19] py-1 px-2.5 rounded-lg border border-gray-800">
                                        <span class="w-2 h-2 rounded-full border border-gray-700 block" :style="{ backgroundColor: item.foreground_color }"></span>
                                        <span class="text-gray-500">{{ item.foreground_color }}</span>
                                    </div>
                                    <span class="bg-[#0B0F19] py-1 px-2.5 rounded-lg border border-gray-800 text-gray-500">
                                        LVL: <strong class="text-purple-400">{{ item.redundancy_level }}</strong>
                                    </span>
                                </div>
                            </template>
                        </div>

                        <!-- Actions -->
                        <div class="mt-5 pt-4 border-t border-gray-800/80 flex justify-between items-center">
                            <template v-if="item.type === 'image'">
                                <span class="text-[9px] font-bold text-amber-500/80 uppercase tracking-widest">
                                    ⏳ Expires soon
                                </span>
                                <a 
                                    :href="item.download_url"
                                    class="py-1.5 px-4 rounded-xl bg-emerald-600/10 hover:bg-emerald-600 text-emerald-400 hover:text-white font-bold text-[10px] uppercase tracking-widest transition-all border border-emerald-500/20 active:scale-95"
                                >
                                    Fetch
                                </a>
                            </template>
                            <template v-else>
                                <span class="text-[9px] font-bold text-purple-400/80 uppercase tracking-widest">
                                    ✨ Vector Node
                                </span>
                                <Link 
                                    href="/qr-code-generator" 
                                    class="py-1.5 px-4 rounded-xl bg-purple-600/10 hover:bg-purple-600 text-purple-400 hover:text-white font-bold text-[10px] uppercase tracking-widest transition-all border border-purple-500/20 active:scale-95"
                                >
                                    Clone
                                </Link>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <footer class="mt-20 border-t border-gray-800/80 pt-8 pb-12 text-center">
            <p class="text-[10px] text-gray-600 font-bold uppercase tracking-[0.2em]">FluxMedia Studio · History v1.0.4</p>
        </footer>
    </div>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');

.font-jakarta {
    font-family: 'Plus Jakarta Sans', sans-serif;
}

.no-scrollbar::-webkit-scrollbar {
    display: none;
}
.no-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
</style>
