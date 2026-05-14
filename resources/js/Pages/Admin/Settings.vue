<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    settings: Object,
    status: String,
});

const form = useForm({
    maxUploadSizeMb: props.settings?.maxUploadSizeMb || 20,
    defaultQuality: props.settings?.defaultQuality || 80,
    guestRetentionHours: props.settings?.guestRetentionHours || 6,
    authRetentionDays: props.settings?.authRetentionDays || 7,
    guestDailyLimit: props.settings?.guestDailyLimit || 15,
});

const updateSettings = () => {
    form.post(route('admin.settings.update'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <AdminLayout title="System Settings">
        <div class="max-w-4xl animate-fade-in">
            <div class="mb-8">
                <h3 class="text-2xl font-bold text-white mb-2">Environment Configuration</h3>
                <p class="text-gray-400 text-sm font-medium">Fine-tune the media processing engine and data lifecycle parameters.</p>
            </div>

            <div class="bg-[#121826]/80 backdrop-blur-xl rounded-3xl border border-gray-800/80 p-8 sm:p-10 shadow-2xl overflow-hidden relative">
                <div v-if="status" class="mb-8 rounded-2xl bg-emerald-500/10 p-4 text-sm text-emerald-400 border border-emerald-500/20 flex items-center gap-x-3">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span class="font-bold">{{ status }}</span>
                </div>

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
                            class="inline-flex items-center gap-x-2 rounded-2xl bg-purple-600 px-8 py-3.5 text-sm font-bold text-white shadow-xl shadow-purple-600/30 hover:bg-purple-500 transition-all duration-200 disabled:opacity-50"
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
        </div>
    </AdminLayout>
</template>
