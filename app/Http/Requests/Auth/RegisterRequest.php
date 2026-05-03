<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'shop_name' => 'required|string|max:200',
            'owner_name' => 'required|string|max:100',
            'email' => 'required|email|unique:tenants,email',
            'phone' => ['required', 'string', 'size:10', 'unique:tenants,phone', 'regex:/^[6-9]\d{9}$/'],
            'password' => 'required|string|min:8|confirmed',
            'plan_id' => 'nullable|exists:plans,id',
            'gstin' => ['nullable', 'string', 'size:15', 'regex:/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.regex' => 'Phone must be a valid 10-digit Indian mobile number.',
            'gstin.regex' => 'GSTIN format is invalid.',
        ];
    }
}
