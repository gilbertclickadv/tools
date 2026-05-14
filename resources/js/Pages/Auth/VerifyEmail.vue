<script setup>
import { computed } from 'vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    status: {
        type: String,
    },
});

const form = useForm({});

const submit = () => {
    form.post(route('verification.send'));
};

const verificationLinkSent = computed(
    () => props.status === 'verification-link-sent',
);
</script>

<template>
    <GuestLayout>
        <Head title="Security Validation" />

        <div class="mb-6 text-sm leading-relaxed text-gray-400">
            <span class="text-white font-bold block mb-2">Final Step: Identity Verification</span>
            To maintain the integrity of our creative ecosystem, we require email validation. Please click the activation link we just dispatched to your inbox.
        </div>

        <div
            class="mb-6 p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 text-xs font-bold text-emerald-400 uppercase tracking-widest text-center"
            v-if="verificationLinkSent"
        >
            Security transmission successful. Check your inbox.
        </div>

        <form @submit.prevent="submit">
            <div class="space-y-4">
                <PrimaryButton
                    class="w-full justify-center py-4 bg-purple-600 hover:bg-purple-500 shadow-lg shadow-purple-600/20"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    Resend Activation Link
                </PrimaryButton>

                <div class="flex justify-center">
                    <Link
                        :href="route('logout')"
                        method="post"
                        as="button"
                        class="text-xs font-bold text-gray-500 uppercase tracking-widest hover:text-red-400 transition-colors py-2"
                        >Terminate Session</Link
                    >
                </div>
            </div>
        </form>
    </GuestLayout>
</template>
