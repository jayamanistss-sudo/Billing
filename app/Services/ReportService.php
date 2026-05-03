<?php

namespace App\Services;

use App\Models\Bill;
use App\Models\BillItem;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ReportService
{
    public function dailySalesSummary(Tenant $tenant, Carbon $date): array
    {
        $bills = Bill::where('tenant_id', $tenant->id)
            ->whereDate('billed_at', $date)
            ->whereNull('deleted_at')
            ->get();

        $profit = $bills->sum(fn($b) => $b->profit_amount);

        return [
            'date' => $date->toDateString(),
            'total_sales' => round((float) $bills->sum('total_amount'), 2),
            'total_bills' => $bills->count(),
            'avg_bill_value' => $bills->count() ? round((float) $bills->avg('total_amount'), 2) : 0,
            'net_profit' => round($profit, 2),
            'total_cgst' => round((float) $bills->sum('cgst_amount'), 2),
            'total_sgst' => round((float) $bills->sum('sgst_amount'), 2),
            'payment_breakdown' => [
                'cash' => round((float) $bills->where('payment_method', 'cash')->sum('total_amount'), 2),
                'upi' => round((float) $bills->where('payment_method', 'upi')->sum('total_amount'), 2),
                'card' => round((float) $bills->where('payment_method', 'card')->sum('total_amount'), 2),
                'credit' => round((float) $bills->where('payment_method', 'credit')->sum('total_amount'), 2),
                'mixed' => round((float) $bills->where('payment_method', 'mixed')->sum('total_amount'), 2),
            ],
        ];
    }

    public function monthlySalesSummary(Tenant $tenant, int $year, int $month): array
    {
        $bills = Bill::where('tenant_id', $tenant->id)
            ->whereYear('billed_at', $year)
            ->whereMonth('billed_at', $month)
            ->whereNull('deleted_at')
            ->get();

        $dailyData = $bills->groupBy(fn($b) => $b->billed_at->format('Y-m-d'))
            ->map(fn($dayBills) => [
                'date' => $dayBills->first()->billed_at->toDateString(),
                'total_sales' => round((float) $dayBills->sum('total_amount'), 2),
                'total_bills' => $dayBills->count(),
            ])
            ->values();

        return [
            'year' => $year,
            'month' => $month,
            'total_sales' => round((float) $bills->sum('total_amount'), 2),
            'total_bills' => $bills->count(),
            'avg_bill_value' => $bills->count() ? round((float) $bills->avg('total_amount'), 2) : 0,
            'net_profit' => round($bills->sum(fn($b) => $b->profit_amount), 2),
            'total_cgst' => round((float) $bills->sum('cgst_amount'), 2),
            'total_sgst' => round((float) $bills->sum('sgst_amount'), 2),
            'daily_breakdown' => $dailyData,
        ];
    }

    public function topProducts(Tenant $tenant, Carbon $from, Carbon $to, int $limit = 10): Collection
    {
        return BillItem::join('bills', 'bill_items.bill_id', '=', 'bills.id')
            ->where('bills.tenant_id', $tenant->id)
            ->whereBetween('bills.billed_at', [$from->startOfDay(), $to->endOfDay()])
            ->whereNull('bills.deleted_at')
            ->groupBy('bill_items.product_id', 'bill_items.product_name')
            ->select(
                'bill_items.product_id',
                'bill_items.product_name',
                DB::raw('SUM(bill_items.quantity) as total_quantity'),
                DB::raw('SUM(bill_items.total) as total_revenue')
            )
            ->orderByDesc('total_quantity')
            ->limit($limit)
            ->get();
    }

    public function gstSummary(Tenant $tenant, int $year, int $month): array
    {
        $slabs = BillItem::join('bills', 'bill_items.bill_id', '=', 'bills.id')
            ->where('bills.tenant_id', $tenant->id)
            ->whereYear('bills.billed_at', $year)
            ->whereMonth('bills.billed_at', $month)
            ->whereNull('bills.deleted_at')
            ->groupBy('bill_items.gst_rate')
            ->select(
                'bill_items.gst_rate',
                DB::raw('SUM(bill_items.total - bill_items.gst_amount) as taxable_value'),
                DB::raw('SUM(bill_items.gst_amount / 2) as cgst_amount'),
                DB::raw('SUM(bill_items.gst_amount / 2) as sgst_amount'),
                DB::raw('SUM(bill_items.gst_amount) as total_gst')
            )
            ->orderBy('bill_items.gst_rate')
            ->get();

        return [
            'year' => $year,
            'month' => $month,
            'slabs' => $slabs->map(fn($s) => [
                'gst_rate' => $s->gst_rate,
                'taxable_value' => round((float) $s->taxable_value, 2),
                'cgst_amount' => round((float) $s->cgst_amount, 2),
                'sgst_amount' => round((float) $s->sgst_amount, 2),
                'total_gst' => round((float) $s->total_gst, 2),
            ])->toArray(),
        ];
    }

    public function profitReport(Tenant $tenant, Carbon $from, Carbon $to): array
    {
        $bills = Bill::where('tenant_id', $tenant->id)
            ->whereBetween('billed_at', [$from->startOfDay(), $to->endOfDay()])
            ->whereNull('deleted_at')
            ->with('items')
            ->get();

        $totalRevenue = (float) $bills->sum('total_amount');
        $totalProfit = $bills->sum(fn($b) => $b->profit_amount);
        $margin = $totalRevenue > 0 ? round(($totalProfit / $totalRevenue) * 100, 2) : 0;

        return [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'total_revenue' => round($totalRevenue, 2),
            'total_profit' => round($totalProfit, 2),
            'profit_margin_pct' => $margin,
            'total_bills' => $bills->count(),
        ];
    }
}
