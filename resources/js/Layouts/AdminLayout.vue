<script setup>
import { ref } from 'vue';
import { Link, Head } from '@inertiajs/vue3';
import VerificationAlert from '@/Components/VerificationAlert.vue';

const props = defineProps({
    title: String,
});

const isSidebarOpen = ref(true);
const isMobileMenuOpen = ref(false);

const toggleSidebar = () => {
    isSidebarOpen.value = !isSidebarOpen.value;
};
</script>

<template>
    <div class="min-h-screen bg-[#0B0F19] text-gray-100 font-jakarta flex flex-col overflow-hidden">
        <VerificationAlert />
        <div class="flex-1 flex overflow-hidden">
        <Head :title="title" />

        <!-- Sidebar (Desktop) -->
        <aside 
            :class="[
                'hidden lg:flex flex-col bg-[#121826]/80 backdrop-blur-xl border-r border-gray-800/80 transition-all duration-300 relative z-30',
                isSidebarOpen ? 'w-64' : 'w-20'
            ]"
        >
            <!-- Logo Area -->
            <div class="h-20 flex items-center px-6 border-b border-gray-800/80 shrink-0 overflow-hidden">
                <Link href="/" class="flex items-center gap-x-3 group">
                    <img src="/assets/images/icon_only.webp" class="h-8 w-8 object-contain drop-shadow-[0_0_10px_rgba(168,85,247,0.4)]" alt="Logo" />
                    <span v-if="isSidebarOpen" class="text-lg font-bold tracking-tight bg-gradient-to-r from-white to-gray-400 bg-clip-text text-transparent whitespace-nowrap">
                        FluxMedia
                    </span>
                </Link>
            </div>

            <!-- Navigation Links -->
            <nav class="flex-1 py-6 px-3 space-y-2 overflow-y-auto custom-scrollbar">
                <Link 
                    :href="route('admin.dashboard')" 
                    :class="[
                        'flex items-center gap-x-3 px-3 py-2.5 rounded-xl transition-all duration-200 group',
                        route().current('admin.dashboard') ? 'bg-purple-600/10 text-purple-400 border border-purple-500/20' : 'text-gray-400 hover:text-white hover:bg-gray-800/50'
                    ]"
                >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                    <span v-if="isSidebarOpen" class="font-medium text-sm">Dashboard</span>
                </Link>

                <Link 
                    :href="route('admin.settings')" 
                    :class="[
                        'flex items-center gap-x-3 px-3 py-2.5 rounded-xl transition-all duration-200 group',
                        route().current('admin.settings') ? 'bg-purple-600/10 text-purple-400 border border-purple-500/20' : 'text-gray-400 hover:text-white hover:bg-gray-800/50'
                    ]"
                >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <span v-if="isSidebarOpen" class="font-medium text-sm">Settings</span>
                </Link>

                <Link 
                    :href="route('admin.users.index')" 
                    :class="[
                        'flex items-center gap-x-3 px-3 py-2.5 rounded-xl transition-all duration-200 group',
                        route().current('admin.users.*') ? 'bg-purple-600/10 text-purple-400 border border-purple-500/20' : 'text-gray-400 hover:text-white hover:bg-gray-800/50'
                    ]"
                >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                    <span v-if="isSidebarOpen" class="font-medium text-sm">Users</span>
                </Link>

                <Link 
                    :href="route('admin.url-shortener.index')" 
                    :class="[
                        'flex items-center gap-x-3 px-3 py-2.5 rounded-xl transition-all duration-200 group',
                        route().current('admin.url-shortener.*') ? 'bg-blue-600/10 text-blue-400 border border-blue-500/20' : 'text-gray-400 hover:text-white hover:bg-gray-800/50'
                    ]"
                >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                    </svg>
                    <span v-if="isSidebarOpen" class="font-medium text-sm">URL Shortener</span>
                </Link>

                <Link 
                    :href="route('admin.uuid-generator.index')" 
                    :class="[
                        'flex items-center gap-x-3 px-3 py-2.5 rounded-xl transition-all duration-200 group',
                        route().current('admin.uuid-generator.*') ? 'bg-emerald-600/10 text-emerald-400 border border-emerald-500/20' : 'text-gray-400 hover:text-white hover:bg-gray-800/50'
                    ]"
                >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14" />
                    </svg>
                    <span v-if="isSidebarOpen" class="font-medium text-sm">UUID Generator</span>
                </Link>

                <Link 
                    :href="route('admin.password-generator.index')" 
                    :class="[
                        'flex items-center gap-x-3 px-3 py-2.5 rounded-xl transition-all duration-200 group',
                        route().current('admin.password-generator.*') ? 'bg-purple-600/10 text-purple-400 border border-purple-500/20' : 'text-gray-400 hover:text-white hover:bg-gray-800/50'
                    ]"
                >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                    <span v-if="isSidebarOpen" class="font-medium text-sm">Pass Generator</span>
                </Link>

                <Link 
                    :href="route('admin.ip-lookup.index')" 
                    :class="[
                        'flex items-center gap-x-3 px-3 py-2.5 rounded-xl transition-all duration-200 group',
                        route().current('admin.ip-lookup.*') ? 'bg-blue-600/10 text-blue-400 border border-blue-500/20' : 'text-gray-400 hover:text-white hover:bg-gray-800/50'
                    ]"
                >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                    </svg>
                    <span v-if="isSidebarOpen" class="font-medium text-sm">IP Intelligence</span>
                </Link>

                <Link 
                    :href="route('admin.color-picker.index')" 
                    :class="[
                        'flex items-center gap-x-3 px-3 py-2.5 rounded-xl transition-all duration-200 group',
                        route().current('admin.color-picker.*') ? 'bg-rose-600/10 text-rose-400 border border-rose-500/20' : 'text-gray-400 hover:text-white hover:bg-gray-800/50'
                    ]"
                >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01" />
                    </svg>
                    <span v-if="isSidebarOpen" class="font-medium text-sm">Color Picker</span>
                </Link>

                <Link 
                    :href="route('admin.json-formatter.index')" 
                    :class="[
                        'flex items-center gap-x-3 px-3 py-2.5 rounded-xl transition-all duration-200 group',
                        route().current('admin.json-formatter.*') ? 'bg-amber-600/10 text-amber-400 border border-amber-500/20' : 'text-gray-400 hover:text-white hover:bg-gray-800/50'
                    ]"
                >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
                    </svg>
                    <span v-if="isSidebarOpen" class="font-medium text-sm">JSON Formatter</span>
                </Link>

                <Link 
                    :href="route('admin.text-tools.index')" 
                    :class="[
                        'flex items-center gap-x-3 px-3 py-2.5 rounded-xl transition-all duration-200 group',
                        route().current('admin.text-tools.*') ? 'bg-rose-600/10 text-rose-450 border border-rose-500/20' : 'text-gray-400 hover:text-white hover:bg-gray-800/50'
                    ]"
                >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7" />
                    </svg>
                    <span v-if="isSidebarOpen" class="font-medium text-sm">Text Tools</span>
                </Link>

                <div class="h-px bg-gray-800/60 mx-3 my-4"></div>

                <Link 
                    href="/" 
                    class="flex items-center gap-x-3 px-3 py-2.5 rounded-xl text-gray-400 hover:text-white hover:bg-gray-800/50 transition-all duration-200 group"
                >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    <span v-if="isSidebarOpen" class="font-medium text-sm">Exit to Public</span>
                </Link>
            </nav>

            <!-- Bottom User Info -->
            <div class="p-4 border-t border-gray-800/80 shrink-0">
                <div class="flex items-center gap-x-3 px-2">
                    <div class="h-8 w-8 rounded-lg bg-gradient-to-tr from-purple-600 to-indigo-600 flex items-center justify-center font-bold text-xs">
                        {{ $page.props.auth.user.name[0] }}
                    </div>
                    <div v-if="isSidebarOpen" class="min-w-0">
                        <p class="text-xs font-bold text-white truncate">{{ $page.props.auth.user.name }}</p>
                        <p class="text-[10px] text-gray-500 truncate">Administrator</p>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col min-w-0 overflow-hidden relative">
            <!-- Header (Mobile Toggle + User Dropdown) -->
            <header class="h-20 bg-[#0B0F19]/80 backdrop-blur-md border-b border-gray-800/60 flex items-center justify-between px-4 sm:px-8 relative z-20">
                <div class="flex items-center gap-x-4">
                    <button @click="toggleSidebar" class="hidden lg:flex p-2 text-gray-400 hover:text-white transition-colors">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h8m-8 6h16" />
                        </svg>
                    </button>
                    
                    <!-- Mobile Hamburger -->
                    <button @click="isMobileMenuOpen = !isMobileMenuOpen" class="lg:hidden p-2 text-gray-400 hover:text-white transition-colors">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>

                    <h1 class="text-lg font-bold text-white">{{ title }}</h1>
                </div>

                <div class="flex items-center gap-x-4">
                    <Link :href="route('logout')" method="post" as="button" class="text-sm font-semibold text-gray-400 hover:text-white transition-colors flex items-center gap-x-2 px-3 py-1.5 rounded-lg border border-gray-800 hover:bg-gray-800/50">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                        Logout
                    </Link>
                </div>
            </header>

            <!-- Mobile Sidebar Overlay -->
            <div v-if="isMobileMenuOpen" @click="isMobileMenuOpen = false" class="lg:hidden fixed inset-0 bg-black/60 backdrop-blur-sm z-40"></div>
            
            <!-- Mobile Sidebar Drawer -->
            <aside 
                :class="[
                    'lg:hidden fixed inset-y-0 left-0 w-72 bg-[#121826] border-r border-gray-800 z-50 transform transition-transform duration-300 ease-in-out',
                    isMobileMenuOpen ? 'translate-x-0' : '-translate-x-full'
                ]"
            >
                <!-- Mobile Logo -->
                <div class="h-20 flex items-center px-6 border-b border-gray-800">
                    <img src="/assets/images/icon_only.webp" class="h-8 w-8 mr-3" alt="Logo" />
                    <span class="text-lg font-bold text-white">FluxMedia Admin</span>
                </div>

                <!-- Mobile Nav -->
                <nav class="p-4 space-y-2 overflow-y-auto max-h-[calc(100vh-10rem)] custom-scrollbar">
                    <Link 
                        @click="isMobileMenuOpen = false" 
                        :href="route('admin.dashboard')" 
                        :class="[
                            'flex items-center gap-x-3 px-4 py-3 rounded-xl transition-all duration-200 group border border-transparent',
                            route().current('admin.dashboard') ? 'bg-purple-600/10 text-purple-400 border-purple-500/20' : 'text-gray-400 hover:text-white hover:bg-gray-800/50'
                        ]"
                    >
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                        <span class="font-medium text-sm">Dashboard</span>
                    </Link>

                    <Link 
                        @click="isMobileMenuOpen = false" 
                        :href="route('admin.settings')" 
                        :class="[
                            'flex items-center gap-x-3 px-4 py-3 rounded-xl transition-all duration-200 group border border-transparent',
                            route().current('admin.settings') ? 'bg-purple-600/10 text-purple-400 border-purple-500/20' : 'text-gray-400 hover:text-white hover:bg-gray-800/50'
                        ]"
                    >
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <span class="font-medium text-sm">Settings</span>
                    </Link>

                    <Link 
                        @click="isMobileMenuOpen = false" 
                        :href="route('admin.users.index')" 
                        :class="[
                            'flex items-center gap-x-3 px-4 py-3 rounded-xl transition-all duration-200 group border border-transparent',
                            route().current('admin.users.*') ? 'bg-purple-600/10 text-purple-400 border-purple-500/20' : 'text-gray-400 hover:text-white hover:bg-gray-800/50'
                        ]"
                    >
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                        <span class="font-medium text-sm">Users</span>
                    </Link>

                    <Link 
                        @click="isMobileMenuOpen = false" 
                        :href="route('admin.url-shortener.index')" 
                        :class="[
                            'flex items-center gap-x-3 px-4 py-3 rounded-xl transition-all duration-200 group border border-transparent',
                            route().current('admin.url-shortener.*') ? 'bg-blue-600/10 text-blue-400 border-blue-500/20' : 'text-gray-400 hover:text-white hover:bg-gray-800/50'
                        ]"
                    >
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                        </svg>
                        <span class="font-medium text-sm">URL Shortener</span>
                    </Link>

                    <Link 
                        @click="isMobileMenuOpen = false" 
                        :href="route('admin.uuid-generator.index')" 
                        :class="[
                            'flex items-center gap-x-3 px-4 py-3 rounded-xl transition-all duration-200 group border border-transparent',
                            route().current('admin.uuid-generator.*') ? 'bg-emerald-600/10 text-emerald-400 border-emerald-500/20' : 'text-gray-400 hover:text-white hover:bg-gray-800/50'
                        ]"
                    >
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14" />
                        </svg>
                        <span class="font-medium text-sm">UUID Generator</span>
                    </Link>

                    <Link 
                        @click="isMobileMenuOpen = false" 
                        :href="route('admin.password-generator.index')" 
                        :class="[
                            'flex items-center gap-x-3 px-4 py-3 rounded-xl transition-all duration-200 group border border-transparent',
                            route().current('admin.password-generator.*') ? 'bg-purple-600/10 text-purple-400 border-purple-500/20' : 'text-gray-400 hover:text-white hover:bg-gray-800/50'
                        ]"
                    >
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                        <span class="font-medium text-sm">Pass Generator</span>
                    </Link>

                    <Link 
                        @click="isMobileMenuOpen = false" 
                        :href="route('admin.ip-lookup.index')" 
                        :class="[
                            'flex items-center gap-x-3 px-4 py-3 rounded-xl transition-all duration-200 group border border-transparent',
                            route().current('admin.ip-lookup.*') ? 'bg-blue-600/10 text-blue-400 border-blue-500/20' : 'text-gray-400 hover:text-white hover:bg-gray-800/50'
                        ]"
                    >
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                        </svg>
                        <span class="font-medium text-sm">IP Intelligence</span>
                    </Link>

                    <Link 
                        @click="isMobileMenuOpen = false" 
                        :href="route('admin.color-picker.index')" 
                        :class="[
                            'flex items-center gap-x-3 px-4 py-3 rounded-xl transition-all duration-200 group border border-transparent',
                            route().current('admin.color-picker.*') ? 'bg-rose-600/10 text-rose-400 border-rose-500/20' : 'text-gray-400 hover:text-white hover:bg-gray-800/50'
                        ]"
                    >
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01" />
                        </svg>
                        <span class="font-medium text-sm">Color Picker</span>
                    </Link>

                    <Link 
                        @click="isMobileMenuOpen = false" 
                        :href="route('admin.json-formatter.index')" 
                        :class="[
                            'flex items-center gap-x-3 px-4 py-3 rounded-xl transition-all duration-200 group border border-transparent',
                            route().current('admin.json-formatter.*') ? 'bg-amber-600/10 text-amber-400 border-amber-500/20' : 'text-gray-400 hover:text-white hover:bg-gray-800/50'
                        ]"
                    >
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
                        </svg>
                        <span class="font-medium text-sm">JSON Formatter</span>
                    </Link>

                    <Link 
                        @click="isMobileMenuOpen = false" 
                        :href="route('admin.text-tools.index')" 
                        :class="[
                            'flex items-center gap-x-3 px-4 py-3 rounded-xl transition-all duration-200 group border border-transparent',
                            route().current('admin.text-tools.*') ? 'bg-rose-600/10 text-rose-455 border-rose-500/20' : 'text-gray-400 hover:text-white hover:bg-gray-800/50'
                        ]"
                    >
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7" />
                        </svg>
                        <span class="font-medium text-sm">Text Tools</span>
                    </Link>

                    <div class="h-px bg-gray-800/60 my-4"></div>

                    <Link 
                        @click="isMobileMenuOpen = false" 
                        href="/" 
                        class="flex items-center gap-x-3 px-4 py-3 rounded-xl text-gray-400 hover:text-white hover:bg-gray-800/50 transition-all duration-200 group border border-transparent"
                    >
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        <span class="font-medium text-sm">Exit to Public</span>
                    </Link>
                </nav>
            </aside>

            <!-- Main Scrollable Slot Area -->
            <main class="flex-1 overflow-y-auto p-4 sm:p-8 custom-scrollbar">
                <slot />
            </main>
        </div>
    </div>
</div>
</template>

<style>
@import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');

.font-jakarta {
    font-family: 'Plus Jakarta Sans', sans-serif;
}

.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #1f2937;
    border-radius: 10px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #374151;
}
</style>
