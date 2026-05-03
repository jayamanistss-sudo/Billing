<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'icon_emoji' => $this->icon_emoji,
            'color_hex' => $this->color_hex,
            'sort_order' => $this->sort_order,
            'is_active' => $this->is_active,
        ];
    }
}
