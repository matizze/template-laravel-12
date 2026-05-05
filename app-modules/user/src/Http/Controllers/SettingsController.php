<?php

namespace Modules\User\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use Modules\Tenant\Enums\TenantRole;
use Modules\Tenant\Models\Tenant;
use Modules\Tenant\Models\TenantUser;
use Modules\User\Http\Requests\DeleteAccountRequest;
use Modules\User\Http\Requests\UpdatePasswordRequest;
use Modules\User\Http\Requests\UpdateProfileRequest;
use Modules\User\Models\User;

class SettingsController extends Controller
{
    public function index(Request $request): View
    {
        $tab = $request->query('tab', 'profile');

        $data = [
            'user' => Auth::user(),
            'active' => $tab,
        ];

        if ($tab === 'users') {
            Gate::authorize('manage-users');
            $data['users'] = User::all();
        }

        return view('user::settings.index', $data);
    }

    public function updateProfile(UpdateProfileRequest $request): RedirectResponse
    {
        Auth::user()->update($request->validated());

        return redirect()->route('settings.index', ['tab' => 'profile'])
            ->with('success', 'Perfil atualizado com sucesso!');
    }

    public function updatePassword(UpdatePasswordRequest $request): RedirectResponse
    {
        Auth::user()->update([
            'password' => Hash::make($request->validated('password')),
        ]);

        return redirect()->route('settings.index', ['tab' => 'password'])
            ->with('success', 'Senha atualizada com sucesso!');
    }

    public function destroy(DeleteAccountRequest $request): RedirectResponse
    {
        $user = Auth::user();

        $ownsTenants = TenantUser::where('user_id', $user->id)
            ->where('role', TenantRole::Owner)
            ->exists();

        if ($ownsTenants) {
            return redirect()
                ->route('settings.index', ['tab' => 'profile'])
                ->with('error', 'Você precisa transferir a propriedade dos seus tenants antes de excluir sua conta.');
        }

        TenantUser::where('user_id', $user->id)->delete();

        Tenant::forgetCurrent();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
