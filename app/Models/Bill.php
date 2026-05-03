<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Bill extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'tenant_id', 'customer_id', 'user_id', 'bill_number', 'bill_type',
        'subtotal', 'discount_percent', 'discount_amount', 'extra_charges',
        'extra_charges_label', 'cgst_amount', 'sgst_amount', 'igst_amount',
        'total_amount', 'paid_amount', 'due_amount',
        'payment_status', 'payment_method', 'notes', 'billed_at',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'discount_percent' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'extra_charges' => 'decimal:2',
        'cgst_amount' => 'decimal:2',
        'sgst_amount' => 'decimal:2',
        'igst_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'due_amount' => 'decimal:2',
        'billed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Bill $bill) {
            if (empty($bill->bill_number)) {
                $bill->bill_number = static::generateBillNumber($bill->tenant_id);
            }
            if (empty($bill->billed_at)) {
                $bill->billed_at = now();
            }
        });
    }

    public static function generateBillNumber(int $tenantId): string
    {
        $year = now()->year;
        $lastBill = static::where('tenant_id', $tenantId)
            ->whereYear('billed_at', $year)
            ->lockForUpdate()
            ->orderByDesc('id')
            ->first();

        $sequence = 1;
        if ($lastBill) {
            $parts = explode('-', $lastBill->bill_number);
            $sequence = (int) end($parts) + 1;
        }

        return sprintf('SB-%d-%05d', $year, $sequence);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(BillItem::class);
    }

    public function scopeToday(Builder $query): Builder
    {
        return $query->whereDate('billed_at', today());
    }

    public function scopeThisMonth(Builder $query): Builder
    {
        return $query->whereMonth('billed_at', now()->month)
            ->whereYear('billed_at', now()->year);
    }

    public function scopeByPaymentStatus(Builder $query, string $status): Builder
    {
        return $query->where('payment_status', $status);
    }

    public function getProfitAmountAttribute(): float
    {
        return $this->items->sum(function (BillItem $item) {
            return ((float) $item->unit_price - (float) $item->purchase_price) * $item->quantity;
        });
    }
}
