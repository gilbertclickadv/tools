<script setup>
import { Head, Link } from '@inertiajs/vue3';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
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
        case 'url': return '🔗 Link Endpoint';
        case 'text': return '📝 Plain Text';
        case 'email': return '✉️ Email Flow';
        case 'phone': return '📞 Direct Dial';
        case 'wifi': return '📶 WiFi Config';
        case 'sms': return '💬 SMS Template';
        default: return '📐 Matrix Profile';
    }
};
</script>

<template>
    <Head>
        <title>FluxMedia Studio · Comprehensive Execution History</title>
        <meta name="description" content="Centralized cloud portal aggregating active user session caches. Download optimized Image streams and inspect live customizable vector QR codes securely." />
    </Head>

    <div class="min-h-screen bg-[#0B0F19] text-gray-100 font-jakarta selection:bg-purple-500 selection:text-white pb-20 overflow-x-hidden">
        <header class="border-b border-gray-800/60 bg-[#0B0F19]/80 backdrop-blur-md sticky top-0 z-[100]">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 flex h-20 items-center justify-between">
                <!-- Brand Logo (Left) -->
                <Link href="/" class="flex items-center gap-x-3 shrink-0 group">
                    <img src="/assets/images/icon_only.webp" class="h-10 w-10 object-contain group-hover:scale-105 transition-transform" alt="FluxMedia Icon" />
                    <span class="text-xl font-bold tracking-tight bg-gradient-to-r from-white via-gray-200 to-purple-300 bg-clip-text text-transparent select-none">
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

                <!-- Mobile Hamburger Button -->
                <div class="lg:hidden flex items-center">
                    <button @click="mobileMenuOpen = !mobileMenuOpen" class="p-2 text-gray-400 hover:text-white focus:outline-none">
                        <svg v-if="!mobileMenuOpen" class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                        <svg v-else class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Mobile Menu Dropdown -->
            <div v-if="mobileMenuOpen" class="lg:hidden bg-[#121826]/95 backdrop-blur-xl border-t border-gray-800 p-4 space-y-4 animate-fade-in">
                <div class="flex flex-col gap-y-2">
                    <Link href="/" class="px-4 py-3 rounded-xl text-gray-400 font-semibold hover:bg-gray-800">Image Studio</Link>
                    <Link href="/qr-code-generator" class="px-4 py-3 rounded-xl text-gray-400 font-semibold hover:bg-gray-800">QR Generator</Link>
                    <Link href="/history" class="px-4 py-3 rounded-xl bg-purple-500/10 text-purple-400 font-bold border border-purple-500/20">History Hub</Link>
                </div>
                <div class="pt-4 border-t border-gray-800 flex flex-col gap-y-3">
                    <template v-if="$page.props.auth?.user">
                        <div class="flex items-center gap-x-3 px-4 py-2">
                            <div class="h-9 w-9 rounded-xl bg-gradient-to-tr from-purple-600 to-indigo-600 flex items-center justify-center font-bold text-white">
                                {{ $page.props.auth.user.name[0] }}
                            </div>
                            <div>
                                <p class="text-sm font-bold text-white">{{ $page.props.auth.user.name }}</p>
                                <p class="text-xs text-gray-500">{{ $page.props.auth.user.email }}</p>
                            </div>
                        </div>
                        <Link :href="route('profile.edit')" class="px-4 py-3 rounded-xl text-gray-400 font-semibold hover:bg-gray-800">Profile Settings</Link>
                        <Link v-if="$page.props.auth?.user?.is_admin" :href="route('admin.dashboard')" class="px-4 py-3 rounded-xl text-purple-400 font-bold hover:bg-purple-500/10">Admin Dashboard</Link>
                        <Link :href="route('logout')" method="post" as="button" class="w-full text-left px-4 py-3 rounded-xl text-red-400 font-semibold hover:bg-red-500/10">Logout</Link>
                    </template>
                    <template v-else>
                        <Link :href="route('login')" class="px-4 py-2 text-gray-400 font-medium">Log in</Link>
                        <Link v-if="canRegister" :href="route('register')" class="mx-4 py-2 text-center rounded-lg bg-purple-600 text-white font-bold">Register Now</Link>
                    </template>
                </div>
            </div>
        </header>

        <!-- Hero Title Banner -->
        <div class="relative overflow-hidden pt-12 pb-6 text-center">
            <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[400px] h-[150px] bg-gradient-to-tr from-purple-600/10 via-pink-600/10 to-indigo-600/10 blur-[90px] rounded-full pointer-events-none"></div>
            
            <div class="relative mx-auto max-w-4xl px-6">
                <span class="inline-flex items-center gap-x-2 rounded-full bg-purple-500/10 px-3.5 py-1 text-xs font-semibold text-purple-300 border border-purple-500/20 mb-4">
                    Multi-Tool Lifecycle Log Feed
                </span>

                <img src="/assets/images/fluxmedia_main.webp" class="mx-auto h-16 object-contain mb-6 drop-shadow-[0_0_20px_rgba(168,85,247,0.3)]" alt="FluxMedia Logo" />
                <h1 class="text-3xl sm:text-4xl font-extrabold tracking-tight text-white">
                    Cloud Execution History
                </h1>
                <p class="mt-2 text-sm text-gray-400 max-w-xl mx-auto">
                    Review transient asset pipelines and pre-encoded barcode parameters captured securely during active interface workflows.
                </p>
            </div>
        </div>

        <!-- Filter Controls Hub -->
        <div class="mx-auto max-w-6xl px-4 sm:px-6 mt-4">
            <div class="flex items-center justify-center gap-x-3 pb-6 border-b border-gray-800/60">
                <button 
                    @click="activeFilter = 'all'"
                    :class="['px-4 py-2 rounded-xl text-xs font-semibold transition-all duration-200 cursor-pointer', activeFilter === 'all' ? 'bg-purple-600 text-white shadow-md shadow-purple-600/20' : 'bg-[#121826]/80 text-gray-400 hover:text-white border border-gray-800/80']"
                >
                    🌐 All Activities ({{ historyFeed.length }})
                </button>
                <button 
                    @click="activeFilter = 'images'"
                    :class="['px-4 py-2 rounded-xl text-xs font-semibold transition-all duration-200 cursor-pointer', activeFilter === 'images' ? 'bg-purple-600 text-white shadow-md shadow-purple-600/20' : 'bg-[#121826]/80 text-gray-400 hover:text-white border border-gray-800/80']"
                >
                    🖼️ Image Studio Pipelines ({{ historyFeed.filter(i => i.type === 'image').length }})
                </button>
                <button 
                    @click="activeFilter = 'qrs'"
                    :class="['px-4 py-2 rounded-xl text-xs font-semibold transition-all duration-200 cursor-pointer', activeFilter === 'qrs' ? 'bg-purple-600 text-white shadow-md shadow-purple-600/20' : 'bg-[#121826]/80 text-gray-400 hover:text-white border border-gray-800/80']"
                >
                    🔳 QR Matrices ({{ historyFeed.filter(i => i.type === 'qr').length }})
                </button>
            </div>

            <!-- Feed Grid Viewport -->
            <div class="mt-8">
                <div v-if="filteredFeed.length === 0" class="py-16 text-center text-sm text-gray-600 border border-dashed border-gray-800 rounded-2xl bg-[#121826]/20">
                    No matching studio execution streams retained for this filter perspective.
                </div>

                <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div 
                        v-for="item in filteredFeed" 
                        :key="item.type + '_' + item.id"
                        class="rounded-2xl border border-gray-800/80 bg-[#121826]/60 backdrop-blur-xl hover:border-purple-500/30 transition-all p-5 flex flex-col justify-between shadow-xl relative overflow-hidden group"
                    >
                        <!-- Top Indicator bar -->
                        <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r" :class="item.type === 'image' ? 'from-emerald-500 to-teal-500' : 'from-purple-500 to-pink-500'"></div>

                        <div>
                            <!-- Header Meta -->
                            <div class="flex items-center justify-between gap-x-2 mb-3 mt-1">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase font-mono tracking-wider" :class="item.type === 'image' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-purple-500/10 text-purple-400 border border-purple-500/20'">
                                    {{ item.type === 'image' ? 'Optimized Asset' : 'Matrix Layout' }}
                                </span>
                                <span class="text-[11px] text-gray-500 font-mono">{{ item.created_at }}</span>
                            </div>

                            <!-- Image Studio Item Layout -->
                            <template v-if="item.type === 'image'">
                                <h3 class="text-sm font-semibold text-gray-100 truncate block mb-1" :title="item.original_name">
                                    {{ item.original_name }}
                                </h3>
                                <div class="flex items-center gap-x-2 text-xs text-gray-400 mt-2 font-mono bg-[#0B0F19] py-1 px-2.5 rounded-lg border border-gray-800">
                                    <span>Format: <strong class="text-white">{{ item.format }}</strong></span>
                                    <span>•</span>
                                    <span>Buffer: <strong class="text-teal-400">{{ formatBytes(item.size_bytes) }}</strong></span>
                                </div>
                            </template>

                            <!-- QR Studio Item Layout -->
                            <template v-else>
                                <div class="flex items-start justify-between gap-x-3 mb-2">
                                    <div>
                                        <span class="text-xs font-bold text-gray-200 block">{{ getProfileBadge(item.profile_type) }}</span>
                                        <span class="text-[11px] text-gray-500 block truncate max-w-[160px]" :title="item.summary_payload">
                                            {{ item.summary_payload }}
                                        </span>
                                    </div>
                                    <!-- Embedded Scalable Vector Display Mini Frame -->
                                    <div class="p-1.5 rounded-lg bg-white shrink-0 shadow-sm border border-gray-200">
                                        <QrcodeVue
                                            :value="item.summary_payload"
                                            :size="54"
                                            render-as="svg"
                                            :foreground="item.foreground_color"
                                            :background="item.background_color"
                                            :level="item.redundancy_level"
                                            class="block"
                                        />
                                    </div>
                                </div>

                                <div class="flex items-center gap-x-2 text-[11px] text-gray-400 mt-2 bg-[#0B0F19] py-1 px-2.5 rounded-lg border border-gray-800">
                                    <div class="flex items-center gap-x-1">
                                        <span class="w-2.5 h-2.5 rounded-full border border-gray-700 block" :style="{ backgroundColor: item.foreground_color }"></span>
                                        <span class="font-mono text-[10px]">{{ item.foreground_color }}</span>
                                    </div>
                                    <span>•</span>
                                    <span>Level: <strong>{{ item.redundancy_level }}</strong></span>
                                    <span>•</span>
                                    <span>Size: <strong>{{ item.matrix_size }}px</strong></span>
                                </div>
                            </template>
                        </div>

                        <!-- Action Footer / Downloads -->
                        <div class="mt-4 pt-3 border-t border-gray-800/80 flex justify-between items-center">
                            <template v-if="item.type === 'image'">
                                <span class="text-[11px] text-amber-500/90 font-medium truncate max-w-[140px]" :title="'Expires ' + item.expires_at">
                                    ⏳ {{ item.expires_at || 'Transient cycle' }}
                                </span>
                                <a 
                                    :href="item.download_url"
                                    class="py-1 px-2.5 rounded-lg bg-emerald-600/10 hover:bg-emerald-600/20 text-emerald-400 font-semibold text-xs transition-all border border-emerald-500/20 flex items-center gap-x-1 cursor-pointer"
                                    title="Download Manipulated Asset"
                                >
                                    <span>Fetch</span>
                                    <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                    </svg>
                                </a>
                            </template>
                            <template v-else>
                                <span class="text-[11px] text-purple-300/80 font-medium">
                                    ✨ Live Generated Node
                                </span>
                                <Link 
                                    href="/qr-code-generator" 
                                    class="py-1 px-2.5 rounded-lg bg-purple-600/10 hover:bg-purple-600/20 text-purple-400 font-semibold text-xs transition-all border border-purple-500/20 flex items-center gap-x-1"
                                >
                                    <span>Clone Profile</span>
                                    <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                    </svg>
                                </Link>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- Footer Footer -->
        <footer class="mt-20 border-t border-gray-800/80 pt-8 text-center text-xs text-gray-600">
            <p>FluxMedia Premium Core Studio · Comprehensive Persistent Framework</p>
        </footer>
    </div>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');

.font-jakarta {
    font-family: 'Plus Jakarta Sans', sans-serif;
}
</style>
