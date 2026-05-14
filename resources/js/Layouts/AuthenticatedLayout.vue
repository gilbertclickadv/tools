<script setup>
import { ref } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import VerificationAlert from '@/Components/VerificationAlert.vue';
import PWAInstallPrompt from '@/Components/PWAInstallPrompt.vue';
import { Link } from '@inertiajs/vue3';

const showingNavigationDropdown = ref(false);
</script>

<template>
    <div class="min-h-screen bg-[#0B0F19] text-gray-100 selection:bg-purple-500 selection:text-white pb-24 lg:pb-0">
        <VerificationAlert />
        <PWAInstallPrompt />

        <!-- Main Header -->
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
                    <Link 
                        :href="route('home')" 
                        :class="[route().current('home') ? 'bg-purple-500/10 text-purple-400 border border-purple-500/20' : 'text-gray-400 hover:text-white hover:bg-gray-800/50']"
                        class="px-4 py-2 rounded-xl transition-all duration-200 font-semibold"
                    >
                        Image Studio
                    </Link>
                    <Link 
                        :href="route('qr.generator')" 
                        :class="[route().current('qr.generator') ? 'bg-purple-500/10 text-purple-400 border border-purple-500/20' : 'text-gray-400 hover:text-white hover:bg-gray-800/50']"
                        class="px-4 py-2 rounded-xl transition-all duration-200 font-semibold"
                    >
                        QR Generator
                    </Link>
                    <Link 
                        :href="route('history')" 
                        :class="[route().current('history') ? 'bg-purple-500/10 text-purple-400 border border-purple-500/20' : 'text-gray-400 hover:text-white hover:bg-gray-800/50']"
                        class="px-4 py-2 rounded-xl transition-all duration-200 font-semibold"
                    >
                        History Hub
                    </Link>
                </nav>

                <!-- Desktop Auth (Right) -->
                <div class="hidden lg:flex items-center gap-x-4">
                    <a href="mailto:gilbert@fluxmetrics.site" class="h-10 w-10 flex items-center justify-center rounded-xl border border-gray-800 bg-[#121826]/50 hover:bg-gray-800/80 text-gray-400 hover:text-white transition-all shadow-lg" title="Contact Support">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 18v-6a9 9 0 0118 0v6M3 18a2 2 0 002 2h2a2 2 0 002-2v-5a2 2 0 00-2-2H3m18 9a2 2 0 002-2v-5a2 2 0 00-2-2h-2a2 2 0 00-2 2v5a2 2 0 002 2h2" /></svg>
                    </a>
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
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                                Profile Settings
                            </DropdownLink>
                            <DropdownLink v-if="$page.props.auth.user?.is_admin" :href="route('admin.dashboard')" class="flex items-center gap-x-2 text-amber-400">
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
                </div>

                <!-- Mobile Actions -->
                <div class="lg:hidden flex items-center gap-x-3">
                    <a href="mailto:gilbert@fluxmetrics.site" class="h-9 w-9 flex items-center justify-center rounded-xl border border-gray-800 bg-[#121826]/50 text-gray-400 active:scale-95 transition-all">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 18v-6a9 9 0 0118 0v6M3 18a2 2 0 002 2h2a2 2 0 002-2v-5a2 2 0 00-2-2H3m18 9a2 2 0 002-2v-5a2 2 0 00-2-2h-2a2 2 0 00-2 2v5a2 2 0 002 2h2" /></svg>
                    </a>
                    <Link :href="route('profile.edit')" class="h-9 w-9 rounded-xl bg-gradient-to-tr from-purple-600 to-indigo-600 flex items-center justify-center font-bold text-white text-xs border border-purple-400/30 shadow-lg shadow-purple-600/20">
                        {{ $page.props.auth.user.name[0] }}
                    </Link>
                </div>
                <!-- Mobile Profile Shortcut -->
            </div>
        </header>

        <!-- Bottom Navigation for Mobile -->
        <nav class="lg:hidden fixed bottom-0 left-0 right-0 bg-[#0B0F19]/90 backdrop-blur-xl border-t border-gray-800/60 z-[100] px-6 py-3 flex items-center justify-between pb-[calc(12px+env(safe-area-inset-bottom))] shadow-[0_-10px_40px_rgba(0,0,0,0.4)]">
            <Link :href="route('home')" class="flex flex-col items-center gap-y-1 transition-all" :class="[route().current('home') ? 'text-purple-400 scale-110' : 'text-gray-500 hover:text-gray-300']">
                <div class="h-6 w-6 flex items-center justify-center">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="currentColor"><path d="M1.5 6a2.25 2.25 0 0 1 2.25-2.25h16.5A2.25 2.25 0 0 1 22.5 6v12a2.25 2.25 0 0 1-2.25 2.25H3.75A2.25 2.25 0 0 1 1.5 18V6ZM3 16.06V18c0 .414.336.75.75.75h16.5A.75.75 0 0 0 21 18v-1.94l-2.69-2.689a1.5 1.5 0 0 0-2.12 0l-.88.879.97.97a.75.75 0 1 1-1.06 1.06l-5.16-5.159a1.5 1.5 0 0 0-2.12 0L3 16.061Zm10.125-7.81a1.125 1.125 0 1 0 0 2.25 1.125 1.125 0 0 0 0-2.25Z" /></svg>
                </div>
                <span class="text-[10px] font-bold uppercase tracking-widest">Image</span>
            </Link>
            
            <Link :href="route('qr.generator')" class="flex flex-col items-center gap-y-1 transition-all" :class="[route().current('qr.generator') ? 'text-indigo-400 scale-110' : 'text-gray-500 hover:text-gray-300']">
                <div class="h-6 w-6 flex items-center justify-center">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="currentColor"><path d="M3.75 3A.75.75 0 0 0 3 3.75v3a.75.75 0 0 0 .75.75h3A.75.75 0 0 0 7.5 6.75v-3A.75.75 0 0 0 6.75 3h-3ZM4.5 4.5h1.5v1.5H4.5v-1.5ZM3.75 16.5a.75.75 0 0 0-.75.75v3a.75.75 0 0 0 .75.75h3a.75.75 0 0 0 .75-.75v-3a.75.75 0 0 0-.75-.75h-3ZM4.5 18h1.5v1.5H4.5V18ZM16.5 3a.75.75 0 0 0-.75.75v3a.75.75 0 0 0 .75.75h3a.75.75 0 0 0 .75-.75v-3a.75.75 0 0 0-.75-.75h-3ZM18 4.5h1.5v1.5H18v-1.5ZM10.5 3a.75.75 0 0 0-.75.75v3a.75.75 0 0 0 .75.75h3a.75.75 0 0 0 .75-.75v-3a.75.75 0 0 0-.75-.75h-3ZM12 4.5h1.5v1.5H12v-1.5ZM10.5 10.5a.75.75 0 0 0-.75.75v3a.75.75 0 0 0 .75.75h3a.75.75 0 0 0 .75-.75v-3a.75.75 0 0 0-.75-.75h-3ZM12 12h1.5v1.5H12V12ZM16.5 10.5a.75.75 0 0 0-.75.75v3a.75.75 0 0 0 .75.75h3a.75.75 0 0 0 .75-.75v-3a.75.75 0 0 0-.75-.75h-3ZM18 12h1.5v1.5H18V12ZM16.5 16.5a.75.75 0 0 0-.75.75v3a.75.75 0 0 0 .75.75h3a.75.75 0 0 0 .75-.75v-3a.75.75 0 0 0-.75-.75h-3ZM18 18h1.5v1.5H18V18ZM10.5 16.5a.75.75 0 0 0-.75.75v3a.75.75 0 0 0 .75.75h3a.75.75 0 0 0 .75-.75v-3a.75.75 0 0 0-.75-.75h-3ZM12 18h1.5v1.5H12V18ZM3.75 10.5a.75.75 0 0 0-.75.75v3a.75.75 0 0 0 .75.75h3a.75.75 0 0 0 .75-.75v-3a.75.75 0 0 0-.75-.75h-3ZM4.5 12h1.5v1.5H4.5V12Z" /></svg>
                </div>
                <span class="text-[10px] font-bold uppercase tracking-widest">QR Code</span>
            </Link>


            <Link :href="route('history')" class="flex flex-col items-center gap-y-1 transition-all" :class="[route().current('history') ? 'text-emerald-400 scale-110' : 'text-gray-500 hover:text-gray-300']">
                <div class="h-6 w-6 flex items-center justify-center">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25ZM12.75 6a.75.75 0 0 0-1.5 0v6c0 .414.336.75.75.75h4.5a.75.75 0 0 0 0-1.5h-3.75V6Z" /></svg>
                </div>
                <span class="text-[10px] font-bold uppercase tracking-widest">History</span>
            </Link>

            <Link :href="route('profile.edit')" class="flex flex-col items-center gap-y-1 transition-all" :class="[route().current('profile.edit') ? 'text-gray-100 scale-110' : 'text-gray-500 hover:text-gray-300']">
                <div class="h-6 w-6 flex items-center justify-center">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                </div>
                <span class="text-[10px] font-bold uppercase tracking-widest">Profile</span>
            </Link>
        </nav>

        <!-- Sub-Header Slot -->
        <header v-if="$slots.header" class="bg-[#121826]/40 border-b border-gray-800/40 backdrop-blur-sm">
            <div class="mx-auto max-w-7xl px-4 py-4 lg:py-6 sm:px-6 lg:px-8">
                <slot name="header" />
            </div>
        </header>

        <!-- Page Content -->
        <main>
            <slot />
        </main>
    </div>
</template>
