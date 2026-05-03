<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TenantMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $tenant = Tenant::find($user->tenant_id);

        if (!$tenant) {
            return response()->json(['success' => false, 'message' => 'Tenant not found'], 404);
        }

        if (!$tenant->is_active) {
            return response()->json(['success' => false, 'message' => 'Your account has been suspended'], 403);
        }

        if ($tenant->plan_status === 'expired') {
            return response()->json([
                'success' => false,
                'message' => 'Your subscription has expired. Please renew to continue.',
            ], 402);
        }

        $request->merge(['tenant' => $tenant]);
        $request->attributes->set('tenant', $tenant);

        return $next($request);
    }
}
