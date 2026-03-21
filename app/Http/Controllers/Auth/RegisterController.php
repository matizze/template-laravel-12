<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\MakeRegisterRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class RegisterController extends Controller
{
    public function index(): View
    {
        return view('auth.register');
    }

    public function store(MakeRegisterRequest $request): RedirectResponse
    {
        $user = User::create($request->validated());

        Auth::login($user);

        if ($token = session()->pull('invitation_token')) {
            return redirect()->route('invitation.accept', $token);
        }

        return redirect()->route('onboarding');
    }
}
