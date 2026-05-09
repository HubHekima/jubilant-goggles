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
                x-text="isScanning ? 'Stop Scanner' : 'Tap to Start Scanner'">
            Tap to Start Scanner
        </button>
    </div>
</div>

<script src="https://unpkg.com/html5-qrcode@2.3.8/minified/html5-qrcode.min.js"></script>
<script>
    function scannerHandler() {
        return {
            status: 'idle',
            message: 'Waiting for scan...',
            isScanning: false,
            html5QrCode: null,

            async init() {
                console.log('Scanner initialized');
                // Clean up when leaving
                window.addEventListener('beforeunload', () => {
                    if (this.isScanning && this.html5QrCode) {
                        this.html5QrCode.stop();
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
                try {
                    // Check camera support first
                    const stream = await navigator.mediaDevices.getUserMedia({ 
                        video: { facingMode: "environment" } 
                    });
                    // Stop the test stream immediately
                    stream.getTracks().forEach(track => track.stop());
                    
                    this.html5QrCode = new Html5Qrcode("reader");
                    const config = { 
                        fps: 10, 
                        qrbox: { width: 250, height: 250 } 
                    };

                    await this.html5QrCode.start(
                        { facingMode: "environment" },
                        config,
                        (decodedText) => {
                            // Success callback
                            console.log('Scanned:', decodedText);
                            this.updateStatus('success', 'Code scanned successfully!');
                            @this.call('handleScan', decodedText);
                            
                            // Pause briefly to avoid multiple scans
                            this.html5QrCode.pause(true);
                            setTimeout(() => {
                                if (this.isScanning && this.html5QrCode) {
                                    this.html5QrCode.resume();
                                }
                            }, 2500);
                        },
                        (errorMessage) => {
                            // Normal - fires when no QR code in view
                        }
                    );
                    
                    this.isScanning = true;
                    this.updateStatus('success', 'Camera started. Point at a QR code.');
                    console.log('Camera started successfully');
                    
                } catch (err) {
                    console.error('Camera error:', err);
                    this.isScanning = false;
                    
                    let errorMsg = 'Camera access denied. ';
                    if (err.name === 'NotAllowedError') {
                        errorMsg = 'Please allow camera access in your browser settings.';
                    } else if (err.name === 'NotFoundError') {
                        errorMsg = 'No camera found on this device.';
                    } else if (err.message) {
                        errorMsg += err.message;
                    }
                    
                    this.updateStatus('error', errorMsg);
                }
            },

            async stopScanner() {
                if (this.html5QrCode && this.isScanning) {
                    try {
                        await this.html5QrCode.stop();
                        this.html5QrCode.clear();
                        console.log('Scanner stopped');
                    } catch (e) {
                        console.error('Error stopping scanner:', e);
                    }
                }
                this.isScanning = false;
                this.updateStatus('idle', 'Waiting for scan...');
            },

            updateStatus(status, message) {
                this.status = status;
                this.message = message;
                // Also update Livewire if needed
                @this.call('setStatus', status, message);
            }
        }
    }
</script>