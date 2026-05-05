# Feature Specification: Multi-Tenancy Hierárquico

**Feature Branch**: `004-multi-tenant-hierarchy`
**Created**: 2026-05-05
**Status**: Draft
**Input**: User description: refactor do módulo `workspace` para um modelo de tenants hierárquicos onde a estrutura da árvore (folha vs. nó intermediário) define a semântica de operabilidade, sem campo discriminador explícito.

## Clarifications

### Session 2026-05-05

- Q: Quem pode adicionar um novo usuário ao sistema? → A: Decisão deferida — modelo de autorização (RBAC resource-based) será definido em spec separada. Esta spec se refere genericamente a "usuário autorizado" sem caracterizar as regras de autorização.
- Q: Como tenants podem ser excluídos? → A: Soft-delete permitido apenas em tenants folha que não possuam vínculos `tenant_user` ativos nem dados de domínio. Agrupadores precisam ser esvaziados (filhos movidos ou excluídos) antes da exclusão.
- Q: Restrição de unicidade no nome de tenant? → A: Sem restrição de unicidade. Identidade pelo ID. UI MUST desambiguar visualmente exibindo o caminho ancestral (ex.: "Matriz A › Comercial") quando o mesmo nome puder aparecer em mais de um contexto.
- Q: Atomicidade do envio de e-mail na adição de usuário? → A: Sem complexidade. Sistema cria o User e dispara o e-mail. Tratamentos de falha (rollback, retry, reenvio manual) ficam para evolução futura — não fazem parte desta spec.
- Q: Modelo de relacionamento User × Tenant? → A: User é entidade **global** no sistema, sem `tenant_id` próprio. Vínculo a tenants é feito via tabela pivot `tenant_user` (modelo `TenantUser`, seguindo convenção Laravel), permitindo o mesmo user em múltiplos tenants. Criar um user e atribuir-lhe vínculos são **dois fluxos separados**: o sistema apenas cria o User (com e-mail e nome) e dispara o e-mail de senha; um usuário autorizado depois vincula esse user a um ou mais tenants operáveis.
- Q: Métrica de "instantâneo" em SC-005? → A: Deferida para a fase de planejamento. SC-005 reformulado em termos de UX ("usuário consegue listar descendentes sem percepção de espera em árvores de tamanho típico"); quantificação concreta fica no plan técnico.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Operação isolada em um tenant operável (Priority: P1)

Um usuário autenticado seleciona um tenant operável (uma "ponta" da hierarquia organizacional, como uma base, unidade ou obra) e a partir desse momento todas as operações que ele realiza no sistema ficam restritas àquele tenant: leituras, escritas, listagens e relatórios não cruzam dados com outros tenants.

**Why this priority**: É a razão de existir do módulo. Sem isolamento garantido por tenant, qualquer outra história fica comprometida. É também o que o módulo `workspace` já entrega hoje — preservar essa garantia durante o refactor é não-negociável.

**Independent Test**: Criar dois tenants operáveis A e B, vincular o mesmo usuário aos dois, criar dados em A com a sessão apontando para A, trocar a sessão para B e verificar que (1) nenhum dado de A aparece e (2) novos dados criados em B não vazam para A.

**Acceptance Scenarios**:

1. **Given** usuário autenticado com tenant operável selecionado na sessão, **When** consulta qualquer entidade vinculada a tenant, **Then** recebe somente registros do tenant da sessão
2. **Given** usuário cria um novo registro de domínio, **When** o registro é persistido, **Then** ele é automaticamente vinculado ao tenant ativo da sessão sem o usuário precisar informar o tenant
3. **Given** usuário troca de tenant via switcher, **When** a nova sessão é estabelecida, **Then** todas as consultas subsequentes refletem somente o novo tenant

---

### User Story 2 - Hierarquia organizacional de tenants (Priority: P1)

Um administrador organiza os tenants em uma árvore que reflete a estrutura real da empresa (matriz → regional → base → obra, em qualquer profundidade). Tenants intermediários servem de agrupamento e navegação; tenants nas pontas da árvore (folhas) são os únicos onde dados podem ser produzidos.

**Why this priority**: É a evolução central sobre o modelo `workspace` atual. Sem hierarquia, o refactor não entrega flexibilidade nova; é o motivo pelo qual o usuário decidiu fazer este trabalho ao invés de manter o status quo.

**Independent Test**: Criar manualmente uma árvore de 3 níveis (matriz → regional → base), validar que apenas as bases aparecem como selecionáveis para operar, e que a matriz/regional aparecem na navegação como containers que listam seus descendentes.

**Acceptance Scenarios**:

1. **Given** um tenant sem filhos, **When** o sistema avalia se é operável, **Then** o tenant é classificado como operável
2. **Given** um tenant que possui ao menos um filho, **When** o sistema avalia operabilidade, **Then** o tenant é classificado como agrupador (não-operável)
3. **Given** um seletor de tenant exibido para o usuário, **When** a lista é renderizada, **Then** apenas tenants operáveis (folhas da árvore) aparecem como selecionáveis
4. **Given** um usuário tenta selecionar um tenant agrupador via manipulação de sessão, **When** uma requisição protegida é feita, **Then** o sistema bloqueia a operação com erro de autorização
5. **Given** um tenant folha que recebe um novo filho, **When** algum usuário tinha esse tenant ativo na sessão, **Then** as próximas requisições desse usuário são bloqueadas até ele selecionar um novo tenant operável

---

### User Story 3 - Criação de usuário global (Priority: P2)

Um usuário autorizado cria um novo usuário no sistema informando apenas nome e e-mail. O sistema cria a conta como entidade global (sem nenhum vínculo a tenant) e dispara automaticamente um e-mail para que o novo usuário defina sua própria senha. Ao definir a senha o novo usuário já consegue autenticar-se, mas só ganhará acesso operacional após receber pelo menos um vínculo `tenant_user` (US4).

**Why this priority**: Substitui o fluxo de convite (`Invitation`) por uma criação direta. Separar criação da atribuição de tenant permite reutilizar o mesmo user em múltiplos tenants e divide responsabilidades (criação vs. concessão de acesso) — consistente com a decisão de manter User como entidade global.

**Independent Test**: Criar um usuário novo via formulário, verificar que (1) o User foi persistido sem nenhum vínculo `tenant_user`, (2) um e-mail de redefinição de senha foi disparado, (3) seguindo o link do e-mail o usuário consegue definir senha e autenticar.

**Acceptance Scenarios**:

1. **Given** usuário autorizado, **When** cria usuário com nome e e-mail novos, **Then** sistema cria o User como entidade global (sem nenhum vínculo `tenant_user`) e enfileira e-mail de redefinição de senha
2. **Given** novo usuário recebe e-mail de redefinição, **When** clica no link e define senha válida, **Then** consegue autenticar-se no sistema
3. **Given** usuário autorizado tenta criar usuário com e-mail já existente no sistema, **When** submete o formulário, **Then** sistema responde com mensagem clara informando que o e-mail já está cadastrado e nenhum efeito colateral (e-mail, criação parcial) é executado

---

### User Story 4 - Atribuição de vínculos a um usuário (Priority: P2)

Um usuário autorizado vincula um usuário existente a um ou mais tenants operáveis, atribuindo-lhe um papel em cada vínculo (registro `tenant_user`). O mesmo usuário pode ser vinculado a quantos tenants forem necessários, cada um com seu próprio papel. A revogação de um vínculo remove o acesso do usuário àquele tenant sem afetar os demais.

**Why this priority**: Sem este fluxo, usuários criados em US3 ficam sem acesso operacional. É o complemento direto de US3 e habilita a regra "User pode estar em vários tenants" (FR-015).

**Independent Test**: Para um usuário pré-existente e sem vínculos, atribuir vínculo em dois tenants operáveis distintos, verificar que (1) os dois registros `tenant_user` são persistidos, (2) o switcher do usuário passa a listar os dois tenants, (3) revogar um dos vínculos remove apenas aquele e o usuário continua com acesso ao outro tenant.

**Acceptance Scenarios**:

1. **Given** usuário autorizado e usuário-alvo existente sem vínculos, **When** atribui ao usuário-alvo um registro `tenant_user` com tenant operável e papel selecionado, **Then** sistema persiste o vínculo e o tenant passa a aparecer no switcher do usuário-alvo
2. **Given** usuário-alvo já com vínculo no tenant A, **When** usuário autorizado adiciona vínculo no tenant B, **Then** o usuário-alvo passa a ter acesso aos dois tenants, com papéis independentes em cada
3. **Given** usuário-alvo com vínculos em A e B, **When** usuário autorizado revoga o vínculo em A, **Then** o usuário-alvo perde acesso a A imediatamente (sessões ativas em A são invalidadas) e mantém acesso a B
4. **Given** usuário autorizado tenta atribuir vínculo em um tenant agrupador, **When** submete a operação, **Then** sistema rejeita com mensagem indicando que vínculo só é aceito em tenants operáveis

---

### Edge Cases

- **Usuário sem nenhum vínculo `tenant_user`**: usuário recém-criado consegue autenticar-se mas não possui vínculo com nenhum tenant — sistema deve apresentar tela clara explicando que aguarda atribuição de tenant por um usuário autorizado, sem expor erro genérico nem permitir acesso a rotas operacionais.
- **Usuário sem tenant operável vinculado**: usuário autenticado cujos vínculos foram todos revogados ou apontavam apenas para tenants que viraram agrupadores — mesma tela informativa do caso anterior.
- **Tenant folha vira agrupador**: quando um administrador adiciona um filho a um tenant que estava sendo usado como operável, sessões ativas naquele tenant precisam ser invalidadas / forçadas a re-selecionar.
- **Tenant agrupador vira folha**: quando o último filho de um tenant é movido ou removido, esse tenant passa a ser considerado operável automaticamente, sem nenhuma ação manual de mudança de "tipo".
- **Tentativa de cross-tenant via parâmetro de URL**: usuário tenta acessar `/orders/123` onde o pedido 123 pertence a outro tenant — sistema retorna 404 (e não 403), evitando enumeração.
- **Profundidade extrema**: árvore com muitos níveis ou listas grandes de descendentes — listagens e navegação não devem degradar perceptivelmente.
- **Tentativa de vínculo em agrupador**: administrador tenta vincular um usuário a um tenant que possui filhos — sistema deve recusar com mensagem explicando que o vínculo `tenant_user` só é aceito em tenants operáveis (folhas).
- **Adicionar filho a tenant com vínculos**: usuário tenta tornar um tenant folha em agrupador adicionando-lhe um filho, mas o tenant possui vínculos `tenant_user` ativos — sistema deve recusar a operação até os vínculos serem removidos ou re-direcionados.
- **Tentativa de exclusão bloqueada**: usuário tenta excluir tenant agrupador, ou folha com vínculos, ou folha com dados de domínio — sistema recusa identificando o motivo específico (tem filhos / tem N vínculos / tem N registros de domínio), permitindo ao usuário decidir como proceder.
- **Restauração com pai excluído**: usuário tenta restaurar tenant soft-deletado cujo pai também está soft-deletado — sistema recusa até o pai ser restaurado primeiro.

## Requirements *(mandatory)*

### Functional Requirements

#### Isolamento

- **FR-001**: Sistema MUST aplicar automaticamente um filtro de tenant em todas as consultas a entidades de domínio, baseado no tenant ativo da sessão do usuário, sem o desenvolvedor precisar adicionar a cláusula manualmente em cada query.
- **FR-002**: Sistema MUST atribuir automaticamente o tenant ativo da sessão a qualquer registro de domínio criado pelo usuário, sem que o usuário ou o desenvolvedor precise informá-lo explicitamente.
- **FR-003**: Sistema MUST rejeitar qualquer requisição autenticada cujo tenant ativo da sessão não corresponda a um tenant operável (folha) válido e existente.
- **FR-004**: Sistema MUST garantir que nenhuma rota protegida por tenant aceite valores de identificador de tenant vindos do request (parâmetros, headers, cookies não-assinados) — a fonte de verdade é exclusivamente a sessão.

#### Hierarquia

- **FR-005**: Sistema MUST permitir que cada tenant referencie no máximo um tenant pai e qualquer quantidade de tenants filhos, formando uma árvore.
- **FR-006**: Sistema MUST permitir profundidade arbitrária na árvore, sem limite fixo de níveis.
- **FR-007**: Sistema MUST classificar um tenant como operável se e somente se ele não possui nenhum filho. Essa classificação MUST ser derivada da estrutura, não armazenada como atributo.
- **FR-008**: Sistema MUST oferecer navegação ascendente (todos os ancestrais até a raiz) e descendente (todos os descendentes até as folhas) a partir de qualquer tenant.
- **FR-009**: Sistema MUST listar apenas tenants operáveis no seletor apresentado ao usuário para escolher onde operar.
- **FR-009a**: Sistema MUST não impor unicidade no nome do tenant (nem global, nem entre irmãos). A identidade do tenant é seu identificador interno.
- **FR-009b**: Sempre que um tenant for exibido em um contexto onde possa haver outro tenant de mesmo nome (switcher, listagens administrativas, formulários de seleção), a UI MUST exibir o caminho ancestral (ex.: "Matriz A › Regional Sul › Base Centro") suficiente para desambiguar.

#### Criação de usuário global

- **FR-010**: Sistema MUST oferecer um fluxo de criação de novo usuário que aceita exclusivamente nome e e-mail. O User criado é uma entidade **global**, sem `tenant_id` próprio e **sem nenhum vínculo `tenant_user` criado automaticamente**.
- **FR-011**: Sistema MUST disparar automaticamente um e-mail de redefinição de senha ao novo usuário ao concluir a criação, reaproveitando o fluxo de redefinição já existente.
- **FR-013**: Sistema MUST validar a unicidade do e-mail antes de criar o usuário, retornando mensagem clara em caso de conflito sem efetuar qualquer envio de e-mail ou criação parcial.
- **FR-014**: Sistema MUST aplicar limite de tentativas (rate limiting) na rota de criação de usuário compatível com o aplicado nas demais rotas administrativas.

#### Atribuição de vínculos (tabela `tenant_user`)

- **FR-015**: Sistema MUST permitir que um mesmo usuário possua vínculos `tenant_user` em múltiplos tenants simultaneamente, cada vínculo com seu próprio papel definido independentemente.
- **FR-015a**: Sistema MUST oferecer fluxo para que um usuário autorizado atribua um registro `tenant_user` entre um User existente e um Tenant operável, definindo o papel naquele vínculo.
- **FR-015b**: Sistema MUST oferecer fluxo para que um usuário autorizado revogue um vínculo `tenant_user` existente. A revogação MUST invalidar imediatamente sessões ativas do usuário no tenant cujo vínculo foi removido.
- **FR-016**: Sistema MUST aceitar registros `tenant_user` exclusivamente apontando para tenants operáveis (folhas). Tentativa de criar vínculo em tenant agrupador MUST ser rejeitada com erro de validação.
- **FR-017**: Sistema MUST impedir que um tenant operável que possua vínculos `tenant_user` ativos seja transformado em agrupador (ou seja, ganhe um filho), até que os vínculos sejam removidos ou movidos. A tentativa de adicionar filho a um tenant com vínculos MUST ser rejeitada com erro claro indicando o motivo.

#### Lifecycle do tenant

- **FR-021**: Sistema MUST permitir exclusão (soft-delete reversível) de tenants exclusivamente quando o tenant for folha (sem filhos), não possuir vínculos `tenant_user` ativos e não possuir registros de domínio vinculados.
- **FR-022**: Sistema MUST rejeitar tentativas de excluir tenant agrupador, tenant folha com vínculos `tenant_user` ativos ou tenant folha com dados de domínio, retornando mensagem clara identificando o motivo do bloqueio.
- **FR-023**: Sistema MUST excluir tenants soft-deletados de toda navegação, listagens, switcher e seleção em formulários, sem afetar buscas administrativas explicitamente reservadas.
- **FR-024**: Sistema MUST suportar restauração (undelete) de um tenant soft-deletado, desde que o restauro não viole nenhuma das regras estruturais (ex.: pai não pode estar excluído).

#### Migração do modelo atual

- **FR-018**: Após o refactor, nenhum identificador, nome de classe, nome de tabela, nome de coluna, nome de rota, nome de view ou nome de componente de produção MUST conter o termo `workspace` (em qualquer caixa).
- **FR-019**: Sistema MUST manter funcionalmente equivalente toda a experiência de usuário existente do módulo `workspace` no novo módulo `tenant`, exceto pelos pontos explicitamente removidos (ver FR-020) ou substituídos (ver User Story 3).
- **FR-020**: Sistema MUST remover completamente o conceito de convite (Invitation): modelo, persistência, notificação, modal, rotas, testes. A spec anterior `002-invite-redirect-flow` MUST ser marcada como superseded por esta.

### Key Entities

- **Tenant**: nó da árvore organizacional. Atributos essenciais: nome, referência opcional ao tenant pai. Operabilidade derivada da ausência de filhos. Carrega todos os dados de domínio quando é folha.
- **User**: pessoa autenticada no sistema. **Entidade global, sem `tenant_id`**. Pode ter zero, um ou múltiplos vínculos `tenant_user`. Mantém referência opcional ao último tenant operável selecionado para fins de retomada da sessão (não confere acesso por si só — o acesso vem dos vínculos).
- **TenantUser**: vínculo (tabela pivot `tenant_user`, seguindo convenção Laravel) entre um User e um Tenant operável, com papel atribuído. Existe no máximo um registro por par (user × tenant). É o que efetivamente concede acesso operacional do user ao tenant.
- **Role**: enumeração de papéis possíveis em um registro `tenant_user` (ex.: admin do tenant, membro comum). Define autorização dentro do tenant. (Detalhamento e granularidade tratados na spec de RBAC.)

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Em uma suíte de testes que crie dados em pelo menos dois tenants distintos com o mesmo usuário, 100% das consultas de domínio retornam apenas dados do tenant ativo, sem exceções.
- **SC-002**: Após um usuário autorizado criar uma conta (US3) e atribuir-lhe pelo menos um vínculo `tenant_user` (US4), o novo usuário consegue definir sua senha e completar o primeiro login com acesso operacional ao tenant em menos de 5 minutos, sem auxílio adicional além do e-mail recebido.
- **SC-003**: Após o refactor, uma busca textual no código de produção pelo termo `workspace` (case-insensitive, excluindo a pasta `specs/` e mensagens de commit) retorna zero ocorrências.
- **SC-004**: Toda a suíte de testes Feature e Browser que existia antes do refactor passa após o refactor, exceto os testes diretamente ligados a Invitation (que são removidos com justificativa registrada).
- **SC-005**: Criar uma árvore com pelo menos 4 níveis de profundidade e listar descendentes da raiz é executado sem erro e sem percepção de espera para o usuário em árvores de tamanho típico. (Quantificação concreta — limites e tempos — fica para a fase de planejamento técnico.)
- **SC-006**: Em zero por cento dos testes de tentativa explícita de cross-tenant (acessar registro de outro tenant via URL, manipular sessão para apontar para tenant não-folha, etc.) o sistema permite o acesso indevido.

## Assumptions

- **A1 — Vínculo `tenant_user` exclusivo em folhas**: o relacionamento usuário × tenant só é aceito em tenants operáveis (folhas). Tenants agrupadores não podem possuir registros `tenant_user` apontando para si. Acesso a múltiplas bases de uma matriz exige um registro `tenant_user` explícito para cada base.
- **A6 — User como entidade global**: User não carrega `tenant_id` na sua própria tabela. Acesso a um tenant é determinado exclusivamente pela existência de um registro `tenant_user` ativo para aquele par. A coluna `users.current_tenant_id` (último tenant selecionado) tem função puramente de UX (retomada de sessão) e não confere autorização por si só.
- **A2 — Profundidade não validada**: a spec não impõe limite máximo de profundidade da árvore; assume-se que a UI e os carregamentos de relações usam paginação / lazy loading suficientes para suportar dezenas de níveis sem mudanças adicionais.
- **A3 — Reset password broker**: o fluxo de redefinição de senha já existente (rotas `password.*`) é tratado como dependência confiável e não passa por mudanças neste refactor além do gatilho automático no fluxo de criação de usuário.
- **A4 — Template sem produção**: alterações destrutivas em arquivos de migration existentes são aceitáveis pois o repositório é template e nenhum ambiente de produção depende do schema atual.
- **A5 — Single-tenant active session**: um usuário sempre tem no máximo 1 tenant ativo na sessão. Acesso simultâneo a múltiplos tenants está fora de escopo desta spec.

## Out of Scope

- **Modelo de autorização (RBAC) e definição de quem pode executar cada operação** — será tratado em spec dedicada (resource-based RBAC). Esta spec apenas referencia "usuário autorizado" como ator, sem definir as regras de autorização.
- Relatórios consolidados que somem dados de múltiplas folhas filhas a partir de um agrupador.
- Cache segmentado por tenant.
- Acesso simultâneo a múltiplos tenants em uma mesma sessão.
- Ferramentas administrativas para mover ou re-pendurar tenants na árvore (drag-and-drop, bulk move).
- Auditoria detalhada de mudanças na hierarquia.
- Tratamentos de falha no envio do e-mail de redefinição (rollback, retry automático, reenvio manual via UI) — adiados para evolução futura.

## Dependencies

- Fluxo de redefinição de senha existente no módulo `auth` (rotas `password.request`, `password.email`, `password.reset`, `password.update`).
- Mecanismo de sessão e autenticação já implementado.
- Sistema de notificações por e-mail já configurado.
- Especificação `001-workspace-management` como linha de base funcional a preservar (exceto pontos removidos).
- Especificação `002-invite-redirect-flow` MUST ser marcada como superseded por esta após implementação.
