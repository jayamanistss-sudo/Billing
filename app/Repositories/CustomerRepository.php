<?php

namespace App\Repositories;

use App\Models\Customer;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Collection;

class CustomerRepository extends BaseRepository
{
    public function __construct(Customer $model)
    {
        parent::__construct($model);
    }

    public function findByPhone(Tenant $tenant, string $phone): ?Customer
    {
        return Customer::where('tenant_id', $tenant->id)
            ->where('phone', $phone)
            ->first();
    }

    public function updateCreditBalance(Customer $customer, float $amount): void
    {
        $customer->increment('credit_balance', $amount);
    }

    public function creditSummary(Tenant $tenant): array
    {
        return Customer::where('tenant_id', $tenant->id)
            ->where('credit_balance', '>', 0)
            ->selectRaw('COUNT(*) as customers_with_due, SUM(credit_balance) as total_due')
            ->first()
            ->toArray();
    }

    public function search(Tenant $tenant, string $term): Collection
    {
        return Customer::where('tenant_id', $tenant->id)
            ->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('phone', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%");
            })
            ->limit(20)
            ->get();
    }
}
