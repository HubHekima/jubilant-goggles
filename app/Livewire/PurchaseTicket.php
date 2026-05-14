<?php

namespace App\Livewire;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use App\Models\Event;
use App\Models\Ticket;
use App\Models\TicketType;
use App\Services\MpesaService;
use Barryvdh\DomPDF\Facade\Pdf;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

#[Layout('layouts.guest')]
#[Title('Buy Ticket')]
class PurchaseTicket extends Component
{
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
    public function buy(): void
    {
        // This checks if the user filled everything correctly
        $this->validate([
            'selectedTicketType' => 'required',
            'name' => 'required|min:3',
            'email' => 'required|email',
            'phone' => 'required|digits:12', // Expecting 254XXXXXXXXX
        ]);

        $type = TicketType::find($this->selectedTicketType);
        
        if (! $type) {
            session()->flash('error', 'Invalid ticket type selected.');
            return;
        }

        $this->isProcessing = true;

        // This saves the "Pending" ticket to your database
        $ticket = Ticket::create([
            'event_id' => $this->event->id,
            'ticket_type_id' => $this->selectedTicketType,
            'buyer_name' => $this->name,
            'buyer_email' => $this->email,
            'buyer_phone' => $this->phone,
            'amount_paid' => $type->price,
            'status' => 'pending',
        ]);

        try {
            $response = MpesaService::stkPush($ticket);

            if (isset($response['ResponseCode']) && $response['ResponseCode'] == '0') {
                // Save the ID so we can track it
                $this->ticketId = $ticket->id;
                // SAVE THE LINK HERE!
                $ticket->update([
                    'checkout_request_id' => $response['CheckoutRequestID'],
                ]);

                session()->flash('message', 'STK Push sent! Please enter your PIN on your phone.');
            } else {
                // If Safaricom rejects it (e.g. invalid phone number)
                $this->isProcessing = false;
                session()->flash('error', 'M-Pesa error: ' . ($response['errorMessage'] ?? 'Try again.'));
            }
        } catch (\Exception $e) {
            $this->isProcessing = false;
            session()->flash('error', 'Connection failed. Check your internet or .env keys.');
        }
    }

    public function checkPaymentStatus(): void
    {
        if (! $this->ticketId || $this->isPaid) {
            return;
        }

        $ticket = Ticket::find($this->ticketId);

        if ($ticket && $ticket->status === 'paid') {
            $this->isPaid = true;
            $this->isProcessing = false;
            session()->flash('message', 'Payment Received! Your ticket is ready.');
        }
    }

    public function downloadTicket()
    {
        $ticket = \App\Models\Ticket::with(['event', 'ticketType'])->findOrFail($this->ticketId);

        // Ensure data is ready for the QR
        $qrData = $ticket->uuid ?? (string) $ticket->id;
        $qrCode = new QrCode(data: $qrData, size: 200, margin: 10);
        $writer = new PngWriter();
        $qrcodeDataUri = $writer->write($qrCode)->getDataUri();

        // Generate the PDF
        $pdf = Pdf::loadView('pdf.ticket', [
            'ticket' => $ticket,
            'qrcode' => $qrcodeDataUri,
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
