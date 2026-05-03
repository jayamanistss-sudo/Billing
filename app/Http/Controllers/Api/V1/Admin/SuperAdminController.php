<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SuperAdminController extends Controller
{
    use ApiResponse;

    public function stats(): JsonResponse
    {
        $totalTenants = Tenant::count();
        $activeTenants = Tenant::where('is_active', true)->count();

        $activeSubscriptions = Subscription::where('status', 'active')
            ->with('plan')
            ->get();

        $totalRevenue = $activeSubscriptions->sum(fn ($sub) => $sub->plan?->price_monthly ?? 0);
        $mrr = $totalRevenue;

        $newTenantsThisMonth = Tenant::whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->count();

        $plansBreakdown = Plan::withCount(['subscriptions' => function ($q) {
            $q->where('status', 'active');
        }])->get()->map(fn ($plan) => [
            'plan_name' => $plan->name,
            'count'     => $plan->subscriptions_count,
        ]);

        return $this->success([
            'total_tenants'        => $totalTenants,
            'active_tenants'       => $activeTenants,
            'total_revenue'        => $totalRevenue,
            'mrr'                  => $mrr,
            'new_tenants_this_month' => $newTenantsThisMonth,
            'plans_breakdown'      => $plansBreakdown,
        ]);
    }

    public function tenants(Request $request): JsonResponse
    {
        $query = Tenant::with('plan')->latest();

        if ($request->has('is_active')) {
            $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('shop_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $tenants = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'message' => 'Success',
            'data'    => $tenants->items(),
            'meta'    => [
                'current_page' => $tenants->currentPage(),
                'last_page'    => $tenants->lastPage(),
                'per_page'     => $tenants->perPage(),
                'total'        => $tenants->total(),
            ],
        ]);
    }

    public function showTenant(int $id): JsonResponse
    {
        $tenant = Tenant::with(['plan', 'subscriptions.plan', 'users'])->findOrFail($id);

        return $this->success($tenant);
    }

    public function toggleTenant(int $id): JsonResponse
    {
        $tenant = Tenant::findOrFail($id);
        $tenant->update(['is_active' => !$tenant->is_active]);

        return $this->success($tenant->fresh(), 'Tenant status updated');
    }

    public function plans(): JsonResponse
    {
        $plans = Plan::orderBy('price_monthly')->get();

        return $this->success($plans);
    }

    public function storePlan(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'              => 'required|string|max:100',
            'slug'              => 'required|string|max:100|unique:plans,slug',
            'price_monthly'     => 'required|integer|min:0',
            'max_devices'       => 'required|integer',
            'max_products'      => 'required|integer',
            'max_staff'         => 'required|integer',
            'whatsapp_receipt'  => 'boolean',
            'multi_branch'      => 'boolean',
            'api_access'        => 'boolean',
        ]);

        $plan = Plan::create($data);

        return $this->created($plan, 'Plan created');
    }

    public function updatePlan(Request $request, int $id): JsonResponse
    {
        $plan = Plan::findOrFail($id);

        $data = $request->validate([
            'name'              => 'sometimes|string|max:100',
            'slug'              => 'sometimes|string|max:100|unique:plans,slug,' . $id,
            'price_monthly'     => 'sometimes|integer|min:0',
            'max_devices'       => 'sometimes|integer',
            'max_products'      => 'sometimes|integer',
            'max_staff'         => 'sometimes|integer',
            'whatsapp_receipt'  => 'boolean',
            'multi_branch'      => 'boolean',
            'api_access'        => 'boolean',
        ]);

        $plan->update($data);

        return $this->success($plan, 'Plan updated');
    }

    public function deletePlan(int $id): JsonResponse
    {
        $plan = Plan::findOrFail($id);

        $activeCount = Subscription::where('plan_id', $id)
            ->where('status', 'active')
            ->count();

        if ($activeCount > 0) {
            return $this->error('Cannot delete plan with active subscriptions', 409);
        }

        $plan->delete();

        return $this->success(null, 'Plan deleted');
    }

    public function subscriptions(Request $request): JsonResponse
    {
        $query = Subscription::with(['tenant', 'plan'])->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $subscriptions = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'message' => 'Success',
            'data'    => $subscriptions->items(),
            'meta'    => [
                'current_page' => $subscriptions->currentPage(),
                'last_page'    => $subscriptions->lastPage(),
                'per_page'     => $subscriptions->perPage(),
                'total'        => $subscriptions->total(),
            ],
        ]);
    }
}
