<?php

namespace Modules\Tenant\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Tenant\Http\Requests\DeleteTenantRequest;
use Modules\Tenant\Http\Requests\RestoreTenantRequest;
use Modules\Tenant\Http\Requests\UpdateTenantSettingsRequest;
use Modules\Tenant\Models\Tenant;

class TenantSettingsController extends Controller
{
    public function show(Tenant $tenant): JsonResponse
    {
        $this->authorize('tenants.settings.view', $tenant);

        $tenantUsers = $tenant->tenantUsers()->with('user')->get();

        return response()->json([
            'tenant' => $tenant,
            'tenant_users' => $tenantUsers,
        ]);
    }

    public function update(UpdateTenantSettingsRequest $request, Tenant $tenant): JsonResponse
    {
        $tenant->update($request->validated());

        return response()->json($tenant);
    }

    public function destroy(DeleteTenantRequest $request, Tenant $tenant): JsonResponse
    {
        Tenant::forgetCurrent();

        $tenant->delete();

        return response()->json([
            'message' => 'Tenant excluído com sucesso.',
        ]);
    }

    public function restore(RestoreTenantRequest $request, int $tenantId): JsonResponse
    {
        $tenant = Tenant::onlyTrashed()->findOrFail($tenantId);

        $this->authorize('restore', $tenant);

        $tenant->restore();

        return response()->json([
            'message' => 'Tenant restaurado com sucesso.',
        ]);
    }
}
