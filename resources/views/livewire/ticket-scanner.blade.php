<div class="p-4" x-data="scannerHandler()" x-init="init()">
    <!-- Status Display -->
    <div class="mb-4 p-4 rounded shadow-lg text-center font-bold text-white" 
         :class="{
             'bg-green-600': status === 'success',
             'bg-red-600': status === 'error',
             'bg-blue-600': status === 'idle'
         }">
        <span x-text="message">Waiting for scan...</span>
    </div>

    <!-- Scanner container -->
    <div wire:ignore>
        <div id="reader" class="bg-white rounded-lg border-2 border-gray-200 overflow-hidden" style="min-height: 300px;"></div>
        
        <button @click="toggleScanner" type="button" 
                class="mt-4 w-full bg-blue-600 text-white font-bold py-3 px-4 rounded"
                x-text="isScanning ? 'Stop Scanner' : 'Tap to Start Scanner'"
                :disabled="libraryLoading">
            Tap to Start Scanner
        </button>
        
        <!-- Library loading indicator -->
        <div x-show="libraryLoading" class="mt-2 text-center text-sm text-gray-500">
            Loading scanner library...
        </div>
    </div>
</div>

<!-- Load html5-qrcode before Alpine initializes -->
<script>
    // Store library ready state globally
    window.html5QrcodeReady = false;
    
    // Load the library immediately
    const script = document.createElement('script');
    script.src = 'https://unpkg.com/html5-qrcode@2.3.8/minified/html5-qrcode.min.js';
    script.onload = function() {
        window.html5QrcodeReady = true;
        console.log('Html5Qrcode library loaded');
        // Dispatch event so Alpine knows
        window.dispatchEvent(new Event('html5qrcode-loaded'));
    };
    script.onerror = function() {
        console.error('Failed to load Html5Qrcode library');
    };
    document.head.appendChild(script);
</script>

<script>
    function scannerHandler() {
        return {
            status: 'idle',
            message: 'Waiting for scan...',
            isScanning: false,
            html5QrCode: null,
            libraryLoading: true,

            async init() {
                console.log('Alpine initialized');
                
                // Wait for library if not loaded yet
                if (!window.html5QrcodeReady) {
                    await new Promise(resolve => {
                        window.addEventListener('html5qrcode-loaded', resolve, { once: true });
                    });
                }
                
                this.libraryLoading = false;
                console.log('Scanner ready');
                
                // Clean up when leaving
                window.addEventListener('beforeunload', () => {
                    if (this.isScanning && this.html5QrCode) {
                        this.html5QrCode.stop().catch(() => {});
                    }
                });
                
                // Handle Livewire navigation
                document.addEventListener('livewire:navigating', () => {
                    if (this.isScanning && this.html5QrCode) {
                        this.html5QrCode.stop().catch(() => {});
                    }
                });
            },

            async toggleScanner() {
                if (this.isScanning) {
                    await this.stopScanner();
                } else {
                    await this.startScanner();
                }
            },

            async startScanner() {
                // First test camera permission
                try {
                    const stream = await navigator.mediaDevices.getUserMedia({ 
                        video: { facingMode: "environment" } 
                    });
                    stream.getTracks().forEach(track => track.stop());
                } catch (err) {
                    console.error('Camera permission error:', err);
                    
                    let errorMsg = 'Camera access denied. ';
                    if (err.name === 'NotAllowedError') {
                        errorMsg = 'Please allow camera access in your browser settings.';
                    } else if (err.name === 'NotFoundError') {
                        errorMsg = 'No camera found on this device.';
                    }
                    
                    this.status = 'error';
                    this.message = errorMsg;
                    @this.call('setStatus', 'error', errorMsg);
                    return;
                }

                // Now start QR scanner
                try {
                    this.html5QrCode = new Html5Qrcode("reader");
                    
                    await this.html5QrCode.start(
                        { facingMode: "environment" },
                        { fps: 10, qrbox: { width: 250, height: 250 } },
                        (decodedText) => {
                            console.log('Scanned:', decodedText);
                            this.status = 'success';
                            this.message = 'Scan successful!';
                            @this.call('handleScan', decodedText);
                            
                            // Pause to prevent multiple scans
                            this.html5QrCode.pause(true);
                            setTimeout(() => {
                                if (this.isScanning && this.html5QrCode) {
                                    this.html5QrCode.resume();
                                }
                            }, 2500);
                        },
                        () => {
                            // Normal scanning noise - ignore
                        }
                    );
                    
                    this.isScanning = true;
                    this.status = 'success';
                    this.message = 'Camera started. Point at a QR code.';
                    
                } catch (err) {
                    console.error('Scanner start error:', err);
                    this.isScanning = false;
                    this.status = 'error';
                    this.message = 'Failed to start camera: ' + err.message;
                    @this.call('setStatus', 'error', this.message);
                }
            },

            async stopScanner() {
                if (this.html5QrCode && this.isScanning) {
                    try {
                        await this.html5QrCode.stop();
                        this.html5QrCode.clear();
                    } catch (e) {
                        console.error('Error stopping scanner:', e);
                    }
                }
                this.isScanning = false;
                this.status = 'idle';
                this.message = 'Waiting for scan...';
            }
        }
    }
</script>