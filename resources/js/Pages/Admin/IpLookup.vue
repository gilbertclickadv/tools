<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import Chart from 'chart.js/auto';

const props = defineProps({
    records: Object,
    stats: Object,
    chartData: Array,
    topCountries: Array,
    filters: Object,
});

const searchQuery = ref(props.filters.search || '');
const mainChartRef = ref(null);

onMounted(() => {
    // Activity Chart
    if (mainChartRef.value) {
        new Chart(mainChartRef.value.getContext('2d'), {
            type: 'line',
            data: {
                labels: props.chartData.map(d => d.date),
                datasets: [{
                    label: 'IP Lookups Checked',
                    data: props.chartData.map(d => d.count),
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    borderColor: '#3b82f6',
                    borderWidth: 2,
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: '#3b82f6',
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
});

const handleSearch = () => {
    router.get(route('admin.ip-lookup.index'), { search: searchQuery.value }, {
        preserveState: true,
        replace: true,
    });
};

const deleteRecord = (id) => {
    if (confirm('Are you sure you want to delete this IP search log record?')) {
        router.delete(route('admin.ip-lookup.destroy', id), {
            preserveScroll: true,
        });
    }
};
</script>

<template>
    <AdminLayout title="IP Lookup Engine Analytics">
        <div class="space-y-8 animate-fade-in">
            <!-- Header -->
            <div>
                <h3 class="text-2xl font-bold text-white mb-1">IP Intelligence Metrics</h3>
                <p class="text-gray-400 text-sm font-medium">Trace queries, inspect system caching, and monitor geolocation request patterns.</p>
            </div>

            <!-- Stats grid -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div v-for="s in [
                    { label: 'Total Searches', value: stats.total, color: 'blue', icon: 'M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z' },
                    { label: 'Searches Today', value: stats.today, color: 'purple', icon: 'M13 10V3L4 14h7v7l9-11h-7z' },
                    { label: 'Unique Targets', value: stats.unique_targets, color: 'emerald', icon: 'M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7' },
                    { label: 'Engine Status', value: stats.has_maxmind ? 'Local MMDB' : 'Fallback API', color: stats.has_maxmind ? 'indigo' : 'amber', icon: 'M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z' },
                ]" :key="s.label" class="bg-[#121826]/80 backdrop-blur-xl rounded-2xl border border-gray-800/80 p-5 shadow-xl transition-all group">
                    <div class="flex items-center justify-between mb-3">
                        <div :class="['p-2 rounded-lg bg-opacity-10 text-opacity-100 group-hover:scale-110 transition-transform', `bg-${s.color}-500 text-${s.color}-400`]">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="s.icon" /></svg>
                        </div>
                    </div>
                    <p class="text-[10px] font-bold text-gray-500 uppercase tracking-widest">{{ s.label }}</p>
                    <h4 :class="['text-xl lg:text-2xl font-black tracking-tighter mt-1', `text-${s.color}-400`]">{{ s.value }}</h4>
                </div>
            </div>

            <!-- Charts and Geography row -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Activity chart -->
                <div class="lg:col-span-2 bg-[#121826]/80 backdrop-blur-xl rounded-3xl border border-gray-800/80 p-6 shadow-2xl">
                    <h3 class="text-base font-bold text-white mb-6 uppercase tracking-widest text-gray-400">Search Activity Profile</h3>
                    <div class="h-[280px]">
                        <canvas ref="mainChartRef"></canvas>
                    </div>
                </div>

                <!-- Top Queried Countries -->
                <div class="bg-[#121826]/80 backdrop-blur-xl rounded-3xl border border-gray-800/80 p-6 shadow-2xl flex flex-col justify-between">
                    <div>
                        <h3 class="text-base font-bold text-white mb-6 uppercase tracking-widest text-gray-400">Top Geographies</h3>
                        <div class="space-y-5">
                            <div v-for="(tc, idx) in topCountries" :key="tc.code">
                                <div class="flex justify-between text-xs font-bold text-gray-300 mb-1.5">
                                    <span class="flex items-center gap-1.5">
                                        <span class="text-gray-500 font-mono">#{{ idx + 1 }}</span>
                                        <span>{{ tc.country }}</span>
                                    </span>
                                    <span class="font-mono text-blue-400">{{ tc.count }} searches</span>
                                </div>
                                <div class="h-2 bg-gray-800 rounded-full overflow-hidden">
                                    <div class="h-full bg-blue-500 transition-all duration-1000" :style="{ width: Math.min(100, (tc.count / stats.total) * 100) + '%' }"></div>
                                </div>
                            </div>
                            
                            <div v-if="topCountries.length === 0" class="text-xs text-gray-500 italic py-12 text-center bg-gray-900/30 border border-gray-850 rounded-2xl">
                                No geographic data queried yet.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Logs Directory -->
            <div class="bg-[#121826]/80 backdrop-blur-xl rounded-3xl border border-gray-800/80 shadow-2xl overflow-hidden">
                <div class="p-6 border-b border-gray-800/80 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-bold text-white mb-1">Search Directory</h3>
                        <p class="text-xs text-gray-400">Historical lookups processed by the platform geolocation cluster.</p>
                    </div>

                    <div class="w-full sm:w-72 relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-500">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <input
                            type="text"
                            v-model="searchQuery"
                            @input="handleSearch"
                            placeholder="Filter by IP, country, ISP..."
                            class="w-full bg-[#0B0F19] border border-gray-700 rounded-xl py-2 pl-10 pr-4 text-xs text-white focus:border-blue-500 transition-all outline-none"
                        />
                    </div>
                </div>

                <!-- Logs Table -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-gray-800/80">
                                <th class="px-6 py-5 text-[11px] font-bold text-gray-500 uppercase tracking-widest">Requester IP</th>
                                <th class="px-6 py-5 text-[11px] font-bold text-gray-500 uppercase tracking-widest">Searched IP</th>
                                <th class="px-6 py-5 text-[11px] font-bold text-gray-500 uppercase tracking-widest">Resolved Location</th>
                                <th class="px-6 py-5 text-[11px] font-bold text-gray-500 uppercase tracking-widest">ISP Operator</th>
                                <th class="px-6 py-5 text-[11px] font-bold text-gray-500 uppercase tracking-widest">Date / Time</th>
                                <th class="px-6 py-5 text-[11px] font-bold text-gray-500 uppercase tracking-widest text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-800/40">
                            <tr v-for="r in records.data" :key="r.id" class="hover:bg-gray-800/20 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-x-3">
                                        <div class="h-8 w-8 rounded-lg bg-[#0B0F19] border border-gray-700 flex items-center justify-center font-bold text-[10px] text-gray-400 font-mono">
                                            {{ r.username[0] }}
                                        </div>
                                        <div>
                                            <div class="text-xs font-bold text-white font-mono">{{ r.ip_address }}</div>
                                            <div class="text-[10px] text-gray-500">By {{ r.username }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-xs font-bold text-blue-400 font-mono">
                                    {{ r.searched_ip }}
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-x-2">
                                        <span v-if="r.country_code" class="px-1.5 py-0.5 rounded text-[9px] font-black bg-gray-800 text-gray-300 font-mono">
                                            {{ r.country_code }}
                                        </span>
                                        <span class="text-xs font-semibold text-white leading-none">
                                            {{ r.country_name || 'Internal Network' }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-xs text-gray-400 font-medium max-w-[200px] truncate" :title="r.isp">
                                    {{ r.isp || 'N/A' }}
                                </td>
                                <td class="px-6 py-4 text-xs text-gray-500 font-medium">
                                    {{ r.created_at }}
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <button
                                        @click="deleteRecord(r.id)"
                                        class="p-2 rounded-lg bg-red-500/10 text-red-400 hover:text-white hover:bg-red-500 transition-all cursor-pointer"
                                        title="Delete query log"
                                    >
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </td>
                            </tr>
                            
                            <tr v-if="records.data.length === 0">
                                <td colspan="6" class="px-6 py-12 text-center text-xs text-gray-500 italic">
                                    No records matching filters found.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination footer -->
                <div class="px-6 py-5 border-t border-gray-800/80 flex items-center justify-between">
                    <p class="text-xs text-gray-500 font-medium">Showing {{ records.from || 0 }} to {{ records.to || 0 }} of {{ records.total || 0 }} entries</p>
                    <div class="flex gap-x-2">
                        <button
                            v-for="link in records.links"
                            :key="link.label"
                            :disabled="!link.url || link.active"
                            @click="$inertia.visit(link.url)"
                            class="px-3 py-1.5 rounded-lg text-[10px] font-bold uppercase tracking-widest transition-all cursor-pointer"
                            :class="[
                                link.active ? 'bg-blue-600 text-white' : 'text-gray-400 hover:bg-gray-800',
                                !link.url ? 'opacity-30 cursor-not-allowed' : ''
                            ]"
                            v-html="link.label"
                        />
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
