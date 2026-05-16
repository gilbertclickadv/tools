<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, onMounted, watch } from 'vue';
import Chart from 'chart.js/auto';

const props = defineProps({
    records:   Object,
    stats:     Object,
    chartData: Array,
    filters:   Object,
});

const search = ref(props.filters?.search || '');
let searchTimeout = null;
watch(search, (val) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get(route('admin.uuid-generator.index'), { search: val }, { preserveState: true, replace: true });
    }, 400);
});

const chartRef = ref(null);
onMounted(() => {
    if (chartRef.value) {
        new Chart(chartRef.value.getContext('2d'), {
            type: 'line',
            data: {
                labels: props.chartData.map(d => d.date),
                datasets: [{
                    label: 'UUIDs Generated',
                    data: props.chartData.map(d => d.count),
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    borderColor: '#10B981',
                    borderWidth: 2,
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: '#10B981',
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

const destroy = (record) => {
    if (!confirm('Permanently delete this generation record?')) return;
    router.delete(route('admin.uuid-generator.destroy', record.id), { preserveScroll: true });
};
</script>

<template>
    <AdminLayout title="UUID Generator Admin">
        <div class="space-y-8 animate-fade-in">
            <!-- Header -->
            <div class="flex items-start justify-between">
                <div>
                    <h3 class="text-2xl font-bold text-white mb-1">UUID Analytics</h3>
                    <p class="text-gray-400 text-sm font-medium">Monitor system-wide unique identifier generation.</p>
                </div>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div v-for="s in [
                    { label: 'Total UUIDs', value: stats.total,   color: 'emerald', icon: 'M7 20l4-16m2 16l4-16M6 9h14M4 15h14' },
                    { label: 'Today',       value: stats.today,   color: 'blue',    icon: 'M13 10V3L4 14h7v7l9-11h-7z' },
                    { label: 'v4 (Random)', value: stats.v4count, color: 'purple',  icon: 'M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.022.547l-2.387 2.387a2 2 0 102.828 2.828l.675-.675' },
                    { label: 'v1 (Time)',   value: stats.v1count, color: 'amber',   icon: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z' },
                ]" :key="s.label" class="bg-[#121826]/80 backdrop-blur-xl rounded-2xl border border-gray-800/80 p-5 shadow-xl transition-all hover:border-gray-700/50 group">
                    <div class="flex items-center justify-between mb-3">
                        <div :class="['p-2 rounded-lg bg-opacity-10 text-opacity-100 group-hover:scale-110 transition-transform', `bg-${s.color}-500 text-${s.color}-400`]">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="s.icon" /></svg>
                        </div>
                    </div>
                    <p class="text-[10px] font-bold text-gray-500 uppercase tracking-widest">{{ s.label }}</p>
                    <h4 :class="['text-2xl font-black tracking-tighter mt-1', `text-${s.color}-400`]">{{ s.value.toLocaleString() }}</h4>
                </div>
            </div>

            <!-- Activity Chart -->
            <div class="bg-[#121826]/80 backdrop-blur-xl rounded-3xl border border-gray-800/80 p-6 lg:p-8 shadow-2xl">
                <h3 class="text-lg font-black text-white mb-6">Generation Activity</h3>
                <div class="h-[250px]">
                    <canvas ref="chartRef"></canvas>
                </div>
            </div>

            <!-- Table -->
            <div class="bg-[#121826]/80 backdrop-blur-xl rounded-3xl border border-gray-800/80 shadow-2xl overflow-hidden">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-800/80 gap-x-4">
                    <h3 class="text-sm font-bold text-white shrink-0">Recent Generation Log</h3>
                    <div class="flex-1 max-w-xs">
                        <div class="relative">
                            <svg class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                            <input v-model="search" type="text" placeholder="Search by UUID..." class="w-full bg-[#0B0F19] border border-gray-700 rounded-xl py-2 pl-9 pr-4 text-sm text-white focus:border-emerald-500 focus:outline-none transition-colors" />
                        </div>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-800/60">
                                <th class="text-left px-6 py-3 text-[10px] font-black text-gray-500 uppercase tracking-widest">UUID</th>
                                <th class="text-left px-6 py-3 text-[10px] font-black text-gray-500 uppercase tracking-widest">Ver</th>
                                <th class="text-left px-4 py-3 text-[10px] font-black text-gray-500 uppercase tracking-widest">User/IP</th>
                                <th class="text-left px-4 py-3 text-[10px] font-black text-gray-500 uppercase tracking-widest">Created</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-800/40">
                            <tr v-for="r in records.data" :key="r.id" class="hover:bg-gray-800/20 transition-colors group">
                                <td class="px-6 py-3.5"><code class="text-xs font-mono text-emerald-400 font-bold">{{ r.uuid }}</code></td>
                                <td class="px-6 py-3.5"><span class="text-[10px] font-black bg-gray-800 text-gray-400 px-2 py-0.5 rounded-full border border-gray-700">{{ r.version }}</span></td>
                                <td class="px-4 py-3.5">
                                    <p class="text-[11px] text-gray-300 font-bold">{{ r.user?.name || 'Guest' }}</p>
                                    <p class="text-[9px] text-gray-600 font-mono">{{ r.ip_address }}</p>
                                </td>
                                <td class="px-4 py-3.5 text-[11px] text-gray-600">{{ r.created_at }}</td>
                                <td class="px-4 py-3.5 text-right">
                                    <button @click="destroy(r)" class="h-8 w-8 rounded-lg bg-red-500/10 hover:bg-red-500/20 text-red-400 flex items-center justify-center transition-colors opacity-0 group-hover:opacity-100"><svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg></button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="records.last_page > 1" class="px-6 py-4 border-t border-gray-800/60 flex items-center justify-between">
                    <p class="text-[11px] text-gray-600">Page {{ records.current_page }} of {{ records.last_page }}</p>
                    <div class="flex gap-x-2">
                        <Link v-if="records.prev_page_url" :href="records.prev_page_url" class="rounded-xl bg-gray-800 hover:bg-gray-700 px-4 py-2 text-xs font-bold text-white transition-colors">← Prev</Link>
                        <Link v-if="records.next_page_url" :href="records.next_page_url" class="rounded-xl bg-gray-800 hover:bg-gray-700 px-4 py-2 text-xs font-bold text-white transition-colors">Next →</Link>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
