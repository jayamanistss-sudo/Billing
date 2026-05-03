<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Repositories\ProductRepository;
use App\Services\InventoryService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly ProductRepository $productRepository,
        private readonly InventoryService $inventoryService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tenant = $request->attributes->get('tenant');
        $paginator = $this->productRepository->paginateWithFilters($tenant, $request->all(), 20);
        return $this->paginated($paginator, 'ProductResource');
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $tenant = $request->attributes->get('tenant');
        $product = Product::where('tenant_id', $tenant->id)->with('category')->findOrFail($id);
        return $this->success(new ProductResource($product));
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        try {
            $tenant = $request->attributes->get('tenant');
            $data = array_merge($request->validated(), ['tenant_id' => $tenant->id]);
            $product = Product::create($data);
            return $this->created(new ProductResource($product->load('category')));
        } catch (\Throwable $e) {
            Log::error('Product creation failed', ['error' => $e->getMessage()]);
            return $this->error('Failed to create product');
        }
    }

    public function update(StoreProductRequest $request, int $id): JsonResponse
    {
        try {
            $tenant = $request->attributes->get('tenant');
            $product = Product::where('tenant_id', $tenant->id)->findOrFail($id);
            $product->update($request->validated());
            return $this->success(new ProductResource($product->load('category')));
        } catch (\Throwable $e) {
            Log::error('Product update failed', ['error' => $e->getMessage()]);
            return $this->error('Failed to update product');
        }
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $tenant = $request->attributes->get('tenant');
        $product = Product::where('tenant_id', $tenant->id)->findOrFail($id);
        $product->delete();
        return $this->success(null, 'Product deleted');
    }

    public function lowStock(Request $request): JsonResponse
    {
        $tenant = $request->attributes->get('tenant');
        $products = $this->inventoryService->getLowStockProducts($tenant);
        return $this->success(ProductResource::collection($products));
    }

    public function bulkImport(Request $request): JsonResponse
    {
        $request->validate(['file' => 'required|file|mimes:csv,txt|max:5120']);
        $tenant = $request->attributes->get('tenant');

        try {
            $path = $request->file('file')->getRealPath();
            $handle = fopen($path, 'r');
            if (!$handle) {
                return $this->error('Could not read file');
            }

            $headers = array_map('strtolower', array_map('trim', fgetcsv($handle) ?: []));
            if (empty($headers)) {
                fclose($handle);
                return $this->error('File is empty');
            }

            $mappedRows = [];
            while (($row = fgetcsv($handle)) !== false) {
                $combined = array_combine($headers, $row);
                if ($combined) $mappedRows[] = $combined;
            }
            fclose($handle);

            $result = $this->inventoryService->bulkImportFromArray($tenant, $mappedRows);
            return $this->success($result, "Import complete: {$result['imported']} imported");
        } catch (\Throwable $e) {
            Log::error('Bulk import failed', ['error' => $e->getMessage()]);
            return $this->error('Import failed: ' . $e->getMessage());
        }
    }
}
