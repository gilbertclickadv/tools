<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import Chart from 'chart.js/auto';

const props = defineProps({
    stats: Object,
    chartData: Array,
    recentVisits: Array,
});

const trafficChartRef = ref(null);

onMounted(() => {
    // Traffic Chart
    if (trafficChartRef.value) {
        new Chart(trafficChartRef.value.getContext('2d'), {
            type: 'line',
            data: {
                labels: props.chartData.map(d => d.date),
                coordinates: props.chartData.map(d => d.count),
                datasets: [{
                    label: 'Page Hits',
                    data: props.chartData.map(d => d.count),
                    backgroundColor: 'rgba(244, 63, 94, 0.1)', // Rose-500 tint
                    borderColor: '#f43f5e', // Rose-500
                    borderWidth: 2.5,
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: '#f43f5e',
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
    <AdminLayout title="Regex Tester Analytics">
        <div class="space-y-8 animate-fade-in font-jakarta">
            <!-- Header -->
            <div>
                <h3 class="text-2xl font-bold text-white mb-1">Regex Tester Traffic</h3>
                <p class="text-gray-400 text-sm font-medium">Monitoring visits and metrics logs for the Regex Tester & Visual Explainer utility.</p>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div v-for="s in [
                    { label: 'Total Page Hits', value: stats.totalHits, icon: 'M15 12a3 3 0 11-6 0 3 3 0 016 0z M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z' },
                    { label: 'Active Edge IPs', value: stats.uniqueIPs, icon: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z' },
                    { label: 'Today Hits', value: stats.todayHits, icon: 'M13 10V3L4 14h7v7l9-11h-7z' },
                    { label: 'Authenticated Hits', value: stats.authVisits, icon: 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z' },
                ]" :key="s.label" class="bg-[#121826]/80 backdrop-blur-xl rounded-2xl border border-gray-800/80 p-5 shadow-xl hover:border-gray-700/80 transition-all group">
                    <div class="flex items-center justify-between mb-3">
                        <div class="p-2 rounded-lg bg-rose-500/10 text-rose-455 group-hover:scale-110 transition-transform">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="s.icon" /></svg>
                        </div>
                    </div>
                    <p class="text-[10px] font-bold text-gray-500 uppercase tracking-widest">{{ s.label }}</p>
                    <h4 class="text-2xl font-black tracking-tighter mt-1 text-white">{{ s.value.toLocaleString() }}</h4>
                </div>
            </div>

            <!-- Chart Section -->
            <div class="bg-[#121826]/80 backdrop-blur-xl rounded-3xl border border-gray-800/80 p-6 lg:p-8 shadow-2xl">
                <h3 class="text-lg font-black text-white mb-6">Execution Traffic Stream</h3>
                <div class="h-[300px]">
                    <canvas ref="trafficChartRef"></canvas>
                </div>
            </div>

            <!-- Recent Visitors Log table -->
            <div class="bg-[#121826]/80 backdrop-blur-xl rounded-3xl border border-gray-800/80 p-6 lg:p-8 shadow-2xl">
                <h3 class="text-lg font-black text-white mb-6">Recent Traffic Logs</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-gray-850 text-xs font-bold text-gray-500 uppercase tracking-widest">
                                <th class="pb-4">Client IP</th>
                                <th class="pb-4">User</th>
                                <th class="pb-4">User Agent</th>
                                <th class="pb-4">Timestamp</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-850/60 text-sm">
                            <tr v-for="log in recentVisits" :key="log.id" class="hover:bg-gray-800/20 transition-colors group">
                                <td class="py-4 font-mono text-xs font-bold text-rose-455">
                                    {{ log.ip_address }}
                                </td>
                                <td class="py-4">
                                    <div v-if="log.user">
                                        <p class="font-bold text-white">{{ log.user.name }}</p>
                                        <p class="text-xs text-gray-500">{{ log.user.email }}</p>
                                    </div>
                                    <span v-else class="text-gray-500 italic">Guest User</span>
                                </td>
                                <td class="py-4 text-gray-400 text-xs max-w-xs truncate" :title="log.user_agent">
                                    {{ log.user_agent }}
                                </td>
                                <td class="py-4 text-gray-500 font-medium">
                                    {{ log.created_at }}
                                </td>
                            </tr>
                            <tr v-if="recentVisits.length === 0">
                                <td colspan="4" class="py-8 text-center text-gray-500 italic">No traffic recorded yet.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
