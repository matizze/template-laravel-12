<?php

namespace Modules\Tenant\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Modules\Tenant\Http\Requests\CreateUserRequest;
use Modules\User\Models\User;

class UserCreationController extends Controller
{
    public function create(): View
    {
        return view('tenant::users.create');
    }

    public function store(CreateUserRequest $request): RedirectResponse
    {
        $user = User::create([
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'password' => Str::random(40),
        ]);

        Password::broker()->sendResetLink(['email' => $user->email]);

        return redirect()->back()->with(
            'success',
            "Usuário {$user->email} criado. E-mail de definição de senha enviado."
        );
    }
}
