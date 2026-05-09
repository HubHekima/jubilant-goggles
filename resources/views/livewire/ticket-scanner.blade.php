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
                x-text="isScanning ? 'Stop Scanner' : (libraryLoading ? 'Loading...' : 'Tap to Start Scanner')"
                :disabled="libraryLoading">
            Tap to Start Scanner
        </button>
        
        <!-- Status indicator -->
        <div x-show="libraryLoading" class="mt-2 text-center text-sm text-orange-500">
            Loading scanner library...
        </div>
        <div x-show="!libraryLoading && !isScanning" class="mt-2 text-center text-sm text-green-600">
            Scanner ready ✓
        </div>
    </div>
</div>

<!-- Load html5-qrcode with correct CDN paths -->
<script>
    const cdnUrls = [
        'https://cdn.jsdelivr.net/npm/html5-qrcode@2.3.8/html5-qrcode.min.js',
        'https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js'
    ];
    
    let cdnIndex = 0;
    window.html5QrcodeReady = false;
    
    function tryLoadLibrary() {
        if (cdnIndex >= cdnUrls.length) {
            console.error('All CDNs failed');
            window.dispatchEvent(new Event('html5qrcode-failed'));
            return;
        }
        
        const url = cdnUrls[cdnIndex];
        console.log('Loading from:', url);
        
        const script = document.createElement('script');
        script.src = url;
        
        script.onload = function() {
            console.log('Loaded from:', url);
            window.html5QrcodeReady = true;
            window.dispatchEvent(new Event('html5qrcode-loaded'));
        };
        
        script.onerror = function() {
            console.error('Failed:', url);
            cdnIndex++;
            tryLoadLibrary();
        };
        
        document.head.appendChild(script);
    }
    
    tryLoadLibrary();
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
                // Wait for library (max 15 seconds)
                const result = await Promise.race([
                    new Promise(resolve => {
                        window.addEventListener('html5qrcode-loaded', () => resolve('loaded'), { once: true });
                    }),
                    new Promise(resolve => {
                        window.addEventListener('html5qrcode-failed', () => resolve('failed'), { once: true });
                    }),
                    new Promise(resolve => setTimeout(() => resolve('timeout'), 15000))
                ]);
                
                if (result === 'loaded') {
                    this.libraryLoading = false;
                } else {
                    this.libraryLoading = false;
                    this.status = 'error';
                    this.message = 'Scanner library failed to load. Please refresh.';
                }
                
                // Cleanup handlers
                window.addEventListener('beforeunload', () => {
                    if (this.isScanning && this.html5QrCode) {
                        this.html5QrCode.stop().catch(() => {});
                    }
                });
                
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
                if (typeof Html5Qrcode === 'undefined') {
                    this.status = 'error';
                    this.message = 'Scanner library not loaded. Refresh the page.';
                    return;
                }
                
                // Test camera permission first
                try {
                    const stream = await navigator.mediaDevices.getUserMedia({ 
                        video: { facingMode: "environment" } 
                    });
                    stream.getTracks().forEach(track => track.stop());
                } catch (err) {
                    let errorMsg = 'Camera access denied. ';
                    if (err.name === 'NotAllowedError') {
                        errorMsg = 'Please allow camera in browser settings.';
                    } else if (err.name === 'NotFoundError') {
                        errorMsg = 'No camera found.';
                    }
                    this.status = 'error';
                    this.message = errorMsg;
                    @this.call('setStatus', 'error', errorMsg);
                    return;
                }

                // Start scanning
                try {
                    this.html5QrCode = new Html5Qrcode("reader");
                    
                    await this.html5QrCode.start(
                        { facingMode: "environment" },
                        { fps: 10, qrbox: { width: 250, height: 250 } },
                        (decodedText) => {
                            this.status = 'success';
                            this.message = 'Code scanned!';
                            @this.call('handleScan', decodedText);
                            
                            this.html5QrCode.pause(true);
                            setTimeout(() => {
                                if (this.isScanning) this.html5QrCode.resume();
                            }, 2500);
                        },
                        () => {}
                    );
                    
                    this.isScanning = true;
                    this.status = 'success';
                    this.message = 'Camera active - point at QR code';
                    
                } catch (err) {
                    this.isScanning = false;
                    this.status = 'error';
                    this.message = 'Failed: ' + err.message;
                }
            },

            async stopScanner() {
                if (this.html5QrCode && this.isScanning) {
                    try {
                        await this.html5QrCode.stop();
                        this.html5QrCode.clear();
                    } catch (e) {}
                }
                this.isScanning = false;
                this.status = 'idle';
                this.message = 'Waiting for scan...';
            }
        }
    }
</script>