<script setup>
import { ref, onMounted } from 'vue';

const deferredPrompt = ref(null);
const showPrompt = ref(false);

onMounted(() => {
    window.addEventListener('beforeinstallprompt', (e) => {
        // Prevent Chrome 67 and earlier from automatically showing the prompt
        e.preventDefault();
        // Stash the event so it can be triggered later.
        deferredPrompt.value = e;
        // Update UI notify the user they can add to home screen
        showPrompt.value = true;
    });

    window.addEventListener('appinstalled', () => {
        // Log install to analytics
        console.log('PWA was installed');
        showPrompt.value = false;
        deferredPrompt.value = null;
    });
});

const installPWA = async () => {
    if (!deferredPrompt.value) return;
    
    // Show the prompt
    deferredPrompt.value.prompt();
    
    // Wait for the user to respond to the prompt
    const { outcome } = await deferredPrompt.value.userChoice;
    console.log(`User response to the install prompt: ${outcome}`);
    
    // We've used the prompt, and can't use it again, throw it away
    deferredPrompt.value = null;
    showPrompt.value = false;
};

const dismissPrompt = () => {
    showPrompt.value = false;
};
</script>

<template>
    <Transition
        enter-active-class="transform transition ease-out duration-500"
        enter-from-class="translate-y-full opacity-0"
        enter-to-class="translate-y-0 opacity-100"
        leave-active-class="transform transition ease-in duration-300"
        leave-to-class="translate-y-full opacity-0"
    >
        <div v-if="showPrompt" class="fixed bottom-6 left-4 right-4 sm:left-auto sm:right-6 sm:w-96 z-[200]">
            <div class="bg-[#121826]/90 backdrop-blur-2xl border border-purple-500/30 rounded-3xl p-5 shadow-2xl shadow-purple-500/10 overflow-hidden relative group">
                <!-- Background Glow -->
                <div class="absolute -top-12 -right-12 w-24 h-24 bg-purple-600/20 blur-3xl rounded-full"></div>
                
                <div class="relative z-10 flex items-start gap-x-4">
                    <div class="h-12 w-12 rounded-2xl bg-gradient-to-tr from-purple-600 to-indigo-600 flex items-center justify-center shrink-0 shadow-lg shadow-purple-900/40">
                        <img src="/assets/images/pwa-192.png" class="h-8 w-8 object-contain" alt="App Icon" />
                    </div>
                    
                    <div class="flex-1 min-w-0">
                        <h3 class="text-sm font-bold text-white tracking-tight">Install FluxMedia Studio</h3>
                        <p class="text-[11px] text-gray-400 mt-1 leading-relaxed">Add to your home screen for high-performance offline image processing and instant QR access.</p>
                        
                        <div class="mt-4 flex items-center gap-x-3">
                            <button 
                                @click="installPWA"
                                class="flex-1 bg-purple-600 hover:bg-purple-500 text-white text-[11px] font-extrabold uppercase tracking-widest py-2.5 rounded-xl transition-all shadow-lg shadow-purple-600/20"
                            >
                                Install Now
                            </button>
                            <button 
                                @click="dismissPrompt"
                                class="px-4 py-2.5 text-[11px] font-bold text-gray-500 hover:text-gray-300 transition-colors"
                            >
                                Not Now
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </Transition>
</template>
