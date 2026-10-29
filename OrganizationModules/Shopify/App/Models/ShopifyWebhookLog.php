<?php

namespace OrganizationModules\Shopify\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use OrganizationModules\Shopify\Database\factories\ShopifyWebhookLogFactory;

class ShopifyWebhookLog extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $table = 'shopify_webhook_logs';
    
    protected $fillable = ['organization_id', 'payload'];
    
    protected static function newFactory()
    {
        //return ShopifyWebhookLogFactory::new();
    }
}
