# Quickstart: Multi-Tenancy Hierárquico

Verificação manual após `/speckit.implement` concluir todas as tarefas. Use este guia para validar critérios de sucesso na ordem das User Stories.

## Pré-requisitos

```bash
composer setup            # instala deps + key + migrate + build
php artisan migrate:fresh --seed
```

Garante que o sistema fica em estado limpo: nenhum tenant, nenhum user (exceto seed admin se houver).

---

## US1 — Isolamento (P1)

**Objetivo**: dois tenants A e B, mesmo user em ambos, dados não vazam.

```bash
# Cria admin via CLI
php artisan create:user --admin

# Login como admin no browser → cria dois tenants raiz "Base A" e "Base B"
# Em /dashboard, switcher exibe ambas (ambas são folhas → operáveis)

# Selecione Base A → crie um registro de domínio (ex.: pelo seeder de demo)
# Switch para Base B → confirme que a listagem está vazia
# Crie um registro em B → switch para A → confirme que registro de B não aparece
```

**Asserções esperadas**:
- ✅ Listagem em A mostra apenas dados de A
- ✅ Listagem em B mostra apenas dados de B
- ✅ Tentar acessar `/orders/{id_de_A}` enquanto sessão está em B retorna **404** (não 403)

**Test**: `php artisan test --compact --filter=TenantTest`

---

## US2 — Hierarquia (P1)

**Objetivo**: criar árvore Matriz → Regional → Base, validar que apenas folhas são selecionáveis.

```bash
php artisan tinker
> $matriz = Tenant::factory()->create(['name' => 'Matriz Brasil']);
> $regional = Tenant::factory()->create(['name' => 'Regional Sul', 'parent_id' => $matriz->id]);
> $base = Tenant::factory()->create(['name' => 'Base Curitiba', 'parent_id' => $regional->id]);
> $matriz->isOperable();   // false
> $regional->isOperable(); // false
> $base->isOperable();     // true
> $matriz->descendants()->pluck('name'); // ['Regional Sul', 'Base Curitiba']
```

**No browser**:
1. Vincule o admin a `Base Curitiba` via UI de vínculos.
2. Switcher exibe **apenas** "Base Curitiba" (Matriz e Regional ocultas).
3. Tente forçar `session()->put('tenant_id', $matriz->id)` via tinker → próxima request → redirect para `/onboarding` com flash warning.

**Asserções esperadas**:
- ✅ `isOperable()` retorna correto em todos os níveis
- ✅ Switcher filtra agrupadores
- ✅ Middleware bloqueia tenant agrupador na sessão

**Test**: `php artisan test --compact --filter=TenantHierarchyTest`

---

## US3 — Criação de User (P2)

**Objetivo**: criar User global, e-mail de reset enviado, primeiro login funciona.

```bash
# Como admin logado, vá para /users/create (UI nova)
# Preencha: Nome="João", E-mail="joao@example.com" → submit
```

**Asserções esperadas**:
- ✅ Flash `success` com mensagem "Usuário criado. E-mail enviado para joao@example.com"
- ✅ `User::where('email', 'joao@example.com')->first()` existe
- ✅ `TenantUser::where('user_id', $joao->id)->count() === 0` (nenhum vínculo automático)
- ✅ Mailpit (ou log mailer) registra e-mail de reset

```bash
# Pegue o token do log/mailpit
# Acesse /reset-password/{token}, defina senha
# Faça login → cai na tela "Aguarde atribuição de tenant"
```

**Test**: `php artisan test --compact --filter=UserCreationTest`

---

## US4 — Atribuição de vínculos (P2)

**Objetivo**: admin vincula João a Base Curitiba, João consegue operar.

```bash
# Como admin, vá para /tenant/{base}/users → "Adicionar usuário"
# Selecione João, role=Member → salvar
```

**Asserções esperadas**:
- ✅ Registro `tenant_user` (joao, base, member) persistido
- ✅ João, ao logar/refresh, vê "Base Curitiba" no switcher
- ✅ João seleciona base → entra no dashboard normalmente

**Cenário de revogação**:
```bash
# Admin remove vínculo de João via DELETE /tenant/{base}/users/{tenantUser}
# João, na próxima request, é redirecionado para /onboarding (sessão invalidada just-in-time)
```

**Cenário de tentar vincular em agrupador**:
```bash
# Admin tenta POST /tenant/{regional}/users com user_id=joao
# Resposta: 422 "Vínculo só é aceito em tenants operáveis (folhas)."
```

**Test**: `php artisan test --compact --filter=TenantUserAttachTest`

---

## Lifecycle do tenant (FR-021..024)

```bash
# Tente excluir Matriz (agrupador) → 422 "Tenant possui filhos"
# Tente excluir Base Curitiba (com vínculo de João) → 422 "Tenant possui 1 vínculo ativo"
# Remova o vínculo de João → tente excluir → ✅ soft-delete OK
# Restaure → confirme que volta para listings
```

**Test**: `php artisan test --compact --filter=TenantSoftDeleteTest`

---

## Performance gate (SC-005)

```bash
php artisan test --compact --filter=TenantHierarchyPerformanceTest
```

Espera-se: árvore 4×50, `descendants()` da raiz < 200ms p95.

---

## Validação de remoção de "workspace" (SC-003)

```bash
# Busca textual em código de produção (excluindo specs/ e mensagens de commit)
grep -ri "workspace" app-modules/ --include="*.php" --include="*.blade.php"
# Espera: zero ocorrências
```

---

## Suite completa

```bash
./vendor/bin/phpstan analyse                    # level 3 limpo
./vendor/bin/pint --dirty --format agent       # formatação OK
php artisan test --compact                     # tudo verde
php artisan dusk                               # browser tests passam (Herd-backed)
```

**Critério de sucesso global**:
- ✅ Suite Feature passa
- ✅ Suite Browser passa (exceto testes de Invitation removidos com justificativa)
- ✅ PHPStan level 3 limpo
- ✅ Pint sem diffs
- ✅ Zero referência a "workspace" em código de produção
