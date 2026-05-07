<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use Illuminate\Database\Eloquent\Model;

class MpesaTransaction extends Model
{
    protected $guarded = [];
    //
      // This links the transaction back to the ticket
    public function ticket():BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }
}
