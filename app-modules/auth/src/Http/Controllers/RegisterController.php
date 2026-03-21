<?php

namespace Modules\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Auth\Http\Requests\MakeRegisterRequest;
use Modules\User\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class RegisterController extends Controller
{
    public function index(): View
    {
        return view('auth::auth.register');
    }

    public function store(MakeRegisterRequest $request): RedirectResponse
    {
        $user = User::create($request->validated());

        Auth::login($user);

        $redirect = $request->input('redirect');
        if ($redirect && $this->isValidRedirect($redirect)) {
            return redirect($redirect);
        }

        if ($token = $request->session()->get('invitation_token')) {
            return redirect()->route('invitation.accept', $token);
        }

        return redirect()->route('onboarding');
    }

    private function isValidRedirect(string $redirect): bool
    {
        return str_starts_with($redirect, '/') && ! str_starts_with($redirect, '//');
    }
}
