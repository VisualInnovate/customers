<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentGateway extends Model {
    use HasFactory;

    protected $guarded = [];
    public $timestamps = true;
    protected $casts = [
        'metadata' => 'json'
    ];

    public function listAll()
    {
        $query = $this->paginate(10);

        return $query;
    }
}
