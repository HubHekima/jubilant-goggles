<div class="p-4">
    <!-- Status Display -->
    <div class="mb-4 p-4 rounded shadow-lg text-center font-bold text-white {{ $status == 'success' ? 'bg-green-600' : ($status == 'error' ? 'bg-red-600' : 'bg-blue-600') }}">
        {{ $message }}
    </div>

    <!-- Scanner and Camera Preview container -->
    <div wire:ignore>
        <!-- Camera Preview (WebRTC) -->
        <div class="mb-4">
            <video id="video-preview" autoplay playsinline class="w-full rounded border border-gray-300" style="max-height: 240px;"></video>
            <button id="start-camera-btn" type="button" class="mt-2 w-full bg-gray-600 text-white font-bold py-2 px-4 rounded">
                Show Camera Preview
            </button>
        </div>

        <!-- QR Scanner -->
        <div id="reader" class="bg-white rounded-lg border-2 border-gray-200 overflow-hidden" style="min-height: 300px;"></div>
        <button id="start-btn" type="button" class="mt-4 w-full bg-blue-600 text-white font-bold py-3 px-4 rounded">
            Tap to Start Scanner
        </button>
    </div>

    @push('scripts')
    <script type="module">
        import { Html5Qrcode } from "html5-qrcode";

        // WebRTC Camera Preview
        let cameraStream = null;
        document.getElementById('start-camera-btn').addEventListener('click', async () => {
            const video = document.getElementById('video-preview');
            if (cameraStream) {
                // Stop preview
                cameraStream.getTracks().forEach(track => track.stop());
                cameraStream = null;
                video.srcObject = null;
                event.target.innerText = 'Show Camera Preview';
                return;
            }
            try {
                cameraStream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } });
                video.srcObject = cameraStream;
                event.target.innerText = 'Stop Camera Preview';
            } catch (err) {
                console.error('Camera access error:', err);
                @this.call('setStatus', 'error', 'Camera access denied. Check browser permissions.');
            }
        });

        // QR Scanner
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
                    { facingMode: "environment" },
                    config,
                    (decodedText) => {
                        $wire.handleScan(decodedText);
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

        // Stop camera and scanner when Livewire navigates away
        document.addEventListener('livewire:navigating', () => {
            if (html5QrCode && isScanning) {
                html5QrCode.stop();
            }
            if (cameraStream) {
                cameraStream.getTracks().forEach(track => track.stop());
                cameraStream = null;
                document.getElementById('video-preview').srcObject = null;
            }
        });
    </script>
    @endpush
