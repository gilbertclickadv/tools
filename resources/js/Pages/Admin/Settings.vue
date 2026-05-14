<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
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
    <Head title="Admin Settings" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                Processing Environment Settings
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="overflow-hidden rounded-xl bg-white dark:bg-gray-800 p-8 shadow-sm ring-1 ring-gray-900/5 max-w-2xl">
                    <div v-if="status" class="mb-6 rounded-lg bg-emerald-50 dark:bg-emerald-950/50 p-4 text-sm text-emerald-600 dark:text-emerald-400 border border-emerald-100 dark:border-emerald-900">
                        {{ status }}
                    </div>

                    <form @submit.prevent="updateSettings" class="space-y-6">
                        <!-- Setting 1 -->
                        <div>
                            <label for="maxUploadSizeMb" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Maximum File Upload Size (MB)
                            </label>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">Controls the boundary cap for single client payload requests.</p>
                            <input
                                id="maxUploadSizeMb"
                                type="number"
                                v-model="form.maxUploadSizeMb"
                                min="1"
                                max="100"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white sm:text-sm"
                            />
                            <div v-if="form.errors.maxUploadSizeMb" class="mt-1 text-xs text-red-500">
                                {{ form.errors.maxUploadSizeMb }}
                            </div>
                        </div>

                        <!-- Setting 2 -->
                        <div>
                            <label for="defaultQuality" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Default Image Compression Quality (%)
                            </label>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">Initial target export quality setting for lossy conversion pipelines (10 - 100).</p>
                            <input
                                id="defaultQuality"
                                type="number"
                                v-model="form.defaultQuality"
                                min="10"
                                max="100"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white sm:text-sm"
                            />
                            <div v-if="form.errors.defaultQuality" class="mt-1 text-xs text-red-500">
                                {{ form.errors.defaultQuality }}
                            </div>
                        </div>

                        <!-- Setting 3 -->
                        <div>
                            <label for="guestRetentionHours" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Guest Session Storage Retention (Hours)
                            </label>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">Duration before public temporary images from unauthenticated requests expire and get purged.</p>
                            <input
                                id="guestRetentionHours"
                                type="number"
                                v-model="form.guestRetentionHours"
                                min="1"
                                max="72"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white sm:text-sm"
                            />
                            <div v-if="form.errors.guestRetentionHours" class="mt-1 text-xs text-red-500">
                                {{ form.errors.guestRetentionHours }}
                            </div>
                        </div>

                        <!-- Setting 4 -->
                        <div>
                            <label for="authRetentionDays" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Authenticated Account Storage Retention (Days)
                            </label>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">Lifecycle tracking threshold for persistent files produced by signed-in accounts.</p>
                            <input
                                id="authRetentionDays"
                                type="number"
                                v-model="form.authRetentionDays"
                                min="1"
                                max="365"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white sm:text-sm"
                            />
                            <div v-if="form.errors.authRetentionDays" class="mt-1 text-xs text-red-500">
                                {{ form.errors.authRetentionDays }}
                            </div>
                        </div>

                        <!-- Setting 5 -->
                        <div>
                            <label for="guestDailyLimit" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Unauthenticated Requests Cap per IP (Files/Day)
                            </label>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">Maximum files an anonymous user IP address is allowed to process within a calendar day.</p>
                            <input
                                id="guestDailyLimit"
                                type="number"
                                v-model="form.guestDailyLimit"
                                min="1"
                                max="1000"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white sm:text-sm"
                            />
                            <div v-if="form.errors.guestDailyLimit" class="mt-1 text-xs text-red-500">
                                {{ form.errors.guestDailyLimit }}
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-x-3 pt-4 border-t border-gray-100 dark:border-gray-700">
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="inline-flex justify-center rounded-md border border-transparent bg-purple-600 py-2 px-4 text-sm font-medium text-white shadow-sm hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 disabled:opacity-50 transition-colors"
                            >
                                Save Settings
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
