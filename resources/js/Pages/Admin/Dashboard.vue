<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    stats: Object,
});

const formattedStorageSaved = computed(() => {
    const bytes = props.stats?.storageSavedBytes || 0;
    if (bytes === 0) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
});
</script>

<template>
    <AdminLayout title="Admin Dashboard">
        <div class="space-y-8 animate-fade-in">
            <!-- Welcome Banner -->
            <div class="overflow-hidden rounded-3xl bg-gradient-to-r from-purple-600 via-indigo-600 to-indigo-700 p-8 sm:p-10 shadow-2xl relative">
                <div class="absolute top-0 right-0 w-64 h-64 bg-white/10 blur-[80px] rounded-full -mr-20 -mt-20 pointer-events-none"></div>
                <div class="relative z-10">
                    <h3 class="text-3xl font-extrabold tracking-tight text-white mb-3">Intelligence Control Center</h3>
                    <p class="text-purple-100 max-w-2xl text-lg leading-relaxed font-medium">
                        Real-time visualization of application throughput, resource efficiency, and media stream topologies.
                    </p>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                <!-- Metric Card 1 -->
                <div class="bg-[#121826]/80 backdrop-blur-xl rounded-2xl border border-gray-800/80 p-6 shadow-xl hover:border-purple-500/40 transition-all duration-300">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-2 rounded-lg bg-purple-500/10 text-purple-400">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <span class="text-[10px] font-bold uppercase tracking-widest text-purple-500/60 bg-purple-500/5 px-2 py-1 rounded">Accumulative</span>
                    </div>
                    <div class="space-y-1">
                        <p class="text-sm font-medium text-gray-500">Images Processed</p>
                        <h4 class="text-4xl font-black text-white tracking-tighter">{{ stats?.totalProcessed || 0 }}</h4>
                    </div>
                </div>

                <!-- Metric Card 2 -->
                <div class="bg-[#121826]/80 backdrop-blur-xl rounded-2xl border border-gray-800/80 p-6 shadow-xl hover:border-emerald-500/40 transition-all duration-300">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-2 rounded-lg bg-emerald-500/10 text-emerald-400">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                            </svg>
                        </div>
                        <span class="text-[10px] font-bold uppercase tracking-widest text-emerald-500/60 bg-emerald-500/5 px-2 py-1 rounded">Efficiency</span>
                    </div>
                    <div class="space-y-1">
                        <p class="text-sm font-medium text-gray-500">Storage Capacity Reclaimed</p>
                        <h4 class="text-4xl font-black text-emerald-400 tracking-tighter">{{ formattedStorageSaved }}</h4>
                    </div>
                </div>

                <!-- Metric Card 3 -->
                <div class="bg-[#121826]/80 backdrop-blur-xl rounded-2xl border border-gray-800/80 p-6 shadow-xl hover:border-indigo-500/40 transition-all duration-300">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-2 rounded-lg bg-indigo-500/10 text-indigo-400">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                        </div>
                        <div class="flex items-center gap-x-1.5">
                            <span class="relative flex h-2 w-2">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                            </span>
                            <span class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Active Node</span>
                        </div>
                    </div>
                    <div class="space-y-1">
                        <p class="text-sm font-medium text-gray-500">System Pipeline Status</p>
                        <h4 class="text-4xl font-black text-white tracking-tighter">Operational</h4>
                    </div>
                </div>
            </div>

            <!-- Content Guidance -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mt-12">
                <div class="bg-[#121826]/40 rounded-3xl border border-gray-800/60 p-8">
                    <h4 class="text-xl font-bold text-white mb-4">Architecture Insights</h4>
                    <p class="text-gray-400 text-sm leading-relaxed mb-6">
                        This administrative layer orchestrates high-performance media transformations using scalable Intervention buffers. Every execution is tracked via secure lifecycle logs to maintain environment integrity.
                    </p>
                    <div class="flex flex-wrap gap-3">
                        <span class="px-3 py-1.5 rounded-lg bg-gray-800 text-gray-300 text-xs font-semibold">Redis Cache v7</span>
                        <span class="px-3 py-1.5 rounded-lg bg-gray-800 text-gray-300 text-xs font-semibold">GD/Imagick Engine</span>
                        <span class="px-3 py-1.5 rounded-lg bg-gray-800 text-gray-300 text-xs font-semibold">Inertia v2.0</span>
                    </div>
                </div>
                
                <div class="bg-gradient-to-br from-purple-600/10 to-transparent rounded-3xl border border-purple-500/20 p-8">
                    <h4 class="text-xl font-bold text-white mb-2">UI Intelligence</h4>
                    <p class="text-gray-400 text-sm leading-relaxed">
                        Interface tokens automatically conform to high-contrast availability, micro-animation responsiveness, and optimized typography pairings. Design tokens are optimized for the FluxMedia Creative Suite.
                    </p>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
