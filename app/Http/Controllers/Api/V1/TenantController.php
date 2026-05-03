<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\TenantResource;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class TenantController extends Controller
{
    use ApiResponse;

    public function show(Request $request): JsonResponse
    {
        $tenant = $request->attributes->get('tenant');
        return $this->success(new TenantResource($tenant->load('plan')));
    }

    public function update(Request $request): JsonResponse
    {
        $request->validate([
            'shop_name' => 'nullable|string|max:200',
            'owner_name' => 'nullable|string|max:100',
            'gstin' => ['nullable', 'string', 'size:15', 'regex:/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/'],
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'pincode' => 'nullable|string|max:10',
            'receipt_footer' => 'nullable|string|max:200',
            'timezone' => 'nullable|string|max:50',
        ]);

        try {
            $tenant = $request->attributes->get('tenant');
            $tenant->update($request->validated());
            return $this->success(new TenantResource($tenant->load('plan')));
        } catch (\Throwable $e) {
            Log::error('Tenant update failed', ['error' => $e->getMessage()]);
            return $this->error('Failed to update shop settings');
        }
    }

    public function uploadLogo(Request $request): JsonResponse
    {
        $request->validate(['logo' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048']);

        try {
            $tenant = $request->attributes->get('tenant');

            if ($tenant->logo_url) {
                Storage::disk('public')->delete($tenant->logo_url);
            }

            $path = $request->file('logo')->store("logos/{$tenant->id}", 'public');
            $tenant->update(['logo_url' => Storage::disk('public')->url($path)]);

            return $this->success(['logo_url' => $tenant->logo_url]);
        } catch (\Throwable $e) {
            Log::error('Logo upload failed', ['error' => $e->getMessage()]);
            return $this->error('Logo upload failed');
        }
    }
}
