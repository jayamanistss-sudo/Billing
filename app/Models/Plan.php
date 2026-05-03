<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    use HasFactory;
    protected $fillable = [
        'name', 'slug', 'price_monthly', 'max_devices', 'max_products',
        'max_staff', 'whatsapp_receipt', 'multi_branch', 'api_access',
    ];

    protected $casts = [
        'whatsapp_receipt' => 'boolean',
        'multi_branch' => 'boolean',
        'api_access' => 'boolean',
    ];

    public function tenants(): HasMany
    {
        return $this->hasMany(Tenant::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }
}
