<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import Chart from 'chart.js/auto';

const props = defineProps({
    stats:         Object,
    chartData:     Array,
    lengthBuckets: Object,
    optionStats:   Array,
});

const mainChartRef = ref(null);
const distChartRef = ref(null);

onMounted(() => {
    // Activity Chart
    if (mainChartRef.value) {
        new Chart(mainChartRef.value.getContext('2d'), {
            type: 'line',
            data: {
                labels: props.chartData.map(d => d.date),
                datasets: [{
                    label: 'Passwords Generated',
                    data: props.chartData.map(d => d.count),
                    backgroundColor: 'rgba(168, 85, 247, 0.1)',
                    borderColor: '#a855f7',
                    borderWidth: 2,
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: '#a855f7',
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { grid: { display: false }, ticks: { color: '#6B7280', font: { size: 10 } } },
                    y: { beginAtZero: true, grid: { color: 'rgba(55,65,81,0.3)' }, ticks: { color: '#6B7280', font: { size: 10 } } },
                },
            },
        });
    }

    // Distribution Chart
    if (distChartRef.value) {
        new Chart(distChartRef.value.getContext('2d'), {
            type: 'bar',
            data: {
                labels: Object.keys(props.lengthBuckets),
                datasets: [{
                    label: 'Usage',
                    data: Object.values(props.lengthBuckets),
                    backgroundColor: '#a855f7',
                    borderRadius: 8,
                }],
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { grid: { color: 'rgba(55,65,81,0.2)' }, ticks: { color: '#6B7280', font: { size: 10 } } },
                    y: { grid: { display: false }, ticks: { color: '#6B7280', font: { size: 11, weight: 'bold' } } },
                },
            },
        });
    }
});
</script>

<template>
    <AdminLayout title="Password Generator Admin">
        <div class="space-y-8 animate-fade-in">
            <!-- Header -->
            <div>
                <h3 class="text-2xl font-bold text-white mb-1">Password Gen Analytics</h3>
                <p class="text-gray-400 text-sm font-medium">Insights into user password generation habits and security levels.</p>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div v-for="s in [
                    { label: 'Total Generated', value: stats.total,   color: 'purple',  icon: 'M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z' },
                    { label: 'Avg. Length',     value: stats.avgLength,color: 'blue',    icon: 'M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z' },
                    { label: 'Today',           value: stats.today,   color: 'emerald', icon: 'M13 10V3L4 14h7v7l9-11h-7z' },
                    { label: 'Using Symbols',   value: stats.withSymbols, color: 'amber', icon: 'M11 4a2 2 0 114 0v1a1 1 0 001 1h3a1 1 0 011 1v3a1 1 0 01-1 1h-1a2 2 0 100 4h1a1 1 0 011 1v3a1 1 0 01-1 1h-3a1 1 0 01-1-1v-1a2 2 0 10-4 0v1a1 1 0 01-1 1H7a1 1 0 01-1-1v-3a1 1 0 011-1h1a2 2 0 100-4H7a1 1 0 01-1-1V7a1 1 0 011-1h3a1 1 0 001-1V4z' },
                ]" :key="s.label" class="bg-[#121826]/80 backdrop-blur-xl rounded-2xl border border-gray-800/80 p-5 shadow-xl transition-all group">
                    <div class="flex items-center justify-between mb-3">
                        <div :class="['p-2 rounded-lg bg-opacity-10 text-opacity-100 group-hover:scale-110 transition-transform', `bg-${s.color}-500 text-${s.color}-400`]">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="s.icon" /></svg>
                        </div>
                    </div>
                    <p class="text-[10px] font-bold text-gray-500 uppercase tracking-widest">{{ s.label }}</p>
                    <h4 :class="['text-2xl font-black tracking-tighter mt-1', `text-${s.color}-400`]">{{ s.value.toLocaleString() }}</h4>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Activity Chart -->
                <div class="bg-[#121826]/80 backdrop-blur-xl rounded-3xl border border-gray-800/80 p-6 lg:p-8 shadow-2xl">
                    <h3 class="text-lg font-black text-white mb-6">Generation Activity</h3>
                    <div class="h-[300px]">
                        <canvas ref="mainChartRef"></canvas>
                    </div>
                </div>

                <div class="space-y-6">
                    <!-- Distribution Chart -->
                    <div class="bg-[#121826]/80 backdrop-blur-xl rounded-3xl border border-gray-800/80 p-6 lg:p-8 shadow-2xl">
                        <h3 class="text-lg font-black text-white mb-6">Length Distribution</h3>
                        <div class="h-[180px]">
                            <canvas ref="distChartRef"></canvas>
                        </div>
                    </div>

                    <!-- Option Usage -->
                    <div class="bg-[#121826]/80 backdrop-blur-xl rounded-3xl border border-gray-800/80 p-6 lg:p-8 shadow-2xl">
                        <h3 class="text-sm font-bold text-white mb-4 uppercase tracking-widest text-gray-500">Option Popularity</h3>
                        <div class="space-y-4">
                            <div v-for="opt in optionStats" :key="opt.label">
                                <div class="flex justify-between text-[11px] font-bold text-gray-400 mb-1.5 uppercase tracking-wider">
                                    <span>{{ opt.label }}</span>
                                    <span>{{ opt.pct }}%</span>
                                </div>
                                <div class="h-2 bg-gray-800 rounded-full overflow-hidden">
                                    <div class="h-full bg-purple-500 transition-all duration-1000" :style="{ width: opt.pct + '%' }"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
