# Phase 0 — Research: RBAC Permissions System

**Feature**: 005-rbac-permissions
**Date**: 2026-05-05 (revisado após simplificação radical na Clarifications #3)

## R1 — `Gate::before` como engine única, sem helper global

**Decision**: Usar exclusivamente `Gate::before` do Laravel. **Não criar** função global `can()` em arquivo de helpers.

**Rationale**:
- Constituição (Princípio II — The Laravel Way): "MUST follow idiomatic Laravel conventions".
- Laravel já provê 5 call sites idiomáticos que passam por `Gate::before` automaticamente:
  - `@can('m.r.a')` (Blade — 22 chars, igual a `can('m.r.a')` de hipotético helper)
  - `$user->can('m.r.a')` (User method)
  - `Gate::allows('m.r.a')` (facade)
  - `$this->authorize('m.r.a')` (controller — gera 403 automático com mensagem do framework)
  - `->middleware('can:m.r.a')` (rota)
- Adicionar uma função global `can()` agrega entrada `autoload.files` no `composer.json`, vira quirk do projeto que precisa ser explicado em onboarding, e ganha apenas 9 caracteres em poucos call sites comparado a `Gate::allows()`.
- Princípio IV (Simplicity): "MUST NOT create helpers... unless feature requires" — Laravel já cobre a necessidade.

**Alternatives considered**:
- *Helper global `can()`* (proposta original do user em uma das clarificações; revertida na #3): rejeitada por simplicidade.
- *Custom Gate facade*: overkill. `Gate::before` resolve.
- *Policies por model*: policies servem para regras condicionais sobre instâncias (`update Order #5`); aqui o controle é por permissão nominal globalmente, não por instância.

## R2 — Discriminador para `Gate::before`

**Decision**: O `Gate::before` ativa a resolução RBAC apenas quando `substr_count($ability, '.') === 2`. Para outros formatos, retorna `null` (fall-through para policies/gates registrados normalmente).

**Rationale**:
- Permissões seguem `{module}.{resource}.{action}` — exatamente 2 pontos por construção (FR-002).
- Policies tradicionais usam abilities sem ponto: `view`, `update`, `delete`. Não conflitam.
- Retornar `null` em vez de `false` é importante: `false` em `Gate::before` aborta toda a cadeia.

## R3 — Estratégia de cache

**Decision**:
- Driver: padrão da aplicação (`config('cache.default')`).
- Chave: `permissions:user:{userId}:tenant:{tenantId}`.
- Quando `Tenant::current()` é null: resolver retorna `false` ANTES do cache; nada é gravado.
- Valor armazenado: array PHP com a estrutura aninhada `[module => [resource => [actions]]]` — exatamente o JSON `permissions` da role do user no tenant ativo.
- TTL: **60 segundos**.

**Rationale**:
- TTL curto limita inconsistências em casos onde o listener falhe (defesa em profundidade).
- Estrutura armazenada é o próprio JSON da role — lookup `O(1)` por hash (`$cached[$module][$resource]`).
- Driver default funciona em todos os ambientes (database em prod, array em testes).
- Octane-safe: cache do Laravel é externo, não estado de processo.

**Alternatives considered**:
- *Cache de array flat (`['m.r.a', ...]`)*: O lookup ficaria `O(n)` via `in_array`. Para roles com dezenas de permissões, ainda é trivial — mas estrutura aninhada combina diretamente com o que está no banco (JSON column), evita conversão.
- *Cache tags*: requer Redis/memcached. Adiciona dependência. Manter simples.
- *Sem TTL (só invalidação reativa)*: se um listener falhar silenciosamente, cache fica permanentemente stale. TTL é guard-rail.

## R4 — Invalidação reativa

**Decision**: Listener único `InvalidatePermissionCache` registrado em `PermissionsServiceProvider::boot()`. Reage a:

- `Role::saved` (criação ou atualização do JSON `permissions`) → flush cache de todos os users vinculados a essa role no tenant da role.
- `Role::deleting` → flush cache de todos os users vinculados (antes do delete cascatear ou bloquear).
- `TenantUser::saved` (mudou `role_id` ou criou novo vínculo) → flush cache do `(user_id, tenant_id)` específico.
- `TenantUser::deleting` (vínculo revogado pelo módulo Tenant) → flush cache do `(user_id, tenant_id)` específico.

**Rationale**:
- Listener único concentra a lógica; controllers/serviços ficam naive.
- Diferente do desenho anterior (com pivot `role_user` e events `pivotAttached`), aqui não há pivot — o role_id está na pivot do Tenant (`tenant_user`). Eventos do Eloquent na model `TenantUser` cobrem tudo.
- Catálogo (config) não emite eventos — mudanças via deploy invalidam cache naturalmente quando deploy reinicia o app (cache database persiste, mas TTL absorve).

## R5 — Octane-safety

**Decision**:
- `PermissionResolver` registrado via `$this->app->scoped()` em `PermissionsServiceProvider::register()`.
- Closures em `Gate::before` resolvem o resolver via `app(PermissionResolver::class)` em runtime, não capturam-no via `use`.
- Nenhum estado estático no resolver.
- Cache é externo (database/redis), não em propriedade de classe.
- `Tenant::current()` é resolvido por request via `CurrentTenantManager` (que o módulo Tenant registra como scoped — confirmado no plan da 004).

**Rationale**:
- Constituição (Octane rules em CLAUDE.md): "Never inject the container, request, or config repository into a singleton's constructor; use a resolver closure or bind() instead".
- Cobre o requisito de zero state-leak entre requests.

## R6 — User → Role sem importar Permissions em User

**Decision**: User não importa Role. A navegação do resolver passa pela relação **já existente** `User::tenantUsers` (do módulo Tenant, entregue pelo 004), que retorna `HasMany<TenantUser>`. O `TenantUser` (também já existente) ganha um método `belongsTo(Role::class)` adicionado por esta feature ao próprio model:

```php
// app-modules/tenant/src/Models/TenantUser.php (alterado por esta feature)
public function role(): BelongsTo
{
    return $this->belongsTo(\Modules\Permissions\Models\Role::class);
}
```

Isso significa: o módulo Tenant ganha uma referência direta para `Permissions\Models\Role`. **Esse é o único ponto onde Tenant ↔ Permissions se conhecem**.

**Rationale**:
- A relação é estrutural (FK no banco) — não dá pra evitar referência via `resolveRelationUsing` como fizemos no caso do User-Workspace antes.
- Alternativa seria `resolveRelationUsing` em TenantUser ao invés de método direto. Funciona mas adiciona indireção. Como o TenantUser É o ponto de junção (pivot), a referência direta é natural.
- User permanece intocado: `User::tenantUsers->first()->role->permissions`.

**Alternatives considered**:
- *`resolveRelationUsing('role', ...)` em TenantUser*: mais cerimônia sem benefício real. TenantUser já é alterado por esta feature (drop coluna `role` enum, add `role_id`).
- *Trait `HasRole` em TenantUser*: trait não tipa o retorno. Método direto é mais claro.

## R7 — Tenant context: consumir `Tenant::current()` direto

**Decision**: `PermissionResolver` consome `Modules\Tenant\Models\Tenant::current()` (helper estático já existente do módulo Tenant). Sem interface intermediária.

**Rationale**:
- Tenant é shared-kernel-equivalente do app (todo módulo de domínio depende dele). Permissions depender é normal.
- A interface `TenantContext` que cogitei antes era over-engineering — substituível, mas adicionava cerimônia sem benefício real dado que `Tenant::current()` já é estável e padronizado.
- Isso significa que Permissions tem `require: modules/tenant` no composer.json. Coupling explícito e visível.

**Alternatives considered**:
- *Interface `TenantContext` com binding via container*: adicionava ponto de extensão, mas com custo de duas classes a mais (interface + implementação default). Como a equipe do Tenant já entregou e API é estável, rejeitada.
- *`session('tenant_id')` direto*: ignora a layer de model do Tenant que valida `isOperable`, etc. Pior do que `Tenant::current()`.

## R8 — Validação de role permissions contra `config/permissions.php`

**Decision**: `StoreRoleRequest`/`UpdateRoleRequest` validam o JSON `permissions` submetido contra `config('permissions')` via closure custom:

```php
public function rules(): array
{
    return [
        'name' => ['required', 'string', 'max:255',
            Rule::unique('roles')->where('tenant_id', Tenant::current()?->id)],
        'permissions' => ['required', 'array', new ValidPermissionsTree],
    ];
}
```

`ValidPermissionsTree` é uma Rule (Invokable) que itera o array submetido e confirma que cada `module => resource => action` existe no `config/permissions.php`. Mensagem de erro indica exatamente qual combinação é inválida.

**Rationale**:
- Atende FR-012 e SC-007 (rejeitar combinação inexistente com erro de validação claro, não 500).
- Usa Rule custom (Princípio II — Form Requests + Rules são idiomas Laravel).
- Centraliza a verificação em uma classe reutilizável.

**Alternatives considered**:
- *Validação inline com closure no Form Request*: duplica entre Store/Update. Rule é melhor.
- *Confiar apenas em FK no banco*: não há FK aqui (catálogo é config, não tabela). Validação aplicacional é a única opção.
- *JSON Schema*: overkill para 3 níveis de aninhamento.

## R9 — Bootstrap via `DefaultRolesSeeder`

**Decision**: Quando um tenant é criado (via `Tenant::create()` no fluxo do 004), o módulo Permissions ouve um evento (`TenantCreated`) ou registra um observer e cria automaticamente 4 roles padrão:

| Role | Permissões padrão (subconjunto típico) |
|------|----------------------------------------|
| Owner | TODAS as do `config/permissions.php` (catálogo inteiro) |
| Admin | `core.role.manage`, `core.role.assign`, e todas exceto delete sensitivas |
| Member | leitura + criação básica em todos os módulos |
| Viewer | apenas `view`/`list` |

O criador do tenant recebe automaticamente o vínculo `tenant_user` com role `Owner` (já implementado no 004 como enum; aqui adapta para apontar para a role Owner via `role_id`).

**Rationale**:
- Substitui semanticamente o enum `TenantRole` placeholder do 004.
- Garante que TODO tenant nasce com pelo menos 1 user com `core.role.manage` (o owner). Resolve o "chicken-and-egg" do bootstrap RBAC.
- Roles default ficam editáveis (user com `core.role.manage` pode customizar permissões delas).
- Em testes: feature tests podem rodar `DefaultRolesSeeder` no setUp para ter ambiente consistente.

**Alternatives considered**:
- *Bootstrap manual via tinker*: ruim para onboarding e DX.
- *Roles default inalteráveis (system roles)*: complica o modelo (precisa de flag `is_system_role`). Out of Scope (FR-009 diz "MUST permitir adicionar ou remover permissões de uma role" — sem exceção).
- *1 role default só ("Owner")*: insuficiente — Member/Viewer são úteis na prática para diferenciar acesso operacional.

## Open items deferidos para implementação

- Texto exato das mensagens de validação (`pt_BR`).
- Layout das views Blade (visual em escopo de UI).
- Paginação no `RoleController@index` (default Laravel é razoável).
- Soft-deletes em `roles`: NÃO implementar agora (Princípio IV). Adicionável no futuro sem migration breaking.
- Permissões exatas que cada role default (Owner/Admin/Member/Viewer) recebe — especificadas ao implementar `DefaultRolesSeeder` com base no catálogo final em config.
