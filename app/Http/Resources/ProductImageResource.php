<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductImageResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'image_url' => $this->image_url,
            'is_primary' => (bool) $this->is_primary,
            'sort_order' => $this->sort_order,
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
