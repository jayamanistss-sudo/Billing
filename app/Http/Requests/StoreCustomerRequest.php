<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCustomerRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:200',
            'phone' => ['nullable', 'string', 'size:10', 'regex:/^[6-9]\d{9}$/'],
            'email' => 'nullable|email|max:200',
            'address' => 'nullable|string|max:500',
            'credit_limit' => 'nullable|numeric|min:0',
        ];
    }
}
