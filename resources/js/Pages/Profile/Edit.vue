<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});
</script>

<template>
    <Head title="Profile" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-2xl font-bold tracking-tight text-white">
                Member Profile Settings
            </h2>
        </template>

        <div class="py-12 px-4 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-4xl space-y-8">
                <!-- Profile Information Card -->
                <div class="bg-[#121826]/60 backdrop-blur-xl border border-gray-800/80 rounded-3xl p-6 sm:p-10 shadow-2xl relative overflow-hidden group">
                    <div class="absolute top-0 right-0 w-32 h-32 bg-purple-600/5 blur-3xl rounded-full -mr-16 -mt-16 group-hover:bg-purple-600/10 transition-colors"></div>
                    <UpdateProfileInformationForm
                        :must-verify-email="mustVerifyEmail"
                        :status="status"
                        class="max-w-xl relative z-10"
                    />
                </div>

                <!-- Password Update Card -->
                <div class="bg-[#121826]/60 backdrop-blur-xl border border-gray-800/80 rounded-3xl p-6 sm:p-10 shadow-2xl relative overflow-hidden group">
                    <div class="absolute top-0 right-0 w-32 h-32 bg-indigo-600/5 blur-3xl rounded-full -mr-16 -mt-16 group-hover:bg-indigo-600/10 transition-colors"></div>
                    <UpdatePasswordForm class="max-w-xl relative z-10" />
                </div>

                <!-- Account Deletion Card -->
                <div class="bg-[#121826]/60 backdrop-blur-xl border border-red-900/20 rounded-3xl p-6 sm:p-10 shadow-2xl relative overflow-hidden group">
                    <div class="absolute top-0 right-0 w-32 h-32 bg-red-600/5 blur-3xl rounded-full -mr-16 -mt-16 group-hover:bg-red-600/10 transition-colors"></div>
                    <DeleteUserForm class="max-w-xl relative z-10" />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
