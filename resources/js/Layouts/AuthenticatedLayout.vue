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
    <div class="min-h-screen bg-[#0B0F19] text-gray-100 selection:bg-purple-500 selection:text-white">
        <VerificationAlert />
        <PWAInstallPrompt />

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

                <!-- Mobile Menu Button -->
                <button @click="showingNavigationDropdown = !showingNavigationDropdown" class="lg:hidden p-2 rounded-xl bg-gray-900/80 border border-gray-800/50 text-purple-400">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path v-if="!showingNavigationDropdown" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Mobile Navigation -->
            <Transition
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="opacity-0 -translate-y-4"
                enter-to-class="opacity-100 translate-y-0"
                leave-active-class="transition duration-150 ease-in"
                leave-to-class="opacity-0 -translate-y-4"
            >
                <div v-if="showingNavigationDropdown" class="lg:hidden border-t border-gray-800/60 bg-[#0B0F19] px-4 py-6 space-y-4">
                    <div class="grid grid-cols-1 gap-2">
                        <Link :href="route('home')" class="p-4 rounded-2xl bg-gray-900/50 border border-gray-800/50 flex items-center gap-x-4">
                            <div class="h-10 w-10 rounded-xl bg-purple-500/10 flex items-center justify-center text-purple-400">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                            </div>
                            <span class="font-extrabold uppercase tracking-wider text-sm">Image Studio</span>
                        </Link>
                        <Link :href="route('qr.generator')" class="p-4 rounded-2xl bg-gray-900/50 border border-gray-800/50 flex items-center gap-x-4">
                            <div class="h-10 w-10 rounded-xl bg-indigo-500/10 flex items-center justify-center text-indigo-400">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 17h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" /></svg>
                            </div>
                            <span class="font-extrabold uppercase tracking-wider text-sm text-left">QR Intel</span>
                        </Link>
                        <Link :href="route('history')" class="p-4 rounded-2xl bg-gray-900/50 border border-gray-800/50 flex items-center gap-x-4">
                            <div class="h-10 w-10 rounded-xl bg-emerald-500/10 flex items-center justify-center text-emerald-400">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            </div>
                            <span class="font-extrabold uppercase tracking-wider text-sm text-left">History Hub</span>
                        </Link>
                    </div>
                    <div class="pt-4 border-t border-gray-800/60 flex items-center justify-between">
                        <div class="flex items-center gap-x-3 text-left">
                            <div class="h-10 w-10 rounded-xl bg-gray-800 flex items-center justify-center text-purple-400 font-bold border border-gray-700">
                                {{ $page.props.auth.user.name.charAt(0) }}
                            </div>
                            <div class="text-left">
                                <div class="text-xs font-black text-white uppercase tracking-wide text-left">{{ $page.props.auth.user.name }}</div>
                                <div class="text-[10px] font-bold text-gray-500 truncate max-w-[150px] text-left">{{ $page.props.auth.user.email }}</div>
                            </div>
                        </div>
                        <Link :href="route('logout')" method="post" as="button" class="p-2.5 rounded-xl bg-red-500/10 border border-red-500/20 text-red-400">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" /></svg>
                        </Link>
                    </div>
                </div>
            </Transition>
        </header>

        <!-- Sub-Header Slot -->
        <header v-if="$slots.header" class="bg-[#121826]/40 border-b border-gray-800/40 backdrop-blur-sm">
            <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                <slot name="header" />
            </div>
        </header>

        <!-- Page Content -->
        <main>
            <slot />
        </main>
    </div>
</template>
