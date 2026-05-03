<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'phone' => $this->phone,
            'email' => $this->email,
            'address' => $this->address,
            'credit_limit' => (float) $this->credit_limit,
            'credit_balance' => (float) $this->credit_balance,
            'has_dues' => $this->hasDues(),
            'total_bills' => $this->whenCounted('bills'),
        ];
    }
}
