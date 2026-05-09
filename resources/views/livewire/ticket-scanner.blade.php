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
</div>

<!-- Simple CDN load - NO Alpine, NO complex loading -->
<script src="https://cdn.jsdelivr.net/npm/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>

<script>
    let html5QrCode;
    let isScanning = false;

    document.getElementById('start-btn').addEventListener('click', async () => {
        const startBtn = document.getElementById('start-btn');
        
        if (isScanning) {
            await html5QrCode.stop();
            html5QrCode.clear();
            startBtn.innerText = 'Tap to Start Scanner';
            isScanning = false;
            return;
        }

        html5QrCode = new Html5Qrcode("reader");
        
        try {
            await html5QrCode.start(
                { facingMode: "environment" },
                { fps: 10, qrbox: { width: 250, height: 250 } },
                (decodedText) => {
                    // Send scanned UUID to Livewire
                    @this.call('handleScan', decodedText);
                    
                    // Pause briefly to prevent multiple scans
                    html5QrCode.pause(true);
                    setTimeout(() => {
                        if (isScanning) html5QrCode.resume();
                    }, 2500);
                },
                () => {} // Ignore scanning noise
            );
            isScanning = true;
            startBtn.innerText = 'Stop Scanner';
        } catch (err) {
            console.error(err);
            @this.call('setStatus', 'error', 'Camera error: ' + err.message);
        }
    });

    // Cleanup
    document.addEventListener('livewire:navigating', () => {
        if (html5QrCode && isScanning) {
            html5QrCode.stop();
        }
    });
</script>