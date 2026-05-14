<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ref, computed, nextTick } from 'vue';
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
const formUrl = ref('https://fluxmedia.studio');
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
            return formUrl.value.trim() || 'https://fluxmedia.studio';
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
            return 'https://fluxmedia.studio';
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
    <Head>
        <title>FluxMedia Studio · Advanced QR Code Generator</title>
        <meta name="description" content="Generate highly customizable vector and raster QR codes instantly. Create links, vCards, WiFi connect codes, and pre-formatted SMS payloads with live canvas color formatting." />
    </Head>

    <div class="min-h-screen bg-[#0B0F19] text-gray-100 font-jakarta selection:bg-purple-500 selection:text-white pb-20">
        <!-- Premium Navigation Header -->
        <header class="border-b border-gray-800/60 bg-[#0B0F19]/80 backdrop-blur-md sticky top-0 z-50">
            <div class="mx-auto max-w-7xl px-6 flex h-20 items-center justify-between">
                <div class="flex items-center gap-x-3">
                    <div class="h-10 w-10 rounded-xl bg-gradient-to-tr from-purple-600 to-pink-600 flex items-center justify-center shadow-lg shadow-purple-500/20 font-bold text-xl select-none">
                        F
                    </div>
                    <span class="text-xl font-bold tracking-tight bg-gradient-to-r from-white via-gray-200 to-purple-300 bg-clip-text text-transparent select-none">
                        FluxMedia
                    </span>
                </div>

                <nav class="flex items-center gap-x-4 text-sm font-medium">
                    <Link href="/" class="text-gray-400 hover:text-white transition-colors">
                        Image Studio
                    </Link>
                    <Link href="/qr-code-generator" class="text-purple-400 font-semibold transition-colors">
                        QR Code Generator
                    </Link>
                    <div class="h-4 w-px bg-gray-800 hidden sm:block"></div>
                    <Link
                        v-if="$page.props.auth?.user"
                        :href="route('dashboard')"
                        class="rounded-lg px-3 py-1.5 bg-gray-800/80 hover:bg-gray-700/80 transition-all border border-gray-700/50 text-xs sm:text-sm"
                    >
                        Dashboard
                    </Link>
                    <Link
                        v-if="$page.props.auth?.user?.is_admin"
                        :href="route('admin.dashboard')"
                        class="rounded-lg px-3 py-1.5 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-500 hover:to-indigo-500 transition-all text-white font-semibold shadow-md shadow-purple-600/20 text-xs sm:text-sm"
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
                            class="rounded-lg px-3 py-1.5 bg-purple-600 hover:bg-purple-500 text-white transition-all shadow-md shadow-purple-600/20 font-semibold text-xs sm:text-sm"
                        >
                            Register
                        </Link>
                    </template>
                </nav>
            </div>
        </header>

        <!-- Hero Section -->
        <div class="relative overflow-hidden pt-12 pb-8 text-center">
            <!-- Glow ambient background effect -->
            <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[500px] h-[250px] bg-gradient-to-tr from-purple-600/20 via-indigo-600/10 to-pink-600/10 blur-[100px] rounded-full pointer-events-none"></div>

            <div class="relative mx-auto max-w-4xl px-6">
                <span class="inline-flex items-center gap-x-2 rounded-full bg-purple-500/10 px-4 py-1.5 text-xs font-semibold text-purple-300 border border-purple-500/20 mb-4">
                    <span class="h-1.5 w-1.5 rounded-full bg-purple-400 animate-pulse"></span>
                    FluxMedia Multi-Format Barcode Engine
                </span>

                <h1 class="text-3xl sm:text-5xl font-extrabold tracking-tight text-white max-w-3xl mx-auto leading-tight">
                    Dynamic Matrix <br/>
                    <span class="bg-gradient-to-r from-purple-400 via-pink-400 to-indigo-400 bg-clip-text text-transparent">
                        QR Generation Engine
                    </span>
                </h1>
                <p class="mt-3 text-sm sm:text-base text-gray-400 max-w-xl mx-auto">
                    Design pristine vector matrices locally. Configure custom profiles, live thematic contrast buffers, and error correction structures.
                </p>
            </div>
        </div>

        <!-- Main Engine Container Layout -->
        <div class="mx-auto max-w-6xl px-6 mt-4">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
                
                <!-- Left Column: Input Form Profiles & Tabs (7 cols) -->
                <div class="lg:col-span-7 rounded-2xl border border-gray-800/80 bg-[#121826]/80 backdrop-blur-xl shadow-2xl p-6 overflow-hidden">
                    
                    <!-- Profile Tab Selection Bar -->
                    <div class="flex pb-3 mb-6 overflow-x-auto scrollbar-none border-b border-gray-800/80 gap-x-2">
                        <button 
                            v-for="profile in [
                                { id: 'url', label: '🔗 Link / URL' },
                                { id: 'text', label: '📝 Plain Text' },
                                { id: 'email', label: '✉️ Email Flow' },
                                { id: 'phone', label: '📞 Direct Dial' },
                                { id: 'wifi', label: '📶 WiFi Setup' },
                                { id: 'sms', label: '💬 SMS Form' }
                            ]"
                            :key="profile.id"
                            @click="activeProfile = profile.id"
                            :class="['px-3.5 py-2 rounded-xl text-xs font-semibold whitespace-nowrap transition-all duration-200 cursor-pointer', activeProfile === profile.id ? 'bg-purple-600 text-white shadow-md shadow-purple-600/30' : 'bg-[#0B0F19] text-gray-400 hover:text-white border border-gray-800/80']"
                        >
                            {{ profile.label }}
                        </button>
                    </div>

                    <!-- Profile Form 1: URL -->
                    <div v-if="activeProfile === 'url'" class="space-y-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-400 mb-1.5">Destination URL / Web Endpoint</label>
                            <input 
                                type="url" 
                                v-model="formUrl" 
                                placeholder="https://example.com"
                                class="w-full bg-[#0B0F19] border border-gray-700 rounded-xl py-2.5 px-3.5 text-white text-sm focus:border-purple-500 focus:outline-none"
                            />
                            <span class="text-[11px] text-gray-500 mt-1 block">Barcode matrix updates immediately upon buffer modifications.</span>
                        </div>
                    </div>

                    <!-- Profile Form 2: Plain Text -->
                    <div v-if="activeProfile === 'text'" class="space-y-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-400 mb-1.5">Arbitrary Text Content Payload</label>
                            <textarea 
                                v-model="formText" 
                                rows="4"
                                placeholder="Input any string buffer layout..."
                                class="w-full bg-[#0B0F19] border border-gray-700 rounded-xl py-2.5 px-3.5 text-white text-sm focus:border-purple-500 focus:outline-none resize-none"
                            ></textarea>
                        </div>
                    </div>

                    <!-- Profile Form 3: Email -->
                    <div v-if="activeProfile === 'email'" class="space-y-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-400 mb-1.5">Recipient Target Email</label>
                            <input 
                                type="email" 
                                v-model="formEmailTo" 
                                placeholder="hello@fluxmedia.studio"
                                class="w-full bg-[#0B0F19] border border-gray-700 rounded-xl py-2.5 px-3.5 text-white text-sm focus:border-purple-500 focus:outline-none"
                            />
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-400 mb-1.5">Default Subject Line</label>
                            <input 
                                type="text" 
                                v-model="formEmailSubject" 
                                placeholder="Project Integration Inquiry"
                                class="w-full bg-[#0B0F19] border border-gray-700 rounded-xl py-2.5 px-3.5 text-white text-sm focus:border-purple-500 focus:outline-none"
                            />
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-400 mb-1.5">Pre-formatted Body Content</label>
                            <textarea 
                                v-model="formEmailBody" 
                                rows="3"
                                placeholder="Greetings team..."
                                class="w-full bg-[#0B0F19] border border-gray-700 rounded-xl py-2.5 px-3.5 text-white text-sm focus:border-purple-500 focus:outline-none resize-none"
                            ></textarea>
                        </div>
                    </div>

                    <!-- Profile Form 4: Phone -->
                    <div v-if="activeProfile === 'phone'" class="space-y-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-400 mb-1.5">Direct Telephony Access Number</label>
                            <input 
                                type="tel" 
                                v-model="formPhone" 
                                placeholder="+1 (555) 019-2834"
                                class="w-full bg-[#0B0F19] border border-gray-700 rounded-xl py-2.5 px-3.5 text-white text-sm focus:border-purple-500 focus:outline-none"
                            />
                        </div>
                    </div>

                    <!-- Profile Form 5: WiFi -->
                    <div v-if="activeProfile === 'wifi'" class="space-y-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-400 mb-1.5">Wireless Network SSID</label>
                            <input 
                                type="text" 
                                v-model="formWifiSsid" 
                                placeholder="FluxStudio_5G_Guest"
                                class="w-full bg-[#0B0F19] border border-gray-700 rounded-xl py-2.5 px-3.5 text-white text-sm focus:border-purple-500 focus:outline-none"
                            />
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-gray-400 mb-1.5">Security Cipher Layer</label>
                                <select 
                                    v-model="formWifiEncryption"
                                    class="w-full bg-[#0B0F19] border border-gray-700 rounded-xl py-2.5 px-3.5 text-white text-sm focus:border-purple-500 focus:outline-none cursor-pointer"
                                >
                                    <option value="WPA">WPA / WPA2 / WPA3</option>
                                    <option value="WEP">WEP Legacy</option>
                                    <option value="nopass">Unencrypted (Open)</option>
                                </select>
                            </div>
                            <div v-if="formWifiEncryption !== 'nopass'">
                                <label class="block text-xs font-medium text-gray-400 mb-1.5">Access Password</label>
                                <input 
                                    type="password" 
                                    v-model="formWifiPassword" 
                                    placeholder="••••••••••••"
                                    class="w-full bg-[#0B0F19] border border-gray-700 rounded-xl py-2.5 px-3.5 text-white text-sm focus:border-purple-500 focus:outline-none"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Profile Form 6: SMS -->
                    <div v-if="activeProfile === 'sms'" class="space-y-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-400 mb-1.5">Target Destination Phone</label>
                            <input 
                                type="tel" 
                                v-model="formSmsPhone" 
                                placeholder="+1 (555) 019-2834"
                                class="w-full bg-[#0B0F19] border border-gray-700 rounded-xl py-2.5 px-3.5 text-white text-sm focus:border-purple-500 focus:outline-none"
                            />
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-400 mb-1.5">Pre-filled Message Sub-payload</label>
                            <textarea 
                                v-model="formSmsMessage" 
                                rows="3"
                                placeholder="I would like to activate a support line..."
                                class="w-full bg-[#0B0F19] border border-gray-700 rounded-xl py-2.5 px-3.5 text-white text-sm focus:border-purple-500 focus:outline-none resize-none"
                            ></textarea>
                        </div>
                    </div>

                    <!-- Advanced Styling Tools Header -->
                    <div class="mt-8 pt-6 border-t border-gray-800/80">
                        <h3 class="text-xs font-bold text-purple-400 uppercase tracking-wider mb-4">🎨 Matrix Theming Controls</h3>
                        
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <!-- Foreground Color Picker -->
                            <div>
                                <label class="block text-xs font-medium text-gray-400 mb-1.5 flex justify-between">
                                    <span>Foreground Hex Color</span>
                                    <span class="font-mono text-purple-300">{{ qrForeground }}</span>
                                </label>
                                <div class="flex items-center gap-x-2">
                                    <input 
                                        type="color" 
                                        v-model="qrForeground" 
                                        class="h-9 w-12 rounded-lg bg-[#0B0F19] border border-gray-700 p-0.5 cursor-pointer"
                                    />
                                    <input 
                                        type="text" 
                                        v-model="qrForeground" 
                                        class="flex-1 bg-[#0B0F19] border border-gray-700 rounded-lg py-1.5 px-3 text-white text-xs font-mono focus:border-purple-500 focus:outline-none"
                                    />
                                </div>
                            </div>

                            <!-- Background Color Picker -->
                            <div>
                                <label class="block text-xs font-medium text-gray-400 mb-1.5 flex justify-between">
                                    <span>Background Hex Color</span>
                                    <span class="font-mono text-gray-300">{{ qrBackground }}</span>
                                </label>
                                <div class="flex items-center gap-x-2">
                                    <input 
                                        type="color" 
                                        v-model="qrBackground" 
                                        class="h-9 w-12 rounded-lg bg-[#0B0F19] border border-gray-700 p-0.5 cursor-pointer"
                                    />
                                    <input 
                                        type="text" 
                                        v-model="qrBackground" 
                                        class="flex-1 bg-[#0B0F19] border border-gray-700 rounded-lg py-1.5 px-3 text-white text-xs font-mono focus:border-purple-500 focus:outline-none"
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- Theme Presets Palette Buttons -->
                        <div class="mt-3 flex items-center gap-x-2">
                            <span class="text-[11px] text-gray-500 font-medium">Quick Gradients:</span>
                            <button @click="applyThemePreset('#8B5CF6', '#FFFFFF')" class="w-5 h-5 rounded-full border border-gray-600 bg-purple-500 cursor-pointer" title="Neon Purple"></button>
                            <button @click="applyThemePreset('#000000', '#FFFFFF')" class="w-5 h-5 rounded-full border border-gray-600 bg-black cursor-pointer" title="Classic Pitch Black"></button>
                            <button @click="applyThemePreset('#EC4899', '#FFFFFF')" class="w-5 h-5 rounded-full border border-gray-600 bg-pink-500 cursor-pointer" title="Vivid Pink"></button>
                            <button @click="applyThemePreset('#06B6D4', '#0B0F19')" class="w-5 h-5 rounded-full border border-cyan-500 bg-cyan-400 cursor-pointer" title="Cyberpunk Dark Mode Neon"></button>
                            <button @click="applyThemePreset('#10B981', '#FFFFFF')" class="w-5 h-5 rounded-full border border-gray-600 bg-emerald-500 cursor-pointer" title="Emerald Growth"></button>
                        </div>

                        <!-- Secondary Settings Range Configuration -->
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mt-6">
                            <!-- Matrix Size -->
                            <div>
                                <div class="flex justify-between items-center text-xs mb-1">
                                    <span class="text-gray-400">Resolution Size:</span>
                                    <span class="text-purple-400 font-bold">{{ qrSize }}px</span>
                                </div>
                                <input type="range" v-model="qrSize" min="150" max="450" step="10" class="w-full h-1 bg-gray-800 rounded-lg appearance-none cursor-pointer accent-purple-500" />
                            </div>

                            <!-- Padding / Quiet Zone Margin -->
                            <div>
                                <div class="flex justify-between items-center text-xs mb-1">
                                    <span class="text-gray-400">Quiet Zone Margin:</span>
                                    <span class="text-purple-400 font-bold">{{ qrMargin }} units</span>
                                </div>
                                <input type="range" v-model="qrMargin" min="0" max="6" step="1" class="w-full h-1 bg-gray-800 rounded-lg appearance-none cursor-pointer accent-purple-500" />
                            </div>

                            <!-- Redundancy Level -->
                            <div>
                                <label class="block text-xs font-medium text-gray-400 mb-1">Error Redundancy</label>
                                <select 
                                    v-model="qrLevel"
                                    class="w-full bg-[#0B0F19] border border-gray-700 rounded-lg py-1 px-2.5 text-white text-xs focus:border-purple-500 focus:outline-none cursor-pointer"
                                >
                                    <option value="L">Level L (~7% safe)</option>
                                    <option value="M">Level M (~15% safe)</option>
                                    <option value="Q">Level Q (~25% safe)</option>
                                    <option value="H">Level H (~30% max safe)</option>
                                </select>
                            </div>
                        </div>

                    </div>

                </div>

                <!-- Right Column: Live Responsive Canvas Render Pane (5 cols) -->
                <div class="lg:col-span-5 flex flex-col space-y-6">
                    
                    <!-- Bounding View Frame -->
                    <div class="rounded-2xl border border-purple-500/30 bg-[#121826]/80 backdrop-blur-xl shadow-2xl p-6 text-center relative overflow-hidden flex flex-col items-center">
                        <div class="absolute top-0 right-0 py-1 px-3 bg-purple-500/10 border-b border-l border-purple-500/20 text-[10px] font-bold text-purple-400 uppercase rounded-bl-lg">
                            Live Vector Buffer
                        </div>

                        <span class="text-xs font-bold text-gray-400 uppercase tracking-wider block mb-4 mt-1">Generated View Matrix</span>

                        <!-- Dynamic Matrix Sandbox rendering pipeline -->
                        <div class="bg-gray-900/60 p-4 rounded-2xl border border-gray-800 inline-flex items-center justify-center min-w-[220px] min-h-[220px] max-w-full overflow-hidden shadow-inner">
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

                        <!-- Computed Metadata summary badge -->
                        <div class="mt-4 w-full bg-[#0B0F19] rounded-xl p-3 border border-gray-800 text-left">
                            <span class="text-[10px] text-purple-400 font-bold block uppercase mb-1">Encoded Decoupled Buffer Payload</span>
                            <div class="text-xs text-gray-300 font-mono break-all max-h-20 overflow-y-auto scrollbar-thin scrollbar-thumb-gray-800">
                                {{ computedQrValue }}
                            </div>
                        </div>

                        <!-- Notification alert pop -->
                        <div v-if="exportNotification" class="mt-3 w-full py-1.5 px-3 bg-emerald-500/10 border border-emerald-500/20 rounded-lg text-[11px] text-emerald-400 animate-fade-in font-medium">
                            {{ exportNotification }}
                        </div>

                        <!-- Download Execution Suite Triggers -->
                        <div class="mt-6 w-full pt-4 border-t border-gray-800/80 flex flex-col gap-y-2">
                            <span class="text-[11px] text-gray-500 font-medium block text-left">Export Output Stream Assets:</span>
                            
                            <div class="grid grid-cols-2 gap-3">
                                <!-- PNG Button -->
                                <button
                                    @click="triggerDownload('canvas')"
                                    class="py-2.5 px-4 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-semibold text-xs flex items-center justify-center gap-x-2 transition-all shadow-lg shadow-purple-600/20 cursor-pointer"
                                >
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                    </svg>
                                    <span>Save PNG (Raster)</span>
                                </button>

                                <!-- SVG Button -->
                                <button
                                    @click="triggerDownload('svg')"
                                    class="py-2.5 px-4 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white font-semibold text-xs flex items-center justify-center gap-x-2 transition-all shadow-lg shadow-emerald-600/20 cursor-pointer"
                                >
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                    </svg>
                                    <span>Save SVG (Vector)</span>
                                </button>
                            </div>
                        </div>

                    </div>

                    <!-- SEO Auxiliary Features Guidance Card -->
                    <div class="rounded-xl border border-gray-800 bg-[#121826]/40 p-4 text-left">
                        <span class="text-xs font-bold text-gray-300 block mb-1">💡 Professional Best Practices</span>
                        <p class="text-[11px] text-gray-500 leading-relaxed">
                            For maximum device scanner reliability across long scanning ranges, maintain an optimal contrast ratio (dark foreground blocks layered over light background layers) and keep silent margins quiet.
                        </p>
                    </div>

                </div>

            </div>
        </div>

        <!-- Footer Footer -->
        <footer class="mt-20 border-t border-gray-800/80 pt-8 text-center text-xs text-gray-600">
            <p>FluxMedia Premium Core Studio · Dynamic Matrix Module</p>
        </footer>
    </div>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');

.font-jakarta {
    font-family: 'Plus Jakarta Sans', sans-serif;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(4px); }
    to { opacity: 1; transform: translateY(0); }
}

.animate-fade-in {
    animation: fadeIn 0.2s ease-out forwards;
}
</style>
