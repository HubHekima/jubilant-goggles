<!DOCTYPE html>
<html>
<body>
    <h1>Hi, {{ $ticket->buyer_name }}!</h1>
    <p>Thank you for purchasing a ticket for <strong>{{ $ticket->event->name }}</strong>.</p>
    <p>Your payment (Ref: {{ $ticket->mpesa_reference }}) has been confirmed.</p>
    <p>We have attached your PDF ticket to this email. Please present the QR code at the gate for entry.</p>
    <br>
    <p>Enjoy the event!</p>
</body>
</html>
