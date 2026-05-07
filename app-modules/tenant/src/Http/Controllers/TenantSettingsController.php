<?php

namespace Modules\Tenant\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Resources\TenantResource;
use App\Http\Resources\TenantUserResource;
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
            'tenant' => TenantResource::make($tenant),
            'tenant_users' => TenantUserResource::collection($tenantUsers),
        ]);
    }

    public function update(UpdateTenantSettingsRequest $request, Tenant $tenant): TenantResource
    {
        $tenant->update($request->validated());

        return TenantResource::make($tenant);
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
