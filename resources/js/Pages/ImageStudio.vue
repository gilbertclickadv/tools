<script setup>
import { Head, usePage, Link } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { ref, computed, watch, nextTick, onMounted, onUnmounted } from 'vue';
import axios from 'axios';

const props = defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
    laravelVersion: String,
    phpVersion: String,
    initialHistory: {
        type: Array,
        default: () => [],
    },
});

const userHistory = ref(props.initialHistory || []);

// State Management
const showResultModal = ref(false);
const selectedFile = ref(null);
const originalPreviewUrl = ref(null);
const originalDimensions = ref({ width: 0, height: 0 });
const originalSize = ref(0);

const activeTab = ref('convert'); // convert, resize, adjust
const isProcessing = ref(false);
const processError = ref(null);
const uploadProgress = ref(0);

// Form Options
const settings = usePage().props.appSettings;
const quality = ref(settings?.defaultQuality || 80);
const targetWidth = ref('');
const targetHeight = ref('');
const maintainAspect = ref(true);
const targetFormat = ref('webp');
const applyGreyscale = ref(false);
const blurAmount = ref(0);
const brightnessAmount = ref(0);
const cropWidth = ref('');
const cropHeight = ref('');
const cropX = ref(0);
const cropY = ref(0);
// Interactive Visual Cropping State
const cropImageRef = ref(null);
const cropOverlayX = ref(0);
const cropOverlayY = ref(0);
const cropOverlayWidth = ref(0);
const cropOverlayHeight = ref(0);
const renderedImgWidth = ref(0);
const renderedImgHeight = ref(0);

const initCropOverlay = () => {
    nextTick(() => {
        if (!cropImageRef.value) return;
        renderedImgWidth.value = cropImageRef.value.clientWidth || cropImageRef.value.width;
        renderedImgHeight.value = cropImageRef.value.clientHeight || cropImageRef.value.height;

        if (!renderedImgWidth.value) return;

        // Auto-scale default square filling 60% of visible container
        const size = Math.min(renderedImgWidth.value, renderedImgHeight.value) * 0.6;
        cropOverlayWidth.value = Math.round(size);
        cropOverlayHeight.value = Math.round(size);
        cropOverlayX.value = Math.round((renderedImgWidth.value - size) / 2);
        cropOverlayY.value = Math.round((renderedImgHeight.value - size) / 2);

        updateRealCropValues();
    });
};

watch(activeTab, (newTab) => {
    if (newTab === 'crop') {
        initCropOverlay();
    }
});

const updateRealCropValues = () => {
    if (!renderedImgWidth.value || !originalDimensions.value.width) return;
    const scaleRatio = originalDimensions.value.width / renderedImgWidth.value;
    cropWidth.value = Math.max(10, Math.round(cropOverlayWidth.value * scaleRatio));
    cropHeight.value = Math.max(10, Math.round(cropOverlayHeight.value * scaleRatio));
    cropX.value = Math.max(0, Math.round(cropOverlayX.value * scaleRatio));
    cropY.value = Math.max(0, Math.round(cropOverlayY.value * scaleRatio));
};

const syncOverlayFromInputs = () => {
    if (!renderedImgWidth.value || !originalDimensions.value.width) return;
    const scaleRatio = renderedImgWidth.value / originalDimensions.value.width;
    cropOverlayWidth.value = Math.max(30, Math.min(renderedImgWidth.value, Number(cropWidth.value) * scaleRatio));
    cropOverlayHeight.value = Math.max(30, Math.min(renderedImgHeight.value, Number(cropHeight.value) * scaleRatio));
    cropOverlayX.value = Math.max(0, Math.min(renderedImgWidth.value - cropOverlayWidth.value, Number(cropX.value) * scaleRatio));
    cropOverlayY.value = Math.max(0, Math.min(renderedImgHeight.value - cropOverlayHeight.value, Number(cropY.value) * scaleRatio));
};

// Dragging / Resizing Mechanics
let isDraggingBox = false;
let isResizingBox = false;
let resizeHandle = '';
let startClientX = 0;
let startClientY = 0;
let startOverlayX = 0;
let startOverlayY = 0;
let startOverlayW = 0;
let startOverlayH = 0;

const startDragBox = (e) => {
    isDraggingBox = true;
    const clientX = e.touches ? e.touches[0].clientX : e.clientX;
    const clientY = e.touches ? e.touches[0].clientY : e.clientY;
    startClientX = clientX;
    startClientY = clientY;
    startOverlayX = cropOverlayX.value;
    startOverlayY = cropOverlayY.value;

    window.addEventListener('mousemove', onMouseMove);
    window.addEventListener('mouseup', onMouseUp);
    window.addEventListener('touchmove', onMouseMove, { passive: false });
    window.addEventListener('touchend', onMouseUp);
};

const startResize = (handle, e) => {
    isResizingBox = true;
    resizeHandle = handle;
    const clientX = e.touches ? e.touches[0].clientX : e.clientX;
    const clientY = e.touches ? e.touches[0].clientY : e.clientY;
    startClientX = clientX;
    startClientY = clientY;
    startOverlayX = cropOverlayX.value;
    startOverlayY = cropOverlayY.value;
    startOverlayW = cropOverlayWidth.value;
    startOverlayH = cropOverlayHeight.value;

    window.addEventListener('mousemove', onMouseMove);
    window.addEventListener('mouseup', onMouseUp);
    window.addEventListener('touchmove', onMouseMove, { passive: false });
    window.addEventListener('touchend', onMouseUp);
};

const onMouseMove = (e) => {
    const clientX = e.touches ? e.touches[0].clientX : e.clientX;
    const clientY = e.touches ? e.touches[0].clientY : e.clientY;
    const dx = clientX - startClientX;
    const dy = clientY - startClientY;

    if (isDraggingBox) {
        let newX = startOverlayX + dx;
        let newY = startOverlayY + dy;
        newX = Math.max(0, Math.min(newX, renderedImgWidth.value - cropOverlayWidth.value));
        newY = Math.max(0, Math.min(newY, renderedImgHeight.value - cropOverlayHeight.value));
        cropOverlayX.value = newX;
        cropOverlayY.value = newY;
    } else if (isResizingBox) {
        let newX = startOverlayX;
        let newY = startOverlayY;
        let newW = startOverlayW;
        let newH = startOverlayH;

        if (resizeHandle.includes('w')) {
            newW = startOverlayW - dx;
            newX = startOverlayX + dx;
            if (newW < 40) {
                newX = startOverlayX + startOverlayW - 40;
                newW = 40;
            }
            if (newX < 0) {
                newW = startOverlayW + startOverlayX;
                newX = 0;
            }
        }
        if (resizeHandle.includes('e')) {
            newW = startOverlayW + dx;
            if (newX + newW > renderedImgWidth.value) {
                newW = renderedImgWidth.value - newX;
            }
        }
        if (resizeHandle.includes('n')) {
            newH = startOverlayH - dy;
            newY = startOverlayY + dy;
            if (newH < 40) {
                newY = startOverlayY + startOverlayH - 40;
                newH = 40;
            }
            if (newY < 0) {
                newH = startOverlayH + startOverlayY;
                newY = 0;
            }
        }
        if (resizeHandle.includes('s')) {
            newH = startOverlayH + dy;
            if (newY + newH > renderedImgHeight.value) {
                newH = renderedImgHeight.value - newY;
            }
        }

        cropOverlayX.value = newX;
        cropOverlayY.value = newY;
        cropOverlayWidth.value = newW;
        cropOverlayHeight.value = newH;
    }

    updateRealCropValues();
};

const onMouseUp = () => {
    isDraggingBox = false;
    isResizingBox = false;
    window.removeEventListener('mousemove', onMouseMove);
    window.removeEventListener('mouseup', onMouseUp);
    window.removeEventListener('touchmove', onMouseMove);
    window.removeEventListener('touchend', onMouseUp);
};

// Result State
const processedResult = ref(null);

// Handle file selection
const onFileSelected = (event) => {
    const file = event.target.files?.[0] || event.dataTransfer?.files?.[0];
    if (!file) return;

    if (!file.type.startsWith('image/')) {
        alert('Please select a valid image file.');
        return;
    }

    selectedFile.value = file;
    originalSize.value = file.size;
    processedResult.value = null;
    processError.value = null;

    // Generate local preview URL
    if (originalPreviewUrl.value) URL.revokeObjectURL(originalPreviewUrl.value);
    originalPreviewUrl.value = URL.createObjectURL(file);

    // Read initial dimensions
    const img = new Image();
    img.src = originalPreviewUrl.value;
    img.onload = () => {
        originalDimensions.value = { width: img.width, height: img.height };
        if (!targetWidth.value) targetWidth.value = img.width;
        if (!targetHeight.value) targetHeight.value = img.height;
        if (!cropWidth.value) cropWidth.value = Math.min(300, img.width);
        if (!cropHeight.value) cropHeight.value = Math.min(300, img.height);
    };
};

const triggerFileInput = () => {
    document.getElementById('file-upload').click();
};

const formatBytes = (bytes) => {
    if (bytes === 0) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
};

// Process Image via backend API
const submitProcess = async () => {
    if (!selectedFile.value) return;

    isProcessing.value = true;
    processError.value = null;
    processedResult.value = null;

    const formData = new FormData();
    formData.append('image', selectedFile.value);
    formData.append('action', activeTab.value);

    // Append options based on active tab
    if (activeTab.value === 'resize') {
        if (targetWidth.value) formData.append('width', targetWidth.value);
        if (targetHeight.value) formData.append('height', targetHeight.value);
        formData.append('maintainAspectRatio', maintainAspect.value ? '1' : '0');
    } else if (activeTab.value === 'convert') {
        formData.append('format', targetFormat.value);
        formData.append('quality', quality.value);
    } else if (activeTab.value === 'adjust') {
        formData.append('greyscale', applyGreyscale.value ? '1' : '0');
        formData.append('blur', blurAmount.value);
        formData.append('brightness', brightnessAmount.value);
    } else if (activeTab.value === 'crop') {
        if (cropWidth.value) formData.append('crop_width', cropWidth.value);
        if (cropHeight.value) formData.append('crop_height', cropHeight.value);
        formData.append('crop_x', cropX.value);
        formData.append('crop_y', cropY.value);
    }

    try {
        uploadProgress.value = 0;
        const response = await axios.post('/api/process-image', formData, {
            headers: {
                'Content-Type': 'multipart/form-data'
            },
            onUploadProgress: (progressEvent) => {
                if (progressEvent.total) {
                    uploadProgress.value = Math.round((progressEvent.loaded * 100) / progressEvent.total);
                }
            }
        });

        const result = response.data;
        if (result.success) {
            processedResult.value = result;
            if (result.historyItem) {
                userHistory.value.unshift(result.historyItem);
                // keep max 12 items in view array to prevent overflow
                if (userHistory.value.length > 12) {
                    userHistory.value.pop();
                }
            }
            showResultModal.value = true;
        } else {
            processError.value = result.message || 'Processing failed.';
        }
    } catch (err) {
        processError.value = err.response?.data?.message || 'Network error during image processing.';
    } finally {
        isProcessing.value = false;
    }
};

const downloadProcessedImage = () => {
    if (processedResult.value?.downloadUrl) {
        window.location.href = processedResult.value.downloadUrl;
        return;
    }
    if (!processedResult.value?.dataUrl) return;
    const link = document.createElement('a');
    link.href = processedResult.value.dataUrl;
    link.download = `optimized_${selectedFile.value.name.replace(/\.[^/.]+$/, "")}.${processedResult.value.format}`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
};
</script>

<template>
    <PublicLayout>
        <Head>
            <title>FluxMedia · Image Studio — Convert, Resize & Compress Images Free</title>
            <meta name="description" content="Free online image studio. Convert, resize, crop, and adjust JPEG, PNG, WebP, GIF, and AVIF. No signup required." />
            <meta name="keywords" content="image converter, image compressor, online image optimizer, webp converter, png to webp, jpeg optimizer, free image tools" />
            
            <!-- Open Graph / Facebook -->
            <meta property="og:title" content="FluxMedia · Image Studio — Convert, Resize & Compress" />
            <meta property="og:description" content="Free online image studio. Convert, resize, crop, and adjust images instantly." />
            <meta property="og:image" content="/assets/images/fluxmedia_main.webp" />
            <meta property="og:url" content="https://fluxmedia.space/tools/image" />

            <!-- Twitter -->
            <meta name="twitter:title" content="FluxMedia · Image Studio" />
            <meta name="twitter:description" content="Optimize and convert your images for free. Fast online tool to compress JPEG, PNG, WebP, and AVIF files." />
            <meta name="twitter:image" content="/assets/images/fluxmedia_main.webp" />
        </Head>

        <!-- Main Body -->
        <div class="relative min-h-screen">
            <!-- Hero Header (Desktop Only) -->
            <div class="hidden lg:block relative overflow-hidden pt-8 pb-4 text-center">
                <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[500px] h-[200px] bg-gradient-to-tr from-purple-600/20 via-indigo-600/10 to-pink-600/10 blur-[100px] rounded-full pointer-events-none"></div>
                
                <div class="relative mx-auto max-w-4xl px-6 space-y-3">
                    <div class="flex items-center justify-center">
                        <span class="inline-flex items-center gap-x-2 rounded-full bg-purple-500/10 px-4 py-1.5 text-[10px] font-bold text-purple-300 border border-purple-500/20 uppercase tracking-[0.2em]">
                            <span class="h-1.5 w-1.5 rounded-full bg-purple-400 animate-pulse"></span>
                            Image Converter & Image Compressor
                        </span>
                    </div>

                    <h1 class="text-2xl lg:text-3xl font-extrabold tracking-tight text-white leading-tight uppercase tracking-tighter">
                        Image <span class="text-purple-400">Converter & Compressor</span>
                    </h1>
                </div>
            </div>

            <!-- Main Workspace Area -->
            <div class="mx-auto max-w-6xl px-3 lg:px-6 mt-6 lg:mt-0">
                <div class="rounded-3xl border border-gray-800/80 bg-[#121826]/80 backdrop-blur-xl shadow-2xl overflow-hidden p-5 lg:p-10">
                    
                    <!-- Storage Policy & Info -->
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-[10px] font-bold text-gray-500 uppercase tracking-widest flex items-center gap-x-2">
                            <svg class="h-3.5 w-3.5 text-purple-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                            Process Control
                        </h3>
                        <div class="group relative">
                            <button class="inline-flex items-center gap-x-1.5 rounded-full bg-amber-500/10 px-3 py-1 text-[9px] font-bold text-amber-500 border border-amber-500/20 hover:bg-amber-500/20 transition-colors shadow-lg shadow-amber-900/20">
                                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                Storage: {{ $page.props.auth?.user ? $page.props.appSettings.authRetentionDays + 'd' : $page.props.appSettings.guestRetentionHours + 'h' }}
                            </button>
                            <div class="absolute top-full right-0 mt-3 w-56 p-3 bg-[#1A2133] border border-gray-700 rounded-2xl shadow-2xl opacity-0 group-hover:opacity-100 transition-all transform -translate-y-2 group-hover:translate-y-0 pointer-events-none z-[100]">
                                <div class="absolute -top-1 right-6 w-2 h-2 bg-[#1A2133] border-t border-l border-gray-700 rotate-45"></div>
                                <p class="text-[9px] leading-relaxed text-gray-300 font-medium">
                                    <span class="text-amber-400 font-bold">Important:</span> Assets are permanently purged after 
                                    <span class="text-white font-bold">{{ $page.props.auth?.user ? $page.props.appSettings.authRetentionDays + ' days' : $page.props.appSettings.guestRetentionHours + ' hours' }}</span> 
                                    from our secure storage.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- File Upload Zone -->
                    <div 
                        @dragover.prevent 
                        @drop.prevent="onFileSelected"
                        @click="triggerFileInput"
                        class="relative flex flex-col items-center justify-center rounded-2xl border-2 border-dashed border-gray-700/80 hover:border-purple-500/60 bg-[#0B0F19]/50 py-10 lg:py-16 px-6 text-center cursor-pointer transition-all group"
                    >
                        <input id="file-upload" type="file" class="hidden" accept="image/*" @change="onFileSelected" />
                        
                        <div class="h-14 w-14 lg:h-16 lg:w-16 rounded-full bg-purple-500/10 flex items-center justify-center text-purple-400 mb-4 group-hover:scale-110 transition-transform">
                            <svg class="h-7 w-7 lg:h-8 lg:w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                            </svg>
                        </div>

                        <span class="text-sm lg:text-base font-semibold text-white">
                            {{ selectedFile ? selectedFile.name : 'Choose a file or drag & drop' }}
                        </span>
                        <p class="mt-1 text-[10px] lg:text-xs text-gray-400">
                            {{ selectedFile ? `Size: ${formatBytes(originalSize)}` : `Supports JPEG, PNG, WebP, GIF, AVIF & Mobile Formats up to ${$page.props.appSettings.maxUploadMb}MB` }}
                        </p>

                        <div v-if="selectedFile" @click.stop="selectedFile = null" class="absolute top-3 right-3 text-[10px] bg-red-500/20 text-red-400 hover:bg-red-500/30 px-2 py-1 rounded-lg transition-colors font-bold uppercase tracking-wider">
                            Clear
                        </div>
                    </div>

                    <!-- Processing Mode Tabs -->
                    <div v-if="selectedFile" class="mt-6 lg:mt-8">
                        <div class="flex overflow-x-auto no-scrollbar gap-2 p-1 bg-[#0B0F19] rounded-xl border border-gray-800">
                            <button 
                                v-for="tab in [
                                    { id: 'convert', label: 'Convert' },
                                    { id: 'resize', label: 'Resize' },
                                    { id: 'adjust', label: 'Adjust' },
                                    { id: 'crop', label: 'Crop' }
                                ]"
                                :key="tab.id"
                                @click="activeTab = tab.id"
                                :class="['flex-1 py-2 lg:py-2.5 px-4 rounded-lg text-xs lg:text-sm font-semibold transition-all whitespace-nowrap', activeTab === tab.id ? 'bg-gradient-to-r from-purple-600 to-indigo-600 text-white shadow-md' : 'text-gray-400 hover:text-white']"
                            >
                                {{ tab.label }}
                            </button>
                        </div>

                        <!-- Configuration Panels -->
                        <div class="mt-5 lg:mt-6 p-5 lg:p-6 rounded-2xl bg-[#0B0F19]/40 border border-gray-800/80">

                            <!-- Panel: Resize -->
                            <div v-if="activeTab === 'resize'" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Width (px)</label>
                                    <input type="number" v-model="targetWidth" class="w-full bg-[#0B0F19] border border-gray-700 rounded-xl py-2 px-3.5 text-white text-sm focus:border-purple-500 focus:outline-none" />
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Height (px)</label>
                                    <input type="number" v-model="targetHeight" class="w-full bg-[#0B0F19] border border-gray-700 rounded-xl py-2 px-3.5 text-white text-sm focus:border-purple-500 focus:outline-none" />
                                </div>
                                <div class="sm:col-span-2 pt-2">
                                    <label class="inline-flex items-center gap-x-2 cursor-pointer">
                                        <input type="checkbox" v-model="maintainAspect" class="rounded border-gray-700 bg-gray-900 accent-purple-500 text-purple-600" />
                                        <span class="text-xs text-gray-400 font-medium">Maintain aspect ratio</span>
                                    </label>
                                </div>
                            </div>

                            <!-- Panel: Convert -->
                            <div v-if="activeTab === 'convert'" class="space-y-5">
                                <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider">Target Format</label>
                                <div class="grid grid-cols-3 sm:grid-cols-4 gap-2">
                                    <label v-for="fmt in ['webp', 'jpeg', 'png', 'gif', 'avif', 'ico']" :key="fmt" :class="['flex items-center justify-center py-2 px-2 rounded-xl border cursor-pointer text-[10px] font-bold uppercase transition-all', targetFormat === fmt ? 'bg-purple-600/20 border-purple-500 text-purple-300' : 'border-gray-800 bg-[#0B0F19] text-gray-500 hover:border-gray-700']">
                                        <input type="radio" v-model="targetFormat" :value="fmt" class="hidden" />
                                        {{ fmt }}
                                    </label>
                                </div>
                                <div v-if="['webp', 'jpeg', 'avif'].includes(targetFormat)" class="pt-2">
                                    <div class="flex justify-between items-center text-[10px] mb-2 font-bold uppercase tracking-wider">
                                        <span class="text-gray-500">Quality:</span>
                                        <span class="text-purple-400">{{ quality }}%</span>
                                    </div>
                                    <input type="range" v-model="quality" min="10" max="100" class="w-full h-1.5 bg-gray-800 rounded-lg appearance-none cursor-pointer accent-purple-500" />
                                </div>
                            </div>

                            <!-- Panel: Adjust -->
                            <div v-if="activeTab === 'adjust'" class="space-y-5">
                                <label class="inline-flex items-center gap-x-2 cursor-pointer">
                                    <input type="checkbox" v-model="applyGreyscale" class="rounded border-gray-700 bg-gray-900 accent-purple-500 text-purple-600" />
                                    <span class="text-xs text-gray-400 font-medium">Grayscale Filter</span>
                                </label>

                                <div>
                                    <div class="flex justify-between items-center text-[10px] mb-2 font-bold uppercase tracking-wider">
                                        <span class="text-gray-500">Blur:</span>
                                        <span class="text-purple-400">{{ blurAmount }}px</span>
                                    </div>
                                    <input type="range" v-model="blurAmount" min="0" max="100" class="w-full h-1.5 bg-gray-800 rounded-lg appearance-none cursor-pointer accent-purple-500" />
                                </div>

                                <div>
                                    <div class="flex justify-between items-center text-[10px] mb-2 font-bold uppercase tracking-wider">
                                        <span class="text-gray-500">Brightness:</span>
                                        <span class="text-purple-400">{{ brightnessAmount }}</span>
                                    </div>
                                    <input type="range" v-model="brightnessAmount" min="-100" max="100" class="w-full h-1.5 bg-gray-800 rounded-lg appearance-none cursor-pointer accent-purple-500" />
                                </div>
                            </div>

                            <!-- Panel: Crop -->
                            <div v-if="activeTab === 'crop'" class="space-y-4">
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Width (px)</label>
                                        <input type="number" v-model="cropWidth" @input="syncOverlayFromInputs" class="w-full bg-[#0B0F19] border border-gray-700 rounded-xl py-2 px-3 text-white text-sm focus:border-purple-500 focus:outline-none" />
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Height (px)</label>
                                        <input type="number" v-model="cropHeight" @input="syncOverlayFromInputs" class="w-full bg-[#0B0F19] border border-gray-700 rounded-xl py-2 px-3 text-white text-sm focus:border-purple-500 focus:outline-none" />
                                    </div>
                                </div>
                                <div class="text-[10px] text-purple-400/80 font-medium leading-relaxed bg-purple-500/5 p-3 rounded-lg border border-purple-500/10">
                                    Tip: You can also drag the purple box directly on the preview below to select your area.
                                </div>
                            </div>

                            <!-- Progress Indicators -->
                            <div v-if="isProcessing" class="mt-6 pt-4 border-t border-gray-800/80">
                                <div class="flex justify-between items-center text-[10px] mb-2 font-bold uppercase tracking-wider">
                                    <span class="text-purple-300">Processing Stream...</span>
                                    <span class="text-purple-400">{{ uploadProgress }}%</span>
                                </div>
                                <div class="w-full bg-gray-900 rounded-full h-1.5 overflow-hidden">
                                    <div class="bg-gradient-to-r from-purple-600 to-pink-500 h-full transition-all duration-300" :style="{ width: uploadProgress + '%' }"></div>
                                </div>
                            </div>

                            <!-- Action Button -->
                            <div class="mt-6">
                                <button
                                    @click="submitProcess"
                                    :disabled="isProcessing"
                                    class="w-full lg:w-auto inline-flex items-center justify-center gap-x-2 rounded-2xl bg-purple-600 py-3.5 px-8 text-sm font-bold text-white shadow-lg shadow-purple-600/30 hover:bg-purple-500 transition-all disabled:opacity-50 active:scale-95"
                                >
                                    <svg v-if="isProcessing" class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                    </svg>
                                    <span>{{ isProcessing ? 'Processing...' : 'Convert & Optimize' }}</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Error Response -->
                    <div v-if="processError" class="mt-6 rounded-xl bg-red-500/10 border border-red-500/20 p-4 text-xs text-red-400 flex items-center gap-x-3">
                        <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>{{ processError }}</span>
                    </div>

                    <!-- Live Results Workspace -->
                    <div v-if="processedResult || originalPreviewUrl" class="mt-10 pt-8 border-t border-gray-800">
                        <h3 class="text-sm lg:text-lg font-bold text-white mb-6 flex items-center justify-between">
                            <span>Workspace Preview</span>
                            <span v-if="processedResult" class="text-[10px] font-bold py-1 px-3 rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 uppercase tracking-widest">
                                Optimized {{ processedResult.reductionPercentage }}%
                            </span>
                        </h3>

                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 lg:gap-8">
                            
                            <!-- Original Frame -->
                            <div v-if="originalPreviewUrl" class="rounded-2xl border border-gray-800 bg-[#0B0F19]/60 p-4 lg:p-5 overflow-hidden">
                                <div class="flex justify-between items-center text-[10px] text-gray-500 mb-4 pb-2 border-b border-gray-800/80 font-bold uppercase tracking-wider">
                                    <span>Original File</span>
                                    <span>{{ formatBytes(originalSize) }}</span>
                                </div>
                                <div class="flex items-center justify-center bg-[#05070C] rounded-xl p-2 min-h-[200px] lg:min-h-[250px] relative overflow-hidden">
                                    <div class="relative inline-block max-w-full overflow-hidden">
                                        <img :src="originalPreviewUrl" ref="cropImageRef" class="block max-h-[250px] lg:max-h-[300px] rounded-lg object-contain pointer-events-none" @load="initCropOverlay" alt="Original Frame" />
                                        
                                        <!-- Interactive Visual Cropping Overlay -->
                                        <div v-if="activeTab === 'crop' && cropOverlayWidth > 0" class="absolute inset-0 pointer-events-none overflow-hidden rounded-lg">
                                            <div class="absolute inset-0 bg-black/60"></div>
                                            <div 
                                                class="absolute border-2 border-purple-500 cursor-move box-border pointer-events-auto shadow-[0_0_0_9999px_rgba(0,0,0,0.65)]"
                                                :style="{
                                                    left: cropOverlayX + 'px',
                                                    top: cropOverlayY + 'px',
                                                    width: cropOverlayWidth + 'px',
                                                    height: cropOverlayHeight + 'px'
                                                }"
                                                @mousedown.prevent="startDragBox"
                                                @touchstart.prevent="startDragBox"
                                            >
                                                <div class="absolute inset-x-0 top-1/3 border-t border-white/20"></div>
                                                <div class="absolute inset-x-0 top-2/3 border-t border-white/20"></div>
                                                <div class="absolute inset-y-0 left-1/3 border-l border-white/20"></div>
                                                <div class="absolute inset-y-0 left-2/3 border-l border-white/20"></div>
                                                <div class="absolute -top-2 -left-2 w-5 h-5 bg-purple-500 border-2 border-white rounded-full cursor-nwse-resize" @mousedown.stop.prevent="startResize('nw', $event)" @touchstart.stop.prevent="startResize('nw', $event)"></div>
                                                <div class="absolute -top-2 -right-2 w-5 h-5 bg-purple-500 border-2 border-white rounded-full cursor-nesw-resize" @mousedown.stop.prevent="startResize('ne', $event)" @touchstart.stop.prevent="startResize('ne', $event)"></div>
                                                <div class="absolute -bottom-2 -left-2 w-5 h-5 bg-purple-500 border-2 border-white rounded-full cursor-nesw-resize" @mousedown.stop.prevent="startResize('sw', $event)" @touchstart.stop.prevent="startResize('sw', $event)"></div>
                                                <div class="absolute -bottom-2 -right-2 w-5 h-5 bg-purple-500 border-2 border-white rounded-full cursor-nwse-resize" @mousedown.stop.prevent="startResize('se', $event)" @touchstart.stop.prevent="startResize('se', $event)"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-3 text-right text-[10px] text-gray-600 font-mono">
                                    {{ originalDimensions.width }} × {{ originalDimensions.height }} px
                                </div>
                            </div>

                            <!-- Processed Frame -->
                            <div v-if="processedResult" class="rounded-2xl border border-purple-500/30 bg-[#0B0F19]/60 p-4 lg:p-5 overflow-hidden">
                                <div class="flex justify-between items-center text-[10px] text-purple-400/80 mb-4 pb-2 border-b border-gray-800/80 font-bold uppercase tracking-wider">
                                    <span>Optimized Image</span>
                                    <span class="text-emerald-400">{{ formatBytes(processedResult.processedSizeBytes) }}</span>
                                </div>
                                <div class="flex items-center justify-center bg-[#05070C] rounded-xl p-2 min-h-[200px] lg:min-h-[250px]">
                                    <img :src="processedResult.dataUrl" class="max-h-[250px] lg:max-h-[300px] rounded-lg object-contain" alt="Processed Output" />
                                </div>
                                <div class="mt-4 flex justify-between items-center">
                                    <span class="text-[10px] text-gray-600 font-mono">
                                        {{ processedResult.dimensions?.width }} × {{ processedResult.dimensions?.height }} px
                                    </span>
                                    <button
                                        @click="downloadProcessedImage"
                                        class="py-2 px-4 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-[10px] uppercase tracking-widest flex items-center gap-x-2 transition-all shadow-lg shadow-emerald-600/20 active:scale-95"
                                    >
                                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                        </svg>
                                        <span>Download</span>
                                    </button>
                                </div>
                            </div>
                            <div v-else-if="originalPreviewUrl" class="rounded-2xl border border-dashed border-gray-800 flex flex-col items-center justify-center p-8 text-center bg-[#0B0F19]/20">
                                <div class="h-12 w-12 rounded-full bg-gray-800/50 flex items-center justify-center text-gray-700 mb-3">
                                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                </div>
                                <span class="text-[10px] text-gray-600 uppercase font-bold tracking-widest">Output will appear here</span>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <!-- Live History Feed -->
            <div class="mx-auto max-w-6xl px-3 lg:px-6 mt-10 lg:mt-12">
                <div class="rounded-3xl border border-gray-800/80 bg-[#121826]/60 backdrop-blur-xl p-5 lg:p-7 shadow-2xl">
                    <div class="flex items-center justify-between border-b border-gray-800/80 pb-4 mb-5">
                        <span class="text-[10px] font-bold text-purple-400 uppercase tracking-widest flex items-center gap-x-2">
                            <span class="h-2 w-2 rounded-full bg-purple-500 animate-pulse"></span>
                            Live Session Feed
                        </span>
                        <Link :href="route('history')" class="text-[10px] font-bold text-gray-500 hover:text-purple-400 transition-colors uppercase tracking-widest">View All →</Link>
                    </div>
                    
                    <div v-if="userHistory.length === 0" class="py-10 text-center">
                        <p class="text-xs text-gray-600 font-medium italic">No recent engine cycles detected in current session.</p>
                    </div>
                    <div v-else class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 lg:gap-4">
                        <div v-for="item in userHistory" :key="item.id" class="group relative aspect-square rounded-xl overflow-hidden bg-[#0B0F19] border border-gray-800 hover:border-purple-500/50 transition-all">
                            <img :src="item.output_url || item.data_url" class="h-full w-full object-cover opacity-60 group-hover:opacity-100 group-hover:scale-110 transition-all" />
                            <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/20 to-transparent opacity-0 group-hover:opacity-100 transition-opacity flex flex-col justify-end p-2.5">
                                <div class="flex items-center justify-between gap-x-2">
                                    <div class="flex-1 min-w-0">
                                        <p class="text-[9px] font-bold text-white uppercase truncate">{{ item.original_name }}</p>
                                        <p class="text-[8px] text-purple-300 font-bold uppercase">{{ item.format }} · {{ item.created_at }}</p>
                                    </div>
                                    <a 
                                        :href="item.download_url" 
                                        class="h-7 w-7 rounded-lg bg-emerald-500 hover:bg-emerald-400 text-white flex items-center justify-center shrink-0 transition-colors shadow-lg shadow-emerald-500/20"
                                        title="Download Asset"
                                    >
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                        </svg>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <footer class="mt-16 lg:mt-20 border-t border-gray-800/80 pt-8 pb-12 text-center">
                <p class="text-[10px] text-gray-600 font-bold uppercase tracking-[0.2em]">FluxMedia Studio · Core v2.4.0</p>
            </footer>

            <!-- Result Modal -->
            <div v-if="showResultModal" class="fixed inset-0 z-[200] flex items-center justify-center p-4 sm:p-6 py-12 lg:py-20 overflow-y-auto">
                <!-- Backdrop -->
                <div @click="showResultModal = false" class="fixed inset-0 bg-[#05070C]/90 backdrop-blur-sm"></div>
                
                <!-- Modal Content -->
                <div class="relative w-full max-w-lg bg-[#121826] border border-gray-800 rounded-[2.5rem] shadow-2xl overflow-hidden animate-in fade-in zoom-in duration-300 my-auto">
                    <!-- Close Button -->
                    <button @click="showResultModal = false" class="absolute top-6 right-6 p-2 rounded-full bg-gray-800/50 text-gray-400 hover:text-white transition-colors z-10">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>

                    <div class="p-8">
                        <div class="text-center mb-6">
                            <div class="h-16 w-16 bg-emerald-500/10 rounded-2xl flex items-center justify-center text-emerald-400 mx-auto mb-4">
                                <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                            </div>
                            <h2 class="text-2xl font-bold text-white mb-2">Processing Complete</h2>
                            <p class="text-sm text-gray-400 font-medium">Your optimized asset is ready for deployment.</p>
                        </div>

                        <!-- Preview Frame -->
                        <div class="rounded-3xl bg-[#0B0F19] p-4 border border-gray-800/50 mb-6 group">
                            <img :src="processedResult.dataUrl" class="w-full max-h-[300px] object-contain rounded-2xl shadow-xl transition-transform group-hover:scale-[1.02]" alt="Result Preview" />
                        </div>

                        <!-- Stats Bar -->
                        <div class="grid grid-cols-2 gap-4 mb-8">
                            <div class="bg-[#1A2133]/50 p-4 rounded-2xl border border-gray-800/50 text-center">
                                <span class="block text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-1">Savings</span>
                                <span class="text-lg font-black text-emerald-400">{{ processedResult.reductionPercentage }}%</span>
                            </div>
                            <div class="bg-[#1A2133]/50 p-4 rounded-2xl border border-gray-800/50 text-center">
                                <span class="block text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-1">Final Size</span>
                                <span class="text-lg font-black text-purple-400">{{ formatBytes(processedResult.processedSizeBytes) }}</span>
                            </div>
                        </div>

                        <!-- Main Action -->
                        <button 
                            @click="downloadProcessedImage(); showResultModal = false"
                            class="w-full py-5 rounded-2xl bg-gradient-to-tr from-purple-600 to-indigo-600 hover:from-purple-500 hover:to-indigo-500 text-white font-bold text-base shadow-xl shadow-purple-600/20 active:scale-[0.98] transition-all flex items-center justify-center gap-x-3"
                        >
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                            Download Optimized File
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </PublicLayout>
</template>

<style scoped>
.no-scrollbar::-webkit-scrollbar {
    display: none;
}
.no-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
</style>
