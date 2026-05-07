<?php

namespace Modules\User\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Tenant\Models\Tenant;
use Modules\User\Events\UserDeleting;
use Modules\User\Http\Requests\DeleteAccountRequest;
use Modules\User\Http\Requests\UpdatePasswordRequest;
use Modules\User\Http\Requests\UpdateProfileRequest;

class SettingsController extends Controller
{
    public function showProfile(Request $request): UserResource
    {
        return UserResource::make($request->user());
    }

    public function updateProfile(UpdateProfileRequest $request): UserResource
    {
        $user = $request->user();
        $user->update($request->validated());

        return UserResource::make($user);
    }

    public function updatePassword(UpdatePasswordRequest $request): JsonResponse
    {
        $user = $request->user();

        $user->update([
            'password' => $request->validated('password'),
        ]);

        $user->tokens()->delete();

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'message' => 'Senha atualizada com sucesso!',
            'token' => $token,
        ]);
    }

    public function destroy(DeleteAccountRequest $request): JsonResponse
    {
        $user = $request->user();

        DB::transaction(function () use ($user): void {
            event(new UserDeleting($user));
            $user->tokens()->delete();
            $user->delete();
        });

        Tenant::forgetCurrent();

        return response()->json(['message' => 'Conta excluída com sucesso!']);
    }
}
