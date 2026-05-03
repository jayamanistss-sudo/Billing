<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BillItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'product_name' => $this->product_name,
            'unit_price' => (float) $this->unit_price,
            'purchase_price' => (float) $this->purchase_price,
            'quantity' => $this->quantity,
            'discount_percent' => (float) $this->discount_percent,
            'gst_rate' => (float) $this->gst_rate,
            'gst_amount' => (float) $this->gst_amount,
            'total' => (float) $this->total,
        ];
    }
}
