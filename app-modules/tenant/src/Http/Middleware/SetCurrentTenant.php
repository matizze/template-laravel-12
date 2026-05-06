<?php

namespace Modules\Tenant\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Tenant\Models\Tenant;
use Symfony\Component\HttpFoundation\Response;

class SetCurrentTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        $tenantId = $request->header('X-Tenant-ID');

        if (! $tenantId) {
            return $next($request);
        }

        /** @var Tenant|null $tenant */
        $tenant = $user->tenants()
            ->where('tenants.id', $tenantId)
            ->first();

        if (! $tenant) {
            return response()->json([
                'message' => 'Tenant não encontrado ou acesso negado.',
            ], 403);
        }

        Tenant::setCurrentModel($tenant);

        return $next($request);
    }
}
