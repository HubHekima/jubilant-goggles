<div class="p-4">
    <!-- Status Display -->
    <div class="mb-4 p-4 rounded shadow-lg text-center font-bold text-white {{ $status == 'success' ? 'bg-green-600' : ($status == 'error' ? 'bg-red-600' : 'bg-blue-600') }}">
        {{ $message }}
    </div>

    <!-- ADD wire:ignore HERE -->
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
</div>
