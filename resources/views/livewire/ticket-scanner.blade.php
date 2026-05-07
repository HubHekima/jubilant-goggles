<div class="p-4">
    <!-- Status Display -->
    <div class="mb-4 p-4 rounded shadow-lg text-center font-bold text-white {{ $status == 'success' ? 'bg-green-600' : ($status == 'error' ? 'bg-red-600' : 'bg-blue-600') }}">
        {{ $message }}
    </div>

    <div wire:ignore>
        <div id="reader" class="bg-white rounded-lg border-2 border-gray-200 overflow-hidden" style="min-height: 300px;"></div>
        
        <button id="start-btn" type="button" class="mt-4 w-full bg-blue-600 text-white font-bold py-3 px-4 rounded">
            Allow Camera & Start Scan
        </button>
    </div>

    <!-- Include the library via CDN if not in your bundle -->
    <script src="https://unpkg.com" type="text/javascript"></script>

    <script>
        document.addEventListener('livewire:initialized', () => {
            let html5QrCode;
            const startBtn = document.getElementById('start-btn');

            startBtn.addEventListener('click', async () => {
                // Initialize if not already done
                if (!html5QrCode) {
                    html5QrCode = new Html5Qrcode("reader");
                }

                const config = { fps: 10, qrbox: { width: 250, height: 250 } };

                try {
                    // This specific call triggers the browser's permission prompt
                    await html5QrCode.start(
                        { facingMode: "environment" }, 
                        config,
                        (decodedText) => {
                            // Call Livewire handleScan
                            @this.handleScan(decodedText);
                            
                            // Visual feedback: Pause briefly so user sees the result
                            html5QrCode.pause(true);
                            setTimeout(() => html5QrCode.resume(), 3000);
                        }
                    );

                    startBtn.style.display = 'none'; // Hide button once camera is active
                } catch (err) {
                    console.error("Camera access failed", err);
                    @this.setStatus('error', 'Camera access denied or not found.');
                }
            });

            // Cleanup when component is destroyed
            @this.on('stop-scanner', () => {
                if(html5QrCode) html5QrCode.stop();
            });
        });
    </script>
</div>
