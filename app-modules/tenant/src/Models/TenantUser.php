<?php

namespace Modules\Tenant\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Modules\Tenant\Database\Factories\TenantUserFactory;
use Modules\User\Models\User;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use RuntimeException;

/**
 * @mixin IdeHelperTenantUser
 */
class TenantUser extends Pivot implements AuditableContract
{
    /** @use HasFactory<TenantUserFactory> */
    use Auditable, HasFactory;

    protected $table = 'tenant_user';

    public $incrementing = true;

    protected $fillable = ['user_id', 'tenant_id'];

    protected static function newFactory(): TenantUserFactory
    {
        return TenantUserFactory::new();
    }

    protected static function booted(): void
    {
        static::creating(function (TenantUser $tenantUser): void {
            $tenant = Tenant::withTrashed()->find($tenantUser->tenant_id);

            if (! $tenant || $tenant->trashed() || ! $tenant->isOperable()) {
                throw new RuntimeException('Vínculo só é aceito em tenants operáveis (folhas) ativos.');
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
