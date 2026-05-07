<!-- <div class="p-4">
    
    <div class="mb-4 p-4 rounded shadow-lg text-center font-bold text-white {{ $status == 'success' ? 'bg-green-600' : ($status == 'error' ? 'bg-red-600' : 'bg-blue-600') }}">
        {{ $message }}
    </div>

  
    <div wire:ignore id="reader" class="bg-white rounded-lg border-2 border-gray-200 overflow-hidden" style="min-height: 300px;"></div>

    <script>
        function startScanner() {
            // Check if the scanner is already running to prevent double-initialization
            if (document.getElementById('reader').children.length > 0) return;

            const ScannerClass = window.Html5QrcodeScanner || Html5QrcodeScanner;

            if (ScannerClass) {
                const scanner = new ScannerClass("reader", { 
                    fps: 10, 
                    qrbox: { width: 250, height: 250 }
                });

                scanner.render((decodedText) => {
                    @this.handleScan(decodedText);
                    scanner.pause(true);
                    setTimeout(() => scanner.resume(), 2500);
                });
            }
        }

        // Initialize correctly with Livewire's lifecycle
        document.addEventListener('livewire:initialized', () => {
            startScanner();
        });
    </script>
</div> -->
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
    <script type="module">
        import { Html5Qrcode } from "html5-qrcode";
        
        let html5QrCode;
        let isScanning = false;

        document.getElementById('start-btn').addEventListener('click', async () => {
            const startBtn = document.getElementById('start-btn');
            
            if (isScanning) {
                await html5QrCode.stop();
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
                    $wire.handleScan(decodedText); // Standard Livewire 3 way
                    html5QrCode.pause(true);
                    setTimeout(() => html5QrCode.resume(), 2500);
                },

                    (errorMessage) => {
                        // Parse error, ignore
                    }
                );
                isScanning = true;
                startBtn.innerText = 'Stop Scanner';
            } catch (err) {
                console.error(`Camera error: ${err}`);
                @this.call('setStatus', 'error', 'Camera access denied. Check browser permissions.');
            }
        });

        // Stop camera when Livewire navigates away
        document.addEventListener('livewire:navigating', () => {
            if (html5QrCode && isScanning) {
                html5QrCode.stop();
            }
        });
    </script>
    @endpush
</div>

