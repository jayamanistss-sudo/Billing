<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\TenantResource;
use App\Http\Resources\UserResource;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    use ApiResponse;

    public function register(RegisterRequest $request): JsonResponse
    {
        try {
            $plan = Plan::where('slug', 'starter')->first()
                ?? Plan::find($request->plan_id)
                ?? Plan::first();

            $tenant = Tenant::create([
                'shop_name' => $request->shop_name,
                'owner_name' => $request->owner_name,
                'email' => $request->email,
                'phone' => $request->phone,
                'gstin' => $request->gstin,
                'plan_id' => $plan->id,
                'plan_status' => 'trial',
                'trial_ends_at' => now()->addDays(14),
                'is_active' => true,
            ]);

            $user = User::create([
                'tenant_id' => $tenant->id,
                'name' => $request->owner_name,
                'email' => $request->email,
                'phone' => $request->phone,
                'password' => Hash::make($request->password),
                'role' => 'owner',
                'is_active' => true,
            ]);

            $token = auth()->login($user);

            return $this->created([
                'token' => $token,
                'token_type' => 'bearer',
                'expires_in' => config('jwt.ttl') * 60,
                'user' => new UserResource($user),
                'tenant' => new TenantResource($tenant->load('plan')),
            ], 'Registration successful. 14-day trial started.');
        } catch (\Throwable $e) {
            Log::error('Registration failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return $this->error('Registration failed. Please try again.');
        }
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $login = $request->login;
        $field = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';

        $user = User::where($field, $login)->where('is_active', true)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return $this->unauthorized('Invalid credentials');
        }

        $token = auth()->login($user);
        $user->update(['last_login_at' => now()]);

        return $this->success([
            'token' => $token,
            'token_type' => 'bearer',
            'expires_in' => config('jwt.ttl') * 60,
            'user' => new UserResource($user),
            'tenant' => new TenantResource($user->tenant()->with('plan')->first()),
        ], 'Login successful');
    }

    public function pinLogin(Request $request): JsonResponse
    {
        $request->validate([
            'pin' => 'required|string|size:6',
            'tenant_id' => 'required|integer|exists:tenants,id',
        ]);

        $users = User::where('tenant_id', $request->tenant_id)
            ->where('is_active', true)
            ->whereNotNull('pin')
            ->get();

        $user = $users->first(fn($u) => Hash::check($request->pin, $u->pin));

        if (!$user) {
            return $this->unauthorized('Invalid PIN');
        }

        $token = auth()->login($user);
        $user->update(['last_login_at' => now()]);

        return $this->success([
            'token' => $token,
            'token_type' => 'bearer',
            'expires_in' => config('jwt.ttl') * 60,
            'user' => new UserResource($user),
        ], 'PIN login successful');
    }

    public function refresh(): JsonResponse
    {
        try {
            $token = auth()->refresh();
            return $this->success(['token' => $token, 'token_type' => 'bearer']);
        } catch (\Throwable $e) {
            return $this->unauthorized('Token refresh failed');
        }
    }

    public function logout(): JsonResponse
    {
        auth()->logout();
        return $this->success(null, 'Logged out successfully');
    }

    public function me(): JsonResponse
    {
        $user = auth()->user();
        return $this->success([
            'user' => new UserResource($user),
            'tenant' => new TenantResource($user->tenant()->with('plan')->first()),
        ]);
    }
}
