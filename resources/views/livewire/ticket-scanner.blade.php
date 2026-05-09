<div class="min-h-screen bg-gray-100">
    <!-- Header with Stats -->
    <div class="bg-white shadow mb-4">
        <div class="max-w-7xl mx-auto px-4 py-3">
            <div class="flex justify-between items-center">
                <h1 class="text-xl font-bold">Ticket Scanner</h1>
                
                @if($eventStats)
                <div class="flex space-x-4 text-sm">
                    <div class="text-center">
                        <div class="font-bold text-blue-600">{{ $eventStats['scanned_in'] }}</div>
                        <div class="text-gray-600">Scanned In</div>
                    </div>
                    <div class="text-center">
                        <div class="font-bold">{{ $eventStats['total_tickets'] }}</div>
                        <div class="text-gray-600">Total</div>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>

    <div class="max-w-lg mx-auto px-4">
        <!-- Event Selector -->
        <div class="mb-4">
            <select wire:model.live="selectedEvent" class="w-full rounded border-gray-300">
                <option value="">All Events</option>
                @foreach($events as $event)
                    <option value="{{ $event->id }}">{{ $event->name }} - {{ $event->event_date->format('M d') }}</option>
                @endforeach
            </select>
        </div>

        <!-- Status Display -->
        <div class="mb-4 p-4 rounded shadow-lg text-center font-bold text-white {{ 
            $status == 'success' ? 'bg-green-600' : 
            ($status == 'error' ? 'bg-red-600' : 
            ($status == 'warning' ? 'bg-yellow-500' : 'bg-blue-600')) 
        }}">
            {{ $message }}
        </div>

        <!-- Scanner -->
        <div wire:ignore>
            <div id="reader" class="bg-white rounded-lg border-2 border-gray-200 overflow-hidden" style="min-height: 300px;"></div>
            
            <button id="start-btn" type="button" class="mt-4 w-full bg-blue-600 text-white font-bold py-3 px-4 rounded">
                Start Scanner
            </button>
        </div>

        <!-- Last Scan Info -->
        @if($lastScannedTicket)
        <div class="mt-4 bg-white p-4 rounded shadow">
            <h3 class="font-bold mb-2">Last Scan</h3>
            <p>{{ $lastScannedTicket['name'] }}</p>
            <p class="text-sm text-gray-600">{{ $lastScannedTicket['type'] }} | {{ $lastScannedTicket['time'] }}</p>
        </div>
        @endif

        <!-- Recent Scans -->
        @if(count($recentScans) > 0)
        <div class="mt-4 bg-white rounded shadow">
            <div class="p-4">
                <h3 class="font-bold mb-2">Recent Scans</h3>
                <div class="space-y-2">
                    @foreach($recentScans as $scan)
                    <div class="flex justify-between text-sm border-b pb-2">
                        <span>{{ $scan['name'] }}</span>
                        <span class="text-gray-600">{{ $scan['time'] }}</span>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif

        <!-- Sound Effects (hidden audio elements) -->
        <audio id="success-sound" preload="auto">
            <source src="/sounds/success.mp3" type="audio/mpeg">
        </audio>
        <audio id="error-sound" preload="auto">
            <source src="/sounds/error.mp3" type="audio/mpeg">
        </audio>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
    let html5QrCode;
    let isScanning = false;

    document.addEventListener('livewire:initialized', () => {
        const startBtn = document.getElementById('start-btn');
        
        startBtn.addEventListener('click', async () => {
            if (isScanning) {
                await html5QrCode.stop();
                html5QrCode.clear();
                startBtn.textContent = 'Start Scanner';
                isScanning = false;
                return;
            }

            html5QrCode = new Html5Qrcode("reader");
            
            try {
                await html5QrCode.start(
                    { facingMode: "environment" },
                    { fps: 10, qrbox: { width: 250, height: 250 } },
                    (decodedText) => {
                        @this.call('handleScan', decodedText);
                        html5QrCode.pause(true);
                        setTimeout(() => {
                            if (isScanning) html5QrCode.resume();
                        }, 2500);
                    },
                    () => {}
                );
                isScanning = true;
                startBtn.textContent = 'Stop Scanner';
            } catch (err) {
                console.error(err);
                @this.call('setStatus', 'error', 'Camera error: ' + err.message);
            }
        });
    });

    // Reset message after 3 seconds
    Livewire.on('auto-reset-message', () => {
        setTimeout(() => {
            @this.call('setStatus', 'idle', 'Waiting for scan...');
        }, 3000);
    });

    // Play sound effects
    Livewire.on('play-scan-sound', (data) => {
        const audio = document.getElementById(data.sound + '-sound');
        if (audio) {
            audio.currentTime = 0;
            audio.play().catch(() => {});
        }
    });

    // Clean up on navigation
    document.addEventListener('livewire:navigating', () => {
        if (html5QrCode && isScanning) {
            html5QrCode.stop().catch(() => {});
        }
    });
</script>
@endpush