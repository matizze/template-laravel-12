<?php

namespace Modules\Tenant\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Modules\Tenant\Database\Factories\TenantFactory;
use Modules\Tenant\Enums\TenantRole;
use Modules\Tenant\Services\CurrentTenantManager;
use Modules\User\Models\User;

class Tenant extends Model
{
    /** @use HasFactory<TenantFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = ['name', 'slug', 'description', 'logo_path', 'user_id', 'parent_id'];

    protected static function newFactory(): TenantFactory
    {
        return TenantFactory::new();
    }

    public static function current(): ?self
    {
        return app(CurrentTenantManager::class)->get();
    }

    public static function setCurrent(?int $tenantId): void
    {
        $manager = app(CurrentTenantManager::class);

        if ($tenantId) {
            $manager->setById($tenantId);
        } else {
            $manager->forget();
        }
    }

    public static function setCurrentModel(self $tenant): void
    {
        app(CurrentTenantManager::class)->set($tenant);
    }

    public static function forgetCurrent(): void
    {
        app(CurrentTenantManager::class)->forget();
    }

    protected static function booted(): void
    {
        static::creating(function (Tenant $tenant): void {
            if (! $tenant->slug) {
                $tenant->slug = Str::slug($tenant->name);
            }
        });
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'tenant_user')
            ->using(TenantUser::class)
            ->withPivot('id', 'role')
            ->withCasts(['role' => TenantRole::class])
            ->withTimestamps();
    }

    public function tenantUsers(): HasMany
    {
        return $this->hasMany(TenantUser::class);
    }
}
