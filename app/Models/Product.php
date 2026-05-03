<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'tenant_id', 'category_id', 'name', 'sku', 'barcode', 'hsn_code',
        'unit', 'purchase_price', 'selling_price', 'wholesale_price',
        'gst_rate', 'gst_type', 'stock_quantity', 'reorder_level',
        'expiry_date', 'image_url', 'is_active',
    ];

    protected $casts = [
        'purchase_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'wholesale_price' => 'decimal:2',
        'gst_rate' => 'decimal:2',
        'is_active' => 'boolean',
        'stock_quantity' => 'integer',
        'reorder_level' => 'integer',
        'expiry_date' => 'date',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function billItems(): HasMany
    {
        return $this->hasMany(BillItem::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeLowStock(Builder $query): Builder
    {
        return $query->whereColumn('stock_quantity', '<=', 'reorder_level');
    }

    public function scopeByCategory(Builder $query, int $categoryId): Builder
    {
        return $query->where('category_id', $categoryId);
    }

    public function getIsLowStockAttribute(): bool
    {
        return $this->stock_quantity <= $this->reorder_level;
    }

    public function getEffectivePriceAttribute(): float
    {
        return (float) $this->selling_price;
    }

    public function effectivePriceForType(string $billType): float
    {
        if ($billType === 'wholesale' && $this->wholesale_price !== null) {
            return (float) $this->wholesale_price;
        }
        return (float) $this->selling_price;
    }
}
