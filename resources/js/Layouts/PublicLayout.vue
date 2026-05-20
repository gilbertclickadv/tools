<script setup>
import { ref } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import VerificationAlert from '@/Components/VerificationAlert.vue';
import PWAInstallPrompt from '@/Components/PWAInstallPrompt.vue';

const props = defineProps({
    title: String,
});

const page = usePage();
</script>

<template>
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
                <nav class="hidden lg:flex items-center absolute left-1/2 -translate-x-1/2 gap-x-1 text-sm font-medium">
                    <Link
                        :href="route('home')"
                        :class="[route().current('home') ? 'bg-purple-500/10 text-purple-400 border border-purple-500/20' : 'text-gray-400 hover:text-white hover:bg-gray-800/50']"
                        class="px-4 py-2 rounded-xl transition-all duration-200 font-semibold flex items-center gap-x-1.5"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" /></svg>
                        All Tools
                    </Link>
                    <Link
                        :href="route('tools.image')"
                        :class="[route().current('tools.image') ? 'bg-purple-500/10 text-purple-400 border border-purple-500/20' : 'text-gray-400 hover:text-white hover:bg-gray-800/50']"
                        class="px-4 py-2 rounded-xl transition-all duration-200 font-semibold"
                    >
                        Image Studio
                    </Link>
                    <Link
                        :href="route('tools.qr')"
                        :class="[route().current('tools.qr') ? 'bg-purple-500/10 text-purple-400 border border-purple-500/20' : 'text-gray-400 hover:text-white hover:bg-gray-800/50']"
                        class="px-4 py-2 rounded-xl transition-all duration-200 font-semibold"
                    >
                        QR Generator
                    </Link>
                    <Link
                        :href="route('history')"
                        :class="[route().current('history') ? 'bg-purple-500/10 text-purple-400 border border-purple-500/20' : 'text-gray-400 hover:text-white hover:bg-gray-800/50']"
                        class="px-4 py-2 rounded-xl transition-all duration-200 font-semibold"
                    >
                        History
                    </Link>
                </nav>

                <!-- Desktop Auth (Right) -->
                <div class="hidden lg:flex items-center gap-x-4">
                    <a href="mailto:gilbert@fluxmetrics.site" class="h-10 w-10 flex items-center justify-center rounded-xl border border-gray-800 bg-[#121826]/50 hover:bg-gray-800/80 text-gray-400 hover:text-white transition-all shadow-lg" title="Contact Support">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" class="h-5 w-5" fill="currentColor"><path d="M320 128C241 128 175.3 185.3 162.3 260.7C171.6 257.7 181.6 256 192 256L208 256C234.5 256 256 277.5 256 304L256 400C256 426.5 234.5 448 208 448L192 448C139 448 96 405 96 352L96 288C96 164.3 196.3 64 320 64C443.7 64 544 164.3 544 288L544 456.1C544 522.4 490.2 576.1 423.9 576.1L336 576L304 576C277.5 576 256 554.5 256 528C256 501.5 277.5 480 304 480L336 480C362.5 480 384 501.5 384 528L384 528L424 528C463.8 528 496 495.8 496 456L496 435.1C481.9 443.3 465.5 447.9 448 447.9L432 447.9C405.5 447.9 384 426.4 384 399.9L384 303.9C384 277.4 405.5 255.9 432 255.9L448 255.9C458.4 255.9 468.3 257.5 477.7 260.6C464.7 185.3 399.1 127.9 320 127.9z"/></svg>
                    </a>
                    
                    <template v-if="page.props.auth?.user">
                        <Dropdown align="right" width="56">
                            <template #trigger>
                                <button class="flex items-center gap-x-2.5 px-3 py-1.5 rounded-xl border border-gray-800 bg-[#121826]/50 hover:bg-gray-800/80 transition-all group">
                                    <div class="h-7 w-7 rounded-lg bg-gradient-to-tr from-purple-600 to-indigo-600 flex items-center justify-center font-bold text-[11px] text-white">
                                        {{ page.props.auth.user.name[0] }}
                                    </div>
                                    <span class="text-sm font-semibold text-gray-300 group-hover:text-white transition-colors">
                                        {{ page.props.auth.user.name.split(' ')[0] }}
                                    </span>
                                    <svg class="h-4 w-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>
                            </template>
                            <template #content>
                                <div class="px-4 py-2 border-b border-gray-800/80 mb-1">
                                    <p class="text-xs font-bold text-white truncate">{{ page.props.auth.user.name }}</p>
                                    <p class="text-[10px] text-gray-500 truncate">{{ page.props.auth.user.email }}</p>
                                </div>
                                <DropdownLink :href="route('profile.edit')" class="flex items-center gap-x-2">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                                    Profile Settings
                                </DropdownLink>
                                <DropdownLink v-if="page.props.auth.user?.is_admin" :href="route('admin.dashboard')" class="flex items-center gap-x-2 text-amber-400">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                                    Command Center
                                </DropdownLink>
                                <div class="border-t border-gray-800/80 my-1"></div>
                                <DropdownLink :href="route('logout')" method="post" as="button" class="flex items-center gap-x-2 text-red-400">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" /></svg>
                                    Terminate Session
                                </DropdownLink>
                            </template>
                        </Dropdown>
                    </template>
                    <template v-else>
                        <Link :href="route('login')" class="text-sm font-medium text-gray-400 hover:text-white transition-colors">Log in</Link>
                        <Link :href="route('register')" class="rounded-lg px-4 py-2 bg-purple-600 hover:bg-purple-500 text-white font-semibold text-sm shadow-md shadow-purple-600/20">Register Now</Link>
                    </template>
                </div>

                <!-- Mobile Actions -->
                <div class="lg:hidden flex items-center gap-x-3">
                    <a href="mailto:gilbert@fluxmetrics.site" class="h-9 w-9 flex items-center justify-center rounded-xl border border-gray-800 bg-[#121826]/50 text-gray-400 active:scale-95 transition-all">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" class="h-4 w-4" fill="currentColor"><path d="M320 128C241 128 175.3 185.3 162.3 260.7C171.6 257.7 181.6 256 192 256L208 256C234.5 256 256 277.5 256 304L256 400C256 426.5 234.5 448 208 448L192 448C139 448 96 405 96 352L96 288C96 164.3 196.3 64 320 64C443.7 64 544 164.3 544 288L544 456.1C544 522.4 490.2 576.1 423.9 576.1L336 576L304 576C277.5 576 256 554.5 256 528C256 501.5 277.5 480 304 480L336 480C362.5 480 384 501.5 384 528L384 528L424 528C463.8 528 496 495.8 496 456L496 435.1C481.9 443.3 465.5 447.9 448 447.9L432 447.9C405.5 447.9 384 426.4 384 399.9L384 303.9C384 277.4 405.5 255.9 432 255.9L448 255.9C458.4 255.9 468.3 257.5 477.7 260.6C464.7 185.3 399.1 127.9 320 127.9z"/></svg>
                    </a>
                    <template v-if="page.props.auth?.user">
                        <Link :href="route('profile.edit')" class="h-9 w-9 rounded-xl bg-gradient-to-tr from-purple-600 to-indigo-600 flex items-center justify-center font-bold text-white text-xs border border-purple-400/30 shadow-lg shadow-purple-600/20">
                            {{ page.props.auth.user.name[0] }}
                        </Link>
                    </template>
                    <template v-else>
                        <Link :href="route('login')" class="text-xs font-bold text-purple-400 uppercase tracking-widest bg-purple-500/10 px-3 py-1.5 rounded-lg border border-purple-500/20">Login</Link>
                    </template>
                </div>
            </div>
        </header>

        <!-- Bottom Navigation for Mobile -->
        <nav class="lg:hidden fixed bottom-0 left-0 right-0 bg-[#0B0F19]/90 backdrop-blur-xl border-t border-gray-800/60 z-[100] px-2 py-3 flex items-center justify-around pb-[calc(12px+env(safe-area-inset-bottom))] shadow-[0_-10px_40px_rgba(0,0,0,0.4)]">
            <!-- Tools Hub -->
            <Link :href="route('home')" class="flex flex-col items-center gap-y-1 transition-all px-3" :class="[route().current('home') ? 'text-purple-400 scale-110' : 'text-gray-500 hover:text-gray-300']">
                <div class="h-6 w-6 flex items-center justify-center">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" /></svg>
                </div>
                <span class="text-[10px] font-bold uppercase tracking-widest">Tools</span>
            </Link>

            <!-- Image Studio -->
            <Link :href="route('tools.image')" class="flex flex-col items-center gap-y-1 transition-all px-3" :class="[route().current('tools.image') ? 'text-purple-400 scale-110' : 'text-gray-500 hover:text-gray-300']">
                <div class="h-6 w-6 flex items-center justify-center">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="currentColor"><path d="M1.5 6a2.25 2.25 0 0 1 2.25-2.25h16.5A2.25 2.25 0 0 1 22.5 6v12a2.25 2.25 0 0 1-2.25 2.25H3.75A2.25 2.25 0 0 1 1.5 18V6ZM3 16.06V18c0 .414.336.75.75.75h16.5A.75.75 0 0 0 21 18v-1.94l-2.69-2.689a1.5 1.5 0 0 0-2.12 0l-.88.879.97.97a.75.75 0 1 1-1.06 1.06l-5.16-5.159a1.5 1.5 0 0 0-2.12 0L3 16.061Zm10.125-7.81a1.125 1.125 0 1 0 0 2.25 1.125 1.125 0 0 0 0-2.25Z" /></svg>
                </div>
                <span class="text-[10px] font-bold uppercase tracking-widest">Image</span>
            </Link>

            <!-- History -->
            <Link :href="route('history')" class="flex flex-col items-center gap-y-1 transition-all px-3" :class="[route().current('history') ? 'text-purple-400 scale-110' : 'text-gray-500 hover:text-gray-300']">
                <div class="h-6 w-6 flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" class="h-6 w-6" fill="currentColor"><path d="M320 128C426 128 512 214 512 320C512 426 426 512 320 512C254.8 512 197.1 479.5 162.4 429.7C152.3 415.2 132.3 411.7 117.8 421.8C103.3 431.9 99.8 451.9 109.9 466.4C156.1 532.6 233 576 320 576C461.4 576 576 461.4 576 320C576 178.6 461.4 64 320 64C234.3 64 158.5 106.1 112 170.7L112 144C112 126.3 97.7 112 80 112C62.3 112 48 126.3 48 144L48 256C48 273.7 62.3 288 80 288L104.6 288C105.1 288 105.6 288 106.1 288L192.1 288C209.8 288 224.1 273.7 224.1 256C224.1 238.3 209.8 224 192.1 224L153.8 224C186.9 166.6 249 128 320 128zM344 216C344 202.7 333.3 192 320 192C306.7 192 296 202.7 296 216L296 320C296 326.4 298.5 332.5 303 337L375 409C384.4 418.4 399.6 418.4 408.9 409C418.2 399.6 418.3 384.4 408.9 375.1L343.9 310.1L343.9 216z"/></svg>
                </div>
                <span class="text-[10px] font-bold uppercase tracking-widest">History</span>
            </Link>

            <!-- Profile / Login -->
            <Link :href="page.props.auth?.user ? route('profile.edit') : route('login')" class="flex flex-col items-center gap-y-1 transition-all px-3" :class="[route().current('profile.edit') ? 'text-purple-400 scale-110' : 'text-gray-500 hover:text-gray-300']">
                <div class="h-6 w-6 flex items-center justify-center">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                </div>
                <span class="text-[10px] font-bold uppercase tracking-widest">{{ page.props.auth?.user ? 'Profile' : 'Login' }}</span>
            </Link>
        </nav>

        <!-- Main Content -->
        <main>
            <slot />
        </main>

        <!-- ─────────────── FOOTER ─────────────── -->
        <footer class="border-t border-white/[0.04] bg-[#0A0E17]/40 backdrop-blur-md pb-16 pt-8 text-center space-y-4 relative z-10" role="contentinfo">
            <div class="flex flex-wrap items-center justify-center gap-x-6 gap-y-2 text-xs font-semibold text-gray-500">
                <Link :href="route('privacy')" class="hover:text-purple-400 transition-colors">
                    Privacy Policy
                </Link>
                <span class="text-gray-800 select-none hidden sm:inline">&middot;</span>
                <Link :href="route('terms')" class="hover:text-purple-400 transition-colors">
                    Terms of Service
                </Link>
                <span class="text-gray-800 select-none hidden sm:inline">&middot;</span>
                <a href="mailto:gilbert@fluxmetrics.site" class="hover:text-purple-400 transition-colors">
                    Contact Support
                </a>
            </div>
            <p class="text-[10px] font-bold uppercase tracking-[0.25em] text-gray-700">
                FluxMedia Studio &middot; Hub v1.0.0
            </p>
        </footer>
    </div>
</template>

<style>
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
