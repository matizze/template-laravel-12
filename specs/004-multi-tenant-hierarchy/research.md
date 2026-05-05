# Phase 0 Research: Multi-Tenancy Hierárquico

**Date**: 2026-05-05
**Spec**: [spec.md](./spec.md) | **Plan**: [plan.md](./plan.md)

Resolução das incertezas técnicas identificadas no plan. Cada item segue o formato **Decision / Rationale / Alternatives**.

---

## R1. Traversal recursivo da árvore de tenants

**Decision**: Eloquent naive (`parent()` belongsTo + `children()` hasMany) + helper `descendants()` que faz **carga em memória** via uma única query CTE recursiva (`WITH RECURSIVE`) executada manualmente quando necessário. Para SQLite (dev/test) e PostgreSQL (prod) ambos suportam CTE recursiva nativamente — zero dependências externas.

```php
// Em Tenant.php
public function descendants(): Collection
{
    $sql = <<<SQL
        WITH RECURSIVE tree AS (
            SELECT * FROM tenants WHERE id = ?
            UNION ALL
            SELECT t.* FROM tenants t INNER JOIN tree ON t.parent_id = tree.id
        )
        SELECT * FROM tree WHERE id != ?
    SQL;
    return Tenant::hydrate(DB::select($sql, [$this->id, $this->id]));
}
```

**Rationale**:
- Constituição (Princípio IV — Simplicidade): zero dependência nova, zero materialized-path/nested-set para suportar a flexibilidade exigida (mover sub-árvores não está em escopo, mas vai estar em evoluções futuras). CTE é a abordagem mais simples que escala para árvores típicas (até centenas de nós).
- Constituição (Princípio II — Laravel Way): `DB::select` é exceção justificável apenas dentro de helpers de modelo onde o Builder Eloquent não expressa CTE de forma idiomática em Laravel 12. Ainda assim usamos `Tenant::hydrate` para devolver coleção de modelos — comportamento idêntico a `Tenant::query()` para todos os call sites.
- O switcher (que lista folhas) não usa `descendants()` — usa scope `whereDoesntHave('children')` direto, custo O(1) por query.

**Alternatives considered**:
- `staudenmeir/laravel-adjacency-list`: dependência externa não aprovada (precisaria pedir permissão ao usuário). Excelente DX (`->descendants` como relation), mas viola "no new deps" do template.
- Nested-set (`baum`/`kalnoy/nestedset`): otimiza leitura mas penaliza escrita (renumeração); excessivo para profundidade típica.
- Eloquent recursivo via `with(['children.children.children'])`: limita profundidade fixa, gera N+1 catastrófico em árvores fundas.

**Implications**:
- Migration `create_tenants_table` precisa de FK self-ref `parent_id` indexada.
- Test `TenantHierarchyPerformanceTest` (gate de SC-005) gera árvore de 4 níveis × 50 nós e mede tempo de `$root->descendants()`.

---

## R2. Invalidação de sessão em mudanças de acesso

**Decision**: Validação **just-in-time** no `SetCurrentTenant` middleware — não tentamos invalidar sessão proativamente. Em cada requisição autenticada, o middleware verifica:

1. `session('tenant_id')` está setado?
2. Tenant existe e não está soft-deleted?
3. Tenant é folha (`!children()->exists()`)?
4. Existe registro `tenant_user` ativo entre `auth()->user()` e o tenant?

Se qualquer check falhar:
- Limpa `session()->forget('tenant_id')`.
- Redireciona para `/onboarding` (ou tela "sem tenant atribuído", ver R5).

```php
// SetCurrentTenant::handle (esboço)
$tenantId = session('tenant_id');
if (!$tenantId) return redirect()->route('tenant.choose');

$tenant = Tenant::find($tenantId);
if (!$tenant || !$tenant->isOperable() || !auth()->user()->tenants()->whereKey($tenant->id)->exists()) {
    session()->forget('tenant_id');
    return redirect()->route('tenant.choose')->with('warning', 'Tenant indisponível. Escolha outro.');
}
```

**Rationale**:
- Evita complexidade de session-store sweep (Octane não tem hook trivial para varrer todas as sessões ativas e revogar uma específica).
- Latency cost desprezível (1 query indexada por request).
- Cobre 100% dos casos da spec: revogação de vínculo (US4 AC3), tenant folha vira agrupador (US2 AC5), tenant soft-deletado (FR-023).

**Alternatives considered**:
- Pub/sub via `Cache::tags` invalidando sessões — Octane com Swoole tem suporte parcial; complexidade desproporcional ao ganho.
- Coluna `users.session_invalidated_at` com short-circuit no auth middleware — adiciona estado novo sem benefício relevante para 1-3 req/s típicos do template.

**Implications**:
- Test `TenantUserAttachTest::test_revoke_link_invalidates_active_session` simula sessão ativa, revoga vínculo via outra request, confirma 302 → `/tenant/choose` na próxima request da mesma sessão.
- Test `TenantHierarchyTest::test_leaf_becoming_grouper_blocks_session` adiciona filho a tenant ativo, confirma redirect.

---

## R3. Soft-delete + restauração de tenants

**Decision**: `Illuminate\Database\Eloquent\SoftDeletes` no model `Tenant`. Regras enforced em **observer + Form Request validation** (não via constraint de banco — semântica complexa demais para CHECK constraint).

**Regras (FR-021..024) implementadas em `TenantObserver` + `DeleteTenantRequest`:**

| Cenário | Onde | Comportamento |
|---------|------|---------------|
| Soft-delete folha sem vínculos e sem dados | `DeleteTenantRequest::authorize()` + `TenantObserver::deleting()` | Permite |
| Soft-delete folha com vínculos `tenant_user` ativos | Validation rejeita | 422 com mensagem específica |
| Soft-delete folha com dados de domínio | Validation rejeita (precisa contar registros via tabelas que usam `BelongsToTenant`) | 422 |
| Soft-delete agrupador (com filhos) | Validation rejeita | 422 |
| Restaurar tenant cujo pai está soft-deleted | `RestoreTenantRequest::authorize()` rejeita | 422 |
| Soft-deleted aparecer em switcher / listings | Trait `SoftDeletes` adiciona scope global automaticamente — ✅ |

```php
// TenantObserver
public function deleting(Tenant $tenant): void
{
    if ($tenant->children()->exists()) {
        throw ValidationException::withMessages(['tenant' => 'Não é possível excluir tenant com filhos.']);
    }
    if ($tenant->users()->exists()) {
        throw ValidationException::withMessages(['tenant' => 'Não é possível excluir tenant com vínculos ativos.']);
    }
    // domain-data check fica no request (precisa conhecer todos os models BelongsToTenant — exposto via service)
}
```

**Rationale**:
- `SoftDeletes` é Laravel idiomático.
- Observer é o ponto canônico para hooks de lifecycle do model.
- Centralização das regras facilita manutenção quando RBAC for adicionado (spec 005).

**Alternatives considered**:
- Cascade soft-delete: explicitamente rejeitado pela spec (admin deve esvaziar antes).
- Eventos Laravel (`deleting` event listener): equivalente ao observer mas menos discoverable.

---

## R4. Quantificação concreta de SC-005

**Decision**: Árvore-baseline = **4 níveis × 50 nós por nível** (≈ 200 nós totais). Gate: `$root->descendants()` retorna a coleção em **< 200ms p95** medido em ambiente de teste (`PHPUnit` com SQLite in-memory).

**Implementation**: `tests/Feature/TenantHierarchyPerformanceTest.php`:

```php
public function test_descendants_within_budget(): void
{
    $root = Tenant::factory()->create();
    // builds 4-level tree with 50 children per level
    $this->buildTree($root, depth: 4, fanout: 50);

    $start = hrtime(true);
    $descendants = $root->descendants();
    $elapsedMs = (hrtime(true) - $start) / 1_000_000;

    $this->assertCount(/* expected total */, $descendants);
    $this->assertLessThan(200, $elapsedMs, "descendants() exceeded 200ms (got {$elapsedMs}ms)");
}
```

**Rationale**:
- 4 × 50 cobre matriz → regional → base → obra realista (case típico citado pela spec).
- 200ms é folga generosa para SQLite in-memory (prod com PostgreSQL será mais rápido).
- Métrica diretamente verificável em CI sem dependência de infraestrutura.

**Alternatives considered**:
- Benchmark dedicado (não-PHPUnit): adiciona ferramenta nova; CI pipeline atual já roda Pest/PHPUnit.
- Simular 1000+ nós: irrealista para o caso de uso (matriz de empresa com 1000 obras seria outliner).

---

## R5. Tela "usuário sem tenant atribuído"

**Decision**: Reaproveitar a view `onboarding.blade.php` existente com **dois estados condicionais**:

1. **User pode criar tenant** (autorizado para `tenant.create`): mostra formulário "Criar primeiro tenant" (caso de bootstrap inicial do sistema).
2. **User não pode criar tenant** (caso normal de US3): mostra mensagem "Aguarde um administrador atribuir-lhe acesso a um tenant" + botão de logout.

**Rationale**:
- Reaproveitamento da view existente — sem rota nova, sem componente novo.
- A condição "pode criar tenant" será determinada por `Gate::allows('tenant.create')` que, na spec atual (sem RBAC), retorna `true` apenas se não houver nenhum tenant raiz no sistema (bootstrap) — RBAC granular vem na spec 005.

**Alternatives considered**:
- Tela dedicada "no-access": adiciona view + rota só para apresentar mensagem. YAGNI.

---

## R6. Octane safety do `CurrentTenantManager`

**Decision**: Registrar como `scoped` (não `singleton`) no `TenantServiceProvider`:

```php
$this->app->scoped(CurrentTenantManager::class, function (Application $app) {
    return new CurrentTenantManager(fn () => session());  // resolver closure, não session direta
});
```

**Rationale**:
- `scoped` é descartado ao fim de cada request no Octane (resolve corretamente o problema de state leak entre requests).
- Resolver closure para `session()` evita capturar a request atual no constructor.

**Alternatives considered**:
- `singleton` com `setSession()` no middleware: mais código, mais propenso a bug.
- `bind` (factory por chamada): cria nova instância em cada `app(CurrentTenantManager::class)`; aceitável mas perde memoization da resolução do tenant.

---

## R7. `tenant_user` pivot model

**Decision**: Pivot model **`TenantUser`** com `$incrementing = true` (mantém o padrão atual do `Member`), tabela `tenant_user` com PK `id` autoincrement + UNIQUE(`user_id`, `tenant_id`) + coluna `role` (TenantRole enum).

```php
class TenantUser extends Pivot
{
    public $incrementing = true;
    protected $table = 'tenant_user';
    protected $casts = ['role' => TenantRole::class];
}
```

**Rationale**:
- Pivot model permite Eloquent events (`creating`, `deleting`) — necessário para R2 (invalidação de sessão na revogação) e R3 (validação contra atributo agrupador).
- UNIQUE garante no banco que não existam vínculos duplicados.
- Convenção Laravel: `tenant_user` (snake_case alfabético).

**Alternatives considered**:
- Pivot puro sem PK auto-increment: padrão Laravel mais comum, mas perde events. O módulo `workspace` atual já usa pivot model com `$incrementing = true` — manter consistência.

---

## Resumo das decisões

| # | Tópico | Decisão |
|---|--------|---------|
| R1 | Traversal | Eloquent + CTE recursiva via `DB::select` em helpers |
| R2 | Invalidação de sessão | Just-in-time no middleware (sem sweep proativo) |
| R3 | Soft-delete | `SoftDeletes` + Observer + Form Request validation |
| R4 | SC-005 metric | 4 níveis × 50 nós, < 200ms p95 em PHPUnit |
| R5 | Tela sem-tenant | Reaproveitar `onboarding.blade.php` com condicional |
| R6 | Octane | `scoped` + resolver closure |
| R7 | Pivot model | `TenantUser` com PK auto-increment + UNIQUE composto |

**Status**: todos os NEEDS CLARIFICATION resolvidos. Pronto para Phase 1.
