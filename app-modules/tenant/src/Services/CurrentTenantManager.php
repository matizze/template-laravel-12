<?php

namespace Modules\Tenant\Services;

use Modules\Tenant\Models\Tenant;
use RuntimeException;

/**
 * Manages the current tenant for the request lifecycle.
 *
 * Registered as scoped in the container for automatic reset between Octane requests.
 */
class CurrentTenantManager
{
    private ?Tenant $tenant = null;

    public function get(): ?Tenant
    {
        return $this->tenant ??= $this->resolveFromSession();
    }

    public function set(Tenant $tenant): void
    {
        if ($tenant->trashed() || ! $tenant->isOperable()) {
            throw new RuntimeException('Apenas tenants operáveis (folhas) ativos podem ser ativados na sessão.');
        }

        $this->tenant = $tenant;
        session(['current_tenant_id' => $tenant->id]);
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
        session()->forget('current_tenant_id');
    }

    private function resolveFromSession(): ?Tenant
    {
        $tenantId = session('current_tenant_id');

        return $tenantId ? Tenant::find($tenantId) : null;
    }
}
