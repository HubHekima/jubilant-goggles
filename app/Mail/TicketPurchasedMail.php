<?php

namespace App\Mail;

use App\Models\Ticket;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Barryvdh\DomPDF\Facade\Pdf;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Encoding\Encoding;

class TicketPurchasedMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(public \App\Models\Ticket $ticket) 
    {
        // No return here; just assign the ticket property
    }
    
    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Ticket Purchased Mail',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.ticket-success',
            with: [
                'ticket' => $this->ticket,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, Attachment>
     */
  /* public function attachments(): array
{
    // Now $this->ticket will actually exist
    $qrCode = new \Endroid\QrCode\QrCode(
        data: $this->ticket->uuid, 
        size: 200, 
        margin: 10
    );
    
    $writer = new \Endroid\QrCode\Writer\PngWriter();
    $qrcodeDataUri = $writer->write($qrCode)->getDataUri();

    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.ticket', [
        'ticket' => $this->ticket,
        'qrcode' => $qrcodeDataUri
    ]);

    return [
        \Illuminate\Mail\Mailables\Attachment::fromData(
            fn () => $pdf->output(), 
            "Ticket-{$this->ticket->id}.pdf"
        )->withMime('application/pdf'),
    ];
}*/
public function attachments(): array
{
    try {
        // Use only the most basic constructor - works for almost all versions
        $qrCode = new \Endroid\QrCode\QrCode($this->ticket->uuid);
        
        // Generate the image
        $writer = new \Endroid\QrCode\Writer\PngWriter();
        $qrcodeDataUri = $writer->write($qrCode)->getDataUri();

        // Load your PDF view
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.ticket', [
            'ticket' => $this->ticket,
            'qrcode' => $qrcodeDataUri
        ]);

        return [
            \Illuminate\Mail\Mailables\Attachment::fromData(
                fn () => $pdf->output(), 
                "Ticket-{$this->ticket->id}.pdf"
            )->withMime('application/pdf'),
        ];
    } catch (\Exception $e) {
        \Illuminate\Support\Facades\Log::error("QR RECOVERY FAILED: " . $e->getMessage());
        return []; 
    }
}


}
