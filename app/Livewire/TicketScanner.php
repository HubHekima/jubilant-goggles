<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Ticket;

class TicketScanner extends Component
{
    public $message = "Waiting for scan...";
    public $status = "idle"; // idle, success, error
    
    public function handleScan($uuid)
    {
        // Find ticket by UUID
        $ticket = Ticket::where('uuid', $uuid)->first();

        // Check 1: Ticket exists?
        if (!$ticket) {
            $this->status = "error";
            $this->message = "❌ INVALID TICKET - Not found in system";
            return;
        }

        // Check 2: Ticket cancelled?
        if ($ticket->status === 'cancelled') {
            $this->status = "error";
            $this->message = "❌ CANCELLED TICKET";
            return;
        }

        // Check 3: Payment pending?
        if ($ticket->status === 'pending') {
            $this->status = "error";
            $this->message = "⚠️ PAYMENT PENDING - Cannot enter";
            return;
        }

        // Check 4: Already scanned?
        if ($ticket->scanned_at !== null) {
            $this->status = "error";
            $this->message = "⚠️ ALREADY SCANNED at " . $ticket->scanned_at->format('H:i');
            return;
        }

        // ✅ VALID TICKET - Mark as scanned
        $ticket->update([
            'scanned_at' => now()
        ]);
        
        $this->status = "success";
        $this->message = "✅ VALID ENTRY - " . $ticket->buyer_name;
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