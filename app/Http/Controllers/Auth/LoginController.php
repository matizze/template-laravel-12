<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\MakeLoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function index(): View
    {
        return view('auth.login');
    }

    public function store(MakeLoginRequest $request): RedirectResponse
    {
        $credentials = $request->safe()->only(['email', 'password']);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            $redirect = $request->input('redirect');
            if ($redirect && $this->isValidRedirect($redirect)) {
                return redirect($redirect);
            }

            if ($token = $request->session()->get('invitation_token')) {
                return redirect()->route('invitation.accept', $token);
            }

            return redirect('dashboard');
        }

        return back()->with('error', 'E-mail ou senha incorretos!');
    }

    private function isValidRedirect(string $redirect): bool
    {
        return str_starts_with($redirect, '/') && ! str_starts_with($redirect, '//');
    }

    public function destroy(Request $request): RedirectResponse
    {
        if (Auth::check()) {
            Auth::logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login');
        }

        return back();
    }
}
