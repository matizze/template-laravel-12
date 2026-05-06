<?php

namespace Modules\Tenant\Services;

use Modules\Tenant\Models\Tenant;
use RuntimeException;

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
            throw new RuntimeException("Tenant ID {$tenantId} não encontrado ou excluído.");
        }

        $this->set($tenant);
    }

    public function forget(): void
    {
        $this->tenant = null;
    }
}
