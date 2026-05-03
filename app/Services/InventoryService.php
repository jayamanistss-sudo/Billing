<?php

namespace App\Services;

use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    public function adjustStock(
        Product $product,
        int $qty,
        string $type,
        User $user,
        ?string $notes = null
    ): StockMovement {
        return DB::transaction(function () use ($product, $qty, $type, $user, $notes) {
            $product = Product::lockForUpdate()->find($product->id);
            $stockBefore = $product->stock_quantity;

            if (in_array($type, ['sale', 'damage'])) {
                $product->decrement('stock_quantity', abs($qty));
                $movementQty = -abs($qty);
            } else {
                $product->increment('stock_quantity', abs($qty));
                $movementQty = abs($qty);
            }

            $product->refresh();

            return StockMovement::create([
                'tenant_id' => $product->tenant_id,
                'product_id' => $product->id,
                'user_id' => $user->id,
                'type' => $type,
                'quantity' => $movementQty,
                'stock_before' => $stockBefore,
                'stock_after' => $product->stock_quantity,
                'notes' => $notes,
            ]);
        });
    }

    public function bulkImportFromArray(Tenant $tenant, array $rows): array
    {
        $imported = 0;
        $failed = [];

        foreach ($rows as $index => $row) {
            try {
                if (empty($row['name']) || empty($row['selling_price'])) {
                    $failed[] = ['row' => $index + 2, 'error' => 'Name and selling_price are required'];
                    continue;
                }

                Product::updateOrCreate(
                    ['tenant_id' => $tenant->id, 'sku' => $row['sku'] ?? null],
                    [
                        'tenant_id' => $tenant->id,
                        'name' => $row['name'],
                        'sku' => $row['sku'] ?? null,
                        'barcode' => $row['barcode'] ?? null,
                        'selling_price' => (float) $row['selling_price'],
                        'purchase_price' => (float) ($row['purchase_price'] ?? 0),
                        'stock_quantity' => (int) ($row['stock_quantity'] ?? 0),
                        'reorder_level' => (int) ($row['reorder_level'] ?? 5),
                        'gst_rate' => (float) ($row['gst_rate'] ?? 0),
                        'unit' => $row['unit'] ?? 'piece',
                        'is_active' => true,
                    ]
                );
                $imported++;
            } catch (\Throwable $e) {
                $failed[] = ['row' => $index + 2, 'error' => $e->getMessage()];
            }
        }

        return ['imported' => $imported, 'failed' => $failed];
    }

    public function getLowStockProducts(Tenant $tenant): Collection
    {
        return Product::where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->whereColumn('stock_quantity', '<=', 'reorder_level')
            ->with('category')
            ->orderBy('stock_quantity')
            ->get();
    }

    public function getExpiringProducts(Tenant $tenant, int $withinDays = 30): Collection
    {
        return Product::where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->whereNotNull('expiry_date')
            ->where('expiry_date', '<=', Carbon::now()->addDays($withinDays))
            ->where('expiry_date', '>=', Carbon::today())
            ->orderBy('expiry_date')
            ->get();
    }
}
