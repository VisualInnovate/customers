<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Call extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'start_time' => 'timestamp',
        'end_time' => 'timestamp',
        'duration' => 'integer',
        'metadata' => 'json',
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }
}
