<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ticket extends Model
{
     protected $guarded = [];
    //
      protected static function booted()
    {
        static::creating(function ($ticket) {
            $ticket->uuid = (string) Str::uuid();
        });
    }

    public function event():BelongsTo { return $this->belongsTo(Event::class); }
    public function ticketType():BelongsTo { return $this->belongsTo(TicketType::class, 'ticket_type_id'); }
}
