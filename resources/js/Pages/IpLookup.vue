<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';

// Programmatic JSON-LD Injection
let ldScript = null;
import axios from 'axios';

const props = defineProps({
    userIp: String,
    initialIpData: Object,
});

const searchQuery = ref('');
const ipData = ref(null);
const isLoading = ref(false);
const errorMessage = ref('');

// Visitor active IPs
const detectedIpv4 = ref('');
const detectedIpv6 = ref('');
const isLoadingIps = ref(false);
const copiedText = ref('');

// Currency converter reactive states
const usdAmount = ref(100);
const localAmount = ref(0);
const selectedCurrency = ref(null);
const isDropdownOpen = ref(false);
const currencySearchQuery = ref('');
const selectorRef = ref(null);

const handleUsdChange = () => {
    if (selectedCurrency.value?.rate) {
        localAmount.value = roundValue(usdAmount.value * selectedCurrency.value.rate);
    } else {
        localAmount.value = 0;
    }
};

const handleLocalChange = () => {
    if (selectedCurrency.value?.inverse_rate) {
        usdAmount.value = roundValue(localAmount.value * selectedCurrency.value.inverse_rate);
    } else {
        usdAmount.value = 0;
    }
};

const roundValue = (val) => {
    return Math.round((val + Number.EPSILON) * 100) / 100;
};

const roundToFour = (val) => {
    return Math.round((val + Number.EPSILON) * 10000) / 10000;
};

// Update selected currency details dynamically
const updateSelectedCurrency = (code) => {
    if (!ipData.value?.currency) return;
    
    const meta = ipData.value.currency.all_currencies?.find(c => c.code === code) || {
        code: code,
        name: code === 'USD' ? 'US Dollar' : code,
        symbol: code === 'USD' ? '$' : ''
    };
    
    const allRates = ipData.value.currency.all_rates || {};
    let rate = 1.0;
    
    if (code !== 'USD') {
        rate = allRates[code] ? parseFloat(allRates[code]) : null;
    }
    
    const inverseRate = rate && rate > 0 ? 1 / rate : null;
    
    selectedCurrency.value = {
        code: code,
        name: meta.name,
        symbol: meta.symbol,
        rate: rate ? roundToFour(rate) : null,
        inverse_rate: inverseRate ? roundToFour(inverseRate) : null,
        has_rates: rate !== null
    };
    
    // Recalculate conversions
    handleUsdChange();
};

const selectCurrency = (code) => {
    updateSelectedCurrency(code);
    isDropdownOpen.value = false;
    currencySearchQuery.value = '';
};

// Computed property to filter the currencies
const filteredCurrencies = computed(() => {
    const list = ipData.value?.currency?.all_currencies || [];
    const q = currencySearchQuery.value.trim().toLowerCase();
    if (!q) return list;
    return list.filter(c => 
        c.code.toLowerCase().includes(q) || 
        c.name.toLowerCase().includes(q)
    );
});

// Click outside handling for dropdown
const handleClickOutside = (event) => {
    if (selectorRef.value && !selectorRef.value.contains(event.target)) {
        isDropdownOpen.value = false;
    }
};

// Watcher to initialize selected currency when IP data changes
watch(ipData, (newData) => {
    if (newData?.currency?.code) {
        updateSelectedCurrency(newData.currency.code);
    }
}, { immediate: true });

const copyToClipboard = (text, type) => {
    navigator.clipboard.writeText(text);
    copiedText.value = type;
    setTimeout(() => {
        copiedText.value = '';
    }, 2000);
};

const fetchIpv4 = async () => {
    // Primary: Ipify IPv4 (100% CORS guarantee)
    try {
        const res = await axios.get('https://api.ipify.org?format=json', { timeout: 3500 });
        if (res.data?.ip) {
            detectedIpv4.value = res.data.ip;
            return;
        }
    } catch (e) {
        console.warn('Ipify IPv4 failed, trying fallback:', e);
    }

    // Fallback: FreeIPAPI
    try {
        const res = await axios.get('https://freeipapi.com/api/json', { timeout: 3500 });
        if (res.data?.ipAddress && res.data.ipVersion === 4) {
            detectedIpv4.value = res.data.ipAddress;
            return;
        }
    } catch (e) {
        console.warn('FreeIPAPI failed:', e);
    }
};

const fetchIpv6 = async () => {
    // Primary: Ipify IPv6
    try {
        const res = await axios.get('https://api64.ipify.org?format=json', { timeout: 3500 });
        if (res.data?.ip && res.data.ip.includes(':')) {
            detectedIpv6.value = res.data.ip;
            return;
        }
    } catch (e) {
        console.warn('Ipify IPv6 failed, trying fallback:', e);
    }
};

const detectYourIPs = async () => {
    isLoadingIps.value = true;
    try {
        await Promise.allSettled([
            fetchIpv4(),
            fetchIpv6()
        ]);
    } catch (e) {
        console.error('IP detection error:', e);
    } finally {
        isLoadingIps.value = false;
    }
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
    // Register click outside handler for currency selector dropdown
    document.addEventListener('click', handleClickOutside);

    // Pre-populate connecting IP securely from server request proxy headers
    if (props.userIp) {
        if (props.userIp.includes(':')) {
            detectedIpv6.value = props.userIp;
        } else {
            detectedIpv4.value = props.userIp;
        }
    }

    if (props.initialIpData) {
        ipData.value = props.initialIpData;
        searchQuery.value = props.initialIpData.ip;
        
        // Reset and compute initial currency conversion
        usdAmount.value = 100;
        if (props.initialIpData.currency?.rate) {
            localAmount.value = roundValue(100 * props.initialIpData.currency.rate);
        }
    } else {
        // Initial fetch of user's own IP details as fallback
        fetchIpDetails(props.userIp);
    }

    // Detect visitor dual stack IP addresses asynchronously
    detectYourIPs();

    // Inject JSON-LD structured data
    ldScript = document.createElement('script');
    ldScript.type = 'application/ld+json';
    ldScript.textContent = JSON.stringify({
        '@context': 'https://schema.org',
        '@type': 'WebApplication',
        'name': 'FluxMedia IP Lookup & Geolocation',
        'url': 'https://fluxmedia.space/tools/ip-lookup',
        'description': 'Discover instant geolocation, ISP details, timezone coordinates, and currency exchange rates for any IP address with our high-speed lookup engine.',
        'applicationCategory': 'UtilityApplication',
        'operatingSystem': 'Web',
        'offers': {
            '@type': 'Offer',
            'price': '0',
            'priceCurrency': 'USD'
        }
    });
    document.head.appendChild(ldScript);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
    ldScript?.remove();
});
</script>

<template>
    <PublicLayout>
        <Head>
            <title>Free Online IP Lookup & IP Geolocation Finder | FluxMedia</title>

            <!-- Primary SEO -->
            <meta name="description" content="Discover instant geolocation, ISP details, timezone coordinates, and currency exchange rates for any IP address with our high-speed lookup engine." />
            <meta name="keywords" content="ip lookup, ip geolocation, find ip address, my ip address, track ip, ip details, autonomous system number, asn lookup, ip location finder, free network tool" />
            <meta name="author" content="FluxMedia" />
            <meta name="robots" content="index, follow" />
            <link rel="canonical" href="https://fluxmedia.space/tools/ip-lookup" />

            <!-- Open Graph / Facebook -->
            <meta property="og:type" content="website" />
            <meta property="og:title" content="Free Online IP Lookup & IP Geolocation Finder | FluxMedia" />
            <meta property="og:description" content="Discover instant geolocation, ISP details, and currency exchange rates for any IP address." />
            <meta property="og:image" content="https://fluxmedia.space/assets/images/fluxmedia_main.webp" />
            <meta property="og:url" content="https://fluxmedia.space/tools/ip-lookup" />
            <meta property="og:site_name" content="FluxMedia" />

            <!-- Twitter Card -->
            <meta name="twitter:card" content="summary_large_image" />
            <meta name="twitter:site" content="@fluxmedia" />
            <meta name="twitter:creator" content="@fluxmedia" />
            <meta name="twitter:title" content="Free Online IP Lookup & IP Geolocation Finder" />
            <meta name="twitter:description" content="Discover instant geolocation, ISP details, and currency exchange rates for any IP address." />
            <meta name="twitter:image" content="https://fluxmedia.space/assets/images/fluxmedia_main.webp" />
        </Head>

        <!-- Background Accents -->
        <div class="relative overflow-hidden pt-8 pb-4 text-center">
            <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[200px] bg-gradient-to-tr from-blue-600/10 via-indigo-600/5 to-purple-600/5 blur-[100px] rounded-full pointer-events-none"></div>

            <div class="relative mx-auto max-w-4xl px-6 space-y-3">
                <div class="flex items-center justify-center">
                    <span class="inline-flex items-center gap-x-1.5 rounded-full bg-blue-500/10 px-4 py-1.5 text-[10px] font-bold text-blue-300 border border-blue-500/20 uppercase tracking-[0.2em]">
                        <span class="h-1.5 w-1.5 rounded-full bg-blue-400 animate-pulse"></span>
                        Network Utility
                    </span>
                </div>

                <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-white leading-tight">
                    IP Address <span class="bg-gradient-to-r from-blue-400 via-indigo-400 to-purple-400 bg-clip-text text-transparent">Intelligence</span>
                </h1>
                <p class="text-xs text-gray-400 max-w-xl mx-auto leading-relaxed">
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

            <!-- Visitor Dual Stack IP Credentials Card -->
            <div class="bg-[#121826]/80 backdrop-blur-xl rounded-3xl border border-gray-800/80 p-6 shadow-xl max-w-2xl mx-auto">
                <h4 class="text-[10px] font-black uppercase text-gray-400 tracking-[0.2em] mb-4 flex items-center gap-2">
                    <span class="h-1.5 w-1.5 rounded-full bg-blue-500 animate-ping"></span>
                    Your Detected IP Configuration (Dual Stack)
                </h4>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- IPv4 Detection Column -->
                    <div class="bg-[#0B0F19]/60 rounded-2xl border border-gray-800/80 p-4 flex flex-col justify-between gap-y-3 relative group overflow-hidden">
                        <div class="flex justify-between items-start">
                            <span class="text-[10px] font-bold text-gray-500 uppercase tracking-widest">Visitor IPv4</span>
                            <span v-if="detectedIpv4" class="px-1.5 py-0.5 rounded text-[9px] font-black bg-blue-500/10 text-blue-400 border border-blue-500/20 uppercase tracking-wider">IPv4 Active</span>
                        </div>
                        <div class="flex items-center justify-between gap-2 overflow-hidden">
                            <span v-if="detectedIpv4" class="text-sm font-mono font-bold text-white truncate break-all select-all">{{ detectedIpv4 }}</span>
                            <span v-else-if="isLoadingIps" class="text-xs text-gray-500 italic animate-pulse">Detecting IPv4...</span>
                            <span v-else class="text-xs text-gray-500 italic">Not Detected</span>

                            <button 
                                v-if="detectedIpv4"
                                @click="copyToClipboard(detectedIpv4, 'ipv4')"
                                class="p-1.5 rounded-lg bg-gray-800 hover:bg-gray-700 text-gray-400 hover:text-white transition-all shrink-0 cursor-pointer"
                                title="Copy IPv4 Address"
                            >
                                <svg v-if="copiedText === 'ipv4'" class="h-3.5 w-3.5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                </svg>
                                <svg v-else class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3" />
                                </svg>
                            </button>
                        </div>
                        <button 
                            v-if="detectedIpv4"
                            @click="fetchIpDetails(detectedIpv4)"
                            class="text-[10px] font-bold text-blue-400 hover:text-blue-300 transition-colors uppercase tracking-widest text-left w-fit cursor-pointer flex items-center gap-1 group/btn"
                        >
                            Analyze IPv4 Network
                            <svg class="h-3 w-3 group-hover/btn:translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </button>
                    </div>

                    <!-- IPv6 Detection Column -->
                    <div class="bg-[#0B0F19]/60 rounded-2xl border border-gray-800/80 p-4 flex flex-col justify-between gap-y-3 relative group overflow-hidden">
                        <div class="flex justify-between items-start">
                            <span class="text-[10px] font-bold text-gray-500 uppercase tracking-widest">Visitor IPv6</span>
                            <span v-if="detectedIpv6" class="px-1.5 py-0.5 rounded text-[9px] font-black bg-purple-500/10 text-purple-400 border border-purple-500/20 uppercase tracking-wider">IPv6 Active</span>
                            <span v-else-if="!isLoadingIps && !detectedIpv6" class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-gray-850 text-gray-600 border border-gray-800 uppercase tracking-wider">No IPv6 Stack</span>
                        </div>
                        <div class="flex items-center justify-between gap-2 overflow-hidden">
                            <span v-if="detectedIpv6" class="text-xs font-mono font-bold text-white break-all leading-normal select-all overflow-hidden max-w-[82%]">{{ detectedIpv6 }}</span>
                            <span v-else-if="isLoadingIps" class="text-xs text-gray-500 italic animate-pulse">Detecting IPv6...</span>
                            <span v-else class="text-xs text-gray-500 italic">Not Detected / Disabled</span>

                            <button 
                                v-if="detectedIpv6"
                                @click="copyToClipboard(detectedIpv6, 'ipv6')"
                                class="p-1.5 rounded-lg bg-gray-800 hover:bg-gray-700 text-gray-400 hover:text-white transition-all shrink-0 cursor-pointer"
                                title="Copy IPv6 Address"
                            >
                                <svg v-if="copiedText === 'ipv6'" class="h-3.5 w-3.5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                </svg>
                                <svg v-else class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3" />
                                </svg>
                            </button>
                        </div>
                        <button 
                            v-if="detectedIpv6"
                            @click="fetchIpDetails(detectedIpv6)"
                            class="text-[10px] font-bold text-purple-400 hover:text-purple-300 transition-colors uppercase tracking-widest text-left w-fit cursor-pointer flex items-center gap-1 group/btn"
                        >
                            Analyze IPv6 Network
                            <svg class="h-3 w-3 group-hover/btn:translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </button>
                    </div>
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
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-gray-800/40 pb-2">
                                <span class="text-gray-500 font-medium">IP Address</span>
                                <div class="flex items-center gap-1.5 max-w-full overflow-hidden">
                                    <span class="text-white font-mono font-bold text-xs md:text-sm break-all text-right select-all truncate max-w-[180px] sm:max-w-none" :title="ipData.ip">
                                        {{ ipData.ip }}
                                    </span>
                                    <button 
                                        @click="copyToClipboard(ipData.ip, 'analyzed')"
                                        class="p-1 rounded bg-gray-800 hover:bg-gray-700 text-gray-400 hover:text-white transition-all shrink-0 cursor-pointer"
                                        title="Copy IP Address"
                                    >
                                        <svg v-if="copiedText === 'analyzed'" class="h-3.5 w-3.5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                        </svg>
                                        <svg v-else class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3" />
                                        </svg>
                                    </button>
                                </div>
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
                                {{ selectedCurrency?.code || ipData.currency.code }}
                            </span>
                        </div>

                        <!-- Currency Selector Dropdown -->
                        <div v-if="ipData.currency.has_rates && ipData.currency.all_currencies?.length" ref="selectorRef" class="relative z-20">
                            <label class="text-[10px] font-black uppercase text-gray-500 tracking-wider block mb-1">Target Currency</label>
                            <button
                                @click="isDropdownOpen = !isDropdownOpen"
                                class="w-full bg-[#0B0F19]/60 hover:bg-[#0B0F19] transition duration-200 border border-gray-800 rounded-xl px-3 py-2 flex items-center justify-between text-white text-xs font-bold focus:outline-none focus:ring-1 focus:ring-emerald-500/50"
                            >
                                <span class="flex items-center gap-2 truncate">
                                    <span class="text-emerald-400 font-black font-mono bg-emerald-500/10 px-1.5 py-0.5 rounded border border-emerald-500/20">
                                        {{ selectedCurrency?.code }}
                                    </span>
                                    <span class="text-gray-200 truncate">{{ selectedCurrency?.name }}</span>
                                </span>
                                <svg class="h-4 w-4 text-gray-400 transition-transform duration-200 shrink-0" :class="{ 'rotate-180': isDropdownOpen }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>

                            <!-- Dropdown Menu -->
                            <div
                                v-show="isDropdownOpen"
                                class="absolute left-0 right-0 mt-1 bg-[#121826]/95 border border-gray-800 rounded-2xl shadow-2xl z-50 overflow-hidden backdrop-blur-xl max-h-56 flex flex-col"
                            >
                                <!-- Search bar inside dropdown -->
                                <div class="p-2 border-b border-gray-800/60 shrink-0">
                                    <input
                                        type="text"
                                        v-model="currencySearchQuery"
                                        placeholder="Search currency..."
                                        class="w-full bg-[#0B0F19] text-white text-xs rounded-lg px-2.5 py-1.5 border border-gray-800 focus:outline-none focus:ring-1 focus:ring-emerald-500/50 focus:border-emerald-500/50"
                                    />
                                </div>
                                <!-- Currencies List -->
                                <div class="overflow-y-auto flex-1 divide-y divide-gray-850/40">
                                    <button
                                        v-for="currency in filteredCurrencies"
                                        :key="currency.code"
                                        @click="selectCurrency(currency.code)"
                                        class="w-full px-3 py-2 hover:bg-emerald-500/10 text-left transition duration-150 flex items-center justify-between text-xs text-gray-300 hover:text-white"
                                        :class="{ 'bg-emerald-500/5 font-bold text-white': selectedCurrency?.code === currency.code }"
                                    >
                                        <span class="truncate flex items-center gap-2">
                                            <span class="font-mono bg-gray-900 text-emerald-400 px-1.5 py-0.5 rounded border border-gray-800/80">{{ currency.code }}</span>
                                            <span>{{ currency.name }}</span>
                                        </span>
                                        <span class="text-gray-500 font-bold font-mono">{{ currency.symbol }}</span>
                                    </button>
                                    <div v-if="filteredCurrencies.length === 0" class="p-3 text-center text-xs text-gray-500 italic">
                                        No currencies found
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Currency Info -->
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between border-b border-gray-800/40 pb-2">
                                <span class="text-gray-500 font-medium">Name</span>
                                <span class="text-white font-bold">{{ selectedCurrency?.name || ipData.currency.name }} ({{ selectedCurrency?.symbol || ipData.currency.symbol }})</span>
                            </div>
                            <div v-if="selectedCurrency?.has_rates" class="flex justify-between border-b border-gray-800/40 pb-2">
                                <span class="text-gray-500 font-medium">USD base rate</span>
                                <span class="text-white font-bold font-mono">1 USD = {{ selectedCurrency.rate }} {{ selectedCurrency.code }}</span>
                            </div>
                            <div v-if="selectedCurrency?.has_rates" class="flex justify-between">
                                <span class="text-gray-500 font-medium">Inverse rate</span>
                                <span class="text-white font-bold font-mono">1 {{ selectedCurrency.code }} = {{ selectedCurrency.inverse_rate }} USD</span>
                            </div>
                            <div v-else class="text-xs text-gray-500 italic mt-2 text-center bg-gray-900/40 p-2 rounded-xl border border-gray-850">
                                Real-time exchange rates not available for this localized environment.
                            </div>
                        </div>

                        <!-- Interactive Micro-Conversion Tool -->
                        <div v-if="selectedCurrency?.has_rates" class="mt-4 pt-4 border-t border-gray-800/60 space-y-3">
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
                                    <span class="absolute right-2 top-2 text-[10px] font-black text-gray-500">{{ selectedCurrency.code }}</span>
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
