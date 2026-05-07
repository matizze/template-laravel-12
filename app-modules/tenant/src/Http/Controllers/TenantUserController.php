<?php

namespace Modules\Tenant\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Resources\TenantUserResource;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Tenant\Http\Requests\AttachTenantUserRequest;
use Modules\Tenant\Http\Requests\DetachTenantUserRequest;
use Modules\Tenant\Models\Tenant;
use Modules\User\Models\User;

class TenantUserController extends Controller
{
    public function index(Request $request, Tenant $tenant): JsonResponse
    {
        $this->authorize('tenants.users.view', $tenant);

        $tenantUsers = $tenant->tenantUsers()
            ->with('user')
            ->get();

        $linkedIds = $tenantUsers->pluck('user_id');

        $user = $request->user();
        $availableQuery = User::query()->whereNotIn('id', $linkedIds);

        if (! $user->can('tenants.view')) {
            $availableQuery->whereHas('tenants', fn ($q) => $q->whereHas('users', fn ($u) => $u->whereKey($user->id)));
        }

        $perPage = max(1, min(100, $request->integer('per_page', 15)));

        $availableUsers = UserResource::collection(
            $availableQuery->orderBy('name')->paginate($perPage)
        )->response()->getData(true);

        return response()->json([
            'tenant_users' => TenantUserResource::collection($tenantUsers),
            'available_users' => $availableUsers,
        ]);
    }

    public function store(AttachTenantUserRequest $request, Tenant $tenant): JsonResponse
    {
        $tenant->tenantUsers()->create([
            'user_id' => $request->validated('user_id'),
        ]);

        return response()->json([
            'message' => 'Usuário vinculado com sucesso.',
        ], 201);
    }

    public function remove(DetachTenantUserRequest $request, Tenant $tenant, User $user): JsonResponse
    {
        $tenant->users()->detach($user->id);

        return response()->json([
            'message' => 'Vínculo removido.',
        ]);
    }

    public function leave(Request $request, Tenant $tenant): JsonResponse
    {
        $user = $request->user();

        $tenant->tenantUsers()->where('user_id', $user->id)->firstOrFail();

        $tenant->users()->detach($user->id);

        Tenant::forgetCurrent();

        return response()->json([
            'message' => 'Você saiu do tenant com sucesso.',
        ]);
    }
}
