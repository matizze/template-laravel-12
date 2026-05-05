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
        if ($request->user()) {
            $tenantId = session('current_tenant_id');

            /** @var Tenant|null $tenant */
            $tenant = $tenantId
                ? $request->user()->tenants()->where('tenants.id', $tenantId)->first()
                : $request->user()->tenants()->first();

            if ($tenant) {
                Tenant::setCurrentModel($tenant);
            } else {
                session()->forget('current_tenant_id');

                return redirect()->route('onboarding');
            }
        }

        return $next($request);
    }
}
