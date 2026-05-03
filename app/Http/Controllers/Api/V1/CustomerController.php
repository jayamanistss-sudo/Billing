<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCustomerRequest;
use App\Http\Resources\BillResource;
use App\Http\Resources\CustomerResource;
use App\Models\Bill;
use App\Models\Customer;
use App\Repositories\CustomerRepository;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CustomerController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly CustomerRepository $customerRepository) {}

    public function index(Request $request): JsonResponse
    {
        $tenant = $request->attributes->get('tenant');
        $query = Customer::where('tenant_id', $tenant->id)->withCount('bills');

        if ($request->filled('search')) {
            $term = $request->search;
            $query->where(fn($q) => $q->where('name', 'like', "%$term%")->orWhere('phone', 'like', "%$term%"));
        }

        $paginator = $query->orderBy('name')->paginate(20);
        return $this->paginated($paginator, 'CustomerResource');
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $tenant = $request->attributes->get('tenant');
        $customer = Customer::where('tenant_id', $tenant->id)->withCount('bills')->findOrFail($id);
        return $this->success(new CustomerResource($customer));
    }

    public function store(StoreCustomerRequest $request): JsonResponse
    {
        try {
            $tenant = $request->attributes->get('tenant');
            $customer = Customer::create(array_merge($request->validated(), ['tenant_id' => $tenant->id]));
            return $this->created(new CustomerResource($customer));
        } catch (\Throwable $e) {
            Log::error('Customer creation failed', ['error' => $e->getMessage()]);
            return $this->error('Failed to create customer');
        }
    }

    public function update(StoreCustomerRequest $request, int $id): JsonResponse
    {
        $tenant = $request->attributes->get('tenant');
        $customer = Customer::where('tenant_id', $tenant->id)->findOrFail($id);
        $customer->update($request->validated());
        return $this->success(new CustomerResource($customer));
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $tenant = $request->attributes->get('tenant');
        $customer = Customer::where('tenant_id', $tenant->id)->findOrFail($id);
        $customer->delete();
        return $this->success(null, 'Customer deleted');
    }

    public function search(Request $request): JsonResponse
    {
        $request->validate(['q' => 'required|string|min:2']);
        $tenant = $request->attributes->get('tenant');
        $customers = $this->customerRepository->search($tenant, $request->q);
        return $this->success(CustomerResource::collection($customers));
    }

    public function creditLedger(Request $request, int $id): JsonResponse
    {
        $tenant = $request->attributes->get('tenant');
        $customer = Customer::where('tenant_id', $tenant->id)->findOrFail($id);

        $bills = Bill::where('tenant_id', $tenant->id)
            ->where('customer_id', $customer->id)
            ->whereIn('payment_status', ['due', 'partial'])
            ->with(['items', 'user'])
            ->orderByDesc('billed_at')
            ->paginate(20);

        return $this->paginated($bills, 'BillResource');
    }
}
