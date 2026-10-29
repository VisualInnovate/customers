<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\OrganizationPaymentGateway;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OrganizationPaymentGatewayData extends Model
{
    use HasFactory;
    protected $guarded = [];
    public $timestamps = true;

    public function organization_payment_gateway()
    {
        return $this->belongsTo(OrganizationPaymentGateway::class);
    }
}
