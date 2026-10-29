<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomTicketNote extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function customTicket()
    {
        return $this->belongsTo(CustomTicket::class);
    }
}
