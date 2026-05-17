<script setup>
import { ref, onMounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import axios from 'axios';

const props = defineProps({
    userIp: String,
});

const searchQuery = ref('');
const ipData = ref(null);
const isLoading = ref(false);
const errorMessage = ref('');

// Currency converter reactive states
const usdAmount = ref(100);
const localAmount = ref(0);

const handleUsdChange = () => {
    if (ipData.value?.currency?.rate) {
        localAmount.value = roundValue(usdAmount.value * ipData.value.currency.rate);
    }
};

const handleLocalChange = () => {
    if (ipData.value?.currency?.inverse_rate) {
        usdAmount.value = roundValue(localAmount.value * ipData.value.currency.inverse_rate);
    }
};

const roundValue = (val) => {
    return Math.round((val + Number.EPSILON) * 100) / 100;
};

const fetchIpDetails = async (ipAddress = '') => {
    isLoading.value = true;
    errorMessage.value = '';
    
    try {
        const response = await axios.post(route('ip.lookup'), {
            ip: ipAddress,
        });
        
        ipData.value = response.data;
        searchQuery.value = response.data.ip;
        
        // Reset and compute initial currency conversion
        usdAmount.value = 100;
        if (response.data?.currency?.rate) {
            localAmount.value = roundValue(100 * response.data.currency.rate);
        }
    } catch (error) {
        console.error(error);
        if (error.response?.data?.message) {
            errorMessage.value = error.response.data.message;
        } else {
            errorMessage.value = 'Failed to look up IP details. Please try again.';
        }
    } finally {
        isLoading.value = false;
    }
};

onMounted(() => {
    // Initial fetch of user's own IP details
    fetchIpDetails(props.userIp);
});
</script>

<template>
    <PublicLayout>
        <Head>
            <title>IP Lookup & Geolocation · FluxMedia</title>
            <meta name="description" content="Discover instant geolocation, ISP details, timezone coordinates, and currency exchange rates for any IP address with our high-speed lookup engine." />
        </Head>

        <!-- Background Accents -->
        <div class="relative overflow-hidden pt-12 pb-6 text-center">
            <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[250px] bg-gradient-to-tr from-blue-600/10 via-indigo-600/5 to-purple-600/5 blur-[100px] rounded-full pointer-events-none"></div>

            <div class="relative mx-auto max-w-4xl px-6">
                <div class="flex items-center justify-center gap-x-2.5 mb-6">
                    <span class="inline-flex items-center gap-x-1.5 rounded-full bg-blue-500/10 px-4 py-1.5 text-[10px] font-bold text-blue-300 border border-blue-500/20 uppercase tracking-[0.2em]">
                        <span class="h-1.5 w-1.5 rounded-full bg-blue-400 animate-pulse"></span>
                        Network Utility
                    </span>
                </div>

                <h1 class="text-3xl lg:text-5xl font-extrabold tracking-tight text-white mb-4 leading-tight">
                    IP Address <span class="bg-gradient-to-r from-blue-400 via-indigo-400 to-purple-400 bg-clip-text text-transparent">Intelligence</span>
                </h1>
                <p class="text-xs lg:text-sm text-gray-400 font-medium max-w-xl mx-auto leading-relaxed">
                    Retrieve high-fidelity physical coordinates, Autonomous System Number (ASN) mappings, and real-time financial currency profiles instantly.
                </p>
            </div>
        </div>

        <div class="mx-auto max-w-5xl px-4 pb-24 space-y-8 relative z-10">
            <!-- Search & Control Panel -->
            <div class="bg-[#121826]/80 backdrop-blur-xl rounded-3xl border border-gray-800/80 p-6 shadow-xl max-w-2xl mx-auto">
                <form @submit.prevent="fetchIpDetails(searchQuery)" class="flex flex-col sm:flex-row gap-3">
                    <div class="relative flex-1">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-gray-500">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <input
                            type="text"
                            v-model="searchQuery"
                            placeholder="Enter IP Address (e.g. 8.8.8.8 or 2001:4860:4860::8888)"
                            class="w-full bg-[#0B0F19] border border-gray-700 rounded-2xl py-3.5 pl-12 pr-4 text-white text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500/50 transition-all outline-none"
                            :disabled="isLoading"
                        />
                    </div>
                    
                    <button
                        type="submit"
                        :disabled="isLoading"
                        class="inline-flex items-center justify-center gap-x-2 rounded-2xl bg-blue-600 hover:bg-blue-500 text-white font-bold px-8 py-3.5 text-sm shadow-lg shadow-blue-600/20 transition-all duration-200 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed shrink-0"
                    >
                        <svg v-if="isLoading" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span>Analyze Route</span>
                    </button>
                </form>

                <!-- Search IP Error Banner -->
                <div v-if="errorMessage" class="mt-4 rounded-xl bg-red-500/10 border border-red-500/20 p-3 text-xs text-red-400 flex items-center gap-x-2">
                    <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <span class="font-bold">{{ errorMessage }}</span>
                </div>
            </div>

            <!-- Dashboard Skeleton / Loading state -->
            <div v-if="isLoading && !ipData" class="grid grid-cols-1 md:grid-cols-3 gap-6 animate-pulse">
                <div class="md:col-span-2 h-[220px] bg-[#121826]/40 rounded-3xl border border-gray-800"></div>
                <div class="h-[220px] bg-[#121826]/40 rounded-3xl border border-gray-800"></div>
                <div class="h-[300px] bg-[#121826]/40 rounded-3xl border border-gray-800"></div>
                <div class="md:col-span-2 h-[300px] bg-[#121826]/40 rounded-3xl border border-gray-800"></div>
            </div>

            <!-- Main Results Grid -->
            <div v-else-if="ipData" class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- 1. Geolocation Card -->
                <div class="md:col-span-2 bg-[#121826]/80 backdrop-blur-xl rounded-3xl border border-gray-800 p-6 flex flex-col justify-between shadow-xl">
                    <div class="space-y-4">
                        <div class="flex items-center justify-between pb-3 border-b border-gray-850">
                            <h4 class="text-sm font-black uppercase text-white tracking-widest flex items-center gap-2">
                                <svg class="h-5 w-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                Geolocation Coordinates
                            </h4>
                            <span class="text-xs px-2.5 py-0.5 rounded-full font-bold bg-blue-500/10 text-blue-400 border border-blue-500/20">
                                {{ ipData.country_code }}
                            </span>
                        </div>

                        <div class="grid grid-cols-2 gap-x-6 gap-y-4 text-sm">
                            <div class="space-y-1">
                                <p class="text-gray-500 text-xs font-bold uppercase tracking-wider">Country</p>
                                <p class="text-white font-bold">{{ ipData.country_name || 'N/A' }}</p>
                            </div>
                            <div class="space-y-1">
                                <p class="text-gray-500 text-xs font-bold uppercase tracking-wider">City / Settlement</p>
                                <p class="text-white font-bold">{{ ipData.city_name || 'N/A' }}</p>
                            </div>
                            <div class="space-y-1">
                                <p class="text-gray-500 text-xs font-bold uppercase tracking-wider">Region / State</p>
                                <p class="text-white font-bold">{{ ipData.region_name || 'N/A' }}</p>
                            </div>
                            <div class="space-y-1">
                                <p class="text-gray-500 text-xs font-bold uppercase tracking-wider">Postal / Zip Code</p>
                                <p class="text-white font-bold font-mono">{{ ipData.zip_code || 'N/A' }}</p>
                            </div>
                            <div class="space-y-1">
                                <p class="text-gray-500 text-xs font-bold uppercase tracking-wider">Timezone</p>
                                <p class="text-white font-bold">{{ ipData.timezone || 'N/A' }}</p>
                            </div>
                            <div class="space-y-1">
                                <p class="text-gray-500 text-xs font-bold uppercase tracking-wider">Latitude / Longitude</p>
                                <p class="text-white font-bold font-mono">{{ ipData.latitude }}, {{ ipData.longitude }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. Connection Details Card -->
                <div class="bg-[#121826]/80 backdrop-blur-xl rounded-3xl border border-gray-800 p-6 flex flex-col justify-between shadow-xl">
                    <div class="space-y-4">
                        <div class="flex items-center justify-between pb-3 border-b border-gray-850">
                            <h4 class="text-sm font-black uppercase text-white tracking-widest flex items-center gap-2">
                                <svg class="h-5 w-5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
                                </svg>
                                Network Routing
                            </h4>
                        </div>

                        <div class="space-y-3.5 text-sm">
                            <div class="flex justify-between border-b border-gray-800/40 pb-2">
                                <span class="text-gray-500 font-medium">IP Address</span>
                                <span class="text-white font-bold font-mono">{{ ipData.ip }}</span>
                            </div>
                            <div class="flex justify-between border-b border-gray-800/40 pb-2">
                                <span class="text-gray-500 font-medium">Protocol</span>
                                <span class="text-white font-bold">IPv{{ ipData.ip_version }}</span>
                            </div>
                            <div class="flex justify-between border-b border-gray-800/40 pb-2">
                                <span class="text-gray-500 font-medium">ASN</span>
                                <span class="text-white font-bold font-mono">AS{{ ipData.asn || 'N/A' }}</span>
                            </div>
                            <div class="flex flex-col gap-y-1">
                                <span class="text-gray-500 font-medium">ISP Provider</span>
                                <span class="text-white font-bold line-clamp-1 leading-snug">{{ ipData.isp || 'N/A' }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 3. Financial Currency Profile Card (FREECURRENCY_API_KEY Fallback rates) -->
                <div class="bg-[#121826]/80 backdrop-blur-xl rounded-3xl border border-gray-800 p-6 flex flex-col justify-between shadow-xl">
                    <div class="space-y-4">
                        <div class="flex items-center justify-between pb-3 border-b border-gray-850">
                            <h4 class="text-sm font-black uppercase text-white tracking-widest flex items-center gap-2">
                                <svg class="h-5 w-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Local Currency Exchange
                            </h4>
                            <span class="text-xs px-2.5 py-0.5 rounded-full font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                {{ ipData.currency.code }}
                            </span>
                        </div>

                        <!-- Currency Info -->
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between border-b border-gray-800/40 pb-2">
                                <span class="text-gray-500 font-medium">Name</span>
                                <span class="text-white font-bold">{{ ipData.currency.name }} ({{ ipData.currency.symbol }})</span>
                            </div>
                            <div v-if="ipData.currency.has_rates" class="flex justify-between border-b border-gray-800/40 pb-2">
                                <span class="text-gray-500 font-medium">USD base rate</span>
                                <span class="text-white font-bold font-mono">1 USD = {{ ipData.currency.rate }} {{ ipData.currency.code }}</span>
                            </div>
                            <div v-if="ipData.currency.has_rates" class="flex justify-between">
                                <span class="text-gray-500 font-medium">Inverse rate</span>
                                <span class="text-white font-bold font-mono">1 {{ ipData.currency.code }} = {{ ipData.currency.inverse_rate }} USD</span>
                            </div>
                            <div v-else class="text-xs text-gray-500 italic mt-2 text-center bg-gray-900/40 p-2 rounded-xl border border-gray-850">
                                Real-time exchange rates not available for this localized environment.
                            </div>
                        </div>

                        <!-- Interactive Micro-Conversion Tool -->
                        <div v-if="ipData.currency.has_rates" class="mt-4 pt-4 border-t border-gray-800/60 space-y-3">
                            <p class="text-[10px] font-black uppercase text-gray-400 tracking-wider">Currency Exchange Calculator</p>
                            <div class="grid grid-cols-2 gap-2">
                                <div class="bg-[#0B0F19] rounded-xl p-2 border border-gray-800 relative">
                                    <span class="absolute right-2 top-2 text-[10px] font-black text-gray-500">USD</span>
                                    <input
                                        type="number"
                                        v-model="usdAmount"
                                        @input="handleUsdChange"
                                        class="w-full bg-transparent border-none text-white text-xs font-bold font-mono p-0 focus:ring-0 outline-none"
                                    />
                                </div>
                                <div class="bg-[#0B0F19] rounded-xl p-2 border border-gray-800 relative">
                                    <span class="absolute right-2 top-2 text-[10px] font-black text-gray-500">{{ ipData.currency.code }}</span>
                                    <input
                                        type="number"
                                        v-model="localAmount"
                                        @input="handleLocalChange"
                                        class="w-full bg-transparent border-none text-white text-xs font-bold font-mono p-0 focus:ring-0 outline-none"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 4. Interactive Geolocation Map Card -->
                <div class="md:col-span-2 bg-[#121826]/80 backdrop-blur-xl rounded-3xl border border-gray-800 p-6 flex flex-col shadow-xl overflow-hidden min-h-[360px]">
                    <div class="flex items-center justify-between pb-3 border-b border-gray-850 mb-4 shrink-0">
                        <h4 class="text-sm font-black uppercase text-white tracking-widest flex items-center gap-2">
                            <svg class="h-5 w-5 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                            </svg>
                            Physical Geolocation Map
                        </h4>
                        <span class="text-[10px] font-bold text-gray-500">Coordinates: {{ ipData.latitude }}, {{ ipData.longitude }}</span>
                    </div>

                    <!-- Map Frame -->
                    <div class="flex-1 bg-[#0B0F19] rounded-2xl overflow-hidden border border-gray-850 relative group">
                        <!-- Custom styled Google Maps iframe embed -->
                        <iframe
                            :src="`https://maps.google.com/maps?q=${ipData.latitude},${ipData.longitude}&z=11&output=embed`"
                            class="w-full h-full border-none rounded-2xl saturate-[0.8] contrast-[1.05] invert-[0.9] hue-rotate-[180deg]"
                            allowfullscreen=""
                            loading="lazy"
                        ></iframe>
                    </div>
                </div>
            </div>
        </div>
    </PublicLayout>
</template>
