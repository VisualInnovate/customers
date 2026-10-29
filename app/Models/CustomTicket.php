<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomTicket extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function customTicketNotes()
    {
        return $this->hasMany(CustomTicketNote::class);
    }

    // protected $casts = [
    //     'contact' => 'array',
    // ];
}
