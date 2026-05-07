<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Tenant\Models\Tenant;

/**
 * @mixin Tenant
 */
class TenantResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'parent_id' => $this->parent_id,
            'children_count' => $this->when(isset($this->children_count), $this->children_count),
            'users_count' => $this->when(isset($this->users_count), $this->users_count),
            'created_at' => $this->created_at,
            'deleted_at' => $this->deleted_at,
        ];
    }
}
