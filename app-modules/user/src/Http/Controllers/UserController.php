<?php

namespace Modules\User\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Permission\Models\Role;
use Modules\User\Http\Requests\StoreUserRequest;
use Modules\User\Http\Requests\UpdateUserRoleRequest;
use Modules\User\Models\User;

class UserController extends Controller
{
    public function store(StoreUserRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
        ]);

        if (isset($validated['role_name'])) {
            $role = Role::where('name', $validated['role_name'])
                ->whereNull('tenant_id')
                ->firstOrFail();
            $role->assign($user);
        }

        return response()->json($user->load('roles'), 201);
    }

    public function update(UpdateUserRoleRequest $request, User $user): JsonResponse
    {
        $this->authorize('update', $user);

        $validated = $request->validated();

        $user->roles()->whereNull('tenant_id')->detach();

        if (isset($validated['role_name'])) {
            $role = Role::where('name', $validated['role_name'])
                ->whereNull('tenant_id')
                ->firstOrFail();
            $role->assign($user);
        }

        return response()->json($user->load('roles'));
    }

    public function destroy(User $user): JsonResponse
    {
        $this->authorize('delete', $user);

        $user->delete();

        return response()->json(['message' => 'Usuário deletado com sucesso!']);
    }
}
