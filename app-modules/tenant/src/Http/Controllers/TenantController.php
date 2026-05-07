<?php

namespace Modules\Tenant\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Tenant\Http\Requests\CreateTenantRequest;
use Modules\Tenant\Models\Tenant;

class TenantController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = $user->can('tenants.view')
            ? Tenant::query()->withCount(['children', 'users'])
            : $user->tenants()->withCount(['children', 'users']);

        $parentId = $request->query('parent_id');

        if ($parentId !== null) {
            $query->where('parent_id', $parentId);
        } else {
            $query->whereNull('parent_id');
        }

        return response()->json($query->get());
    }

    public function store(CreateTenantRequest $request): JsonResponse
    {
        $tenant = Tenant::create([
            'name' => $request->validated('name'),
            'slug' => $request->validated('slug'),
            'description' => $request->validated('description'),
            'parent_id' => $request->validated('parent_id'),
        ]);

        return response()->json($tenant, 201);
    }
}
