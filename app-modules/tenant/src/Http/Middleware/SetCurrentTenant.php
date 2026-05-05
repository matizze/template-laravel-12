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

        $tenantId = session('current_tenant_id');

        if (! $tenantId) {
            /** @var Tenant|null $tenant */
            $tenant = $user->tenants()
                ->whereDoesntHave('children')
                ->orderBy('tenants.id')
                ->first();

            if ($tenant) {
                Tenant::setCurrentModel($tenant);

                return $next($request);
            }

            return redirect()->route('onboarding');
        }

        /** @var Tenant|null $tenant */
        $tenant = $user->tenants()
            ->whereDoesntHave('children')
            ->where('tenants.id', $tenantId)
            ->first();

        if (! $tenant) {
            session()->forget('current_tenant_id');

            return redirect()->route('onboarding')
                ->with('warning', 'Tenant indisponível. Selecione outro.');
        }

        Tenant::setCurrentModel($tenant);

        return $next($request);
    }
}
