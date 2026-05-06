<?php

namespace Modules\Tenant\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Modules\Tenant\Http\Requests\CreateUserRequest;
use Modules\User\Models\User;

class UserCreationController extends Controller
{
    public function store(CreateUserRequest $request): JsonResponse
    {
        $user = User::create([
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'password' => Str::random(40),
        ]);

        Password::broker()->sendResetLink(['email' => $user->email]);

        return response()->json($user, 201);
    }
}
