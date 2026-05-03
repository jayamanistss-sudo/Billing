<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBillRequest;
use App\Http\Resources\BillResource;
use App\Models\Bill;
use App\Services\BillingService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class BillController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly BillingService $billingService) {}

    public function index(Request $request): JsonResponse
    {
        $tenant = $request->attributes->get('tenant');
        $query = Bill::where('tenant_id', $tenant->id)->with(['customer', 'user']);

        if ($request->filled('date')) {
            $query->whereDate('billed_at', $request->date);
        }
        if ($request->filled('from') && $request->filled('to')) {
            $query->whereBetween('billed_at', [$request->from . ' 00:00:00', $request->to . ' 23:59:59']);
        }
        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }
        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }
        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }
        if ($request->filled('bill_type')) {
            $query->where('bill_type', $request->bill_type);
        }

        $paginator = $query->orderByDesc('billed_at')->paginate(20);
        return $this->paginated($paginator, 'BillResource');
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $tenant = $request->attributes->get('tenant');
        $bill = Bill::where('tenant_id', $tenant->id)
            ->with(['items', 'customer', 'user', 'tenant'])
            ->findOrFail($id);
        return $this->success(new BillResource($bill));
    }

    public function store(StoreBillRequest $request): JsonResponse
    {
        try {
            $tenant = $request->attributes->get('tenant');
            $user = auth()->user();
            $bill = $this->billingService->createBill($tenant, $user, $request->validated());
            return $this->created(new BillResource($bill->load(['items', 'customer', 'user'])));
        } catch (ValidationException $e) {
            return $this->error($e->getMessage(), 422, $e->errors());
        } catch (\Throwable $e) {
            Log::error('Bill creation failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return $this->error('Failed to create bill');
        }
    }

    public function processReturn(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        try {
            $tenant = $request->attributes->get('tenant');
            $bill = Bill::where('tenant_id', $tenant->id)->with('items')->findOrFail($id);
            $creditNote = $this->billingService->processReturn($bill, $request->items);
            return $this->created(new BillResource($creditNote), 'Return processed successfully');
        } catch (\Throwable $e) {
            Log::error('Return processing failed', ['error' => $e->getMessage()]);
            return $this->error('Failed to process return: ' . $e->getMessage());
        }
    }
}
