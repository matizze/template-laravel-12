<?php

declare(strict_types=1);

namespace Modules\User\Http\Controllers;

use App\Enums\RoleName;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Modules\Permission\Services\RoleAssigner;
use Modules\User\Http\Requests\StoreUserRequest;
use Modules\User\Http\Requests\UpdateUserRoleRequest;
use Modules\User\Models\User;

class UserController extends Controller
{
    public function __construct(private readonly RoleAssigner $roles) {}

    public function store(StoreUserRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
        ]);

        if (isset($validated['role_name'])) {
            $this->roles->assign($user, RoleName::from($validated['role_name']));
        }

        return UserResource::make($user->load('roles'))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateUserRoleRequest $request, User $user): JsonResponse
    {
        $this->authorize('update', $user);

        $validated = $request->validated();

        if (isset($validated['role_name'])) {
            $this->roles->replaceGlobal($user, RoleName::from($validated['role_name']));
        }

        return response()->json(UserResource::make($user->load('roles')));
    }

    public function destroy(User $user): JsonResponse
    {
        $this->authorize('delete', $user);

        $user->delete();

        return response()->json(['message' => 'Usuário deletado com sucesso!']);
    }
}
