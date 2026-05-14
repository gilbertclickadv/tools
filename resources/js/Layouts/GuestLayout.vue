<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="min-h-screen bg-[#0B0F19] text-gray-100 font-jakarta flex flex-col items-center justify-center relative overflow-hidden px-4">
        <!-- Ambient Background Effects -->
        <div class="absolute top-0 left-1/4 w-[500px] h-[500px] bg-purple-600/10 blur-[120px] rounded-full pointer-events-none"></div>
        <div class="absolute bottom-0 right-1/4 w-[400px] h-[400px] bg-indigo-600/10 blur-[100px] rounded-full pointer-events-none"></div>

        <div class="w-full max-w-md relative z-10">
            <!-- Brand Identity -->
            <div class="text-center mb-10">
                <Link href="/" class="inline-flex flex-col items-center gap-y-4 group">
                    <img src="/assets/images/icon_only.webp" class="h-20 w-20 object-contain drop-shadow-[0_0_20px_rgba(168,85,247,0.4)] group-hover:scale-110 transition-transform duration-500" alt="FluxMedia" />
                    <div class="space-y-1">
                        <h2 class="text-3xl font-extrabold tracking-tight bg-gradient-to-r from-white via-gray-200 to-purple-300 bg-clip-text text-transparent">
                            FluxMedia
                        </h2>
                        <p class="text-gray-500 text-sm font-medium">Creative Intelligence Portal</p>
                    </div>
                </Link>
            </div>

            <!-- Auth Card -->
            <div class="bg-[#121826]/80 backdrop-blur-xl border border-gray-800/80 rounded-3xl shadow-2xl p-8 sm:p-10">
                <slot />
            </div>

            <!-- Footer Links -->
            <div class="mt-8 text-center">
                <Link href="/" class="text-sm text-gray-500 hover:text-purple-400 transition-colors font-medium">
                    &larr; Back to Image Studio
                </Link>
            </div>
        </div>
    </div>
</template>

<style>
@import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');

.font-jakarta {
    font-family: 'Plus Jakarta Sans', sans-serif;
}
</style>
