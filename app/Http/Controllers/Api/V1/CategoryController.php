<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CategoryController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $tenant = $request->attributes->get('tenant');
        $categories = Category::where('tenant_id', $tenant->id)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
        return $this->success(CategoryResource::collection($categories));
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $tenant = $request->attributes->get('tenant');
        $category = Category::where('tenant_id', $tenant->id)->findOrFail($id);
        return $this->success(new CategoryResource($category));
    }

    public function store(StoreCategoryRequest $request): JsonResponse
    {
        try {
            $tenant = $request->attributes->get('tenant');
            $category = Category::create(array_merge($request->validated(), ['tenant_id' => $tenant->id]));
            return $this->created(new CategoryResource($category));
        } catch (\Throwable $e) {
            Log::error('Category creation failed', ['error' => $e->getMessage()]);
            return $this->error('Failed to create category');
        }
    }

    public function update(StoreCategoryRequest $request, int $id): JsonResponse
    {
        $tenant = $request->attributes->get('tenant');
        $category = Category::where('tenant_id', $tenant->id)->findOrFail($id);
        $category->update($request->validated());
        return $this->success(new CategoryResource($category));
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $tenant = $request->attributes->get('tenant');
        $category = Category::where('tenant_id', $tenant->id)->findOrFail($id);
        $category->delete();
        return $this->success(null, 'Category deleted');
    }

    public function reorder(Request $request): JsonResponse
    {
        $request->validate([
            'order' => 'required|array',
            'order.*.id' => 'required|integer',
            'order.*.sort_order' => 'required|integer|min:0',
        ]);

        $tenant = $request->attributes->get('tenant');
        foreach ($request->order as $item) {
            Category::where('tenant_id', $tenant->id)
                ->where('id', $item['id'])
                ->update(['sort_order' => $item['sort_order']]);
        }

        return $this->success(null, 'Categories reordered');
    }
}
