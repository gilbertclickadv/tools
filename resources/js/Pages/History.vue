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
                    <img src="/assets/images/pwa-192.png" class="h-8 w-8 lg:h-10 lg:w-10 object-contain group-hover:scale-105 transition-transform" alt="FluxMedia Icon" />
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

                <!-- Mobile Profile Shortcut -->
                <div class="lg:hidden flex items-center gap-x-3">
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
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                </div>
                <span class="text-[10px] font-bold uppercase tracking-widest">Studio</span>
            </Link>
            
            <Link href="/qr-code-generator" class="flex flex-col items-center gap-y-1 transition-all text-gray-500 hover:text-gray-300">
                <div class="h-6 w-6 flex items-center justify-center">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 17h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" /></svg>
                </div>
                <span class="text-[10px] font-bold uppercase tracking-widest">QR Intel</span>
            </Link>

            <Link href="/" class="relative -top-6 h-14 w-14 rounded-full bg-gradient-to-tr from-purple-600 to-indigo-600 flex items-center justify-center shadow-xl shadow-purple-900/40 border-4 border-[#0B0F19] transition-transform active:scale-95">
                <svg class="h-7 w-7 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" /></svg>
            </Link>

            <Link href="/history" class="flex flex-col items-center gap-y-1 transition-all text-emerald-400 scale-110">
                <div class="h-6 w-6 flex items-center justify-center">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
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

        <!-- Hero Section -->
        <div class="relative overflow-hidden pt-10 lg:pt-12 pb-6 lg:pb-8 text-center">
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
