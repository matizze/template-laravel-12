<?php

namespace Modules\Tenant\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Tenant\Enums\TenantRole;
use Modules\Tenant\Http\Requests\AttachTenantUserRequest;
use Modules\Tenant\Http\Requests\DetachTenantUserRequest;
use Modules\Tenant\Http\Requests\UpdateTenantUserRoleRequest;
use Modules\Tenant\Models\Tenant;
use Modules\Tenant\Models\TenantUser;
use Modules\User\Models\User;

class TenantUserController extends Controller
{
    public function index(Tenant $tenant): View
    {
        $this->authorize('manageTenantUsers', $tenant);

        $tenantUsers = $tenant->tenantUsers()
            ->with('user')
            ->get();

        $linkedIds = $tenantUsers->pluck('user_id');

        $availableUsers = User::query()
            ->whereNotIn('id', $linkedIds)
            ->orderBy('name')
            ->get();

        return view('tenant::dashboard.users.index', [
            'tenant' => $tenant,
            'tenantUsers' => $tenantUsers,
            'availableUsers' => $availableUsers,
        ]);
    }

    public function store(AttachTenantUserRequest $request, Tenant $tenant): RedirectResponse
    {
        $tenant->tenantUsers()->create([
            'user_id' => $request->validated('user_id'),
            'role' => TenantRole::from($request->validated('role')),
        ]);

        return redirect()
            ->route('tenant.users.index', $tenant)
            ->with('success', 'Usuário vinculado com sucesso.');
    }

    public function updateRole(UpdateTenantUserRoleRequest $request, Tenant $tenant, User $user): RedirectResponse
    {
        $tenant->tenantUsers()->where('user_id', $user->id)->firstOrFail();

        $newRole = TenantRole::from($request->validated('role'));

        if ($newRole === TenantRole::Owner) {
            return redirect()
                ->route('tenant.users.index', $tenant)
                ->with('error', 'Não é possível promover um usuário a proprietário. Use a transferência de propriedade.');
        }

        $tenant->users()->updateExistingPivot($user->id, [
            'role' => $newRole,
        ]);

        return redirect()
            ->route('tenant.users.index', $tenant)
            ->with('success', 'Função do usuário atualizada com sucesso!');
    }

    public function remove(DetachTenantUserRequest $request, Tenant $tenant, User $user): RedirectResponse
    {
        $tenant->users()->detach($user->id);

        return redirect()
            ->route('tenant.users.index', $tenant)
            ->with('success', 'Vínculo removido.');
    }

    public function leave(Request $request, Tenant $tenant): RedirectResponse
    {
        $user = $request->user();

        /** @var TenantUser $tenantUser */
        $tenantUser = $tenant->tenantUsers()->where('user_id', $user->id)->firstOrFail();

        if ($tenantUser->role === TenantRole::Owner) {
            return redirect()
                ->back()
                ->with('error', 'O proprietário não pode sair do tenant. Transfira a propriedade antes de sair.');
        }

        $tenant->users()->detach($user->id);

        Tenant::forgetCurrent();

        return redirect()
            ->route('dashboard')
            ->with('success', 'Você saiu do tenant com sucesso.');
    }
}
