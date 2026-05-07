<?php

namespace Modules\Permission\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Modules\Permission\Database\Factories\RoleFactory;
use Modules\Tenant\Models\Tenant;
use Modules\User\Models\User;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

/**
 * @mixin IdeHelperRole
 */
class Role extends Model implements AuditableContract
{
    /** @use HasFactory<RoleFactory> */
    use Auditable, HasFactory;

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

    /**
     * Limit query to roles for the given tenant. When `$tenantId` is null,
     * only global roles (with `tenant_id IS NULL`) are returned.
     *
     * @param  Builder<Role>  $query
     */
    public function scopeForTenant(Builder $query, ?int $tenantId): void
    {
        if ($tenantId !== null) {
            $query->where(fn ($q) => $q->whereNull('tenant_id')->orWhere('tenant_id', $tenantId));
        } else {
            $query->whereNull('tenant_id');
        }
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
