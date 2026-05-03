<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\StockMovementResource;
use App\Models\Product;
use App\Models\StockMovement;
use App\Services\InventoryService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class StockMovementController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly InventoryService $inventoryService) {}

    public function index(Request $request): JsonResponse
    {
        $tenant = $request->attributes->get('tenant');
        $query = StockMovement::where('tenant_id', $tenant->id)->with(['product', 'user']);

        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($request->filled('from')) {
            $query->where('created_at', '>=', $request->from . ' 00:00:00');
        }
        if ($request->filled('to')) {
            $query->where('created_at', '<=', $request->to . ' 23:59:59');
        }

        $paginator = $query->orderByDesc('created_at')->paginate(20);
        return $this->paginated($paginator, 'StockMovementResource');
    }

    public function adjust(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|integer|exists:products,id',
            'type' => 'required|in:purchase,adjustment,damage',
            'quantity' => 'required|integer|min:1',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            $tenant = $request->attributes->get('tenant');
            $product = Product::where('tenant_id', $tenant->id)
                ->findOrFail($request->product_id);

            $movement = $this->inventoryService->adjustStock(
                $product,
                $request->quantity,
                $request->type,
                auth()->user(),
                $request->notes
            );

            return $this->created(new StockMovementResource($movement->load(['product', 'user'])));
        } catch (\Throwable $e) {
            Log::error('Stock adjustment failed', ['error' => $e->getMessage()]);
            return $this->error('Stock adjustment failed: ' . $e->getMessage());
        }
    }
}
