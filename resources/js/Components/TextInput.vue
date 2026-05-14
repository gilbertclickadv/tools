<script setup>
import { onMounted, ref } from 'vue';

const model = defineModel({
    type: String,
    required: true,
});

const input = ref(null);

onMounted(() => {
    if (input.value.hasAttribute('autofocus')) {
        input.value.focus();
    }
});

defineExpose({ focus: () => input.value.focus() });
</script>

<template>
    <input
        class="w-full bg-[#0B0F19] border border-gray-700 rounded-xl py-2.5 px-4 text-white text-sm focus:border-purple-500 focus:ring-1 focus:ring-purple-500/50 transition-all outline-none placeholder:text-gray-600"
        v-model="model"
        ref="input"
    />
</template>
