<?php

namespace Modules\Tenant\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Modules\Tenant\Enums\TenantRole;
use Modules\Tenant\Http\Requests\CreateTenantRequest;
use Modules\Tenant\Http\Requests\TransferOwnershipRequest;
use Modules\Tenant\Models\Tenant;
use Modules\Tenant\Models\TenantUser;

class TenantController extends Controller
{
    public function store(CreateTenantRequest $request): RedirectResponse
    {
        $tenant = DB::transaction(function () use ($request): Tenant {
            $tenant = Tenant::create([
                'name' => $request->validated('name'),
                'slug' => $request->validated('slug'),
                'description' => $request->validated('description'),
                'user_id' => $request->user()->id,
            ]);

            TenantUser::create([
                'user_id' => $request->user()->id,
                'tenant_id' => $tenant->id,
                'role' => TenantRole::Owner,
            ]);

            return $tenant;
        });

        Tenant::setCurrentModel($tenant);

        return redirect()->route('dashboard')
            ->with('success', 'Tenant criado com sucesso!');
    }

    public function transferOwnership(TransferOwnershipRequest $request, Tenant $tenant): RedirectResponse
    {
        $newOwnerId = $request->validated('user_id');

        DB::transaction(function () use ($tenant, $request, $newOwnerId): void {
            $tenant->users()->updateExistingPivot($request->user()->id, [
                'role' => TenantRole::Admin,
            ]);

            $tenant->users()->updateExistingPivot($newOwnerId, [
                'role' => TenantRole::Owner,
            ]);

            $tenant->update(['user_id' => $newOwnerId]);
        });

        return redirect()
            ->route('dashboard')
            ->with('success', 'Propriedade do tenant transferida com sucesso!');
    }

    public function switch(Tenant $tenant): RedirectResponse
    {
        $this->authorize('view', $tenant);

        Tenant::setCurrentModel($tenant);

        return redirect()->back()
            ->with('success', 'Tenant alterado com sucesso!');
    }
}
