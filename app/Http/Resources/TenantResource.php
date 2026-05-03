<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TenantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'shop_name' => $this->shop_name,
            'owner_name' => $this->owner_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'gstin' => $this->gstin,
            'address' => $this->address,
            'city' => $this->city,
            'state' => $this->state,
            'pincode' => $this->pincode,
            'logo_url' => $this->logo_url,
            'receipt_footer' => $this->receipt_footer,
            'currency' => $this->currency,
            'timezone' => $this->timezone,
            'plan' => $this->whenLoaded('plan', fn() => [
                'id' => $this->plan->id,
                'name' => $this->plan->name,
                'slug' => $this->plan->slug,
                'price_monthly' => $this->plan->price_monthly,
                'whatsapp_receipt' => $this->plan->whatsapp_receipt,
                'multi_branch' => $this->plan->multi_branch,
                'api_access' => $this->plan->api_access,
                'max_products' => $this->plan->max_products,
                'max_staff' => $this->plan->max_staff,
            ]),
            'plan_status' => $this->plan_status,
            'trial_ends_at' => $this->trial_ends_at?->toISOString(),
            'is_active' => $this->is_active,
        ];
    }
}
