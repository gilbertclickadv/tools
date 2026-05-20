<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import Chart from 'chart.js/auto';

const props = defineProps({
    stats: Object,
    chartData: Array,
    popularColors: Array,
    recentSwatches: Array,
});

const mainChartRef = ref(null);

onMounted(() => {
    // Activity Chart
    if (mainChartRef.value) {
        new Chart(mainChartRef.value.getContext('2d'), {
            type: 'line',
            data: {
                labels: props.chartData.map(d => d.date),
                datasets: [{
                    label: 'Colors Saved',
                    data: props.chartData.map(d => d.count),
                    backgroundColor: 'rgba(99, 102, 241, 0.1)',
                    borderColor: '#6366f1',
                    borderWidth: 2.5,
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: '#6366f1',
                    pointRadius: 4,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { grid: { display: false }, ticks: { color: '#6B7280', font: { size: 10 } } },
                    y: { beginAtZero: true, grid: { color: 'rgba(55,65,81,0.3)' }, ticks: { color: '#6B7280', font: { size: 10 }, stepSize: 1 } },
                },
            },
        });
    }
});
</script>

<template>
    <AdminLayout title="Color Picker Analytics">
        <div class="space-y-8 animate-fade-in">
            <!-- Header -->
            <div>
                <h3 class="text-2xl font-bold text-white mb-1">Color Picker Intel</h3>
                <p class="text-gray-400 text-sm font-medium">Tracking designer trends, swatch saves, and custom palette naming.</p>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div v-for="s in [
                    { label: 'Total Saved Swatches', value: stats.totalSwatches, color: 'indigo', icon: 'M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.344l2.143-2.143a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01' },
                    { label: 'Active Designers', value: stats.activeDesigners, color: 'sky', icon: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z' },
                    { label: 'Today Saves', value: stats.todaySaves, color: 'emerald', icon: 'M13 10V3L4 14h7v7l9-11h-7z' },
                    { label: 'Named Swatches', value: stats.namedSwatches, color: 'amber', icon: 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z' },
                ]" :key="s.label" class="bg-[#121826]/80 backdrop-blur-xl rounded-2xl border border-gray-800/80 p-5 shadow-xl hover:border-gray-700/80 transition-all group">
                    <div class="flex items-center justify-between mb-3">
                        <div :class="['p-2 rounded-lg bg-opacity-10 text-opacity-100 group-hover:scale-110 transition-transform', `bg-${s.color}-500 text-${s.color}-400`]">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="s.icon" /></svg>
                        </div>
                    </div>
                    <p class="text-[10px] font-bold text-gray-500 uppercase tracking-widest">{{ s.label }}</p>
                    <h4 :class="['text-2xl font-black tracking-tighter mt-1', `text-${s.color}-400`]">{{ s.value.toLocaleString() }}</h4>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Activity Chart -->
                <div class="lg:col-span-2 bg-[#121826]/80 backdrop-blur-xl rounded-3xl border border-gray-800/80 p-6 lg:p-8 shadow-2xl">
                    <h3 class="text-lg font-black text-white mb-6">Palette Generation Stream</h3>
                    <div class="h-[300px]">
                        <canvas ref="mainChartRef"></canvas>
                    </div>
                </div>

                <!-- Popular Colors -->
                <div class="bg-[#121826]/80 backdrop-blur-xl rounded-3xl border border-gray-800/80 p-6 lg:p-8 shadow-2xl flex flex-col">
                    <h3 class="text-lg font-black text-white mb-6">Top Swatches</h3>
                    <div class="space-y-4 flex-1">
                        <div v-for="color in popularColors" :key="color.hex" class="flex items-center justify-between p-3.5 bg-[#0B0F19] rounded-2xl border border-gray-800/60 hover:border-gray-700 transition-colors">
                            <div class="flex items-center gap-x-3.5">
                                <div class="h-10 w-10 rounded-xl shadow-lg border border-white/10 relative overflow-hidden" :style="{ backgroundColor: color.hex }">
                                    <div class="absolute inset-0 bg-gradient-to-tr from-black/20 via-transparent to-white/10"></div>
                                </div>
                                <div>
                                    <p class="text-sm font-bold text-white uppercase tracking-wider font-mono">{{ color.hex }}</p>
                                    <p class="text-[10px] text-gray-500 font-bold uppercase tracking-widest">Pantone Node</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="px-2.5 py-1 text-xs font-bold bg-indigo-500/10 text-indigo-400 rounded-lg border border-indigo-500/15">
                                    {{ color.count }} saves
                                </span>
                            </div>
                        </div>
                        <div v-if="popularColors.length === 0" class="h-full flex items-center justify-center text-gray-500 text-sm italic">
                            No swatches recorded yet.
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent saved swatches table -->
            <div class="bg-[#121826]/80 backdrop-blur-xl rounded-3xl border border-gray-800/80 p-6 lg:p-8 shadow-2xl">
                <h3 class="text-lg font-black text-white mb-6">Recent Swatch Pipeline</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-gray-850 text-xs font-bold text-gray-500 uppercase tracking-widest">
                                <th class="pb-4">Color Node</th>
                                <th class="pb-4">Custom Name</th>
                                <th class="pb-4">Designer</th>
                                <th class="pb-4">Timestamp</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-850/60 text-sm">
                            <tr v-for="swatch in recentSwatches" :key="swatch.id" class="hover:bg-gray-800/20 transition-colors group">
                                <td class="py-4 flex items-center gap-x-3">
                                    <div class="h-8 w-8 rounded-lg shadow border border-white/5 relative overflow-hidden" :style="{ backgroundColor: swatch.hex }">
                                        <div class="absolute inset-0 bg-gradient-to-tr from-black/10 to-white/10"></div>
                                    </div>
                                    <span class="font-mono text-xs font-bold text-gray-200 uppercase tracking-wider">{{ swatch.hex }}</span>
                                </td>
                                <td class="py-4 text-gray-300 font-semibold">
                                    {{ swatch.name || '—' }}
                                </td>
                                <td class="py-4">
                                    <div v-if="swatch.user">
                                        <p class="font-bold text-white">{{ swatch.user.name }}</p>
                                        <p class="text-xs text-gray-500">{{ swatch.user.email }}</p>
                                    </div>
                                    <span v-else class="text-gray-500 italic">Unknown Guest</span>
                                </td>
                                <td class="py-4 text-gray-500 font-medium">
                                    {{ swatch.created_at }}
                                </td>
                            </tr>
                            <tr v-if="recentSwatches.length === 0">
                                <td colspan="4" class="py-8 text-center text-gray-500 italic">No swatches saved yet.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
