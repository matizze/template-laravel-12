<?php

namespace Modules\Permission\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Modules\Permission\Database\Factories\RoleFactory;
use Modules\Tenant\Models\Tenant;
use Modules\User\Models\User;

class Role extends Model
{
    /** @use HasFactory<RoleFactory> */
    use HasFactory;

    protected $fillable = ['name', 'tenant_id', 'permissions'];

    protected static function newFactory(): RoleFactory
    {
        return RoleFactory::new();
    }

    protected function casts(): array
    {
        return [
            'permissions' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query) {
            if (app()->runningInConsole()) {
                return;
            }

            $tenantId = Tenant::current()?->id;

            if ($tenantId) {
                $query->where('tenant_id', $tenantId);
            }
        });
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_roles')
            ->withTimestamps();
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function assign(User $user): void
    {
        $this->users()->syncWithoutDetaching([$user->id]);
    }

    public function revoke(User $user): void
    {
        $this->users()->detach($user->id);
    }

    public function isGlobal(): bool
    {
        return $this->tenant_id === null;
    }
}
