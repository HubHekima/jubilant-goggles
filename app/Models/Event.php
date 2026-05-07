<?php

namespace App\Models;
use Illuminate\Support\Str;

use Illuminate\Database\Eloquent\Model;

// app/Models/Ticket.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Event extends Model
{
    protected $guarded = [];

    protected $casts = [
    'starts_at' => 'datetime',
];
    public function ticketTypes(): HasMany
    {
        return $this->hasMany(TicketType::class);
    }
    public function tickets():HasMany { return $this->hasMany(Ticket::class); }
    
}
