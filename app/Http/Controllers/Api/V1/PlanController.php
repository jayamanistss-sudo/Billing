<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\TenantResource;
use App\Models\Plan;
use App\Models\Subscription;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PlanController extends Controller
{
    use ApiResponse;

    public function index(): JsonResponse
    {
        $plans = Plan::orderBy('price_monthly')->get();
        return $this->success($plans);
    }

    public function current(Request $request): JsonResponse
    {
        $tenant = $request->attributes->get('tenant');
        return $this->success(new TenantResource($tenant->load('plan')));
    }

    public function upgrade(Request $request): JsonResponse
    {
        $request->validate([
            'plan_id' => 'required|exists:plans,id',
            'razorpay_payment_id' => 'required|string',
            'razorpay_order_id' => 'required|string',
            'razorpay_signature' => 'required|string',
        ]);

        try {
            $tenant = $request->attributes->get('tenant');
            $plan = Plan::findOrFail($request->plan_id);

            // In production, verify Razorpay signature here
            // $this->verifyRazorpaySignature($request);

            $tenant->update([
                'plan_id' => $plan->id,
                'plan_status' => 'active',
                'plan_renewed_at' => now(),
            ]);

            Subscription::create([
                'tenant_id' => $tenant->id,
                'plan_id' => $plan->id,
                'razorpay_payment_id' => $request->razorpay_payment_id,
                'status' => 'active',
                'starts_at' => now(),
                'ends_at' => now()->addMonth(),
            ]);

            return $this->success(new TenantResource($tenant->load('plan')), 'Plan upgraded successfully');
        } catch (\Throwable $e) {
            Log::error('Plan upgrade failed', ['error' => $e->getMessage()]);
            return $this->error('Plan upgrade failed');
        }
    }
}
