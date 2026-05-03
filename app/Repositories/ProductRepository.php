<?php

namespace App\Repositories;

use App\Models\Product;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class ProductRepository extends BaseRepository
{
    public function __construct(Product $model)
    {
        parent::__construct($model);
    }

    public function findByBarcode(Tenant $tenant, string $barcode): ?Product
    {
        return Product::where('tenant_id', $tenant->id)
            ->where('barcode', $barcode)
            ->where('is_active', true)
            ->first();
    }

    public function search(Tenant $tenant, string $term): Collection
    {
        return Product::where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('sku', 'like', "%{$term}%")
                    ->orWhere('barcode', 'like', "%{$term}%");
            })
            ->with('category')
            ->orderBy('name')
            ->limit(50)
            ->get();
    }

    public function lowStock(Tenant $tenant): Collection
    {
        return Product::where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->whereColumn('stock_quantity', '<=', 'reorder_level')
            ->with('category')
            ->orderBy('stock_quantity')
            ->get();
    }

    public function paginateWithFilters(Tenant $tenant, array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = Product::where('tenant_id', $tenant->id)
            ->with('category');

        if (!empty($filters['search'])) {
            $term = $filters['search'];
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('sku', 'like', "%{$term}%")
                    ->orWhere('barcode', 'like', "%{$term}%");
            });
        }

        if (!empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (isset($filters['low_stock']) && $filters['low_stock']) {
            $query->whereColumn('stock_quantity', '<=', 'reorder_level');
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', (bool) $filters['is_active']);
        } else {
            $query->where('is_active', true);
        }

        return $query->orderBy('name')->paginate($perPage);
    }
}
