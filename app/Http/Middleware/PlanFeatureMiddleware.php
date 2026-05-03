<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PlanFeatureMiddleware
{
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        $tenant = $request->attributes->get('tenant');

        if (!$tenant) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $tenant->load('plan');

        if (!$tenant->plan->{$feature}) {
            return response()->json([
                'success' => false,
                'message' => 'Upgrade your plan to access this feature',
            ], 403);
        }

        return $next($request);
    }
}
