<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Ticket;

class TicketScanner extends Component
{
       public $message = "Waiting for scan...";
        public $status = "info"; 
        
    public function handleScan($uuid)
{
    $ticket = Ticket::where('uuid', $uuid)->first();

    if (!$ticket) {
        $this->message = "❌ TICKET NOT FOUND";
        $this->status = "error";
        return;
    }

    // Check your 'scanned_at' column
    if ($ticket->scanned_at) {
        $this->message = "⚠️ ALREADY USED at " . $ticket->scanned_at->format('H:i');
        $this->status = "error";
        return;
    }

    // Mark as scanned
    $ticket->update(['scanned_at' => now()]);
    
    $this->message = "✅ VALID: " . $ticket->buyer_name;
    $this->status = "success";
}
    public function setStatus($status, $message) {
       $this->status = $status;
       $this->message = $message;
   }
    public function render()
    {
        return view('livewire.ticket-scanner');
    }
}
