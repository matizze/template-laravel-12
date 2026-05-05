<?php

namespace Modules\Tenant\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Modules\Tenant\Enums\TenantRole;
use Modules\Tenant\Models\Tenant;
use Modules\Tenant\Models\TenantUser;

class OnboardingController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        if ($request->user()->tenants()->exists()) {
            return redirect()->route('dashboard');
        }

        $canCreate = ! Tenant::query()->exists();

        return view('tenant::onboarding', [
            'canCreate' => $canCreate,
            'defaultName' => $request->user()->name."'s Tenant",
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $tenant = DB::transaction(function () use ($request, $validated): Tenant {
            $tenant = new Tenant;
            $tenant->fill([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
            ]);
            $tenant->user_id = $request->user()->id;
            $tenant->save();

            TenantUser::create([
                'user_id' => $request->user()->id,
                'tenant_id' => $tenant->id,
                'role' => TenantRole::Owner,
            ]);

            return $tenant;
        });

        Tenant::setCurrentModel($tenant);
        session(['current_tenant_id' => $tenant->id]);

        return redirect()->route('dashboard')->with('success', 'Tenant criado com sucesso!');
    }
}
