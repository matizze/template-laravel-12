<?php

namespace Modules\Tenant\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Tenant\Http\Requests\UpdateTenantSettingsRequest;
use Modules\Tenant\Models\Tenant;

class TenantSettingsController extends Controller
{
    public function show(Request $request, Tenant $tenant): View
    {
        $this->authorize('update', $tenant);

        $tab = $request->query('tab', 'geral');

        $tenantUsers = $tenant->tenantUsers()->with('user')->get();

        return view('tenant::dashboard.tenant-settings.index', [
            'tenant' => $tenant,
            'active' => $tab,
            'tenantUsers' => $tenantUsers,
        ]);
    }

    public function update(UpdateTenantSettingsRequest $request, Tenant $tenant): RedirectResponse
    {
        $tenant->update($request->validated());

        return redirect()->route('tenant.settings.show', $tenant)
            ->with('success', 'Tenant atualizado com sucesso!');
    }

    public function destroy(Tenant $tenant): RedirectResponse
    {
        $this->authorize('delete', $tenant);

        Tenant::forgetCurrent();

        $tenant->delete();

        return redirect()->route('dashboard')
            ->with('success', 'Tenant excluído com sucesso!');
    }
}
