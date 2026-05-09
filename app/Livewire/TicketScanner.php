<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Ticket;
use App\Models\Event;
use Illuminate\Support\Facades\Log;

class TicketScanner extends Component
{
    public $message = "Waiting for scan...";
    public $status = "idle"; // idle, success, error, warning
    public $selectedEvent = null;
    public $events = [];
    
    // Statistics for the current session
    public $scannedCount = 0;
    public $lastScannedTicket = null;
    public $recentScans = [];
    
    // Scanner settings
    public $enableSound = true;
    public $autoReset = true;
    
    public function mount()
    {
        // Load events for the selector (if you have multiple events)
        $this->events = Event::where('event_date', '>=', now())
            ->orderBy('event_date', 'asc')
            ->get();
        
        // Default to first event if available
        if ($this->events->isNotEmpty()) {
            $this->selectedEvent = $this->events->first()->id;
        }
    }
    
    public function handleScan($uuid)
    {
        try {
            // Find the ticket with eager loaded relationships
            $ticket = Ticket::with(['event', 'ticketType'])
                ->where('uuid', $uuid)
                ->first();

            // Check 1: Ticket exists
            if (!$ticket) {
                $this->showResult('error', '❌ INVALID TICKET', 'This QR code is not recognized in our system.');
                $this->playSound('error');
                Log::warning('Scan attempt with invalid UUID: ' . $uuid);
                return;
            }

            // Check 2: Ticket status
            if ($ticket->status === 'cancelled') {
                $this->showResult('error', '❌ CANCELLED TICKET', 'This ticket has been cancelled.');
                $this->playSound('error');
                Log::info('Attempt to scan cancelled ticket: ' . $uuid);
                return;
            }

            if ($ticket->status === 'pending') {
                $this->showResult('warning', '⚠️ PAYMENT PENDING', 'Ticket payment not confirmed. Buyer: ' . $ticket->buyer_name);
                $this->playSound('error');
                return;
            }

            // Check 3: Already scanned today (prevent duplicate same-day scans)
            if ($ticket->scanned_at && $ticket->scanned_at->isToday()) {
                $this->showResult('error', '⚠️ ALREADY USED TODAY', 
                    sprintf('Scanned at %s | %s - %s', 
                        $ticket->scanned_at->format('H:i'),
                        $ticket->buyer_name,
                        $ticket->ticketType->name ?? 'Standard'
                    )
                );
                $this->playSound('error');
                Log::warning('Duplicate scan attempt for ticket: ' . $uuid);
                return;
            }

            // Check 4: Event validation (optional - if you want to restrict to specific event)
            if ($this->selectedEvent && $ticket->event_id != $this->selectedEvent) {
                $this->showResult('error', '❌ WRONG EVENT', 
                    sprintf('This ticket is for "%s"', $ticket->event->name)
                );
                $this->playSound('error');
                return;
            }

            // Check 5: Event date validation (optional)
            if ($ticket->event->event_date->isPast()) {
                $this->showResult('warning', '⚠️ EVENT ENDED', 
                    sprintf('This event was on %s', $ticket->event->event_date->format('M d, Y'))
                );
                // You might still want to allow scanning for post-event verification
            }

            // VALID TICKET - Mark as scanned
            $ticket->update([
                'scanned_at' => now(),
                // Add more fields if needed, e.g., scanned_by, scan_location, etc.
            ]);

            // Update statistics
            $this->scannedCount++;
            $this->lastScannedTicket = [
                'name' => $ticket->buyer_name,
                'type' => $ticket->ticketType->name ?? 'Standard',
                'time' => now()->format('H:i'),
                'email' => $ticket->buyer_email,
            ];

            // Add to recent scans (keep last 5)
            array_unshift($this->recentScans, $this->lastScannedTicket);
            $this->recentScans = array_slice($this->recentScans, 0, 5);

            // Show success with ticket details
            $this->showResult('success', '✅ VALID TICKET', 
                sprintf('%s | %s | %s', 
                    $ticket->buyer_name,
                    $ticket->ticketType->name ?? 'Standard',
                    $ticket->event->name ?? ''
                )
            );
            $this->playSound('success');

            // Auto reset message after 3 seconds
            if ($this->autoReset) {
                $this->dispatch('auto-reset-message', delay: 3000);
            }

            Log::info('Successful scan for ticket: ' . $uuid, [
                'buyer' => $ticket->buyer_name,
                'event' => $ticket->event->name,
                'scan_count' => $this->scannedCount
            ]);

        } catch (\Exception $e) {
            Log::error('Scanner error: ' . $e->getMessage());
            $this->showResult('error', '❌ SYSTEM ERROR', 'An error occurred. Please try again.');
            $this->playSound('error');
        }
    }
    
    /**
     * Set status and message
     */
    public function setStatus($status, $message)
    {
        $this->status = $status;
        $this->message = $message;
    }
    
    /**
     * Helper to show scan result
     */
    private function showResult($status, $title, $details = '')
    {
        $this->status = $status;
        $this->message = $details ? $title . "\n" . $details : $title;
    }
    
    /**
     * Play sound effect (implement with JavaScript)
     */
    private function playSound($type)
    {
        if ($this->enableSound) {
            $this->dispatch('play-scan-sound', sound: $type);
        }
    }
    
    /**
     * Manual ticket lookup by UUID
     */
    public function manualLookup($uuid)
    {
        $this->handleScan($uuid);
    }
    
    /**
     * Toggle scanner settings
     */
    public function toggleSound()
    {
        $this->enableSound = !$this->enableSound;
    }
    
    public function toggleAutoReset()
    {
        $this->autoReset = !$this->autoReset;
    }
    
    /**
     * Get event capacity statistics
     */
    public function getEventStats()
    {
        if (!$this->selectedEvent) return null;
        
        $event = Event::find($this->selectedEvent);
        return [
            'total_tickets' => $event->tickets()->count(),
            'scanned_in' => $event->tickets()->whereNotNull('scanned_at')->count(),
            'pending' => $event->tickets()->where('status', 'pending')->count(),
            'revenue' => $event->tickets()->where('status', 'paid')->sum('amount_paid'),
        ];
    }
    
    public function render()
    {
        return view('livewire.ticket-scanner', [
            'eventStats' => $this->getEventStats(),
        ]);
    }
}