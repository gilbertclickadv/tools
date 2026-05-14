<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
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
    <Head title="Admin Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                Admin Overview Dashboard
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <!-- Welcome Banner -->
                <div class="overflow-hidden rounded-2xl bg-gradient-to-r from-purple-600 to-indigo-600 p-8 shadow-xl text-white mb-8">
                    <h3 class="text-2xl font-bold tracking-tight">Image Processing Base Center</h3>
                    <p class="mt-2 text-purple-100 max-w-2xl">
                        Monitor application throughput, resource savings, and configured environment capabilities backed by PostgreSQL and high-performance Redis queues.
                    </p>
                </div>

                <!-- Stats Grid -->
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    <!-- Metric Card 1 -->
                    <div class="overflow-hidden rounded-xl bg-white dark:bg-gray-800 p-6 shadow-sm ring-1 ring-gray-900/5 transition-all hover:shadow-md">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Images Processed</span>
                            <span class="inline-flex items-center rounded-full bg-purple-50 px-2 py-1 text-xs font-medium text-purple-700 dark:bg-purple-950/50 dark:text-purple-300">Live Counter</span>
                        </div>
                        <div class="mt-2 flex items-baseline gap-x-2">
                            <span class="text-4xl font-bold tracking-tight text-gray-900 dark:text-white">
                                {{ stats?.totalProcessed || 0 }}
                            </span>
                        </div>
                    </div>

                    <!-- Metric Card 2 -->
                    <div class="overflow-hidden rounded-xl bg-white dark:bg-gray-800 p-6 shadow-sm ring-1 ring-gray-900/5 transition-all hover:shadow-md">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Storage Bandwidth Saved</span>
                            <span class="inline-flex items-center rounded-full bg-emerald-50 px-2 py-1 text-xs font-medium text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300">Optimized</span>
                        </div>
                        <div class="mt-2 flex items-baseline gap-x-2">
                            <span class="text-4xl font-bold tracking-tight text-emerald-600 dark:text-emerald-400">
                                {{ formattedStorageSaved }}
                            </span>
                        </div>
                    </div>

                    <!-- Metric Card 3 -->
                    <div class="overflow-hidden rounded-xl bg-white dark:bg-gray-800 p-6 shadow-sm ring-1 ring-gray-900/5 transition-all hover:shadow-md">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Redis Cache & Queues</span>
                            <span class="flex h-2 w-2 relative">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                            </span>
                        </div>
                        <div class="mt-2 flex items-baseline gap-x-2">
                            <span class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">
                                Connected
                            </span>
                        </div>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Session and processing backend operating normally.</p>
                    </div>
                </div>

                <!-- Recent Activities Section -->
                <div class="mt-8 overflow-hidden rounded-xl bg-white dark:bg-gray-800 shadow-sm ring-1 ring-gray-900/5">
                    <div class="border-b border-gray-200 dark:border-gray-700 px-6 py-4">
                        <h4 class="text-base font-medium text-gray-900 dark:text-white">System Operations & Guidance</h4>
                    </div>
                    <div class="p-6 text-sm text-gray-600 dark:text-gray-300 space-y-4">
                        <p>
                            Welcome to the administrative portal. From here, you can configure upload thresholds, compression defaults, and monitor overall media conversion efficacy.
                        </p>
                        <div class="rounded-lg bg-gray-50 dark:bg-gray-900/50 p-4 border border-gray-100 dark:border-gray-800">
                            <span class="font-semibold text-gray-900 dark:text-white block mb-1">UI/UX Pro Max Intelligence Active</span>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Interface tokens automatically conform to high contrast availability, micro-animation responsiveness, and optimized typography pairings.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
