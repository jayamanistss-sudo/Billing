<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $tenantId = request()->attributes->get('tenant')->id;

        return [
            'name' => 'required|string|max:200',
            'category_id' => 'nullable|exists:categories,id',
            'sku' => 'nullable|string|max:100',
            'barcode' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('products')->where(fn($q) => $q->where('tenant_id', $tenantId))->ignore($this->route('product')),
            ],
            'hsn_code' => 'nullable|string|max:20',
            'unit' => 'nullable|in:piece,kg,gram,litre,ml,box,pack,dozen',
            'purchase_price' => 'nullable|numeric|min:0',
            'selling_price' => 'required|numeric|min:0.01',
            'wholesale_price' => 'nullable|numeric|min:0',
            'gst_rate' => 'nullable|in:0,5,12,18,28',
            'gst_type' => 'nullable|in:inclusive,exclusive',
            'stock_quantity' => 'required|integer|min:0',
            'reorder_level' => 'required|integer|min:0',
            'expiry_date' => 'nullable|date|after:today',
            'is_active' => 'nullable|boolean',
        ];
    }
}
