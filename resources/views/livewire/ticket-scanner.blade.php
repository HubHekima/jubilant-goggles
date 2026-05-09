@push('scripts')
<script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
<script>
    let html5QrCode;
    let isScanning = false;

    // Test if Html5Qrcode is available
    console.log('Html5Qrcode available:', typeof Html5Qrcode);

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

        // Check camera availability first
        try {
            const devices = await navigator.mediaDevices.enumerateDevices();
            const videoDevices = devices.filter(device => device.kind === 'videoinput');
            console.log('Available cameras:', videoDevices);
            
            if (videoDevices.length === 0) {
                @this.call('setStatus', 'error', 'No camera found on this device.');
                return;
            }
        } catch (e) {
            console.error('Cannot enumerate devices:', e);
        }

        html5QrCode = new Html5Qrcode("reader");
        const config = { fps: 10, qrbox: { width: 250, height: 250 } };

        try {
            await html5QrCode.start(
                { facingMode: "environment" },
                config,
                (decodedText) => {
                    console.log('QR Code detected:', decodedText);
                    @this.call('handleScan', decodedText);
                    html5QrCode.pause(true);
                    setTimeout(() => {
                        if (isScanning) {
                            html5QrCode.resume();
                        }
                    }, 2500);
                },
                (errorMessage) => {
                    // This is normal - fires when no QR code in view
                    // console.log('Scanning...', errorMessage);
                }
            );
            isScanning = true;
            startBtn.innerText = 'Stop Scanner';
            @this.call('setStatus', 'success', 'Camera started. Point at a QR code.');
        } catch (err) {
            console.error('Camera error details:', err);
            isScanning = false;
            @this.call('setStatus', 'error', 'Cannot access camera: ' + err.message);
        }
    });

    document.addEventListener('livewire:navigating', () => {
        if (html5QrCode && isScanning) {
            html5QrCode.stop().catch(e => console.error(e));
        }
    });
</script>
@endpush