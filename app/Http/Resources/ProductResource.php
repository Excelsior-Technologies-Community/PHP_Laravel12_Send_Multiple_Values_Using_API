<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_name' => $this->product_name,
            'price' => $this->price,
            'price_in_inr' => '₹' . number_format($this->price, 2),
            'image' => $this->image ? url($this->image) : null,
            'colors' => $this->color_names,
            'created_at' => $this->created_at->format('d-m-Y h:i A'),
        ];
    }
}