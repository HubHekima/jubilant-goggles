<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Ticket;

class TicketScanner extends Component
{
    public $message = "Waiting for scan...";
    public $status = "idle";
    public $lastScannedTicket = null;
    
    public function handleScan($uuid)
    {
        // Find ticket
        $ticket = Ticket::where('uuid', $uuid)->first();

        if (!$ticket) {
            $this->status = "error";
            $this->message = "❌ TICKET NOT FOUND";
            return;
        }

        // Check if cancelled
        if ($ticket->status === 'cancelled') {
            $this->status = "error";
            $this->message = "❌ CANCELLED TICKET";
            return;
        }

        // Check if pending payment
        if ($ticket->status === 'pending') {
            $this->status = "error";
            $this->message = "⚠️ PAYMENT PENDING";
            return;
        }

        // Check if already scanned today
        if ($ticket->scanned_at && $ticket->scanned_at->isToday()) {
            $this->status = "error";
            $this->message = "⚠️ ALREADY USED at " . $ticket->scanned_at->format('H:i');
            return;
        }

        // Valid ticket - mark as scanned
        $ticket->update(['scanned_at' => now()]);
        
        // Store last scanned ticket info
        $this->lastScannedTicket = $ticket->buyer_name;
        
        $this->status = "success";
        $this->message = "✅ VALID: " . $ticket->buyer_name;
    }
    
    public function setStatus($status, $message)
    {
        $this->status = $status;
        $this->message = $message;
    }
    
    public function render()
    {
        return view('livewire.ticket-scanner');
    }
}