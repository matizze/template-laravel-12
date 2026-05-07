<?php

namespace Modules\Tenant\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Resources\TenantResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Tenant\Http\Requests\CreateTenantRequest;
use Modules\Tenant\Models\Tenant;

class TenantController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();

        $query = Tenant::query()
            ->visibleTo($user)
            ->withCount(['children', 'users']);

        $parentId = $request->query('parent_id');

        if ($parentId !== null) {
            $query->where('parent_id', $parentId);
        } else {
            $query->whereNull('parent_id');
        }

        return TenantResource::collection($query->paginate($request->perPage()));
    }

    public function store(CreateTenantRequest $request): JsonResponse
    {
        $tenant = Tenant::create([
            'name' => $request->validated('name'),
            'slug' => $request->validated('slug'),
            'description' => $request->validated('description'),
            'parent_id' => $request->validated('parent_id'),
        ]);

        return TenantResource::make($tenant)->response()->setStatusCode(201);
    }
}
