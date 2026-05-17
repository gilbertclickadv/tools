<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    settings: Object,
    maxmind: Object,
    status: String,
    error: String,
});

const form = useForm({
    maxUploadSizeMb: props.settings?.maxUploadSizeMb || 20,
    defaultQuality: props.settings?.defaultQuality || 80,
    guestRetentionHours: props.settings?.guestRetentionHours || 6,
    authRetentionDays: props.settings?.authRetentionDays || 7,
    guestDailyLimit: props.settings?.guestDailyLimit || 15,
});

const isUpdatingMaxmind = ref(false);

const updateSettings = () => {
    form.post(route('admin.settings.update'), {
        preserveScroll: true,
    });
};

const updateMaxmindDb = () => {
    isUpdatingMaxmind.value = true;
    router.post(route('admin.settings.maxmind.update'), {}, {
        preserveScroll: true,
        onFinish: () => {
            isUpdatingMaxmind.value = false;
        }
    });
};
</script>

<template>
    <AdminLayout title="System Settings">
        <div class="max-w-4xl animate-fade-in space-y-10">
            <div>
                <h3 class="text-2xl font-bold text-white mb-2">Environment Configuration</h3>
                <p class="text-gray-400 text-sm font-medium">Fine-tune the media processing engine and data lifecycle parameters.</p>
            </div>

            <!-- Settings Alerts -->
            <div v-if="status" class="rounded-2xl bg-emerald-500/10 p-4 text-sm text-emerald-400 border border-emerald-500/20 flex items-center gap-x-3">
                <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span class="font-bold">{{ status }}</span>
            </div>

            <div v-if="error" class="rounded-2xl bg-red-500/10 p-4 text-sm text-red-400 border border-red-500/20 flex items-center gap-x-3">
                <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <span class="font-bold">{{ error }}</span>
            </div>

            <!-- Config Form -->
            <div class="bg-[#121826]/80 backdrop-blur-xl rounded-3xl border border-gray-800/80 p-8 sm:p-10 shadow-2xl overflow-hidden relative">
                <form @submit.prevent="updateSettings" class="grid grid-cols-1 md:grid-cols-2 gap-x-12 gap-y-8">
                    <!-- Setting 1 -->
                    <div class="space-y-3">
                        <label for="maxUploadSizeMb" class="block text-xs font-bold text-gray-400 uppercase tracking-widest">
                            Max Upload Payload (MB)
                        </label>
                        <input
                            id="maxUploadSizeMb"
                            type="number"
                            v-model="form.maxUploadSizeMb"
                            class="w-full bg-[#0B0F19] border border-gray-700 rounded-xl py-3 px-4 text-white text-sm focus:border-purple-500 focus:ring-1 focus:ring-purple-500/50 transition-all outline-none"
                        />
                        <p class="text-[11px] text-gray-500 leading-relaxed font-medium">Cap for single client payload requests to ensure server stability.</p>
                        <div v-if="form.errors.maxUploadSizeMb" class="mt-1 text-xs text-red-400 font-bold">
                            {{ form.errors.maxUploadSizeMb }}
                        </div>
                    </div>

                    <!-- Setting 2 -->
                    <div class="space-y-3">
                        <label for="defaultQuality" class="block text-xs font-bold text-gray-400 uppercase tracking-widest">
                            Compression Fidelity (%)
                        </label>
                        <input
                            id="defaultQuality"
                            type="number"
                            v-model="form.defaultQuality"
                            class="w-full bg-[#0B0F19] border border-gray-700 rounded-xl py-3 px-4 text-white text-sm focus:border-purple-500 focus:ring-1 focus:ring-purple-500/50 transition-all outline-none"
                        />
                        <p class="text-[11px] text-gray-500 leading-relaxed font-medium">Default target export quality for lossy conversion pipelines.</p>
                        <div v-if="form.errors.defaultQuality" class="mt-1 text-xs text-red-400 font-bold">
                            {{ form.errors.defaultQuality }}
                        </div>
                    </div>

                    <!-- Setting 3 -->
                    <div class="space-y-3">
                        <label for="guestRetentionHours" class="block text-xs font-bold text-gray-400 uppercase tracking-widest">
                            Guest Asset Lifecycle (Hours)
                        </label>
                        <input
                            id="guestRetentionHours"
                            type="number"
                            v-model="form.guestRetentionHours"
                            class="w-full bg-[#0B0F19] border border-gray-700 rounded-xl py-3 px-4 text-white text-sm focus:border-purple-500 focus:ring-1 focus:ring-purple-500/50 transition-all outline-none"
                        />
                        <p class="text-[11px] text-gray-500 leading-relaxed font-medium">Duration before anonymous files are purged from storage.</p>
                        <div v-if="form.errors.guestRetentionHours" class="mt-1 text-xs text-red-400 font-bold">
                            {{ form.errors.guestRetentionHours }}
                        </div>
                    </div>

                    <!-- Setting 4 -->
                    <div class="space-y-3">
                        <label for="authRetentionDays" class="block text-xs font-bold text-gray-400 uppercase tracking-widest">
                            Member Storage Window (Days)
                        </label>
                        <input
                            id="authRetentionDays"
                            type="number"
                            v-model="form.authRetentionDays"
                            class="w-full bg-[#0B0F19] border border-gray-700 rounded-xl py-3 px-4 text-white text-sm focus:border-purple-500 focus:ring-1 focus:ring-purple-500/50 transition-all outline-none"
                        />
                        <p class="text-[11px] text-gray-500 leading-relaxed font-medium">Lifecycle window for files produced by authenticated accounts.</p>
                        <div v-if="form.errors.authRetentionDays" class="mt-1 text-xs text-red-400 font-bold">
                            {{ form.errors.authRetentionDays }}
                        </div>
                    </div>

                    <!-- Setting 5 -->
                    <div class="space-y-3">
                        <label for="guestDailyLimit" class="block text-xs font-bold text-gray-400 uppercase tracking-widest">
                            Anonymous IP Quota (Files/Day)
                        </label>
                        <input
                            id="guestDailyLimit"
                            type="number"
                            v-model="form.guestDailyLimit"
                            class="w-full bg-[#0B0F19] border border-gray-700 rounded-xl py-3 px-4 text-white text-sm focus:border-purple-500 focus:ring-1 focus:ring-purple-500/50 transition-all outline-none"
                        />
                        <p class="text-[11px] text-gray-500 leading-relaxed font-medium">Maximum daily allowance for unauthenticated IP addresses.</p>
                        <div v-if="form.errors.guestDailyLimit" class="mt-1 text-xs text-red-400 font-bold">
                            {{ form.errors.guestDailyLimit }}
                        </div>
                    </div>

                    <div class="col-span-full pt-8 flex justify-end">
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="inline-flex items-center gap-x-2 rounded-2xl bg-purple-600 px-8 py-3.5 text-sm font-bold text-white shadow-xl shadow-purple-600/30 hover:bg-purple-500 transition-all duration-200 disabled:opacity-50 cursor-pointer"
                        >
                            <svg v-if="form.processing" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span>Save Cluster Config</span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- MaxMind Geolocation Section -->
            <div class="bg-[#121826]/80 backdrop-blur-xl rounded-3xl border border-gray-800/80 p-8 sm:p-10 shadow-2xl overflow-hidden relative space-y-6">
                <div>
                    <h4 class="text-xl font-bold text-white mb-2">MaxMind Geolocation Engine</h4>
                    <p class="text-gray-400 text-xs font-medium">Download and update local GeoLite2 City and ASN databases for high-speed offline IP lookups.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- City Database Status Card -->
                    <div class="bg-[#0B0F19] rounded-2xl p-6 border border-gray-800 flex flex-col justify-between">
                        <div>
                            <div class="flex items-center justify-between mb-4">
                                <span class="text-xs font-black text-white uppercase tracking-widest">GeoLite2 City Database</span>
                                <span v-if="maxmind.cityDb.exists" class="inline-flex items-center gap-x-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                    <span class="h-1 w-1 rounded-full bg-emerald-400"></span>
                                    Available
                                </span>
                                <span v-else class="inline-flex items-center gap-x-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-red-500/10 text-red-400 border border-red-500/20">
                                    <span class="h-1 w-1 rounded-full bg-red-400"></span>
                                    Not Found
                                </span>
                            </div>

                            <div class="space-y-2 text-xs">
                                <div class="flex justify-between border-b border-gray-800/60 pb-1.5">
                                    <span class="text-gray-500">Local Path</span>
                                    <span class="text-gray-300 font-mono">storage/app/maxmind/GeoLite2-City.mmdb</span>
                                </div>
                                <div v-if="maxmind.cityDb.exists" class="flex justify-between border-b border-gray-800/60 pb-1.5">
                                    <span class="text-gray-500">File Size</span>
                                    <span class="text-gray-300 font-medium">{{ maxmind.cityDb.size }}</span>
                                </div>
                                <div v-if="maxmind.cityDb.exists" class="flex justify-between">
                                    <span class="text-gray-500">Last Synced</span>
                                    <span class="text-gray-300 font-medium">{{ maxmind.cityDb.updated_at }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ASN Database Status Card -->
                    <div class="bg-[#0B0F19] rounded-2xl p-6 border border-gray-800 flex flex-col justify-between">
                        <div>
                            <div class="flex items-center justify-between mb-4">
                                <span class="text-xs font-black text-white uppercase tracking-widest">GeoLite2 ASN Database</span>
                                <span v-if="maxmind.asnDb.exists" class="inline-flex items-center gap-x-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                    <span class="h-1 w-1 rounded-full bg-emerald-400"></span>
                                    Available
                                </span>
                                <span v-else class="inline-flex items-center gap-x-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-red-500/10 text-red-400 border border-red-500/20">
                                    <span class="h-1 w-1 rounded-full bg-red-400"></span>
                                    Not Found
                                </span>
                            </div>

                            <div class="space-y-2 text-xs">
                                <div class="flex justify-between border-b border-gray-800/60 pb-1.5">
                                    <span class="text-gray-500">Local Path</span>
                                    <span class="text-gray-300 font-mono">storage/app/maxmind/GeoLite2-ASN.mmdb</span>
                                </div>
                                <div v-if="maxmind.asnDb.exists" class="flex justify-between border-b border-gray-800/60 pb-1.5">
                                    <span class="text-gray-500">File Size</span>
                                    <span class="text-gray-300 font-medium">{{ maxmind.asnDb.size }}</span>
                                </div>
                                <div v-if="maxmind.asnDb.exists" class="flex justify-between">
                                    <span class="text-gray-500">Last Synced</span>
                                    <span class="text-gray-300 font-medium">{{ maxmind.asnDb.updated_at }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sync Info & Trigger Button -->
                <div class="pt-6 border-t border-gray-800 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <div class="text-xs space-y-1">
                        <div class="flex items-center gap-2">
                            <span class="text-gray-400 font-medium">Credentials config status:</span>
                            <span v-if="maxmind.hasKeys" class="text-emerald-400 font-bold flex items-center gap-1">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                </svg>
                                Loaded (Key: {{ maxmind.licenseKeySnippet }})
                            </span>
                            <span v-else class="text-red-400 font-bold flex items-center gap-1">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                                Missing in .env
                            </span>
                        </div>
                        <p class="text-gray-500 font-medium">Updates are pulled securely from MaxMind servers. Ensure the license keys are populated in your local config.</p>
                    </div>

                    <div>
                        <button
                            @click="updateMaxmindDb"
                            :disabled="!maxmind.hasKeys || isUpdatingMaxmind"
                            class="inline-flex items-center gap-x-2 rounded-2xl bg-indigo-600 hover:bg-indigo-500 disabled:bg-gray-800 disabled:text-gray-600 disabled:border-transparent px-8 py-3.5 text-sm font-bold text-white shadow-xl shadow-indigo-600/20 transition-all duration-200 cursor-pointer disabled:cursor-not-allowed"
                        >
                            <svg v-if="isUpdatingMaxmind" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <svg v-else class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 1121.23 7M16 9h5V4" />
                            </svg>
                            <span>{{ isUpdatingMaxmind ? 'Updating Databases...' : 'Update Databases' }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
