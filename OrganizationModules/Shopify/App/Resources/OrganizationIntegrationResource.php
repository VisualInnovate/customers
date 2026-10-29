<?php

namespace OrganizationModules\Shopify\App\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class OrganizationIntegrationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'credentials' => json_decode($this->metadata),
        ];
    }
}
