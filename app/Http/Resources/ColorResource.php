<?php
// app/Http/Resources/ColorResource.php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ColorResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'color_name' => $this->color_name,
            'products_count' => $this->whenCounted('products'),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}