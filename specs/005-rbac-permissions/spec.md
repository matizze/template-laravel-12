# Feature Specification: RBAC Permissions System

**Feature Branch**: `005-rbac-permissions`
**Created**: 2026-05-05
**Status**: Draft
**Input**: User description: "Sistema de permissionamento RBAC (Role-Based Access Control) baseado em módulos, recursos e ações."

## Clarifications

### Session 2026-05-05

- Q: Como o sistema concede autoridade para gerenciar perfis (criar, editar, atribuir/remover de usuários) — especialmente no bootstrap do tenant? → A: Não existe o conceito de "administrador" — gerenciar perfis é apenas mais uma permissão do sistema (ex.: `core.role.manage`, `core.role.assign`). O bootstrap (criar o primeiro usuário que possui essas permissões em um tenant novo) acontece FORA da aplicação — via escrita direta no banco ou via um backoffice externo (fora do escopo deste spec).
- Q: Existe algum tipo de perfil que NÃO seja estritamente escopado a um único tenant? → A: Não. Perfis são estritamente por tenant (cada tenant tem seu universo isolado). Templates de perfil reutilizáveis entre tenants e perfis "de plataforma" (acima dos tenants) ficam explicitamente fora deste escopo e podem ser tratados em iterações futuras sem quebrar o modelo atual.
- Q: Como uma permissão obsoleta (que existe no banco mas não é mais usada) deve ser tratada — flag de inativação, sync detectando órfãs, etc.? → A: Não existe sync no app. O catálogo (`modules`, `resources`, `actions`, `permissions`) é gravado EXCLUSIVAMENTE pelo backoffice via acesso direto ao banco — sem regras de aplicação. O backoffice tem permissão para tudo. O app NÃO declara permissões localmente, NÃO oferece comando de sincronização, e NÃO controla o ciclo de vida do catálogo. O app pode escrever em `roles`, `role_permissions` e `user_roles` quando o usuário logado tem a permissão correspondente (ex.: `core.role.manage`, `core.role.assign`, `core.user.manage`).

### Session 2026-05-05 (#3 — simplificação radical)

Após reflexão sobre complexidade, o user reverteu várias decisões anteriores:

- Q: Catálogo permanece em DB (escrito pelo backoffice) ou volta para arquivo? → A: Volta para `config/permissions.php`. Mudanças no catálogo exigem PR + deploy. Backoffice não escreve mais no catálogo. O catálogo deixa de ser entidade do banco — vira array PHP versionado.
- Q: User pode ter múltiplos roles simultaneamente? → A: Não. **1 role por vínculo `tenant_user`**. Composição via roles "agregadas" (criar um role que reúne todas as permissões necessárias).
- Q: Onde fica o `role_id` que vincula user ao role? → A: No pivot `tenant_user.role_id` (FK para `roles.id`). Substitui o enum `tenant_user.role` que a feature 004 entregou como placeholder. Permite que o mesmo user tenha roles DIFERENTES em tenants diferentes (já que ele tem 1 vínculo `tenant_user` por tenant).
- Q: Como armazenar as permissões de cada role? → A: Coluna JSON `permissions` na tabela `roles`. Estrutura SEMPRE 3 níveis: `[module => [resource => [action, action]]]`. Validação no save é contra `config/permissions.php`.
- Q: Tabelas `modules`, `resources`, `actions`, `permissions` (4 normalizadas)? → A: Removidas. Substituídas pela árvore no `config/permissions.php` (catálogo) + JSON em `roles.permissions` (concedidas).
- Q: Tabelas pivot `role_permission` e `role_user`? → A: Removidas. `role_permission` é substituído pelo JSON. `role_user` é substituído por `tenant_user.role_id`.
- Q: Interface `TenantContext` para abstrair acesso ao tenant ativo? → A: Removida. App consome `Tenant::current()` (helper estático entregue pela feature 004) diretamente.
- Q: Helper global `can()`? → A: Removido. Idiomas Laravel (`@can`, `Gate::allows`, `$user->can`, `$this->authorize`) já cobrem todos os call sites com clareza equivalente.
- Q: Enum `TenantRole` (Owner/Admin/Member/Viewer) entregue pelo 004? → A: Era placeholder até esta feature existir. Vira **seed inicial de 4 roles padrão por tenant** (Owner com todas as permissões, Admin com manage, Member com use básico, Viewer só leitura). Pode ser deletado como enum após a migração.

### Session 2026-05-05 (#4 — escopo final)

Última rodada de simplificação antes da implementação:

- Q: Profundidade do catálogo é fixa em 3 níveis ou variável? → A: **Variável**. Estrutura do JSON: cada nó é OU sub-grupo (array nomeado) OU folha (lista de strings = ações). Permissão é o caminho completo: `cadastros.basicos.filial.exportar` (4 segmentos), `core.role.update` (3 segmentos). Permite UI espelhar árvore organizacional.
- Q: Acoplamento Tenant → Permissions via import de `Role` no `TenantUser`? → A: Removido. Usa `resolveRelationUsing('role', ...)` no `PermissionsServiceProvider::boot()`. Tenant fica com zero imports de Permissions.
- Q: Controller dedicado em Permissions para atribuir role a vínculo? → A: Removido. **Reusa** `TenantUserController` + `UpdateTenantUserRoleRequest` que o 004 já entregou — apenas adapta para `role_id` (FK) em vez de enum string.
- Q: `OnboardingController` (criação do primeiro tenant via UI)? → A: **Deletado**. Criação de tenant é responsabilidade de admin/dev, idealmente via DB. `TenantController::store` permanece para criar tenant adicional via UI quando o admin já está dentro do app — gated por `can('core.tenant.create')`.
- Q: Bootstrap automático (DefaultRolesSeeder, auto-Owner, evento de criação)? → A: **Removido**. Tenant nasce com `tenant_user.role_id = NULL`. Criador faz login → dashboard em branco (todas as `@can` retornam false). Adicionar primeiras roles é manual (DB direto, tinker, seeder externo).
- Q: Cache externo + listener de invalidação reativa? → A: **Removido**. Substituído por memoização nativa do Eloquent via `$user->loadMissing('tenantUsers.role')` por request. 1 query no primeiro `can()`, 0 nos seguintes. Mudanças refletem na próxima request automaticamente. Sem TTL, sem listener, sem `flushUser`/`flushRole`.
- Q: Rule class `ValidPermissionsTree`? → A: **Removida**. Closure recursiva inline no Form Request (no método private `validPermissionsTree(): Closure`).
- Q: Scope `Role::forCurrentTenant`? → A: **Removido**. Inline `where('tenant_id', Tenant::current()?->id)` nos call sites.
- Q: `transferOwnership` no `TenantController`? → A: **Deletado**. Conceito de "Owner" não existe mais — quem tem `core.role.update` pode reatribuir roles. Junto vai `TransferOwnershipRequest`.
- Q: Permissão `core.role.assign` separada da `core.role.update`? → A: Unificado. **`core.role.update`** cobre tanto editar a definição da role quanto atribuir role a vínculo `tenant_user`.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Código verifica permissão em tempo de execução (Priority: P1)

Desenvolvedores precisam de uma forma simples, idiomática e performática de perguntar "este usuário pode executar esta ação?" tanto em código de servidor (controladores, middlewares, jobs) quanto em camada de apresentação (templates), de modo que ações sensíveis fiquem efetivamente protegidas e elementos de UI fiquem condicionalmente visíveis.

**Why this priority**: É a forma como o sistema de permissionamento exerce seu propósito no produto final. Sem uma checagem barata e correta em runtime, o RBAC não protege nada. Esta é a entrega de maior valor da feature.

**Independent Test**: Em uma rota de servidor protegida por uma permissão específica, autenticar um usuário com a permissão e confirmar acesso permitido; autenticar um usuário sem a permissão e confirmar bloqueio (HTTP 403). Em um template, confirmar que o elemento condicional aparece para quem tem a permissão e não aparece para quem não tem.

**Acceptance Scenarios**:

1. **Given** um usuário autenticado no tenant T cujos perfis incluem `finance.order.create`, **When** uma checagem `can('finance.order.create')` é feita, **Then** o resultado é verdadeiro.
2. **Given** um usuário autenticado no tenant T cujos perfis NÃO incluem `finance.order.delete`, **When** uma checagem `can('finance.order.delete')` é feita, **Then** o resultado é falso.
3. **Given** nenhum usuário autenticado, **When** qualquer checagem de permissão é feita, **Then** o resultado é falso.
4. **Given** um usuário autenticado, mas sem tenant ativo no contexto, **When** qualquer checagem de permissão é feita, **Then** o resultado é falso (sem tenant, não há perfis aplicáveis).
5. **Given** múltiplas checagens de permissão para o mesmo usuário no mesmo tenant na mesma requisição, **When** as checagens são feitas em sequência, **Then** o conjunto de permissões do usuário é resolvido apenas uma vez (resultado é cacheado).
6. **Given** alguém (usuário com permissão de gerenciamento OU backoffice) altera os perfis de um usuário ou as permissões de um perfil que ele possui, **When** o usuário faz a próxima requisição protegida, **Then** o resultado da checagem reflete o novo estado (não o estado anterior em cache obsoleto).
7. **Given** uma checagem para uma permissão que não existe no catálogo (ex.: typo no nome), **When** a checagem é feita, **Then** o resultado é falso, sem lançar erro.

---

### User Story 2 - Usuário com permissão de gerenciamento define roles e atribui a vínculos `tenant_user` (Priority: P2)

Um usuário que possua as permissões de gerenciamento (`core.role.manage` para criar/editar roles, `core.role.assign` para trocar a role atribuída a um vínculo `tenant_user`) precisa criar roles de acesso (ex.: "Gestor Financeiro", "Operador de RH") combinando permissões existentes do catálogo (`config/permissions.php`), e atribuir essas roles a outros vínculos `tenant_user` do mesmo tenant. Cada vínculo possui exatamente UMA role no tenant correspondente — trocar implica desvincular a anterior.

**Nota**: não há "administrador" implícito — gerenciar roles é apenas mais uma permissão como qualquer outra.

**Why this priority**: Sem roles e atribuições, as permissões do catálogo não chegam aos usuários. É o que torna o sistema operacional para quem opera o tenant.

**Independent Test**: Criar duas roles no mesmo tenant com conjuntos de permissões diferentes; atribuir role A ao vínculo de João e role B ao vínculo de Maria; conferir que cada um enxerga só o seu conjunto. Trocar a role de João para B; conferir que ele agora enxerga as permissões de B. Tentar criar uma role com um user sem `core.role.manage`; confirmar bloqueio (HTTP 403).

**Acceptance Scenarios**:

1. **Given** um usuário com `core.role.manage` no tenant T, **When** ele cria a role "Gestor Financeiro" com permissões `{finance: {order: [view, approve]}}`, **Then** a role é persistida em `roles` (com `tenant_id = T`) e o JSON `permissions` reflete exatamente o submetido.
2. **Given** uma role existente no tenant T e um usuário com `core.role.assign` em T, **When** ele troca a `role_id` de um vínculo `tenant_user` para apontar para essa role, **Then** o user vinculado passa a ter as permissões da role nova ao operar em T.
3. **Given** um usuário com vínculo em T e role atribuída, **When** o JSON `permissions` da role é alterado, **Then** as permissões efetivas do user em T refletem o novo JSON na próxima checagem.
4. **Given** uma role existente no tenant A, **When** um usuário do tenant B (mesmo com permissões de gerenciamento em B) consulta a listagem de roles, **Then** a role de A não aparece nem pode ser atribuída a vínculos de B.
5. **Given** um vínculo `tenant_user` com role atribuída, **When** o vínculo é desvinculado (revogado pelo módulo Tenant), **Then** as permissões deixam de valer para aquele user naquele tenant (sem afetar vínculos do mesmo user em outros tenants).
6. **Given** um usuário SEM `core.role.manage`, **When** ele tenta criar ou editar uma role, **Then** a operação é negada (HTTP 403).
7. **Given** um usuário SEM `core.role.assign`, **When** ele tenta trocar a role de um vínculo `tenant_user`, **Then** a operação é negada.
8. **Given** um usuário com `core.role.manage` tenta criar uma role com uma permissão que NÃO existe em `config/permissions.php` (ex.: `finance.foo.bar`), **When** a operação é submetida, **Then** falha com erro de validação descrevendo qual combinação `module.resource.action` é inválida.

---

### Edge Cases

- O que acontece quando uma permissão consultada via `Gate::allows()` não existe no catálogo (ex.: typo no nome ou permissão removida do `config/permissions.php`)? **Resultado: falso, sem erro.** Falha segura (deny-by-default).
- O que acontece quando uma role é deletada e havia vínculos `tenant_user` apontando para ela via `role_id`? Comportamento depende da regra de FK definida no schema (ON DELETE CASCADE remove o `role_id`; ON DELETE RESTRICT bloqueia o delete). Decisão da spec: **RESTRICT** — não permite deletar role com vínculos ativos; obriga reatribuir antes.
- O que acontece quando uma role tem seu JSON `permissions` alterado? A próxima checagem dos users vinculados a essa role reflete o novo JSON (após invalidação de cache).
- O que acontece quando o `config/permissions.php` é alterado em deploy (combinação removida) e há roles com essa combinação no JSON? O JSON da role permanece (dado histórico), mas a combinação removida do config simplesmente nunca mais será concedida (porque o resolver verifica path no JSON, mas o catálogo é consultado apenas em validação de escrita). Boa prática: rodar limpeza opcional de roles após deploy se houver remoções.
- O que acontece quando um usuário troca de tenant na sessão (via tenant switcher do módulo Tenant)? A próxima checagem resolve via `Tenant::current()` para o novo tenant; cache é segregado por (user, tenant), sem vazamento.
- O que acontece em ambiente Octane (servidor persistente)? Sem vazamento entre requisições — `PermissionResolver` é `scoped` no container; `Tenant::current()` é resolvido na request corrente; cache é externo (driver database/redis).
- O que acontece quando o vínculo `tenant_user` é revogado pelo módulo Tenant enquanto o user tinha sessão ativa? Próxima request: `Tenant::current()` retorna null (módulo Tenant invalida) ou o user perde acesso ao tenant ativo. Resolver retorna `false` em ambos os casos.

## Requirements *(mandatory)*

### Functional Requirements

#### Modelo de permissão e organização

- **FR-001**: O sistema MUST representar cada permissão como uma combinação única de três dimensões: módulo, recurso e ação.
- **FR-002**: O sistema MUST identificar cada permissão por um nome único no formato `{module}.{resource}.{action}` (sempre 3 níveis, sem variação).
- **FR-003**: O catálogo de combinações disponíveis MUST ser declarado em `config/permissions.php` no repositório, no formato de array PHP aninhado em 3 níveis (`module => resource => [actions]`). Mudanças no catálogo são feitas via PR e seguem o ciclo normal de deploy.
- **FR-004**: O sistema MUST tratar o `config/permissions.php` como fonte da verdade do catálogo: validações de permissão (ao registrar uma role) referenciam exclusivamente esse config — não há tabela de catálogo no banco.

#### Roles (perfis) — escrita pelo app, gated por permissão

- **FR-005**: O sistema MUST permitir agrupar permissões em roles nomeadas. Cada role armazena suas permissões em uma estrutura JSON aninhada em 3 níveis (`module => resource => [actions]`), refletindo um subconjunto do catálogo.
- **FR-006**: Roles MUST ser escopadas a um tenant (FK `roles.tenant_id`). Roles de um tenant não são visíveis nem atribuíveis em outro.
- **FR-007**: Cada vínculo `tenant_user` (do módulo Tenant entregue na feature 004) MUST possuir exatamente uma role atribuída via `tenant_user.role_id` (FK para `roles.id`). Como user pode ter múltiplos vínculos `tenant_user` (1 por tenant), ele pode ter roles DIFERENTES em tenants diferentes — mas no MÁXIMO 1 role por tenant.
- **FR-008**: O conjunto efetivo de permissões de um usuário no tenant ativo MUST ser exatamente o JSON `permissions` da role apontada pelo seu vínculo `tenant_user` naquele tenant. Sem união, sem composição.
- **FR-009**: O sistema MUST permitir adicionar ou remover permissões de uma role (mutação do JSON) sem afetar outras roles.
- **FR-010**: O sistema MUST permitir trocar a role de um vínculo `tenant_user` (atualizar `role_id`) sem afetar outros vínculos do mesmo user em outros tenants.
- **FR-011**: As ações de gerenciamento dentro do app (criar/editar role, definir suas permissões, trocar role atribuída a um vínculo `tenant_user`) MUST ser elas próprias controladas por permissões do sistema (ex.: `core.role.manage`, `core.role.assign`) — não existe "administrador" implícito.
- **FR-012**: Operações do app que escrevem na coluna `permissions` de uma role MUST validar que toda combinação `module.resource.action` declarada existe no `config/permissions.php` no momento da escrita; combinações ausentes do catálogo MUST falhar com erro de validação.
- **FR-013**: O bootstrap inicial (criar a primeira role com permissão `core.role.manage` em um tenant novo) acontece via seeder padrão executado quando o tenant é criado: 4 roles default (Owner, Admin, Member, Viewer) — vagamente correspondentes ao enum `TenantRole` que a feature 004 entregou como placeholder. O Owner recebe `core.role.manage` + `core.role.assign` por padrão.

#### Verificação em runtime

- **FR-014**: O sistema MUST oferecer uma operação de verificação em tempo de execução que responde, dado o nome de uma permissão, se o usuário corrente a possui — retornando verdadeiro ou falso.
- **FR-015**: A verificação MUST retornar falso quando não há usuário autenticado (deny-by-default).
- **FR-016**: A verificação MUST retornar falso quando não há tenant ativo no contexto da requisição (deny-by-default).
- **FR-017**: A verificação MUST resolver as permissões com base nos perfis do usuário no tenant ativo da sessão.
- **FR-018**: A verificação MUST retornar falso quando a permissão consultada não existe no catálogo (sem lançar erro), preservando segurança por padrão.
- **FR-019**: A verificação MUST estar disponível tanto em camadas de servidor (controladores, middlewares, jobs) quanto em camadas de apresentação (templates), com a mesma semântica em ambas.

#### Performance e cache

- **FR-020**: O sistema MUST cachear o conjunto efetivo de permissões de um usuário em um tenant para evitar consultas repetidas dentro de uma mesma requisição e entre requisições próximas no tempo.
- **FR-021**: O cache MUST ser segregado por par (usuário, tenant) — uma entrada de cache nunca pode ser usada para outra combinação.
- **FR-022**: O cache MUST ser invalidado pelo app quando ocorrem alterações que afetam as permissões efetivas de um usuário e que são feitas PELO PRÓPRIO APP, incluindo: criar/editar perfil, alterar permissões de um perfil, atribuir ou remover perfil de um usuário.
- **FR-023**: Quando o `config/permissions.php` é alterado (deploy) e existem roles cujo JSON `permissions` referencia combinações removidas, o app MUST manter a integridade dos dados (não corrompe o JSON), e a próxima invalidação de cache (na alteração da role ou expiração de TTL) refletirá o novo estado de validação para futuras escritas. Roles existentes continuam sendo aplicadas conforme o JSON; combinações removidas do catálogo simplesmente não conseguirão ser adicionadas a novas roles.
- **FR-024**: O cache MUST possuir um tempo máximo de validade que limita a janela de inconsistência mesmo na ausência de invalidação explícita.

#### Robustez operacional

- **FR-025**: O sistema MUST funcionar corretamente em ambientes de execução persistentes (long-running, ex.: Octane) sem que estado de uma requisição "vaze" para outra — cada decisão de autorização reflete somente o usuário e o tenant da requisição atual.
- **FR-026**: O sistema MUST tratar permissões consultadas mas inexistentes, falhas de cache e ausência de contexto como condições de "negação", nunca como condições que abrem acesso.

### Key Entities *(include if feature involves data)*

- **Catálogo** *(`config/permissions.php`, versionado no repositório)*: Array PHP aninhado declarando quais combinações `module.resource.action` existem. Mudanças via PR + deploy. **Não é entidade de banco** — é arquivo de configuração.
- **Role** *(tabela `roles`, escrita pelo app)*: Conjunto nomeado de permissões, escopado a um tenant. Atributos:
  - `id`, `tenant_id` (FK), `name` (string), `permissions` (JSON, 3 níveis).
- **TenantUser** *(tabela `tenant_user`, entregue pela feature 004, alterada por esta)*: Vínculo entre um user e um tenant operável. Esta feature **substitui** a coluna `role` (enum) por `role_id` (FK → `roles.id`). Cada vínculo tem exatamente uma role no contexto de seu tenant.
- **Conjunto Efetivo de Permissões do Usuário no Tenant Ativo**: Não é entidade armazenada — é um valor derivado: o JSON `permissions` da role apontada pelo `tenant_user.role_id` do user no `Tenant::current()`. É o objeto cacheado para performance.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Em uma página típica do produto que faz múltiplas verificações de permissão (ordem de 5 a 20 checagens), o tempo total gasto em decisões de autorização é desprezível para o usuário final (não perceptível visualmente).
- **SC-002**: Após uma alteração feita PELO APP (criar/editar perfil, atribuir/remover perfil de usuário), a próxima ação do usuário afetado reflete o novo estado de permissões imediatamente, sem necessidade de relogar.
- **SC-003**: Após uma alteração feita PELO BACKOFFICE diretamente no banco, a próxima ação do usuário afetado reflete o novo estado em até o tempo máximo do TTL do cache (sem requerer ação manual no app).
- **SC-004**: Em uma auditoria de segurança simulada onde se inspecionam todas as rotas e ações sensíveis, 100% delas falham para usuários sem a permissão correspondente (zero falsos positivos de "permitido").
- **SC-005**: Em um cenário em que um usuário pertence a dois tenants distintos com perfis diferentes em cada, alternar de tenant na sessão muda imediatamente o conjunto de permissões efetivas, sem necessidade de relogar e sem qualquer cruzamento entre os dois conjuntos.
- **SC-006**: Tentativas, dentro do app, de criar perfil ou atribuir perfil sem possuir as permissões de gerenciamento correspondentes resultam em bloqueio (HTTP 403 ou equivalente) em 100% dos casos.
- **SC-007**: Tentativas, dentro do app, de associar uma permissão inexistente a um perfil são rejeitadas com erro de validação em 100% dos casos (a integridade da relação é garantida).

## Assumptions

- **Tenant entregue pela feature 004**: Esta spec consome o módulo `Tenant` já entregue pela feature 004-multi-tenant-hierarchy: `Tenant::current()` (helper estático), `tenant_user` (pivot user×tenant), `Tenant::isOperable()` (folha). O `tenant_user.role` (enum string) entregue pelo 004 era placeholder e **será substituído** pelo `role_id` (FK) desta feature.
- **Catálogo em config**: O catálogo de combinações `module.resource.action` vive em `config/permissions.php`, versionado no repositório. Adicionar/remover combinações exige PR + deploy. NÃO há sync, NÃO há tabela de catálogo no banco, NÃO há backoffice escrevendo no catálogo.
- **App escreve roles e atribuições gated por permissão**: O app PODE escrever em `roles` e em `tenant_user.role_id`, APENAS quando o usuário logado possui a permissão correspondente. Não há "administrador" implícito.
- **1 role por vínculo `tenant_user`**: Cada vínculo user×tenant tem exatamente uma role. Múltiplas permissões em um tenant exigem que a role atribuída TENHA todas elas — não dá pra "empilhar" roles. Composição é via "role agregada" (uma role com todas as permissões necessárias).
- **Sem permissões diretas a usuário**: Permissões só são concedidas via role atribuída ao vínculo. Não há `user_permissions` direto.
- **Sem hierarquia entre roles**: Roles não herdam de outras roles.
- **Sem wildcards em verificações**: A verificação aceita apenas o nome exato (3 níveis). Sem `finance.order.*` ou `*.*.create`.
- **Sem super-admin implícito**: Uma role que reúne todas as permissões do catálogo pode ser modelada explicitamente (ex.: a role default "Owner" criada no seed de cada tenant).
- **Bootstrap via seeder padrão por tenant**: Quando um tenant novo é criado, 4 roles default são seedeadas automaticamente (Owner, Admin, Member, Viewer) com permissões pré-definidas. O criador do tenant recebe o vínculo com a role Owner. Esta é a forma como o "primeiro usuário com `core.role.manage`" emerge naturalmente.
- **Idioma**: Identificadores técnicos (module, resource, action) em inglês e minúsculas, sem espaços. `name` da role é livre.
- **Convenção de ações**: Sem repetir o nome do recurso (`approve`, não `approveOrder`).

## Out of Scope

Os itens abaixo NÃO fazem parte desta entrega e devem ser tratados em escopos futuros, se necessário:

- Refactor do módulo Tenant — entregue pela feature 004 e consumido aqui.
- Múltiplos roles por vínculo `tenant_user` (decisão final: 1 role por vínculo).
- Catálogo em DB escrito por backoffice (decisão final invertida: catálogo em `config/permissions.php` + PR + deploy).
- Comando `permissions:sync` — não existe (catálogo é arquivo PHP).
- Tabelas normalizadas de catálogo (`modules`, `resources`, `actions`, `permissions`) — substituídas pelo array em config.
- Tabelas pivot `role_permission` e `role_user` — substituídas por JSON em `roles.permissions` e por FK em `tenant_user.role_id`.
- Helper global `can()` — usar idiomas Laravel nativos (`@can`, `Gate::allows`, `$user->can`, `$this->authorize`, middleware `can:`).
- Templates de role compartilháveis entre tenants (cada tenant tem seu universo isolado).
- Roles "de plataforma" para usuários internos acima dos tenants.
- Permissões por registro / ownership.
- Feature flags por módulo.
- Auditoria de quem alterou quais roles/permissões e quando.
- Permissões condicionais (ex.: válida só dentro de um horário).
- Interface gráfica (UI) polida de gerenciamento de roles — esta spec define o COMPORTAMENTO; o desenho visual fica em escopo de UI.
- Wildcards na verificação (`finance.*`).
- Super-admin implícito ou flag de bypass.
- Atribuição direta de permissões a usuários (sem passar por role).
- Hierarquia de herança entre roles.
