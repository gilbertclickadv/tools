<script setup>
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { Head } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Dashboard" />

    <PublicLayout>
        <div class="py-12 px-4 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-7xl">
                <div class="bg-[#121826]/60 backdrop-blur-xl border border-gray-800/80 rounded-3xl p-10 shadow-2xl">
                    <h2 class="text-3xl font-black text-white uppercase tracking-tight mb-4">Command Center</h2>
                    <p class="text-gray-400 font-medium">Welcome back! You're successfully authenticated to the FluxMedia Core Engine.</p>
                    
                    <div class="mt-8 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                        <Link :href="route('home')" class="p-6 rounded-2xl bg-[#0B0F19] border border-gray-800 hover:border-purple-500/50 transition-all group">
                            <h3 class="text-white font-bold mb-2 group-hover:text-purple-400 transition-colors">Image Studio</h3>
                            <p class="text-xs text-gray-500">Start processing and optimizing your creative assets.</p>
                        </Link>
                        <Link :href="route('qr.generator')" class="p-6 rounded-2xl bg-[#0B0F19] border border-gray-800 hover:border-purple-500/50 transition-all group">
                            <h3 class="text-white font-bold mb-2 group-hover:text-purple-400 transition-colors">QR Generator</h3>
                            <p class="text-xs text-gray-500">Generate custom high-fidelity matrix codes.</p>
                        </Link>
                        <Link :href="route('history')" class="p-6 rounded-2xl bg-[#0B0F19] border border-gray-800 hover:border-purple-500/50 transition-all group">
                            <h3 class="text-white font-bold mb-2 group-hover:text-purple-400 transition-colors">History Hub</h3>
                            <p class="text-xs text-gray-500">Access your archive of processed assets.</p>
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </PublicLayout>
</template>

<script>
import { Link } from '@inertiajs/vue3';
export default {
    components: {
        Link
    }
}
</script>
