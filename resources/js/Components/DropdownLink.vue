<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    href: {
        type: String,
        required: true,
    },
});
</script>

<template>
    <Link
        :href="href"
        class="block w-full px-4 py-2.5 text-start text-sm font-medium text-gray-400 transition-all duration-200 hover:text-white hover:bg-white/5 focus:outline-none"
    >
        <slot />
    </Link>
</template>
