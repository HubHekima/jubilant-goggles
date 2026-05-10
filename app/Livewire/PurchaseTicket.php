<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Event;
use App\Models\Ticket;
use App\Services\MpesaService;
use Barryvdh\DomPDF\Facade\Pdf;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
//use SimpleSoftwareIO\QrCode\Facades\QrCode;

class PurchaseTicket extends Component
{
     #[Layout('layouts.guest')] 
    #[Title('Buy Ticket')]

    public bool $isProcessing = false;
      // 1. These "public" variables automatically link to your form inputs
    public Event $event;
    public $selectedTicketType = '';
    public $name = '';
    public $email = '';
    public $phone = '';
    public $ticketId; // To remember which ticket we are waiting for
    public $isPaid = false; // To toggle the UI once payment hits


    // 2. This runs the moment the page loads
    public function mount(Event $event)
    {
        $this->event = $event;
    }

    // 3. This is the action that happens when the user clicks "Buy"
    public function buy()
    {
        // This checks if the user filled everything correctly
        $this->validate([
            'selectedTicketType' => 'required',
            'name' => 'required|min:3',
            'email' => 'required|email',
            'phone' => 'required|digits:12', // Expecting 254XXXXXXXXX
        ]);

        $this->isProcessing = true;


        $type = \App\Models\TicketType::find($this->selectedTicketType);
        // This saves the "Pending" ticket to your database
         $ticket = Ticket::create([
            'event_id' => $this->event->id,
            'ticket_type_id' => $this->selectedTicketType,
            'buyer_name' => $this->name,
            'buyer_email' => $this->email,
            'buyer_phone' => $this->phone,
            'amount_paid' => $type->price, // This is crucial for Phase 2!
            'status' => 'pending',
        ]);

        try {
            $response = MpesaService::stkPush($ticket);

            if (isset($response['ResponseCode']) && $response['ResponseCode'] == '0') {
                  // Save the ID so we can track it
                 $this->ticketId = $ticket->id;
                // SAVE THE LINK HERE!
                $ticket->update([
                    'checkout_request_id' => $response['CheckoutRequestID']
                ]);

                session()->flash('message', 'STK Push sent! Please enter your PIN on your phone.');
            } else {
        // ... rest of your error handling

            // If Safaricom rejects it (e.g. invalid phone number)
            $this->isProcessing = false;
            session()->flash('error', 'M-Pesa error: ' . ($response['errorMessage'] ?? 'Try again.'));
        }
    } catch (\Exception $e) {
        $this->isProcessing = false;
        session()->flash('error', 'Connection failed. Check your internet or .env keys.');
    }
         // --- THE MISSING ENGINE END ---
    }
    public function checkPaymentStatus()
    {
    if (!$this->ticketId || $this->isPaid) return;

    $ticket = Ticket::find($this->ticketId);

    if ($ticket && $ticket->status === 'paid') {
        $this->isPaid = true;
        $this->isProcessing = false;
        session()->flash('message', 'Payment Received! Your ticket is ready.');
    }
}
/*
public function downloadTicket()
{
    // 1. Fetch the ticket with its relationships
    $ticket = \App\Models\Ticket::with(['event', 'ticketType'])
        ->where('id', $this->ticketId)
        ->firstOrFail();

    // 2. Generate QR Code as a base64 string for the PDF
    $qrcode = base64_encode(QrCode::format('png')
        ->size(200)
        ->merge(public_path('qricon.png'), 0.3, true) // Optional: add a logo in middle
        ->generate($ticket->mpesa_reference));

    // 3. Load the PDF view
    $pdf = Pdf::loadView('pdf.ticket', [
        'ticket' => $ticket,
        'qrcode' => $qrcode
    ]);

    // 4. Stream the download to the browser
    return response()->streamDownload(function () use ($pdf) {
        echo $pdf->stream();
    }, "Ticket-{$ticket->id}.pdf");
}
public function checkPaymentStatus()
{
    if (!$this->ticketId || $this->isPaid) return;

    $ticket = Ticket::find($this->ticketId);

    // Only mark as paid if the status is paid AND we have the reference
    // This prevents the user from downloading a ticket that still says "N/A"
    if ($ticket && $ticket->status === 'paid' && $ticket->mpesa_reference) {
        $this->isPaid = true;
        $this->isProcessing = false;
        session()->flash('message', 'Payment Received! Your ticket is ready.');
    }
}
    
*/
    // app/Livewire/PurchaseTicket.php

    public function downloadTicket()
{
    $ticket = \App\Models\Ticket::with(['event', 'ticketType'])->findOrFail($this->ticketId);

    // Ensure data is ready for the QR
    $qrData = $ticket->uuid ?? (string)$ticket->id;
    $qrCode = new QrCode(data: $qrData, size: 200, margin: 10);
    $writer = new PngWriter();
    $qrcodeDataUri = $writer->write($qrCode)->getDataUri();

    // Generate the PDF
    $pdf = Pdf::loadView('pdf.ticket', [
        'ticket' => $ticket,
        'qrcode' => $qrcodeDataUri
    ]);

    // Use output() then streamDownload for better compatibility with ngrok
    $content = $pdf->output();

    return response()->streamDownload(function () use ($content) {
        echo $content;
    }, "Ticket-{$ticket->id}.pdf");
}


    public function render()
    {
        return view('livewire.purchase-ticket')
         ->layout('layouts.guest');;
    }
}
