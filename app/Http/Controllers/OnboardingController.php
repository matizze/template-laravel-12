<?php

namespace App\Http\Controllers;

use App\Enums\WorkspaceRole;
use App\Models\Member;
use App\Models\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class OnboardingController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        if ($request->user()->workspaces()->exists()) {
            return redirect()->route('dashboard');
        }

        return view('onboarding', [
            'defaultName' => $request->user()->name."'s Workspace",
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $workspace = DB::transaction(function () use ($request, $validated): Workspace {
            $workspace = Workspace::create([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'user_id' => $request->user()->id,
            ]);

            Member::create([
                'user_id' => $request->user()->id,
                'workspace_id' => $workspace->id,
                'role' => WorkspaceRole::Owner,
            ]);

            return $workspace;
        });

        Workspace::setCurrentModel($workspace);
        session(['current_workspace_id' => $workspace->id]);

        return redirect()->route('dashboard')->with('success', 'Workspace criado com sucesso!');
    }
}
