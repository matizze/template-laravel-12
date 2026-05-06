<?php

namespace Modules\Tenant\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Tenant\Database\Factories\TenantFactory;
use Modules\Tenant\Services\CurrentTenantManager;
use Modules\User\Models\User;

class Tenant extends Model
{
    /** @use HasFactory<TenantFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = ['name', 'slug', 'description', 'logo_path'];

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

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'tenant_user')
            ->using(TenantUser::class)
            ->withPivot('id')
            ->withTimestamps();
    }

    public function tenantUsers(): HasMany
    {
        return $this->hasMany(TenantUser::class);
    }

    public function isOperable(): bool
    {
        return ! $this->children()->exists();
    }

    /**
     * Returns all descendants (children, grandchildren, ...) using a recursive CTE.
     *
     * Compatible with SQLite and PostgreSQL. Filters soft-deleted rows.
     *
     * @return Collection<int, Tenant>
     */
    public function descendants(): Collection
    {
        $sql = <<<'SQL'
            WITH RECURSIVE tree AS (
                SELECT * FROM tenants WHERE id = ? AND deleted_at IS NULL
                UNION ALL
                SELECT t.* FROM tenants t
                INNER JOIN tree ON t.parent_id = tree.id
                WHERE t.deleted_at IS NULL
            )
            SELECT * FROM tree WHERE id != ?
            SQL;

        return self::hydrate(DB::select($sql, [$this->id, $this->id]));
    }

    /**
     * Returns all ancestors from root → immediate parent.
     *
     * @return Collection<int, Tenant>
     */
    public function ancestors(): Collection
    {
        $ancestors = collect();
        $current = $this->parent;

        while ($current !== null) {
            $ancestors->prepend($current);
            $current = $current->parent;
        }

        /** @var Collection<int, Tenant> $result */
        $result = new Collection($ancestors->all());

        return $result;
    }

    /**
     * Returns the full hierarchical name path: "Root › Child › Grandchild".
     */
    public function path(string $separator = ' › '): string
    {
        return $this->ancestors()->push($this)->pluck('name')->join($separator);
    }

    /**
     * Limit query to operable tenants (leaves — those without children).
     *
     * @param  Builder<Tenant>  $query
     */
    public function scopeOperable(Builder $query): void
    {
        $query->whereDoesntHave('children');
    }
}
