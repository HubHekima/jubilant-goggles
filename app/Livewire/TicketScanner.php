<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Ticket;

class TicketScanner extends Component
{
    public $message = "Waiting for scan...";
    public $status = "idle";
    
    public function handleScan($uuid)
    {
        $ticket = Ticket::where('uuid', $uuid)->first();

        if (!$ticket) {
            $this->status = "error";
            $this->message = "❌ TICKET NOT FOUND";
            return;
        }

        if ($ticket->status === 'cancelled') {
            $this->status = "error";
            $this->message = "❌ CANCELLED TICKET";
            return;
        }

        if ($ticket->status === 'pending') {
            $this->status = "error";
            $this->message = "⚠️ PAYMENT PENDING";
            return;
        }

        // Check if already scanned - handle both string and Carbon dates
        if (!is_null($ticket->scanned_at)) {
            // Convert to readable time regardless of format
            $time = '';
            if ($ticket->scanned_at instanceof \Carbon\Carbon) {
                $time = $ticket->scanned_at->format('H:i');
            } else {
                // It's a string, just use it directly or format it
                $timestamp = strtotime($ticket->scanned_at);
                $time = $timestamp ? date('H:i', $timestamp) : $ticket->scanned_at;
            }
            
            $this->status = "error";
            $this->message = "⚠️ ALREADY SCANNED at " . $time;
            return;
        }

        // Valid ticket - mark as scanned
        $ticket->scanned_at = now();
        $ticket->save();
        
        $this->status = "success";
        $this->message = "✅ VALID ENTRY: " . $ticket->buyer_name;
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