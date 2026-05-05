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
            $tenant = new Tenant;
            $tenant->fill([
                'name' => $request->validated('name'),
                'slug' => $request->validated('slug'),
                'description' => $request->validated('description'),
            ]);
            $tenant->user_id = $request->user()->id;
            $tenant->parent_id = $request->validated('parent_id');
            $tenant->save();

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

            $tenant->user_id = $newOwnerId;
            $tenant->save();
        });

        return redirect()
            ->route('dashboard')
            ->with('success', 'Propriedade do tenant transferida com sucesso!');
    }

    public function switch(Tenant $tenant): RedirectResponse
    {
        $this->authorize('view', $tenant);

        if (! $tenant->isOperable()) {
            return redirect()->back()
                ->with('error', 'Apenas tenants operáveis (folhas) podem ser ativados.');
        }

        Tenant::setCurrentModel($tenant);

        return redirect()->back()
            ->with('success', 'Tenant alterado com sucesso!');
    }
}
