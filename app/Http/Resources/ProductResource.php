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
            'category_id' => $this->category_id,
            'category' => $this->whenLoaded('category', fn() => [
                'id' => $this->category->id,
                'name' => $this->category->name,
                'icon_emoji' => $this->category->icon_emoji,
                'color_hex' => $this->category->color_hex,
            ]),
            'name' => $this->name,
            'sku' => $this->sku,
            'barcode' => $this->barcode,
            'hsn_code' => $this->hsn_code,
            'unit' => $this->unit,
            'purchase_price' => (float) $this->purchase_price,
            'selling_price' => (float) $this->selling_price,
            'wholesale_price' => $this->wholesale_price ? (float) $this->wholesale_price : null,
            'gst_rate' => (float) $this->gst_rate,
            'gst_type' => $this->gst_type,
            'stock_quantity' => $this->stock_quantity,
            'reorder_level' => $this->reorder_level,
            'expiry_date' => $this->expiry_date?->toDateString(),
            'image_url' => $this->image_url,
            'is_active' => $this->is_active,
            'is_low_stock' => $this->is_low_stock,
            'effective_price' => (float) $this->selling_price,
        ];
    }
}
