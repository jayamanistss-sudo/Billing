<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class ReportController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly ReportService $reportService) {}

    public function daily(Request $request): JsonResponse
    {
        $request->validate(['date' => 'nullable|date']);
        $tenant = $request->attributes->get('tenant');
        $date = $request->filled('date') ? Carbon::parse($request->date) : Carbon::today();
        return $this->success($this->reportService->dailySalesSummary($tenant, $date));
    }

    public function monthly(Request $request): JsonResponse
    {
        $request->validate([
            'year' => 'nullable|integer|min:2020|max:2099',
            'month' => 'nullable|integer|min:1|max:12',
        ]);
        $tenant = $request->attributes->get('tenant');
        $year = (int) ($request->year ?? now()->year);
        $month = (int) ($request->month ?? now()->month);
        return $this->success($this->reportService->monthlySalesSummary($tenant, $year, $month));
    }

    public function topProducts(Request $request): JsonResponse
    {
        $request->validate([
            'from' => 'nullable|date',
            'to' => 'nullable|date|after_or_equal:from',
            'limit' => 'nullable|integer|min:1|max:50',
        ]);
        $tenant = $request->attributes->get('tenant');
        $from = $request->filled('from') ? Carbon::parse($request->from) : Carbon::now()->startOfMonth();
        $to = $request->filled('to') ? Carbon::parse($request->to) : Carbon::now();
        $limit = (int) ($request->limit ?? 10);
        $products = $this->reportService->topProducts($tenant, $from, $to, $limit);
        return $this->success($products);
    }

    public function gstSummary(Request $request): JsonResponse
    {
        $request->validate([
            'year' => 'nullable|integer|min:2020|max:2099',
            'month' => 'nullable|integer|min:1|max:12',
        ]);
        $tenant = $request->attributes->get('tenant');
        $year = (int) ($request->year ?? now()->year);
        $month = (int) ($request->month ?? now()->month);
        return $this->success($this->reportService->gstSummary($tenant, $year, $month));
    }

    public function profit(Request $request): JsonResponse
    {
        $request->validate([
            'from' => 'nullable|date',
            'to' => 'nullable|date|after_or_equal:from',
        ]);
        $tenant = $request->attributes->get('tenant');
        $from = $request->filled('from') ? Carbon::parse($request->from) : Carbon::now()->startOfMonth();
        $to = $request->filled('to') ? Carbon::parse($request->to) : Carbon::now();
        return $this->success($this->reportService->profitReport($tenant, $from, $to));
    }
}
