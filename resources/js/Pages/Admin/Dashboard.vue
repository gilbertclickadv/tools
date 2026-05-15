<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';
import Chart from 'chart.js/auto';

const props = defineProps({
    stats: Object,
    chartData: Array,
});

const trafficChartRef = ref(null);
let chartInstance = null;

onMounted(() => {
    if (trafficChartRef.value) {
        const ctx = trafficChartRef.value.getContext('2d');
        
        chartInstance = new Chart(ctx, {
            type: 'line',
            data: {
                labels: props.chartData.map(d => d.date),
                datasets: [
                    {
                        label: 'Total Visits',
                        data: props.chartData.map(d => d.total),
                        borderColor: '#8B5CF6',
                        backgroundColor: 'rgba(139, 92, 246, 0.1)',
                        fill: true,
                        tension: 0.4,
                        borderWidth: 3,
                        pointRadius: 4,
                        pointBackgroundColor: '#8B5CF6',
                    },
                    {
                        label: 'Unique IPs',
                        data: props.chartData.map(d => d.unique),
                        borderColor: '#10B981',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        fill: true,
                        tension: 0.4,
                        borderWidth: 3,
                        pointRadius: 4,
                        pointBackgroundColor: '#10B981',
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                        labels: {
                            color: '#9CA3AF',
                            font: {
                                family: 'Plus Jakarta Sans',
                                weight: 'bold',
                                size: 10
                            },
                            usePointStyle: true,
                            padding: 20
                        }
                    },
                    tooltip: {
                        backgroundColor: '#1A2133',
                        titleColor: '#F3F4F6',
                        bodyColor: '#9CA3AF',
                        borderColor: '#374151',
                        borderWidth: 1,
                        padding: 12,
                        displayColors: true,
                        callbacks: {
                            label: function(context) {
                                return ` ${context.dataset.label}: ${context.parsed.y}`;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false,
                            drawBorder: false
                        },
                        ticks: {
                            color: '#6B7280',
                            font: {
                                size: 10,
                                family: 'Plus Jakarta Sans'
                            }
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(55, 65, 81, 0.3)',
                            drawBorder: false
                        },
                        ticks: {
                            color: '#6B7280',
                            font: {
                                size: 10,
                                family: 'Plus Jakarta Sans'
                            },
                            stepSize: 1
                        }
                    }
                }
            }
        });
    }
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

            <!-- Metric Cards -->
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
                <!-- Metric Card 1: Images -->
                <div class="bg-[#121826]/80 backdrop-blur-xl rounded-2xl border border-gray-800/80 p-6 shadow-xl hover:border-purple-500/40 transition-all duration-300 group">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-2 rounded-lg bg-purple-500/10 text-purple-400 group-hover:scale-110 transition-transform">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <span class="text-[9px] font-bold uppercase tracking-widest text-purple-400 bg-purple-500/5 px-2 py-1 rounded">Process</span>
                    </div>
                    <div class="space-y-1">
                        <p class="text-[10px] font-bold text-gray-500 uppercase tracking-widest">Images Refined</p>
                        <h4 class="text-3xl font-black text-white tracking-tighter">{{ stats?.totalProcessed || 0 }}</h4>
                    </div>
                </div>

                <!-- Metric Card 2: Storage -->
                <div class="bg-[#121826]/80 backdrop-blur-xl rounded-2xl border border-gray-800/80 p-6 shadow-xl hover:border-emerald-500/40 transition-all duration-300 group">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-2 rounded-lg bg-emerald-500/10 text-emerald-400 group-hover:scale-110 transition-transform">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                            </svg>
                        </div>
                        <span class="text-[9px] font-bold uppercase tracking-widest text-emerald-400 bg-emerald-500/5 px-2 py-1 rounded">Savings</span>
                    </div>
                    <div class="space-y-1">
                        <p class="text-[10px] font-bold text-gray-500 uppercase tracking-widest">Storage Reclaimed</p>
                        <h4 class="text-3xl font-black text-emerald-400 tracking-tighter">{{ formattedStorageSaved }}</h4>
                    </div>
                </div>

                <!-- Metric Card 3: Visits -->
                <div class="bg-[#121826]/80 backdrop-blur-xl rounded-2xl border border-gray-800/80 p-6 shadow-xl hover:border-blue-500/40 transition-all duration-300 group">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-2 rounded-lg bg-blue-500/10 text-blue-400 group-hover:scale-110 transition-transform">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </div>
                        <span class="text-[9px] font-bold uppercase tracking-widest text-blue-400 bg-blue-500/5 px-2 py-1 rounded">Traffic</span>
                    </div>
                    <div class="space-y-1">
                        <p class="text-[10px] font-bold text-gray-500 uppercase tracking-widest">Total Site Visits</p>
                        <h4 class="text-3xl font-black text-white tracking-tighter">{{ stats?.totalVisits || 0 }}</h4>
                    </div>
                </div>

                <!-- Metric Card 4: Unique IPs -->
                <div class="bg-[#121826]/80 backdrop-blur-xl rounded-2xl border border-gray-800/80 p-6 shadow-xl hover:border-amber-500/40 transition-all duration-300 group">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-2 rounded-lg bg-amber-500/10 text-amber-400 group-hover:scale-110 transition-transform">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </div>
                        <span class="text-[9px] font-bold uppercase tracking-widest text-amber-400 bg-amber-500/5 px-2 py-1 rounded">Unique</span>
                    </div>
                    <div class="space-y-1">
                        <p class="text-[10px] font-bold text-gray-500 uppercase tracking-widest">Active Edge IPs</p>
                        <h4 class="text-3xl font-black text-white tracking-tighter">{{ stats?.uniqueIPs || 0 }}</h4>
                    </div>
                </div>
            </div>

            <!-- Graph Section -->
            <div class="bg-[#121826]/80 backdrop-blur-xl rounded-3xl border border-gray-800/80 p-8 shadow-2xl overflow-hidden relative">
                <div class="absolute top-0 right-0 p-8 opacity-5">
                    <svg class="h-32 w-32" fill="currentColor" viewBox="0 0 24 24"><path d="M3 3v18h18V3H3zm16 16H5V5h14v14zM7 10h2v7H7v-7zm4-3h2v10h-2V7zm4 6h2v4h-2v-4z"/></svg>
                </div>
                
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8 relative z-10">
                    <div>
                        <h3 class="text-xl font-black text-white uppercase tracking-tight">Traffic Activity Topology</h3>
                        <p class="text-xs text-gray-500 font-bold uppercase tracking-widest mt-1">Real-time engagement analysis over the last 14 days</p>
                    </div>
                    <div class="flex items-center gap-x-4">
                        <div class="flex items-center gap-x-2">
                            <span class="h-2.5 w-2.5 rounded-full bg-purple-500"></span>
                            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Total Hits</span>
                        </div>
                        <div class="flex items-center gap-x-2">
                            <span class="h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
                            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Unique IPs</span>
                        </div>
                    </div>
                </div>

                <div class="h-[350px] w-full relative z-10">
                    <canvas ref="trafficChartRef"></canvas>
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
