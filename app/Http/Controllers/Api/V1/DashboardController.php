<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\BillResource;
use App\Models\Bill;
use App\Models\Customer;
use App\Models\Product;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $tenant = $request->attributes->get('tenant');

        $todayBills = Bill::where('tenant_id', $tenant->id)
            ->whereDate('billed_at', today())
            ->get();

        $lowStockCount = Product::where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->whereColumn('stock_quantity', '<=', 'reorder_level')
            ->count();

        $pendingCredit = Customer::where('tenant_id', $tenant->id)
            ->sum('credit_balance');

        $recentBills = Bill::where('tenant_id', $tenant->id)
            ->with(['customer', 'user'])
            ->orderByDesc('billed_at')
            ->limit(10)
            ->get();

        return $this->success([
            'today_sales' => round((float) $todayBills->sum('total_amount'), 2),
            'today_bills_count' => $todayBills->count(),
            'low_stock_count' => $lowStockCount,
            'pending_credit_amount' => round((float) $pendingCredit, 2),
            'avg_bill_value' => $todayBills->count()
                ? round((float) $todayBills->avg('total_amount'), 2)
                : 0,
            'recent_bills' => BillResource::collection($recentBills),
        ]);
    }
}
