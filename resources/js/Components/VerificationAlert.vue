<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

const page = usePage();
const resending = ref(false);
const sent = ref(false);

const resendVerification = () => {
    resending.value = true;
    axios.post(route('verification.send'))
        .then(() => {
            sent.value = true;
            resending.value = false;
        })
        .catch(() => {
            resending.value = false;
        });
};
</script>

<template>
    <div v-if="$page.props.auth.user && !$page.props.auth.user.email_verified_at" class="bg-gradient-to-r from-amber-600/20 via-amber-500/10 to-amber-600/20 border-b border-amber-500/20 backdrop-blur-md">
        <div class="mx-auto max-w-7xl px-4 py-3 sm:px-6 lg:px-8 flex items-center justify-between gap-x-4">
            <div class="flex items-center gap-x-3">
                <div class="flex-shrink-0 h-8 w-8 rounded-lg bg-amber-500/20 flex items-center justify-center">
                    <svg class="h-5 w-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <p class="text-sm font-medium text-amber-200">
                    Your account is not verified. <span class="hidden sm:inline">Please check your inbox at <span class="text-white font-bold">{{ $page.props.auth.user.email }}</span>.</span>
                </p>
            </div>
            <div class="flex items-center gap-x-4">
                <button 
                    @click="resendVerification" 
                    :disabled="resending || sent"
                    class="text-xs font-bold uppercase tracking-widest text-amber-500 hover:text-amber-400 disabled:opacity-50 transition-colors"
                >
                    {{ sent ? 'Check Inbox' : (resending ? 'Sending...' : 'Resend Link') }}
                </button>
            </div>
        </div>
    </div>
</template>
