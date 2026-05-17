<script setup>
import { ref, watch, onMounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import md5 from 'crypto-js/md5';
import sha1 from 'crypto-js/sha1';
import sha256 from 'crypto-js/sha256';
import sha512 from 'crypto-js/sha512';
import sha384 from 'crypto-js/sha384';
import sha3 from 'crypto-js/sha3';

const inputText = ref('');
const hashes = ref({
    md5: '',
    sha1: '',
    sha256: '',
    sha384: '',
    sha512: '',
    sha3: ''
});

const copiedHash = ref('');

const generateHashes = () => {
    if (!inputText.value) {
        Object.keys(hashes.value).forEach(key => hashes.value[key] = '');
        return;
    }
    
    // Compute all hashes in real-time
    hashes.value.md5 = md5(inputText.value).toString();
    hashes.value.sha1 = sha1(inputText.value).toString();
    hashes.value.sha256 = sha256(inputText.value).toString();
    hashes.value.sha384 = sha384(inputText.value).toString();
    hashes.value.sha512 = sha512(inputText.value).toString();
    hashes.value.sha3 = sha3(inputText.value).toString();
};

watch(inputText, generateHashes);

const copyToClipboard = async (text, hashType) => {
    if (!text) return;
    try {
        await navigator.clipboard.writeText(text);
        copiedHash.value = hashType;
        setTimeout(() => {
            if (copiedHash.value === hashType) {
                copiedHash.value = '';
            }
        }, 2000);
    } catch (err) {
        console.error('Failed to copy hash: ', err);
    }
};

const clearInput = () => {
    inputText.value = '';
};

// Initial empty state trigger
onMounted(generateHashes);
</script>

<template>
    <PublicLayout>
        <Head>
            <title>Hash Generator · MD5, SHA-256, SHA-512 · FluxMedia</title>
            <meta name="description" content="Generate cryptographic hashes instantly in your browser. Supports MD5, SHA-1, SHA-256, SHA-512, and SHA-3. 100% secure client-side execution." />
        </Head>

        <!-- Background Accents -->
        <div class="fixed inset-0 overflow-hidden pointer-events-none -z-10 bg-[#0B0F19]">
            <div class="absolute top-[10%] left-[20%] w-[40%] h-[40%] rounded-full bg-amber-600/10 blur-[120px]"></div>
            <div class="absolute bottom-[20%] right-[10%] w-[35%] h-[35%] rounded-full bg-orange-600/10 blur-[120px]"></div>
            <div class="absolute top-[60%] left-[-10%] w-[30%] h-[30%] rounded-full bg-red-600/10 blur-[100px]"></div>
        </div>

        <!-- Header -->
        <div class="relative pt-8 pb-4 text-center px-4 overflow-hidden z-10">
            <div class="absolute inset-0 bg-[url('/assets/images/grid-pattern.svg')] opacity-5 mask-image-gradient-b"></div>
            <div class="relative max-w-3xl mx-auto space-y-3">
                <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-gray-800/50 border border-gray-700/50 backdrop-blur-md">
                    <span class="w-2 h-2 rounded-full bg-amber-400 animate-pulse"></span>
                    <span class="text-xs font-semibold text-gray-300 uppercase tracking-wider">Zero-Knowledge Execution</span>
                </div>
                
                <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-white leading-tight">
                    Cryptographic <span class="bg-gradient-to-r from-amber-400 via-orange-400 to-red-400 bg-clip-text text-transparent">Hashes</span>
                </h1>
                <p class="text-xs text-gray-400 max-w-xl mx-auto leading-relaxed">
                    Compute standard cryptographic hashes instantly as you type. All calculations are performed strictly in your browser.
                </p>
            </div>
        </div>

        <!-- Tool Container -->
        <div class="mx-auto max-w-4xl px-4 pb-24 relative z-10">
            
            <!-- Input Area -->
            <div class="bg-[#121826]/80 backdrop-blur-xl rounded-t-3xl border border-gray-800 shadow-2xl p-6 sm:p-8 relative z-20">
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <label class="text-sm font-black text-gray-300 uppercase tracking-widest flex items-center gap-2">
                            <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" /></svg>
                            String to Hash
                        </label>
                        <button @click="clearInput" class="text-xs font-bold text-gray-500 hover:text-red-400 transition-colors uppercase tracking-wider">Clear</button>
                    </div>
                    <textarea
                        v-model="inputText"
                        placeholder="Type or paste your text here to generate hashes..."
                        class="w-full h-[150px] bg-[#0B0F19] border border-gray-700/80 rounded-2xl p-5 text-gray-100 placeholder-gray-600 focus:border-amber-500 focus:ring-1 focus:ring-amber-500/50 transition-all outline-none resize-none font-mono text-sm shadow-inner custom-scrollbar"
                        spellcheck="false"
                    ></textarea>
                </div>
            </div>

            <!-- Output Grid -->
            <div class="bg-[#0B0F19]/90 backdrop-blur-2xl rounded-b-3xl border-x border-b border-gray-800 shadow-2xl p-6 sm:p-8 space-y-6">
                
                <div v-for="(hashValue, hashType) in hashes" :key="hashType" class="group relative bg-[#121826]/50 rounded-2xl border border-gray-800/80 p-4 transition-all duration-300 hover:border-amber-500/30 hover:bg-[#121826]">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div class="flex items-center gap-3 w-[100px] shrink-0">
                            <span class="inline-flex items-center justify-center h-8 px-3 rounded-lg bg-gray-800 text-xs font-black text-gray-300 uppercase tracking-widest border border-gray-700/50 shadow-inner group-hover:bg-amber-500/10 group-hover:text-amber-400 group-hover:border-amber-500/20 transition-all">
                                {{ hashType }}
                            </span>
                        </div>
                        
                        <div class="flex-1 overflow-hidden">
                            <input 
                                type="text" 
                                readonly 
                                :value="hashValue" 
                                placeholder="Waiting for input..."
                                class="w-full bg-transparent border-none p-0 text-sm font-mono text-gray-400 focus:ring-0 truncate selection:bg-amber-500/30 selection:text-amber-200"
                            />
                        </div>

                        <div class="shrink-0 flex items-center justify-end">
                            <button 
                                @click="copyToClipboard(hashValue, hashType)"
                                :disabled="!hashValue"
                                :class="[
                                    'inline-flex items-center justify-center w-9 h-9 rounded-xl transition-all duration-200',
                                    hashValue 
                                        ? copiedHash === hashType 
                                            ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' 
                                            : 'bg-gray-800 text-gray-400 border border-gray-700 hover:bg-gray-700 hover:text-white cursor-pointer' 
                                        : 'bg-gray-800/50 text-gray-600 border border-gray-800 cursor-not-allowed'
                                ]"
                                :title="copiedHash === hashType ? 'Copied!' : 'Copy to clipboard'"
                            >
                                <svg v-if="copiedHash === hashType" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" /></svg>
                                <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3" /></svg>
                            </button>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </PublicLayout>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 8px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: rgba(11, 15, 25, 0.5);
    border-radius: 8px;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: rgba(55, 65, 81, 0.8);
    border-radius: 8px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: rgba(75, 85, 99, 1);
}
</style>
