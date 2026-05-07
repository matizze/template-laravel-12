<?php

namespace Modules\Tenant\Services;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Modules\Tenant\Models\Tenant;

class CurrentTenantManager
{
    private ?Tenant $tenant = null;

    public function get(): ?Tenant
    {
        return $this->tenant;
    }

    public function set(Tenant $tenant): void
    {
        $this->tenant = $tenant;
    }

    public function setById(int $tenantId): void
    {
        $tenant = Tenant::find($tenantId);

        if (! $tenant) {
            throw (new ModelNotFoundException)->setModel(Tenant::class, [$tenantId]);
        }

        $this->set($tenant);
    }

    public function forget(): void
    {
        $this->tenant = null;
    }
}
