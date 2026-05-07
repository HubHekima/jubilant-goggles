<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <style>
        body { font-family: sans-serif; }
        .ticket-box { 
            border: 2px solid #333; 
            padding: 30px; 
            text-align: center; 
            max-width: 500px; 
            margin: 0 auto; 
        }
        .event-name { font-size: 28px; font-weight: bold; color: #1a1a1a; margin-bottom: 10px; }
        .details { margin: 20px 0; line-height: 1.6; }
        .qr-section { margin: 25px 0; }
        .footer-text { font-size: 12px; color: #666; margin-top: 20px; }
        hr { border: 0; border-top: 1px dashed #ccc; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="ticket-box">
        <div class="event-name">{{ $ticket->event->name }}</div>
        <div class="details">
            <p><strong>Attendee:</strong> {{ $ticket->buyer_name }}</p>
            <p><strong>Ticket Type:</strong> {{ $ticket->ticketType->name }}</p>
            <p><strong>M-Pesa Ref:</strong> {{ $ticket->mpesa_reference ?? 'N/A' }}</p>
        </div>

        <hr>

        <div class="qr-section">
            {{-- We pass $qrcode as a Data URI from the Livewire component --}}
            <img src="{{ $qrcode }}" width="180" height="180" alt="Ticket QR Code">
            <p style="font-family: monospace; font-size: 10px; margin-top: 5px;">
                {{ $ticket->uuid }}
            </p>
        </div>

        <div class="footer-text">
            <p>Scan this at the gate for entry</p>
            <p><strong>Venue:</strong> {{ $ticket->event->venue }}</p>
        </div>
    </div> 
</body>
</html>
