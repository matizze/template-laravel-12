# Contract: Tenant Switch

Rotas para mudar o tenant ativo na sessão.

---

## `POST /tenant/switch/{tenant}`

Define o tenant ativo na sessão do user.

**Validation** (in `SwitchTenantRequest::authorize`):
1. `$tenant` MUST existir e não estar soft-deleted.
2. `$tenant->isOperable()` MUST ser `true` (FR-003) → 403 `"Apenas tenants operáveis podem ser selecionados."`
3. `auth()->user()->tenants()->whereKey($tenant->id)->exists()` MUST ser `true` → 403 `"Você não tem acesso a este tenant."`

**Success (302)**: `session()->put('tenant_id', $tenant->id)`, redirect `/dashboard`.

**Failures**:
- 403 com flash `error` específico em cada caso.

---

## Switcher view

Componente `<x-tenant::tenant-switcher />` lista exclusivamente tenants:
- ATIVOS (não soft-deleted)
- OPERÁVEIS (folhas — `whereDoesntHave('children')`)
- VINCULADOS ao user atual (`->whereHas('users', fn($q) => $q->where('users.id', auth()->id()))`)

**Desambiguação (FR-009b)**: cada item exibe `$tenant->path()` quando dois tenants vinculados ao mesmo user têm o mesmo `name`.

```blade
<select>
  @foreach ($tenants as $tenant)
    <option value="{{ $tenant->id }}">
      {{ $needsPath ? $tenant->path() : $tenant->name }}
    </option>
  @endforeach
</select>
```

---

## Middleware `tenant`

Aplicado em todas as rotas que dependem de tenant ativo. Validação descrita em [research.md R2](../research.md#r2-invalidação-de-sessão-em-mudanças-de-acesso).

| Estado da sessão | Comportamento |
|------------------|---------------|
| Sem `tenant_id` | redirect `/onboarding` (ou tela "no-access") |
| `tenant_id` aponta para tenant inexistente / soft-deleted / agrupador / sem vínculo | `session()->forget('tenant_id')` + redirect `/onboarding` com flash `warning` |
| Estado válido | `next($request)` |
