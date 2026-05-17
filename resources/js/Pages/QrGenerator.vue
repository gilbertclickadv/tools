<script setup>
import { Head, usePage } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { ref, computed, nextTick } from 'vue';
import axios from 'axios';
import QrcodeVue from 'qrcode.vue';

defineProps({
    canLogin: {
        type: Boolean,
        default: true,
    },
    canRegister: {
        type: Boolean,
        default: true,
    },
});

// Active Profile Mode State
const activeProfile = ref('url');

// Content Form Payloads
const formUrl = ref('https://fluxmedia.space');
const formText = ref('FluxMedia Creative Core Engine');
const formEmailTo = ref('');
const formEmailSubject = ref('');
const formEmailBody = ref('');
const formPhone = ref('');
const formWifiSsid = ref('');
const formWifiPassword = ref('');
const formWifiEncryption = ref('WPA');
const formSmsPhone = ref('');
const formSmsMessage = ref('');

// Computed Final Barcode Value Payload
const computedQrValue = computed(() => {
    switch (activeProfile.value) {
        case 'url':
            return formUrl.value.trim() || 'https://fluxmedia.space';
        case 'text':
            return formText.value || ' ';
        case 'email':
            const to = encodeURIComponent(formEmailTo.value.trim());
            const subject = encodeURIComponent(formEmailSubject.value.trim());
            const body = encodeURIComponent(formEmailBody.value);
            return `mailto:${to}?subject=${subject}&body=${body}`;
        case 'phone':
            return `tel:${formPhone.value.trim()}`;
        case 'wifi':
            // Standard WiFi barcode format: WIFI:S:SSID;T:WPA;P:PASSWORD;;
            const ssid = formWifiSsid.value.replace(/([\\;:"])/g, '\\$1');
            const pass = formWifiPassword.value.replace(/([\\;:"])/g, '\\$1');
            return `WIFI:S:${ssid};T:${formWifiEncryption.value};P:${pass};;`;
        case 'sms':
            return `smsto:${formSmsPhone.value.trim()}:${formSmsMessage.value}`;
        default:
            return 'https://fluxmedia.space';
    }
});

// Advanced Customization Controls
const qrSize = ref(280);
const qrMargin = ref(2);
const qrForeground = ref('#8B5CF6'); // Rich Purple Default
const qrBackground = ref('#FFFFFF'); // Pure White Default
const qrLevel = ref('H'); // High Error Correction default
const renderEngine = ref('canvas'); // canvas | svg

// Export Mechanics References
const qrWrapperRef = ref(null);
const exportNotification = ref('');

const triggerDownload = async (format) => {
    if (!qrWrapperRef.value) return;
    
    // Save current user choice
    const originalEngine = renderEngine.value;
    
    // Temporarily set to targeted format if different
    if (renderEngine.value !== format) {
        renderEngine.value = format;
        await nextTick();
        // Give internal component canvas/svg time to draw
        await new Promise(r => setTimeout(r, 80));
    }

    try {
        if (format === 'canvas') {
            const canvasNode = qrWrapperRef.value.querySelector('canvas');
            if (!canvasNode) throw new Error('Canvas rendering pipeline not found.');
            const dataUrl = canvasNode.toDataURL('image/png');
            const link = document.createElement('a');
            link.href = dataUrl;
            link.download = `fluxmedia_qr_${activeProfile.value}_${Date.now()}.png`;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        } else if (format === 'svg') {
            const svgNode = qrWrapperRef.value.querySelector('svg');
            if (!svgNode) throw new Error('SVG vector layout node not found.');
            const serializer = new XMLSerializer();
            let source = serializer.serializeToString(svgNode);
            // Add xml declaration if missing
            if (!source.match(/^<\?xml/)) {
                source = '<?xml version="1.0" standalone="no"?>\r\n' + source;
            }
            const blob = new Blob([source], { type: 'image/svg+xml;charset=utf-8' });
            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = `fluxmedia_qr_${activeProfile.value}_${Date.now()}.svg`;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            URL.revokeObjectURL(url);
        }
        // Asynchronously commit layout to server-side stored history logs
        axios.post('/api/store-qr-code', {
            profile_type: activeProfile.value,
            summary_payload: computedQrValue.value,
            foreground_color: qrForeground.value,
            background_color: qrBackground.value,
            matrix_size: Number(qrSize.value),
            redundancy_level: qrLevel.value,
        }).catch(() => {});

        exportNotification.value = `Successfully exported layout as ${format.toUpperCase()} binary attachment.`;
        setTimeout(() => { exportNotification.value = ''; }, 4000);
    } catch (err) {
        exportNotification.value = `Export error: ${err.message}`;
        setTimeout(() => { exportNotification.value = ''; }, 4000);
    } finally {
        // Restore original user view config
        if (originalEngine !== renderEngine.value) {
            renderEngine.value = originalEngine;
        }
    }
};

// Preset Fast Theme Colors
const applyThemePreset = (fg, bg) => {
    qrForeground.value = fg;
    qrBackground.value = bg;
};
</script>

<template>
    <PublicLayout>
        <Head>
            <title>FluxMedia · Free Online QR Code Generator with Custom Styles</title>
            <meta name="description" content="Generate custom QR codes for free. Create QR codes for links, WiFi, Email, and SMS with unique colors and designs. Download high-quality PNG or SVG vectors." />
            <meta name="keywords" content="qr code generator, free qr code, custom qr code, qr code wifi, qr code email, svg qr code, png qr code, fluxmedia qr" />

            <!-- Open Graph / Facebook -->
            <meta property="og:title" content="FluxMedia · Free Online QR Code Generator with Custom Styles" />
            <meta property="og:description" content="Generate custom QR codes for free. Create QR codes for links, WiFi, Email, and SMS with unique colors and designs." />
            <meta property="og:image" content="/assets/images/fluxmedia_main.webp" />
            <meta property="og:url" content="https://fluxmedia.space/tools/qr-code" />

            <!-- Twitter -->
            <meta name="twitter:title" content="FluxMedia · Free Online QR Code Generator with Custom Styles" />
            <meta name="twitter:description" content="Generate custom QR codes for free. Create QR codes for links, WiFi, Email, and SMS with unique colors and designs." />
            <meta name="twitter:image" content="/assets/images/fluxmedia_main.webp" />
        </Head>

        <!-- Hero Section (Desktop Only) -->
        <div class="hidden lg:block relative overflow-hidden pt-8 pb-4 text-center">
            <!-- Glow ambient background effect -->
            <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[500px] h-[200px] bg-gradient-to-tr from-purple-600/20 via-indigo-600/10 to-pink-600/10 blur-[100px] rounded-full pointer-events-none"></div>

            <div class="relative mx-auto max-w-4xl px-6 space-y-3">
                <div class="flex items-center justify-center">
                    <span class="inline-flex items-center gap-x-2 rounded-full bg-purple-500/10 px-4 py-1.5 text-[10px] font-bold text-purple-300 border border-purple-500/20 uppercase tracking-widest">
                        <span class="h-1.5 w-1.5 rounded-full bg-purple-400 animate-pulse"></span>
                        QR Code Generator
                    </span>
                </div>

                <h1 class="text-2xl lg:text-3xl font-extrabold tracking-tight text-white max-w-3xl mx-auto leading-tight uppercase">
                    Free QR Code 
                    <span class="bg-gradient-to-r from-purple-400 via-pink-400 to-indigo-400 bg-clip-text text-transparent">
                        Generator
                    </span>
                </h1>
            </div>
        </div>

        <!-- Main Engine Container Layout -->
        <div class="mx-auto max-w-6xl px-3 lg:px-6 mt-4">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 lg:gap-8 items-start">
                
                <!-- Right Column (Moved to top on mobile for immediate visual feedback) -->
                <div class="lg:col-span-5 flex flex-col space-y-6 order-1 lg:order-2">
                    <!-- Bounding View Frame -->
                    <div class="rounded-3xl border border-purple-500/30 bg-[#121826]/80 backdrop-blur-xl shadow-2xl p-5 lg:p-6 text-center relative overflow-hidden flex flex-col items-center">
                        <div class="absolute top-0 right-0 py-1 px-3 bg-purple-500/10 border-b border-l border-purple-500/20 text-[9px] font-black text-purple-400 uppercase rounded-bl-lg tracking-widest">
                            Live Vector
                        </div>

                        <span class="text-[10px] font-bold text-gray-500 uppercase tracking-widest block mb-5 mt-1">QR Code Preview</span>

                        <!-- Dynamic Matrix Sandbox -->
                        <div class="bg-white p-4 rounded-2xl border border-gray-800 inline-flex items-center justify-center min-w-[180px] min-h-[180px] max-w-full overflow-hidden shadow-inner">
                            <div ref="qrWrapperRef" class="transition-all duration-300">
                                <QrcodeVue
                                    :value="computedQrValue"
                                    :size="Number(qrSize)"
                                    :render-as="renderEngine"
                                    :margin="Number(qrMargin)"
                                    :foreground="qrForeground"
                                    :background="qrBackground"
                                    :level="qrLevel"
                                    class="max-w-full h-auto rounded block"
                                />
                            </div>
                        </div>

                        <!-- Computed Metadata -->
                        <div class="mt-5 w-full bg-[#0B0F19] rounded-2xl p-4 border border-gray-800 text-left">
                            <span class="text-[9px] text-purple-400 font-black block uppercase mb-2 tracking-widest">QR Data Content</span>
                            <div class="text-[11px] text-gray-400 font-mono break-all max-h-16 overflow-y-auto no-scrollbar">
                                {{ computedQrValue }}
                            </div>
                        </div>

                        <!-- Export Execution Suite -->
                        <div class="mt-6 w-full pt-5 border-t border-gray-800/80 flex flex-col gap-y-3">
                            <div class="grid grid-cols-2 gap-3">
                                <button
                                    @click="triggerDownload('canvas')"
                                    class="py-3 px-4 rounded-2xl bg-purple-600 hover:bg-purple-500 text-white font-bold text-[10px] uppercase tracking-widest flex items-center justify-center gap-x-2 transition-all shadow-lg shadow-purple-600/20 active:scale-95"
                                >
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                    </svg>
                                    <span>PNG</span>
                                </button>

                                <button
                                    @click="triggerDownload('svg')"
                                    class="py-3 px-4 rounded-2xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white font-bold text-[10px] uppercase tracking-widest flex items-center justify-center gap-x-2 transition-all shadow-lg shadow-emerald-600/20 active:scale-95"
                                >
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                    </svg>
                                    <span>SVG</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Left Column: Inputs -->
                <div class="lg:col-span-7 rounded-3xl border border-gray-800/80 bg-[#121826]/80 backdrop-blur-xl shadow-2xl p-5 lg:p-7 overflow-hidden order-2 lg:order-1">
                    
                    <!-- Profile Tab Selection Bar -->
                    <div class="flex overflow-x-auto no-scrollbar pb-4 mb-6 border-b border-gray-800/80 gap-2">
                        <button 
                            v-for="profile in [
                                { id: 'url', label: 'Link' },
                                { id: 'text', label: 'Text' },
                                { id: 'email', label: 'Email' },
                                { id: 'phone', label: 'Phone' },
                                { id: 'wifi', label: 'WiFi' },
                                { id: 'sms', label: 'SMS' }
                            ]"
                            :key="profile.id"
                            @click="activeProfile = profile.id"
                            :class="['px-4 py-2 rounded-xl text-[10px] font-bold uppercase tracking-widest whitespace-nowrap transition-all duration-200', activeProfile === profile.id ? 'bg-purple-600 text-white shadow-md shadow-purple-600/30' : 'bg-[#0B0F19] text-gray-500 border border-gray-800/80']"
                        >
                            {{ profile.label }}
                        </button>
                    </div>

                    <!-- Profile Forms (Shared styling) -->
                    <div class="space-y-6">
                        <div v-if="activeProfile === 'url'">
                            <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-2">Endpoint URL</label>
                            <input type="url" v-model="formUrl" placeholder="https://example.com" class="w-full bg-[#0B0F19] border border-gray-700 rounded-2xl py-3 px-4 text-white text-sm focus:border-purple-500 focus:outline-none" />
                        </div>

                        <div v-if="activeProfile === 'text'">
                            <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-2">Message Content</label>
                            <textarea v-model="formText" rows="4" placeholder="Type here..." class="w-full bg-[#0B0F19] border border-gray-700 rounded-2xl py-3 px-4 text-white text-sm focus:border-purple-500 focus:outline-none resize-none"></textarea>
                        </div>

                        <div v-if="activeProfile === 'email'" class="space-y-4">
                            <div>
                                <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-2">Recipient</label>
                                <input type="email" v-model="formEmailTo" placeholder="hello@fluxmedia.space" class="w-full bg-[#0B0F19] border border-gray-700 rounded-2xl py-3 px-4 text-white text-sm focus:border-purple-500 focus:outline-none" />
                            </div>
                            <div class="grid grid-cols-1 gap-4">
                                <div>
                                    <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-2">Subject</label>
                                    <input type="text" v-model="formEmailSubject" class="w-full bg-[#0B0F19] border border-gray-700 rounded-2xl py-3 px-4 text-white text-sm focus:border-purple-500 focus:outline-none" />
                                </div>
                            </div>
                        </div>

                        <div v-if="activeProfile === 'phone'">
                            <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-2">Phone Number</label>
                            <input type="tel" v-model="formPhone" placeholder="+1..." class="w-full bg-[#0B0F19] border border-gray-700 rounded-2xl py-3 px-4 text-white text-sm focus:border-purple-500 focus:outline-none" />
                        </div>

                        <div v-if="activeProfile === 'wifi'" class="space-y-4">
                            <div>
                                <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-2">SSID (Name)</label>
                                <input type="text" v-model="formWifiSsid" class="w-full bg-[#0B0F19] border border-gray-700 rounded-2xl py-3 px-4 text-white text-sm focus:border-purple-500 focus:outline-none" />
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-2">Security</label>
                                    <select v-model="formWifiEncryption" class="w-full bg-[#0B0F19] border border-gray-700 rounded-2xl py-3 px-4 text-white text-sm focus:border-purple-500 focus:outline-none">
                                        <option value="WPA">WPA/WPA2/WPA3</option>
                                        <option value="WEP">WEP</option>
                                        <option value="nopass">None</option>
                                    </select>
                                </div>
                                <div v-if="formWifiEncryption !== 'nopass'">
                                    <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-2">Password</label>
                                    <input type="password" v-model="formWifiPassword" class="w-full bg-[#0B0F19] border border-gray-700 rounded-2xl py-3 px-4 text-white text-sm focus:border-purple-500 focus:outline-none" />
                                </div>
                            </div>
                        </div>

                        <div v-if="activeProfile === 'sms'" class="space-y-4">
                            <div>
                                <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-2">Phone</label>
                                <input type="tel" v-model="formSmsPhone" class="w-full bg-[#0B0F19] border border-gray-700 rounded-2xl py-3 px-4 text-white text-sm focus:border-purple-500 focus:outline-none" />
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-2">Message</label>
                                <textarea v-model="formSmsMessage" rows="2" class="w-full bg-[#0B0F19] border border-gray-700 rounded-2xl py-3 px-4 text-white text-sm focus:border-purple-500 focus:outline-none resize-none"></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Styling Controls -->
                    <div class="mt-10 pt-8 border-t border-gray-800/80">
                        <h3 class="text-[10px] font-black text-purple-400 uppercase tracking-[0.2em] mb-6">Design Settings</h3>
                        
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-3 flex justify-between">
                                    <span>Foreground</span>
                                    <span class="font-mono text-purple-300">{{ qrForeground }}</span>
                                </label>
                                <div class="flex items-center gap-x-2">
                                    <input type="color" v-model="qrForeground" class="h-10 w-14 rounded-xl bg-[#0B0F19] border border-gray-700 p-1 cursor-pointer" />
                                    <input type="text" v-model="qrForeground" class="flex-1 bg-[#0B0F19] border border-gray-700 rounded-xl py-2 px-3 text-white text-xs font-mono focus:border-purple-500 focus:outline-none" />
                                </div>
                            </div>

                            <div>
                                <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-3 flex justify-between">
                                    <span>Background</span>
                                    <span class="font-mono text-gray-300">{{ qrBackground }}</span>
                                </label>
                                <div class="flex items-center gap-x-2">
                                    <input type="color" v-model="qrBackground" class="h-10 w-14 rounded-xl bg-[#0B0F19] border border-gray-700 p-1 cursor-pointer" />
                                    <input type="text" v-model="qrBackground" class="flex-1 bg-[#0B0F19] border border-gray-700 rounded-xl py-2 px-3 text-white text-xs font-mono focus:border-purple-500 focus:outline-none" />
                                </div>
                            </div>
                        </div>

                        <!-- Secondary Config -->
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mt-8">
                            <div>
                                <div class="flex justify-between items-center text-[10px] font-bold uppercase tracking-widest mb-3">
                                    <span class="text-gray-500">Size</span>
                                    <span class="text-purple-400">{{ qrSize }}px</span>
                                </div>
                                <input type="range" v-model="qrSize" min="150" max="450" step="10" class="w-full h-1.5 bg-gray-800 rounded-lg appearance-none cursor-pointer accent-purple-500" />
                            </div>

                            <div>
                                <div class="flex justify-between items-center text-[10px] font-bold uppercase tracking-widest mb-3">
                                    <span class="text-gray-500">Margin</span>
                                    <span class="text-purple-400">{{ qrMargin }}</span>
                                </div>
                                <input type="range" v-model="qrMargin" min="0" max="6" step="1" class="w-full h-1.5 bg-gray-800 rounded-lg appearance-none cursor-pointer accent-purple-500" />
                            </div>

                            <div>
                                <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-3">Redundancy</label>
                                <select v-model="qrLevel" class="w-full bg-[#0B0F19] border border-gray-700 rounded-xl py-2 px-3 text-white text-xs focus:border-purple-500 focus:outline-none">
                                    <option value="L">Level L (7%)</option>
                                    <option value="M">Level M (15%)</option>
                                    <option value="Q">Level Q (25%)</option>
                                    <option value="H">Level H (30%)</option>
                                </select>
                            </div>
                        </div>
                    </div>

                </div>

            </div>
        </div>

        <!-- Footer -->
        <footer class="mt-20 border-t border-gray-800/80 pt-8 pb-12 text-center">
            <p class="text-[10px] text-gray-600 font-bold uppercase tracking-[0.2em]">FluxMedia Studio · Matrix v1.2.0</p>
        </footer>
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
