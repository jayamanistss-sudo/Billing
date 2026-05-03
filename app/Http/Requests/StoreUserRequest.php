<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:100',
            'email' => 'nullable|email|max:200',
            'phone' => ['nullable', 'string', 'size:10', 'regex:/^[6-9]\d{9}$/'],
            'password' => 'required|string|min:8',
            'pin' => 'nullable|string|size:6|regex:/^\d{6}$/',
            'role' => 'required|in:manager,cashier',
            'is_active' => 'nullable|boolean',
        ];
    }
}
