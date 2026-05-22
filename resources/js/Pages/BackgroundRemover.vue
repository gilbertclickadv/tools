<script setup>
import { Head, usePage, Link } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { ref, reactive, computed, onMounted, onUnmounted, nextTick } from 'vue';
import axios from 'axios';

const props = defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
    initialHistory: {
        type: Array,
        default: () => [],
    },
});

// App State
const userHistory = ref(props.initialHistory || []);
const selectedFile = ref(null);
const originalPreviewUrl = ref(null);
const processedPreviewUrl = ref(null);
const originalSize = ref(0);
const processedSize = ref(0);
const isProcessing = ref(false);
const processProgress = ref(0); // For fake/stepwise smooth loader progress
const processError = ref(null);

// Workspace Drag & Slider Compare State
const comparisonSliderVal = ref(50);
const isDraggingSlider = ref(false);
const containerRef = ref(null);

// Delete Modal State
const deleteModal = reactive({ open: false, item: null, loading: false });

// Toast System
const toasts = reactive([]);
let toastId = 0;

const showToast = (message, type = 'success') => {
    const id = ++toastId;
    toasts.push({ id, message, type });
    setTimeout(() => {
        const idx = toasts.findIndex(t => t.id === id);
        if (idx !== -1) toasts.splice(idx, 1);
    }, 3200);
};

const dismissToast = (id) => {
    const idx = toasts.findIndex(t => t.id === id);
    if (idx !== -1) toasts.splice(idx, 1);
};

const toastConfig = {
    success: { bg: 'bg-emerald-500/10 border-emerald-500/30', text: 'text-emerald-400', icon: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z' },
    copy:    { bg: 'bg-blue-500/10 border-blue-500/30',       text: 'text-blue-400',    icon: 'M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z' },
    delete:  { bg: 'bg-red-500/10 border-red-500/30',         text: 'text-red-400',     icon: 'M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16' },
    error:   { bg: 'bg-red-500/10 border-red-500/30',         text: 'text-red-400',     icon: 'M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z' },
};

// Formatting Helper
const formatBytes = (bytes, decimals = 1) => {
    if (!bytes) return '0 Bytes';
    const k = 1024;
    const dm = decimals < 0 ? 0 : decimals;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
};

// Drag and drop events
const isDragActive = ref(false);
const onDragEnter = () => isDragActive.value = true;
const onDragLeave = () => isDragActive.value = false;
const onDragOver = (e) => e.preventDefault();
const onDrop = (e) => {
    e.preventDefault();
    isDragActive.value = false;
    const file = e.dataTransfer?.files[0];
    if (file) handleFileSelection(file);
};

const onFileChange = (e) => {
    const file = e.target.files[0];
    if (file) handleFileSelection(file);
};

const handleFileSelection = (file) => {
    if (!file.type.startsWith('image/')) {
        showToast('Please upload a valid image file.', 'error');
        return;
    }
    selectedFile.value = file;
    originalSize.value = file.size;
    originalPreviewUrl.value = URL.createObjectURL(file);
    processedPreviewUrl.value = null;
    processError.value = null;
    comparisonSliderVal.value = 50;
};

// Fake smooth progress bar triggers
let progressInterval = null;
const startProgressSimulation = () => {
    processProgress.value = 0;
    progressInterval = setInterval(() => {
        if (processProgress.value < 35) {
            processProgress.value += Math.floor(Math.random() * 5) + 3; // Rapid upload simulation
        } else if (processProgress.value < 85) {
            processProgress.value += Math.floor(Math.random() * 2) + 1; // Heavy AI computation simulation
        } else if (processProgress.value < 98) {
            processProgress.value += 0.5; // Finalizing cache
        }
    }, 150);
};

const stopProgressSimulation = (success = true) => {
    clearInterval(progressInterval);
    if (success) {
        processProgress.value = 100;
    }
};

// Primary background removal execution
const removeBackground = async () => {
    if (!selectedFile.value) return;
    isProcessing.value = true;
    processError.value = null;
    startProgressSimulation();

    const formData = new FormData();
    formData.append('image', selectedFile.value);

    try {
        const response = await axios.post('/api/remove-background', formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
        });
        
        if (response.data.success) {
            processedPreviewUrl.value = response.data.dataUrl;
            processedSize.value = response.data.processedSizeBytes;
            
            // Add to session history list
            if (response.data.historyItem) {
                userHistory.value.unshift(response.data.historyItem);
            }
            showToast('AI engine cycle complete. Background removed successfully!', 'success');
        } else {
            throw new Error(response.data.message || 'Background removal execution failed.');
        }
    } catch (err) {
        const errMsg = err.response?.data?.message || err.message || 'AI pipeline inference failure. Please check server console.';
        processError.value = errMsg;
        showToast(errMsg, 'error');
        stopProgressSimulation(false);
    } finally {
        stopProgressSimulation(true);
        isProcessing.value = false;
    }
};

// Workspace slider interaction logic
const startSliderDrag = () => {
    isDraggingSlider.value = true;
    window.addEventListener('mousemove', onSliderDrag);
    window.addEventListener('mouseup', stopSliderDrag);
    window.addEventListener('touchmove', onSliderDrag);
    window.addEventListener('touchend', stopSliderDrag);
};

const stopSliderDrag = () => {
    isDraggingSlider.value = false;
    window.removeEventListener('mousemove', onSliderDrag);
    window.removeEventListener('mouseup', stopSliderDrag);
    window.removeEventListener('touchmove', onSliderDrag);
    window.removeEventListener('touchend', stopSliderDrag);
};

const onSliderDrag = (e) => {
    if (!isDraggingSlider.value || !containerRef.value) return;
    
    const clientX = e.touches ? e.touches[0].clientX : e.clientX;
    const rect = containerRef.value.getBoundingClientRect();
    const relativeX = clientX - rect.left;
    
    let percentage = (relativeX / rect.width) * 100;
    if (percentage < 0) percentage = 0;
    if (percentage > 100) percentage = 100;
    
    comparisonSliderVal.value = Math.round(percentage);
};

// Deletion logic
const promptDelete = (item) => {
    deleteModal.item = item;
    deleteModal.open = true;
};

const cancelDelete = () => {
    deleteModal.open = false;
    deleteModal.item = null;
    deleteModal.loading = false;
};

const confirmDelete = async () => {
    if (!deleteModal.item) return;
    const itemId = deleteModal.item.id;
    deleteModal.loading = true;
    
    try {
        await axios.delete(`/api/process-image/${itemId}`);
        userHistory.value = userHistory.value.filter(item => item.id !== itemId);
        
        // If current workspace displays the deleted item, reset it
        if (processedPreviewUrl.value === deleteModal.item.output_url) {
            processedPreviewUrl.value = null;
            selectedFile.value = null;
            originalPreviewUrl.value = null;
        }

        deleteModal.open = false;
        deleteModal.item = null;
        deleteModal.loading = false;
        showToast('Asset purged successfully.', 'delete');
    } catch (err) {
        deleteModal.loading = false;
        const msg = err.response?.data?.message || 'Failed to delete asset.';
        showToast(msg, 'error');
    }
};

const resetWorkspace = () => {
    selectedFile.value = null;
    originalPreviewUrl.value = null;
    processedPreviewUrl.value = null;
    processError.value = null;
};
</script>

<template>
    <PublicLayout>
        <Head>
            <title>Free AI Background Remover — Instant Transparent PNGs | FluxMedia</title>
            <meta name="description" content="Remove backgrounds from images instantly with AI. Completely free, high-speed, secure background segmentation. Download transparent PNGs. No login required." />
            <meta name="keywords" content="background remover, remove background, ai background remover, background eraser, transparent background, background removal free, image background remover, transparent png generator, cut out image background" />
            <meta name="author" content="FluxMedia" />
            <meta name="robots" content="index, follow" />
            <link rel="canonical" href="https://fluxmedia.space/tools/background-remover" />
        </Head>

        <!-- Toast Notifications Portal -->
        <Teleport to="body">
            <div class="fixed top-4 right-4 z-[300] flex flex-col gap-y-2.5 w-[calc(100vw-2rem)] sm:w-80 pointer-events-none">
                <TransitionGroup
                    enter-active-class="transition-all duration-300 ease-out"
                    enter-from-class="opacity-0 translate-y-[-12px] scale-95"
                    enter-to-class="opacity-100 translate-y-0 scale-100"
                    leave-active-class="transition-all duration-200 ease-in"
                    leave-from-class="opacity-100 translate-y-0 scale-100"
                    leave-to-class="opacity-0 translate-y-[-8px] scale-95"
                >
                    <div
                        v-for="toast in toasts"
                        :key="toast.id"
                        :class="[
                            'pointer-events-auto flex items-start gap-x-3 rounded-2xl border px-4 py-3 shadow-2xl backdrop-blur-xl transition-all',
                            toastConfig[toast.type].bg,
                        ]"
                    >
                        <svg :class="['h-4 w-4 mt-0.5 shrink-0', toastConfig[toast.type].text]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="toastConfig[toast.type].icon" />
                        </svg>
                        <p :class="['flex-1 text-xs font-semibold leading-snug', toastConfig[toast.type].text]">{{ toast.message }}</p>
                        <button @click="dismissToast(toast.id)" class="shrink-0 text-gray-600 hover:text-gray-400 transition-colors">
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>
                </TransitionGroup>
            </div>
        </Teleport>

        <!-- Delete Confirmation Modal -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition-all duration-200 ease-out"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition-all duration-150 ease-in"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div
                    v-if="deleteModal.open"
                    class="fixed inset-0 z-[400] flex items-end sm:items-center justify-center p-4"
                    @keydown.esc="cancelDelete"
                    tabindex="-1"
                    @click.self="cancelDelete"
                >
                    <!-- Backdrop -->
                    <div class="absolute inset-0 bg-black/75 backdrop-blur-sm"></div>

                    <!-- Modal panel -->
                    <Transition
                        enter-active-class="transition-all duration-200 ease-out"
                        enter-from-class="opacity-0 scale-95 translate-y-4"
                        enter-to-class="opacity-100 scale-100 translate-y-0"
                        leave-active-class="transition-all duration-150 ease-in"
                        leave-from-class="opacity-100 scale-100 translate-y-0"
                        leave-to-class="opacity-0 scale-95 translate-y-2"
                    >
                        <div
                            v-if="deleteModal.open"
                            class="relative w-full max-w-sm rounded-3xl border border-red-500/20 bg-[#121826]/95 backdrop-blur-xl shadow-2xl p-6"
                        >
                            <!-- Icon -->
                            <div class="flex items-center justify-center h-12 w-12 rounded-2xl bg-red-500/10 border border-red-500/20 mx-auto mb-4">
                                <svg class="h-6 w-6 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </div>

                            <!-- Text -->
                            <h3 class="text-base font-bold text-white text-center mb-1">Purge Session Asset?</h3>
                            <p class="text-xs text-gray-400 text-center mb-4">This action is irreversible. The transparent image asset will be permanently deleted from safe disk storage.</p>

                            <!-- Asset preview -->
                            <div v-if="deleteModal.item" class="rounded-xl bg-gray-900/60 border border-gray-800 px-3 py-2.5 mb-5">
                                <p class="text-[10px] font-black text-red-400 uppercase tracking-widest mb-1">File to delete</p>
                                <p class="text-xs font-bold text-white font-mono truncate">{{ deleteModal.item.original_name }}</p>
                                <p class="text-[10px] text-gray-500 truncate mt-0.5">PNG · {{ formatBytes(deleteModal.item.size_bytes) }}</p>
                            </div>

                            <!-- Actions -->
                            <div class="flex gap-x-3">
                                <button
                                    @click="cancelDelete"
                                    :disabled="deleteModal.loading"
                                    class="flex-1 rounded-2xl border border-gray-700 bg-gray-800/60 hover:bg-gray-700/60 py-3 text-sm font-bold text-gray-300 transition-all active:scale-95 disabled:opacity-50"
                                >
                                    Cancel
                                </button>
                                <button
                                    @click="confirmDelete"
                                    :disabled="deleteModal.loading"
                                    class="flex-1 inline-flex items-center justify-center gap-x-2 rounded-2xl bg-red-600 hover:bg-red-500 py-3 text-sm font-bold text-white shadow-lg shadow-red-600/20 transition-all active:scale-95 disabled:opacity-60"
                                >
                                    <svg v-if="deleteModal.loading" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                                    {{ deleteModal.loading ? 'Purging...' : 'Delete Asset' }}
                                </button>
                            </div>
                        </div>
                    </Transition>
                </div>
            </Transition>
        </Teleport>

        <!-- Hero Title -->
        <div class="relative overflow-hidden pt-8 pb-4 text-center">
            <!-- Glow background -->
            <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[200px] bg-gradient-to-tr from-violet-600/15 via-purple-600/8 to-indigo-600/8 blur-[100px] rounded-full pointer-events-none"></div>
            
            <div class="relative mx-auto max-w-4xl px-4 space-y-3">
                <div class="flex items-center justify-center">
                    <span class="inline-flex items-center gap-x-2 rounded-full bg-violet-500/10 px-4 py-1.5 text-[10px] font-bold text-violet-300 border border-violet-500/20 uppercase tracking-[0.2em]">
                        <span class="h-1.5 w-1.5 rounded-full bg-violet-400 animate-pulse"></span>
                        AI SEGMENTATION SUITE
                    </span>
                </div>
                <h1 class="text-3xl font-black tracking-tight text-white sm:text-4xl lg:text-5xl leading-none">
                    AI <span class="bg-gradient-to-r from-violet-400 via-purple-400 to-indigo-400 bg-clip-text text-transparent">Background Remover</span>
                </h1>
                <p class="mx-auto max-w-lg text-sm text-gray-400 font-medium">
                    Instantly cut out subjects from backgrounds. Fast, ultra-precise transparency powered by highly lightweight RMBG-1.4 segmentation models.
                </p>
            </div>
        </div>

        <!-- Main Workspace -->
        <div class="mx-auto max-w-4xl px-4 pb-16">
            <div class="rounded-3xl border border-gray-800/80 bg-[#121826]/40 backdrop-blur-xl p-5 sm:p-7 shadow-2xl relative">
                
                <!-- Reset Workspace Button (Top Right when image uploaded) -->
                <button 
                    v-if="originalPreviewUrl && !isProcessing" 
                    @click="resetWorkspace"
                    class="absolute top-6 right-6 p-2 rounded-full bg-gray-800/80 text-gray-400 hover:text-white hover:bg-gray-700 transition-colors z-30"
                    title="Clear Image"
                >
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>

                <!-- Zone 1: File Uploader Drag & Drop -->
                <div 
                    v-if="!originalPreviewUrl"
                    @dragenter.prevent="onDragEnter"
                    @dragleave.prevent="onDragLeave"
                    @dragover.prevent="onDragOver"
                    @drop.prevent="onDrop"
                    :class="[
                        'border-2 border-dashed rounded-2xl p-10 flex flex-col items-center justify-center cursor-pointer transition-all duration-300 min-h-[300px]',
                        isDragActive 
                            ? 'border-violet-400 bg-violet-500/[0.05] shadow-[0_0_20px_rgba(139,92,246,0.15)] scale-[0.99]' 
                            : 'border-white/[0.06] bg-white/[0.01] hover:border-violet-500/30 hover:bg-white/[0.03]'
                    ]"
                    @click="$refs.fileInput.click()"
                >
                    <input 
                        ref="fileInput" 
                        type="file" 
                        class="hidden" 
                        accept="image/*" 
                        @change="onFileChange" 
                    />
                    
                    <!-- SVG Icon -->
                    <div class="h-16 w-16 rounded-2xl bg-violet-400/5 border border-violet-400/10 flex items-center justify-center mb-5 text-violet-400 transition-transform group-hover:scale-105">
                        <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                        </svg>
                    </div>

                    <h2 class="text-sm font-bold text-white mb-1.5 uppercase tracking-wider">Drag & drop raw image</h2>
                    <p class="text-xs text-gray-500 max-w-xs text-center leading-relaxed">
                        Supports PNG, JPEG, WebP, AVIF. Max upload size limited to 20MB.
                    </p>
                </div>

                <!-- Zone 2: Processing Loader Overlay -->
                <div v-else-if="isProcessing" class="flex flex-col items-center justify-center min-h-[350px] p-6 space-y-6">
                    <div class="relative flex items-center justify-center">
                        <!-- Orbiting AI ring animation -->
                        <div class="h-24 w-24 rounded-full border-4 border-violet-500/20 border-t-violet-400 animate-spin"></div>
                        <div class="absolute h-16 w-16 rounded-full border-4 border-purple-500/10 border-b-purple-400 animate-spin animate-reverse duration-1000"></div>
                        <svg class="absolute h-8 w-8 text-violet-400 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.813 15.904L9 21L8.188 15.904L3 15L8.188 14.096L9 9L9.813 14.096L15 15L9.813 15.904Z" />
                        </svg>
                    </div>

                    <div class="space-y-2 text-center w-full max-w-xs">
                        <h3 class="text-sm font-bold text-white uppercase tracking-widest">Inference engine active...</h3>
                        <p class="text-[11px] text-gray-500 leading-normal">
                            {{ processProgress < 35 ? 'Uploading image asset...' : processProgress < 85 ? 'Running RMBG-1.4 segmentation pipeline...' : 'Writing transparency alpha layers...' }}
                        </p>
                        
                        <!-- Smooth progress bar -->
                        <div class="h-1 w-full bg-gray-900 rounded-full overflow-hidden mt-3 border border-gray-800">
                            <div 
                                class="h-full bg-gradient-to-r from-violet-500 to-purple-500 rounded-full transition-all duration-150 ease-out"
                                :style="{ width: processProgress + '%' }"
                            ></div>
                        </div>
                    </div>
                </div>

                <!-- Zone 3: Error Message View -->
                <div v-else-if="processError" class="flex flex-col items-center justify-center min-h-[300px] p-6 text-center space-y-4">
                    <div class="h-12 w-12 rounded-full bg-red-500/10 border border-red-500/20 flex items-center justify-center text-red-400">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                    </div>
                    <div class="space-y-1">
                        <h3 class="text-sm font-bold text-white uppercase tracking-widest">Pipeline Processing Error</h3>
                        <p class="text-xs text-red-400 max-w-md font-mono bg-red-950/20 border border-red-900/30 rounded-xl px-4 py-2.5 mt-2 select-all leading-normal">{{ processError }}</p>
                    </div>
                    <div class="flex gap-x-3 pt-2">
                        <button @click="resetWorkspace" class="px-5 py-2.5 text-xs font-bold text-gray-300 border border-gray-800 bg-gray-800/40 rounded-xl hover:bg-gray-700/40 active:scale-95 transition-all">Upload Another</button>
                        <button @click="removeBackground" class="px-5 py-2.5 text-xs font-bold text-white bg-violet-600 rounded-xl hover:bg-violet-500 shadow-lg shadow-violet-600/20 active:scale-95 transition-all">Retry Removal</button>
                    </div>
                </div>

                <!-- Zone 4: Preview Workspace & AI Trigger Actions -->
                <div v-else class="space-y-6">
                    <!-- Action Header when loaded but not processed -->
                    <div v-if="!processedPreviewUrl" class="flex flex-col items-center justify-center p-8 space-y-4">
                        <div class="relative w-full max-w-md aspect-video sm:aspect-square rounded-2xl overflow-hidden border border-gray-800 bg-black/40">
                            <img :src="originalPreviewUrl" class="h-full w-full object-contain" />
                        </div>
                        <div class="space-y-1 text-center">
                            <p class="text-xs font-bold text-white truncate max-w-xs">{{ selectedFile.name }}</p>
                            <p class="text-[10px] text-gray-500 font-mono">Original Size: {{ formatBytes(originalSize) }}</p>
                        </div>
                        
                        <button 
                            @click="removeBackground" 
                            class="px-8 py-3.5 inline-flex items-center gap-x-2 text-sm font-bold text-white bg-gradient-to-r from-violet-600 to-purple-600 rounded-2xl hover:from-violet-500 hover:to-purple-500 hover:shadow-xl hover:shadow-violet-600/20 active:scale-[0.98] transition-all duration-200"
                        >
                            <svg class="h-4.5 w-4.5 text-violet-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.813 15.904L9 21L8.188 15.904L3 15L8.188 14.096L9 9L9.813 14.096L15 15L9.813 15.904ZM19.071 4.929L19.5 7.5L19.929 4.929L22.5 4.5L19.929 4.071L19.5 1.5L19.071 4.071L16.5 4.5L19.071 4.929ZM19.071 19.071L19.5 21.642L19.929 19.071L22.5 18.642L19.929 18.213L19.5 15.642L19.071 18.213L16.5 18.642L19.071 19.071Z" />
                            </svg>
                            Execute AI Background Removal
                        </button>
                    </div>

                    <!-- Side-by-side Visual Slider Compare Workspace when processed successfully -->
                    <div v-else class="space-y-6">
                        <!-- Premium Interactive Compare Slider -->
                        <div 
                            ref="containerRef"
                            class="relative w-full aspect-video sm:aspect-[4/3] rounded-3xl overflow-hidden border border-gray-800 bg-[#070b13] cursor-ew-resize select-none"
                            @mousedown="startSliderDrag"
                            @touchstart="startSliderDrag"
                        >
                            <!-- Background Checkered Pattern for transparency visual indicator -->
                            <div class="absolute inset-0 bg-checkerboard z-0"></div>

                            <!-- Left Side: Original Image (Bottom Layer) -->
                            <img :src="originalPreviewUrl" class="absolute inset-0 h-full w-full object-contain pointer-events-none z-1" />

                            <!-- Right Side: Processed Transparent PNG (Top Layer, clipped) -->
                            <div 
                                class="absolute inset-0 z-2"
                                :style="{ clipPath: 'inset(0 0 0 ' + comparisonSliderVal + '%)' }"
                            >
                                <!-- Backdrop checked board underneath transparent image inside clipped container -->
                                <div class="absolute inset-0 bg-checkerboard"></div>
                                <img :src="processedPreviewUrl" class="absolute inset-0 h-full w-full object-contain pointer-events-none" />
                            </div>

                            <!-- Visual Split slider line handle -->
                            <div 
                                class="absolute top-0 bottom-0 w-0.5 bg-violet-400 shadow-[0_0_10px_rgba(139,92,246,0.6)] z-10 pointer-events-none"
                                :style="{ left: comparisonSliderVal + '%' }"
                            >
                                <!-- Circular Slider handle button -->
                                <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 h-8 w-8 rounded-full border border-violet-400 bg-[#121826]/90 shadow-2xl backdrop-blur-xl flex items-center justify-center text-violet-300">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M8 7l-5 5 5 5M16 7l5 5-5 5" />
                                    </svg>
                                </div>
                            </div>

                            <!-- Visual Badges labels -->
                            <div class="absolute top-4 left-4 px-3 py-1 rounded-lg bg-black/60 backdrop-blur-md border border-white/5 text-[9px] font-black text-gray-400 uppercase tracking-widest pointer-events-none z-20">Original</div>
                            <div class="absolute top-4 right-4 px-3 py-1 rounded-lg bg-violet-950/60 backdrop-blur-md border border-violet-500/20 text-[9px] font-black text-violet-300 uppercase tracking-widest pointer-events-none z-20">Transferred</div>
                        </div>

                        <!-- Info details & Download Actions -->
                        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 border-t border-gray-800/80 pt-5">
                            <div class="space-y-1">
                                <h3 class="text-xs font-bold text-white uppercase tracking-wider truncate max-w-xs sm:max-w-md">{{ selectedFile.name }}</h3>
                                <div class="flex flex-wrap items-center gap-x-2 text-[10px] text-gray-500 font-mono">
                                    <span>Original: {{ formatBytes(originalSize) }}</span>
                                    <span class="text-gray-700 font-normal">|</span>
                                    <span class="text-violet-400 font-bold">Processed: {{ formatBytes(processedSize) }}</span>
                                </div>
                            </div>
                            
                            <div class="flex items-center gap-x-3 w-full sm:w-auto">
                                <button 
                                    @click="resetWorkspace"
                                    class="flex-1 sm:flex-initial px-5 py-3 text-xs font-bold text-gray-300 border border-gray-800 bg-gray-800/40 rounded-2xl hover:bg-gray-700/40 active:scale-95 transition-all"
                                >
                                    Upload Another
                                </button>
                                <a 
                                    :href="userHistory[0]?.download_url" 
                                    class="flex-1 sm:flex-initial px-6 py-3 inline-flex items-center justify-center gap-x-2 text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-500 rounded-2xl hover:shadow-lg hover:shadow-emerald-600/20 active:scale-95 transition-all"
                                    title="Download transparent image asset"
                                >
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                    </svg>
                                    Download Transparent PNG
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Live History Feed Section -->
            <div class="mt-12 sm:mt-16">
                <div class="rounded-3xl border border-gray-800/80 bg-[#121826]/40 backdrop-blur-xl p-5 sm:p-7 shadow-2xl">
                    <div class="flex items-center justify-between border-b border-gray-800/80 pb-4 mb-5">
                        <span class="text-[10px] font-bold text-violet-400 uppercase tracking-widest flex items-center gap-x-2">
                            <span class="h-2 w-2 rounded-full bg-violet-500 animate-pulse"></span>
                            Live Session History
                        </span>
                        <span class="text-[9px] font-bold text-gray-600 uppercase tracking-widest">
                            {{ userHistory.length }} cycle{{ userHistory.length !== 1 ? 's' : '' }} active
                        </span>
                    </div>

                    <div v-if="userHistory.length === 0" class="py-12 text-center">
                        <p class="text-xs text-gray-600 font-medium italic">No recent transparent png cycles detected in current session.</p>
                    </div>

                    <div v-else class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
                        <div 
                            v-for="item in userHistory" 
                            :key="item.id" 
                            class="group relative aspect-square rounded-2xl overflow-hidden bg-[#070b13] border border-gray-800/80 hover:border-violet-500/50 transition-all duration-200"
                        >
                            <!-- Checked board underneath -->
                            <div class="absolute inset-0 bg-checkerboard opacity-40 z-0"></div>
                            
                            <!-- Preview Output Image -->
                            <img :src="item.output_url" class="absolute inset-0 h-full w-full object-contain opacity-70 group-hover:opacity-100 group-hover:scale-105 transition-all duration-300 z-1" loading="lazy" />
                            
                            <!-- Action overlay on hover -->
                            <div class="absolute inset-0 bg-gradient-to-t from-black/95 via-black/30 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-200 flex flex-col justify-end p-3 z-10">
                                <div class="flex items-center justify-between gap-x-2">
                                    <div class="flex-1 min-w-0">
                                        <p class="text-[9px] font-black text-white uppercase truncate" :title="item.original_name">{{ item.original_name }}</p>
                                        <p class="text-[8px] text-violet-300 font-bold uppercase">{{ formatBytes(item.size_bytes) }} · {{ item.created_at }}</p>
                                    </div>
                                    
                                    <div class="flex gap-x-1.5">
                                        <a 
                                            :href="item.download_url" 
                                            class="h-7 w-7 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white flex items-center justify-center shrink-0 transition-colors shadow-lg shadow-emerald-600/20"
                                            title="Download PNG"
                                        >
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                            </svg>
                                        </a>
                                        <button 
                                            @click="promptDelete(item)" 
                                            class="h-7 w-7 rounded-lg bg-red-500/10 hover:bg-red-600 text-red-400 hover:text-white flex items-center justify-center shrink-0 transition-all border border-red-500/20 hover:border-red-600 shadow-lg shadow-red-950/20 active:scale-90"
                                            title="Purge Asset"
                                        >
                                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </PublicLayout>
</template>

<style scoped>
/* Checkerboard CSS grid styling */
.bg-checkerboard {
    background-image: linear-gradient(45deg, rgba(255, 255, 255, 0.02) 25%, transparent 25%),
                      linear-gradient(-45deg, rgba(255, 255, 255, 0.02) 25%, transparent 25%),
                      linear-gradient(45deg, transparent 75%, rgba(255, 255, 255, 0.02) 75%),
                      linear-gradient(-45deg, transparent 75%, rgba(255, 255, 255, 0.02) 75%);
    background-size: 24px 24px;
    background-position: 0 0, 0 12px, 12px -12px, -12px 0px;
}

.animate-reverse {
    animation-direction: reverse;
}
</style>
