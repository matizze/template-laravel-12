<?php

namespace Modules\Tenant\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Modules\Tenant\Database\Factories\TenantUserFactory;
use Modules\Tenant\Enums\TenantRole;
use Modules\User\Models\User;

class TenantUser extends Pivot
{
    /** @use HasFactory<TenantUserFactory> */
    use HasFactory;

    protected $table = 'tenant_user';

    public $incrementing = true;

    protected $fillable = ['user_id', 'tenant_id', 'role'];

    protected static function newFactory(): TenantUserFactory
    {
        return TenantUserFactory::new();
    }

    protected static function booted(): void
    {
        static::creating(function (TenantUser $tenantUser): void {
            $tenant = Tenant::find($tenantUser->tenant_id);

            if (! $tenant || ! $tenant->isOperable()) {
                throw new \RuntimeException('Vínculo só é aceito em tenants operáveis.');
            }
        });
    }

    protected function casts(): array
    {
        return [
            'role' => TenantRole::class,
        ];
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
