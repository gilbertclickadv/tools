<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, onMounted, watch } from 'vue';
import Chart from 'chart.js/auto';
import axios from 'axios';

const props = defineProps({
    links:     Object,
    stats:     Object,
    chartData: Array,
    filters:   Object,
});

// Search
const search  = ref(props.filters?.search || '');
let searchTimeout = null;
watch(search, (val) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get(route('admin.url-shortener.index'), { search: val }, { preserveState: true, replace: true });
    }, 400);
});

// Chart
const clicksChartRef = ref(null);

onMounted(() => {
    if (clicksChartRef.value) {
        new Chart(clicksChartRef.value.getContext('2d'), {
            type: 'bar',
            data: {
                labels:   props.chartData.map(d => d.date),
                datasets: [{
                    label: 'Clicks',
                    data:  props.chartData.map(d => d.clicks),
                    backgroundColor: 'rgba(59, 130, 246, 0.5)',
                    borderColor:     '#3B82F6',
                    borderWidth: 2,
                    borderRadius: 6,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#1A2133',
                        titleColor: '#F3F4F6',
                        bodyColor:  '#9CA3AF',
                        borderColor: '#374151',
                        borderWidth: 1,
                        padding: 12,
                    },
                },
                scales: {
                    x: { grid: { display: false }, ticks: { color: '#6B7280', font: { size: 10 } } },
                    y: { beginAtZero: true, grid: { color: 'rgba(55,65,81,0.3)' }, ticks: { color: '#6B7280', font: { size: 10 }, stepSize: 1 } },
                },
            },
        });
    }
});

// Toggle / Delete
const toggle = (link) => {
    router.patch(route('admin.url-shortener.toggle', link.id), {}, { preserveScroll: true });
};

const destroy = (link) => {
    if (!confirm(`Permanently delete "${link.short_url}"?`)) return;
    router.delete(route('admin.url-shortener.destroy', link.id), { preserveScroll: true });
};

// Copy
const copiedId = ref(null);
const copy = async (url, id) => {
    await navigator.clipboard.writeText(url);
    copiedId.value = id;
    setTimeout(() => { copiedId.value = null; }, 2000);
};

// Stat cards (defined here to avoid apostrophe-in-attribute Vue parser errors)
const statCards = [
    { label: 'Total Links',  key: 'totalLinks',  color: 'blue',    icon: 'M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1' },
    { label: 'Total Clicks', key: 'totalClicks', color: 'purple',  icon: 'M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z' },
    { label: 'Active Links', key: 'activeLinks', color: 'emerald', icon: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z' },
    { label: 'Clicks Today', key: 'todayClicks', color: 'amber',   icon: 'M13 10V3L4 14h7v7l9-11h-7z' },
];
</script>

<template>
    <AdminLayout title="URL Shortener">
        <div class="space-y-8 animate-fade-in">

            <!-- Header -->
            <div class="flex items-start justify-between">
                <div>
                    <h3 class="text-2xl font-bold text-white mb-1">URL Shortener</h3>
                    <p class="text-gray-400 text-sm font-medium">Manage all shortened links and view click analytics.</p>
                </div>
                <Link :href="route('tools.url-shortener')" target="_blank"
                    class="inline-flex items-center gap-x-2 rounded-xl bg-blue-600 hover:bg-blue-500 px-5 py-2.5 text-sm font-bold text-white shadow-lg shadow-blue-600/20 transition-all">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" /></svg>
                    Open Tool
                </Link>
            </div>

            <!-- Stat Cards -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div v-for="stat in statCards" :key="stat.label"
                    :class="[
                        'bg-[#121826]/80 backdrop-blur-xl rounded-2xl border border-gray-800/80 p-5 shadow-xl transition-all group',
                        `hover:border-${stat.color}-500/40`,
                    ]">
                    <div class="flex items-center justify-between mb-3">
                        <div :class="`p-2 rounded-lg bg-${stat.color}-500/10 text-${stat.color}-400 group-hover:scale-110 transition-transform`">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="stat.icon" /></svg>
                        </div>
                    </div>
                    <p class="text-[10px] font-bold text-gray-500 uppercase tracking-widest">{{ stat.label }}</p>
                    <h4 :class="`text-2xl font-black text-${stat.color}-400 tracking-tighter mt-1`">{{ (stats[stat.key] || 0).toLocaleString() }}</h4>
                </div>
            </div>

            <!-- Click Chart -->
            <div class="bg-[#121826]/80 backdrop-blur-xl rounded-3xl border border-gray-800/80 p-6 lg:p-8 shadow-2xl">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h3 class="text-lg font-black text-white">Click Activity</h3>
                        <p class="text-[11px] text-gray-500 font-bold uppercase tracking-widest mt-0.5">Last 14 days</p>
                    </div>
                    <span class="inline-flex items-center gap-x-1.5 rounded-full bg-blue-500/10 px-3 py-1 text-[10px] font-black text-blue-400 border border-blue-500/20 uppercase tracking-widest">
                        <span class="h-1.5 w-1.5 rounded-full bg-blue-400 animate-pulse"></span>
                        Live
                    </span>
                </div>
                <div class="h-[200px]">
                    <canvas ref="clicksChartRef"></canvas>
                </div>
            </div>

            <!-- Links Table -->
            <div class="bg-[#121826]/80 backdrop-blur-xl rounded-3xl border border-gray-800/80 shadow-2xl overflow-hidden">
                <!-- Table Header -->
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-800/80 gap-x-4">
                    <h3 class="text-sm font-bold text-white shrink-0">All Links</h3>
                    <div class="flex-1 max-w-xs">
                        <div class="relative">
                            <svg class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                            <input v-model="search" type="text" placeholder="Search links..." class="w-full bg-[#0B0F19] border border-gray-700 rounded-xl py-2 pl-9 pr-4 text-sm text-white placeholder-gray-600 focus:border-blue-500 focus:outline-none transition-colors" />
                        </div>
                    </div>
                    <span class="text-[11px] text-gray-600 shrink-0">{{ links.total }} links</span>
                </div>

                <!-- Table -->
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-800/60">
                                <th class="text-left px-6 py-3 text-[10px] font-black text-gray-500 uppercase tracking-widest">Short URL</th>
                                <th class="text-left px-6 py-3 text-[10px] font-black text-gray-500 uppercase tracking-widest">Destination</th>
                                <th class="text-left px-4 py-3 text-[10px] font-black text-gray-500 uppercase tracking-widest">Clicks</th>
                                <th class="text-left px-4 py-3 text-[10px] font-black text-gray-500 uppercase tracking-widest">User</th>
                                <th class="text-left px-4 py-3 text-[10px] font-black text-gray-500 uppercase tracking-widest">Created</th>
                                <th class="text-left px-4 py-3 text-[10px] font-black text-gray-500 uppercase tracking-widest">Status</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-800/40">
                            <tr v-if="links.data.length === 0">
                                <td colspan="7" class="text-center py-16 text-gray-600 text-sm font-medium">No links found.</td>
                            </tr>
                            <tr v-for="link in links.data" :key="link.id" class="hover:bg-gray-800/20 transition-colors group">
                                <!-- Short URL -->
                                <td class="px-6 py-3.5">
                                    <div class="flex items-center gap-x-2">
                                        <span class="font-mono text-blue-400 text-xs font-bold truncate max-w-[140px]">fluxmedia.space/s/{{ link.slug }}</span>
                                        <button @click="copy(link.short_url, link.id)" class="h-6 w-6 rounded-lg bg-blue-500/10 hover:bg-blue-500/20 text-blue-400 flex items-center justify-center transition-colors shrink-0 opacity-0 group-hover:opacity-100">
                                            <svg v-if="copiedId !== link.id" class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
                                            <svg v-else class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                        </button>
                                    </div>
                                    <p v-if="link.title" class="text-[10px] text-gray-600 mt-0.5 truncate max-w-[160px]">{{ link.title }}</p>
                                </td>
                                <!-- Original URL -->
                                <td class="px-6 py-3.5 max-w-[200px]">
                                    <a :href="link.original_url" target="_blank" class="text-xs text-gray-400 hover:text-white transition-colors truncate block max-w-[200px]">{{ link.original_url }}</a>
                                </td>
                                <!-- Clicks -->
                                <td class="px-4 py-3.5">
                                    <span class="inline-flex items-center gap-x-1 rounded-full bg-purple-500/10 px-2.5 py-1 text-[10px] font-black text-purple-400 border border-purple-500/20">
                                        {{ link.click_count.toLocaleString() }}
                                    </span>
                                </td>
                                <!-- User -->
                                <td class="px-4 py-3.5">
                                    <span v-if="link.user" class="text-[11px] text-gray-400">{{ link.user.name }}</span>
                                    <span v-else class="text-[11px] text-gray-600 font-mono">{{ link.ip_address }}</span>
                                </td>
                                <!-- Created -->
                                <td class="px-4 py-3.5 text-[11px] text-gray-600 whitespace-nowrap">{{ link.created_at }}</td>
                                <!-- Status -->
                                <td class="px-4 py-3.5">
                                    <button @click="toggle(link)" :class="[
                                        'inline-flex items-center gap-x-1.5 rounded-full px-2.5 py-1 text-[9px] font-black uppercase tracking-widest border transition-all',
                                        link.is_active
                                            ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20 hover:bg-emerald-500/20'
                                            : 'bg-gray-800/60 text-gray-600 border-gray-700/40 hover:bg-gray-700/40',
                                    ]">
                                        <span :class="['h-1.5 w-1.5 rounded-full', link.is_active ? 'bg-emerald-400 animate-pulse' : 'bg-gray-600']"></span>
                                        {{ link.is_active ? 'Active' : 'Paused' }}
                                    </button>
                                </td>
                                <!-- Actions -->
                                <td class="px-4 py-3.5">
                                    <button @click="destroy(link)" class="h-7 w-7 rounded-lg bg-red-500/10 hover:bg-red-500/20 text-red-400 flex items-center justify-center transition-colors opacity-0 group-hover:opacity-100">
                                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="links.last_page > 1" class="flex items-center justify-between px-6 py-4 border-t border-gray-800/60">
                    <p class="text-[11px] text-gray-600">Page {{ links.current_page }} of {{ links.last_page }}</p>
                    <div class="flex items-center gap-x-2">
                        <Link v-if="links.prev_page_url" :href="links.prev_page_url" class="rounded-xl bg-gray-800 hover:bg-gray-700 px-4 py-2 text-xs font-bold text-white transition-colors">← Prev</Link>
                        <Link v-if="links.next_page_url" :href="links.next_page_url" class="rounded-xl bg-gray-800 hover:bg-gray-700 px-4 py-2 text-xs font-bold text-white transition-colors">Next →</Link>
                    </div>
                </div>
            </div>

        </div>
    </AdminLayout>
</template>
