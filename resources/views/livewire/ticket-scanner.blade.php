<div class="p-4">
    <!-- Status Display -->
    <div class="mb-4 p-4 rounded shadow-lg text-center font-bold text-white {{ $status == 'success' ? 'bg-green-600' : ($status == 'error' ? 'bg-red-600' : 'bg-blue-600') }}">
        {{ $message }}
    </div>

    <!-- Scanner container -->
    <div wire:ignore>
        <div id="reader" class="bg-white rounded-lg border-2 border-gray-200 overflow-hidden" style="min-height: 300px;"></div>
        
        <button id="start-btn" type="button" class="mt-4 w-full bg-blue-600 text-white font-bold py-3 px-4 rounded">
            Tap to Start Scanner
        </button>
    </div>

    @push('scripts')
    <!-- Load html5-qrcode from CDN -->
    <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
    <script>
        // Now Html5Qrcode is available globally (no import needed)
        let html5QrCode;
        let isScanning = false;

        document.getElementById('start-btn').addEventListener('click', async () => {
            const startBtn = document.getElementById('start-btn');
            
            if (isScanning) {
                try {
                    await html5QrCode.stop();
                    html5QrCode.clear();
                } catch (e) {
                    console.error('Error stopping scanner:', e);
                }
                startBtn.innerText = 'Tap to Start Scanner';
                isScanning = false;
                return;
            }

            html5QrCode = new Html5Qrcode("reader");
            const config = { fps: 10, qrbox: { width: 250, height: 250 } };

            try {
                await html5QrCode.start(
                    { facingMode: "environment" }, // back camera
                    config,
                    (decodedText) => {
                        // Success
                        @this.call('handleScan', decodedText);
                        html5QrCode.pause(true);
                        setTimeout(() => {
                            if (isScanning) {
                                html5QrCode.resume();
                            }
                        }, 2500);
                    },
                    (errorMessage) => {
                        // Parse error, ignore (this fires continuously when no QR code is found)
                    }
                );
                isScanning = true;
                startBtn.innerText = 'Stop Scanner';
            } catch (err) {
                console.error(`Camera error:`, err);
                isScanning = false;
                @this.call('setStatus', 'error', 'Camera access denied. Please check browser permissions and ensure you\'re using HTTPS.');
            }
        });

        // Stop camera when Livewire navigates away
        document.addEventListener('livewire:navigating', () => {
            if (html5QrCode && isScanning) {
                html5QrCode.stop().catch(e => console.error('Error stopping on navigation:', e));
            }
        });

        // Also handle page unload
        window.addEventListener('beforeunload', () => {
            if (html5QrCode && isScanning) {
                html5QrCode.stop().catch(e => console.error('Error stopping on unload:', e));
            }
        });
    </script>
    @endpush
</div>