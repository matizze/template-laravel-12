<?php

namespace Modules\User\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Modules\User\Http\Requests\StoreUserRequest;
use Modules\User\Http\Requests\UpdateUserRoleRequest;
use Modules\User\Models\User;

class UserController extends Controller
{
    public function store(StoreUserRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
        ]);

        return to_route('settings.index', ['tab' => 'users'])
            ->with('success', 'Usuário criado com sucesso!');
    }

    public function update(UpdateUserRoleRequest $request, User $user): RedirectResponse
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Você não pode alterar sua própria função.');
        }

        $user->update($request->validated());

        return back()->with('success', 'Função atualizada com sucesso!');
    }

    public function destroy(User $user): RedirectResponse
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Você não pode deletar sua própria conta por aqui.');
        }

        $user->delete();

        return back()->with('success', 'Usuário deletado com sucesso!');
    }
}
