<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $tenant = $request->attributes->get('tenant');
        $users = User::where('tenant_id', $tenant->id)->orderBy('name')->get();
        return $this->success(UserResource::collection($users));
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $tenant = $request->attributes->get('tenant');
        $user = User::where('tenant_id', $tenant->id)->findOrFail($id);
        return $this->success(new UserResource($user));
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        try {
            $tenant = $request->attributes->get('tenant');
            $data = $request->validated();
            $data['tenant_id'] = $tenant->id;
            $data['password'] = Hash::make($data['password']);
            if (!empty($data['pin'])) {
                $data['pin'] = Hash::make($data['pin']);
            }

            $user = User::create($data);
            return $this->created(new UserResource($user));
        } catch (\Throwable $e) {
            Log::error('User creation failed', ['error' => $e->getMessage()]);
            return $this->error('Failed to create user');
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'name' => 'nullable|string|max:100',
            'email' => 'nullable|email|max:200',
            'phone' => ['nullable', 'string', 'size:10', 'regex:/^[6-9]\d{9}$/'],
            'password' => 'nullable|string|min:8',
            'pin' => 'nullable|string|size:6|regex:/^\d{6}$/',
            'role' => 'nullable|in:manager,cashier',
            'is_active' => 'nullable|boolean',
        ]);

        $tenant = $request->attributes->get('tenant');
        $user = User::where('tenant_id', $tenant->id)->findOrFail($id);

        $data = $request->validated();
        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }
        if (!empty($data['pin'])) {
            $data['pin'] = Hash::make($data['pin']);
        } else {
            unset($data['pin']);
        }

        $user->update($data);
        return $this->success(new UserResource($user));
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $tenant = $request->attributes->get('tenant');
        $user = User::where('tenant_id', $tenant->id)->where('role', '!=', 'owner')->findOrFail($id);
        $user->delete();
        return $this->success(null, 'User deleted');
    }
}
