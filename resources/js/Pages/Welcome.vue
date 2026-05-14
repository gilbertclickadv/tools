<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ref, computed, watch, nextTick } from 'vue';
import axios from 'axios';

defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
    laravelVersion: String,
    phpVersion: String,
});

// State Management
const selectedFile = ref(null);
const originalPreviewUrl = ref(null);
const originalDimensions = ref({ width: 0, height: 0 });
const originalSize = ref(0);

const activeTab = ref('convert'); // convert, resize, adjust
const isProcessing = ref(false);
const processError = ref(null);
const uploadProgress = ref(0);

// Form Options
const quality = ref(80);
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
    <Head title="Premium Image Processor" />

    <div class="min-h-screen bg-[#0B0F19] text-gray-100 font-jakarta selection:bg-purple-500 selection:text-white pb-20">
        <!-- Premium Navigation Header -->
        <header class="border-b border-gray-800/60 bg-[#0B0F19]/80 backdrop-blur-md sticky top-0 z-50">
            <div class="mx-auto max-w-7xl px-6 flex h-20 items-center justify-between">
                <div class="flex items-center gap-x-3">
                    <div class="h-10 w-10 rounded-xl bg-gradient-to-tr from-purple-600 to-pink-600 flex items-center justify-center shadow-lg shadow-purple-500/20 font-bold text-xl">
                        M
                    </div>
                    <span class="text-xl font-bold tracking-tight bg-gradient-to-r from-white via-gray-200 to-purple-300 bg-clip-text text-transparent">
                        MidasMedia
                    </span>
                </div>

                <nav class="flex items-center gap-x-4 text-sm font-medium">
                    <Link
                        v-if="$page.props.auth?.user"
                        :href="route('dashboard')"
                        class="rounded-lg px-4 py-2 bg-gray-800/80 hover:bg-gray-700/80 transition-all border border-gray-700/50"
                    >
                        Dashboard
                    </Link>
                    <Link
                        v-if="$page.props.auth?.user?.is_admin"
                        :href="route('admin.dashboard')"
                        class="rounded-lg px-4 py-2 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-500 hover:to-indigo-500 transition-all text-white font-semibold shadow-md shadow-purple-600/20"
                    >
                        Admin Portal
                    </Link>
                    <template v-if="!$page.props.auth?.user">
                        <Link :href="route('login')" class="text-gray-400 hover:text-white transition-colors">
                            Log in
                        </Link>
                        <Link
                            v-if="canRegister"
                            :href="route('register')"
                            class="rounded-lg px-4 py-2 bg-purple-600 hover:bg-purple-500 text-white transition-all shadow-md shadow-purple-600/20 font-semibold"
                        >
                            Register
                        </Link>
                    </template>
                </nav>
            </div>
        </header>

        <!-- Hero Section -->
        <div class="relative overflow-hidden pt-16 pb-12 text-center">
            <!-- Glow background effect -->
            <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[300px] bg-gradient-to-tr from-purple-600/20 to-pink-600/10 blur-[120px] rounded-full pointer-events-none"></div>

            <div class="relative mx-auto max-w-4xl px-6">
                <span class="inline-flex items-center gap-x-2 rounded-full bg-purple-500/10 px-4 py-1.5 text-xs font-semibold text-purple-300 border border-purple-500/20 mb-6">
                    <span class="h-1.5 w-1.5 rounded-full bg-purple-400 animate-pulse"></span>
                    UI/UX Pro Max Engine Integrations
                </span>

                <h1 class="text-4xl sm:text-6xl font-extrabold tracking-tight text-white max-w-3xl mx-auto leading-tight">
                    Next-Gen Media <br/>
                    <span class="bg-gradient-to-r from-purple-400 via-pink-400 to-indigo-400 bg-clip-text text-transparent">
                        Processing & Refinement
                    </span>
                </h1>
                <p class="mt-4 text-base sm:text-lg text-gray-400 max-w-2xl mx-auto">
                    Compress byte ratios instantly, transition responsive aspect layers, and convert media stream topologies locally backed by scalable Intervention server buffers.
                </p>
            </div>
        </div>

        <!-- Main Workspace Area -->
        <div class="mx-auto max-w-6xl px-6">
            <div class="rounded-2xl border border-gray-800/80 bg-[#121826]/80 backdrop-blur-xl shadow-2xl overflow-hidden p-6 sm:p-10">
                
                <!-- File Upload Zone -->
                <div 
                    @dragover.prevent 
                    @drop.prevent="onFileSelected"
                    @click="triggerFileInput"
                    class="relative flex flex-col items-center justify-center rounded-xl border-2 border-dashed border-gray-700/80 hover:border-purple-500/60 bg-[#0B0F19]/50 py-12 px-6 text-center cursor-pointer transition-all group"
                >
                    <input id="file-upload" type="file" class="hidden" accept="image/*" @change="onFileSelected" />
                    
                    <div class="h-16 w-16 rounded-full bg-purple-500/10 flex items-center justify-center text-purple-400 mb-4 group-hover:scale-110 transition-transform">
                        <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                        </svg>
                    </div>

                    <span class="text-base font-semibold text-white">
                        {{ selectedFile ? selectedFile.name : 'Choose a file or drag & drop it here' }}
                    </span>
                    <p class="mt-1 text-xs text-gray-400">
                        {{ selectedFile ? `Size: ${formatBytes(originalSize)} | Format: ${selectedFile.type}` : 'Supports JPEG, PNG, WebP, GIF up to 20MB' }}
                    </p>

                    <div v-if="selectedFile" @click.stop="selectedFile = null" class="absolute top-3 right-3 text-xs bg-red-500/20 text-red-400 hover:bg-red-500/30 px-2.5 py-1 rounded-md transition-colors">
                        Clear File
                    </div>
                </div>

                <!-- Processing Mode Tabs -->
                <div v-if="selectedFile" class="mt-8">
                    <div class="flex flex-wrap gap-2 p-1.5 bg-[#0B0F19] rounded-xl border border-gray-800">
                        <button 
                            @click="activeTab = 'convert'"
                            :class="['flex-1 py-2.5 px-4 rounded-lg text-sm font-semibold transition-all', activeTab === 'convert' ? 'bg-gradient-to-r from-purple-600 to-indigo-600 text-white shadow-md' : 'text-gray-400 hover:text-white']"
                        >
                            Convert Format
                        </button>
                        <button 
                            @click="activeTab = 'resize'"
                            :class="['flex-1 py-2.5 px-4 rounded-lg text-sm font-semibold transition-all', activeTab === 'resize' ? 'bg-gradient-to-r from-purple-600 to-indigo-600 text-white shadow-md' : 'text-gray-400 hover:text-white']"
                        >
                            Resize Dimension
                        </button>
                        <button 
                            @click="activeTab = 'adjust'"
                            :class="['flex-1 py-2.5 px-4 rounded-lg text-sm font-semibold transition-all', activeTab === 'adjust' ? 'bg-gradient-to-r from-purple-600 to-indigo-600 text-white shadow-md' : 'text-gray-400 hover:text-white']"
                        >
                            Filter Adjustments
                        </button>
                        <button 
                            @click="activeTab = 'crop'"
                            :class="['flex-1 py-2.5 px-4 rounded-lg text-sm font-semibold transition-all', activeTab === 'crop' ? 'bg-gradient-to-r from-purple-600 to-indigo-600 text-white shadow-md' : 'text-gray-400 hover:text-white']"
                        >
                            Crop Region
                        </button>
                    </div>

                    <!-- Configuration Panels -->
                    <div class="mt-6 p-6 rounded-xl bg-[#0B0F19]/40 border border-gray-800/80">

                        <!-- Panel 2: Resize -->
                        <div v-if="activeTab === 'resize'" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-gray-400 mb-1">Target Width (px)</label>
                                <input 
                                    type="number" 
                                    v-model="targetWidth" 
                                    class="w-full bg-[#0B0F19] border border-gray-700 rounded-lg py-2 px-3 text-white text-sm focus:border-purple-500 focus:outline-none"
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-400 mb-1">Target Height (px)</label>
                                <input 
                                    type="number" 
                                    v-model="targetHeight" 
                                    class="w-full bg-[#0B0F19] border border-gray-700 rounded-lg py-2 px-3 text-white text-sm focus:border-purple-500 focus:outline-none"
                                />
                            </div>
                            <div class="sm:col-span-2 pt-2">
                                <label class="inline-flex items-center gap-x-2 cursor-pointer">
                                    <input type="checkbox" v-model="maintainAspect" class="rounded border-gray-700 bg-gray-900 accent-purple-500 text-purple-600" />
                                    <span class="text-xs text-gray-300 font-medium">Auto-scale boundary targets to maintain raw aspect ratio</span>
                                </label>
                            </div>
                        </div>

                        <!-- Panel 3: Convert -->
                        <div v-if="activeTab === 'convert'" class="space-y-4">
                            <label class="block text-xs font-medium text-gray-400">Target Export Stream Codec</label>
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                                <label v-for="fmt in ['webp', 'jpeg', 'png', 'gif']" :key="fmt" :class="['flex items-center justify-center py-2.5 px-3 rounded-lg border cursor-pointer text-xs font-semibold uppercase transition-all', targetFormat === fmt ? 'bg-purple-600/20 border-purple-500 text-purple-300' : 'border-gray-800 bg-[#0B0F19] text-gray-500 hover:border-gray-700']">
                                    <input type="radio" v-model="targetFormat" :value="fmt" class="hidden" />
                                    {{ fmt }} Stream
                                </label>
                            </div>
                            <div v-if="['webp', 'jpeg'].includes(targetFormat)" class="pt-2">
                                <div class="flex justify-between items-center text-xs mb-1">
                                    <span class="text-gray-400">Encoder Quality:</span>
                                    <span class="text-purple-400 font-bold">{{ quality }}%</span>
                                </div>
                                <input type="range" v-model="quality" min="10" max="100" class="w-full h-1.5 bg-gray-800 rounded-lg appearance-none cursor-pointer accent-purple-500" />
                            </div>
                        </div>

                        <!-- Panel 4: Adjust -->
                        <div v-if="activeTab === 'adjust'" class="space-y-4">
                            <label class="inline-flex items-center gap-x-2 cursor-pointer">
                                <input type="checkbox" v-model="applyGreyscale" class="rounded border-gray-700 bg-gray-900 accent-purple-500 text-purple-600" />
                                <span class="text-xs text-gray-300 font-medium">Strip Chromatic Channel (Monochrome/Greyscale)</span>
                            </label>

                            <div>
                                <div class="flex justify-between items-center text-xs mb-1">
                                    <span class="text-gray-400">Gaussian Blur Radius:</span>
                                    <span class="text-purple-400 font-bold">{{ blurAmount }}px</span>
                                </div>
                                <input type="range" v-model="blurAmount" min="0" max="100" class="w-full h-1.5 bg-gray-800 rounded-lg appearance-none cursor-pointer accent-purple-500" />
                            </div>

                            <div>
                                <div class="flex justify-between items-center text-xs mb-1">
                                    <span class="text-gray-400">Brightness Offset:</span>
                                    <span class="text-purple-400 font-bold">{{ brightnessAmount }}</span>
                                </div>
                                <input type="range" v-model="brightnessAmount" min="-100" max="100" class="w-full h-1.5 bg-gray-800 rounded-lg appearance-none cursor-pointer accent-purple-500" />
                            </div>
                        </div>

                        <!-- Panel 5: Crop -->
                        <div v-if="activeTab === 'crop'" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="sm:col-span-2 pb-1">
                                <span class="text-xs text-purple-400 font-semibold block mb-1">💡 Interactive Studio Cropping Guide</span>
                                <p class="text-xs text-gray-400">Drag and resize the active purple overlay box below directly over your uploaded graphic frame to intuitively extract any specific target region.</p>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-400 mb-1">Crop Width (px)</label>
                                <input 
                                    type="number" 
                                    v-model="cropWidth" 
                                    @input="syncOverlayFromInputs"
                                    class="w-full bg-[#0B0F19] border border-gray-700 rounded-lg py-2 px-3 text-white text-sm focus:border-purple-500 focus:outline-none"
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-400 mb-1">Crop Height (px)</label>
                                <input 
                                    type="number" 
                                    v-model="cropHeight" 
                                    @input="syncOverlayFromInputs"
                                    class="w-full bg-[#0B0F19] border border-gray-700 rounded-lg py-2 px-3 text-white text-sm focus:border-purple-500 focus:outline-none"
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-400 mb-1">Offset X / Left (px)</label>
                                <input 
                                    type="number" 
                                    v-model="cropX" 
                                    @input="syncOverlayFromInputs"
                                    class="w-full bg-[#0B0F19] border border-gray-700 rounded-lg py-2 px-3 text-white text-sm focus:border-purple-500 focus:outline-none"
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-400 mb-1">Offset Y / Top (px)</label>
                                <input 
                                    type="number" 
                                    v-model="cropY" 
                                    @input="syncOverlayFromInputs"
                                    class="w-full bg-[#0B0F19] border border-gray-700 rounded-lg py-2 px-3 text-white text-sm focus:border-purple-500 focus:outline-none"
                                />
                            </div>
                        </div>

                        <!-- Progress Indicators -->
                        <div v-if="isProcessing" class="mt-6 pt-4 border-t border-gray-800/80">
                            <div class="flex justify-between items-center text-xs mb-2">
                                <span class="text-purple-300 font-medium flex items-center gap-x-2">
                                    <svg class="animate-spin h-3.5 w-3.5 text-purple-400" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                    </svg>
                                    <span>{{ uploadProgress < 100 ? 'Transmitting payload buffer...' : 'Processing multi-channel graphics buffer...' }}</span>
                                </span>
                                <span class="text-purple-400 font-bold">{{ uploadProgress }}%</span>
                            </div>
                            <div class="w-full bg-gray-900 rounded-full h-2 overflow-hidden border border-gray-800">
                                <div class="bg-gradient-to-r from-purple-600 to-pink-500 h-full rounded-full transition-all duration-300" :style="{ width: uploadProgress + '%' }"></div>
                            </div>
                        </div>

                        <!-- Action Button -->
                        <div class="mt-6 flex justify-end">
                            <button
                                @click="submitProcess"
                                :disabled="isProcessing"
                                class="inline-flex items-center gap-x-2 rounded-xl bg-purple-600 py-3 px-6 text-sm font-semibold text-white shadow-lg shadow-purple-600/30 hover:bg-purple-500 transition-all disabled:opacity-50"
                            >
                                <span>Process Image Stream</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Error Response Banner -->
                <div v-if="processError" class="mt-6 rounded-xl bg-red-500/10 border border-red-500/20 p-4 text-sm text-red-400 flex items-center gap-x-3">
                    <svg class="h-5 w-5 shrink-0 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>{{ processError }}</span>
                </div>

                <!-- Live Side-by-Side View Results -->
                <div v-if="processedResult || originalPreviewUrl" class="mt-10 pt-8 border-t border-gray-800">
                    <h3 class="text-lg font-bold text-white mb-6 flex items-center gap-x-2">
                        <span>Workspace Preview Comparison</span>
                        <span v-if="processedResult" class="text-xs font-medium py-1 px-2.5 rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                            Saved {{ processedResult.reductionPercentage }}% Storage
                        </span>
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        
                        <!-- Original Frame -->
                        <div v-if="originalPreviewUrl" class="rounded-xl border border-gray-800 bg-[#0B0F19]/60 p-4 overflow-hidden">
                            <div class="flex justify-between items-center text-xs text-gray-400 mb-3 pb-2 border-b border-gray-800/80">
                                <span class="font-semibold text-gray-300">Original Uploaded Payload</span>
                                <span>{{ formatBytes(originalSize) }}</span>
                            </div>
                            <div class="flex items-center justify-center bg-[#05070C] rounded-lg p-2 min-h-[220px] select-none">
                                <div class="relative inline-block max-w-full overflow-hidden" ref="cropContainerRef">
                                    <img :src="originalPreviewUrl" ref="cropImageRef" class="block max-h-[300px] rounded object-contain pointer-events-none select-none" @load="initCropOverlay" alt="Original Frame" />
                                    
                                    <!-- Interactive Visual Cropping Overlay -->
                                    <div v-if="activeTab === 'crop' && cropOverlayWidth > 0" class="absolute inset-0 pointer-events-none overflow-hidden">
                                        <!-- Darkened Backdrop -->
                                        <div class="absolute inset-0 bg-black/60"></div>
                                        
                                        <!-- Active Clear Crop Box -->
                                        <div 
                                            class="absolute border-2 border-purple-500 cursor-move box-border pointer-events-auto"
                                            :style="{
                                                left: cropOverlayX + 'px',
                                                top: cropOverlayY + 'px',
                                                width: cropOverlayWidth + 'px',
                                                height: cropOverlayHeight + 'px',
                                                boxShadow: '0 0 0 9999px rgba(0, 0, 0, 0.65)'
                                            }"
                                            @mousedown.prevent="startDragBox"
                                            @touchstart.prevent="startDragBox"
                                        >
                                            <!-- Visual Grid Guide Lines -->
                                            <div class="absolute inset-x-0 top-1/3 border-t border-white/30 pointer-events-none"></div>
                                            <div class="absolute inset-x-0 top-2/3 border-t border-white/30 pointer-events-none"></div>
                                            <div class="absolute inset-y-0 left-1/3 border-l border-white/30 pointer-events-none"></div>
                                            <div class="absolute inset-y-0 left-2/3 border-l border-white/30 pointer-events-none"></div>

                                            <!-- Center Indicator -->
                                            <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                                                <div class="w-2 h-2 bg-purple-500/50 rounded-full"></div>
                                            </div>

                                            <!-- 4 Corner Resizable Handles -->
                                            <div class="absolute -top-2 -left-2 w-4 h-4 bg-purple-500 border-2 border-white rounded-full cursor-nwse-resize pointer-events-auto" @mousedown.stop.prevent="startResize('nw', $event)" @touchstart.stop.prevent="startResize('nw', $event)"></div>
                                            <div class="absolute -top-2 -right-2 w-4 h-4 bg-purple-500 border-2 border-white rounded-full cursor-nesw-resize pointer-events-auto" @mousedown.stop.prevent="startResize('ne', $event)" @touchstart.stop.prevent="startResize('ne', $event)"></div>
                                            <div class="absolute -bottom-2 -left-2 w-4 h-4 bg-purple-500 border-2 border-white rounded-full cursor-nesw-resize pointer-events-auto" @mousedown.stop.prevent="startResize('sw', $event)" @touchstart.stop.prevent="startResize('sw', $event)"></div>
                                            <div class="absolute -bottom-2 -right-2 w-4 h-4 bg-purple-500 border-2 border-white rounded-full cursor-nwse-resize pointer-events-auto" @mousedown.stop.prevent="startResize('se', $event)" @touchstart.stop.prevent="startResize('se', $event)"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-2 text-right text-[11px] text-gray-500 font-mono">
                                {{ originalDimensions.width }} × {{ originalDimensions.height }} px
                            </div>
                        </div>

                        <!-- Processed Frame -->
                        <div v-if="processedResult" class="rounded-xl border border-purple-500/30 bg-[#0B0F19]/60 p-4 overflow-hidden relative">
                            <div class="flex justify-between items-center text-xs text-purple-300 mb-3 pb-2 border-b border-gray-800/80">
                                <span class="font-semibold text-white">Manipulated Output Buffer</span>
                                <span class="text-emerald-400 font-bold">{{ formatBytes(processedResult.processedSizeBytes) }}</span>
                            </div>
                            <div class="flex items-center justify-center bg-[#05070C] rounded-lg p-2 min-h-[220px]">
                                <img :src="processedResult.dataUrl" class="max-h-[300px] rounded object-contain" alt="Processed Output" />
                            </div>
                            <div class="mt-3 flex justify-between items-center">
                                <span class="text-[11px] text-gray-500 font-mono">
                                    {{ processedResult.dimensions?.width }} × {{ processedResult.dimensions?.height }} px
                                </span>
                                <button
                                    @click="downloadProcessedImage"
                                    class="py-1.5 px-3 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white font-semibold text-xs flex items-center gap-x-1.5 transition-all shadow-md shadow-emerald-600/20"
                                >
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                    </svg>
                                    <span>Download {{ processedResult.format.toUpperCase() }}</span>
                                </button>
                            </div>
                        </div>
                        <div v-else-if="originalPreviewUrl" class="rounded-xl border border-dashed border-gray-800 flex flex-col items-center justify-center p-6 text-center">
                            <span class="text-xs text-gray-600">Processed stream will render here instantly upon execution request.</span>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- Footer Footer -->
        <footer class="mt-20 border-t border-gray-800/80 pt-8 text-center text-xs text-gray-600">
            <p>MidasMedia Premium Core Engine · Laravel v{{ laravelVersion }} · PHP v{{ phpVersion }}</p>
        </footer>
    </div>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');

.font-jakarta {
    font-family: 'Plus Jakarta Sans', sans-serif;
}
</style>
