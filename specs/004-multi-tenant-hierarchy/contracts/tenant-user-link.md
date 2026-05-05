# Contract: TenantUser Link Management (US4)

Rotas para criar e revogar vínculos `tenant_user`.

---

## `POST /tenant/{tenant}/users`

Vincula um User existente ao tenant especificado.

**Request body**:

| Campo | Tipo | Validação |
|-------|------|-----------|
| `user_id` | int, required, exists:users,id | |
| `role` | string, required, in:owner,admin,member,viewer | enum `TenantRole` |

**Validation** (in `AttachTenantUserRequest`):
1. `tenant` (route param) MUST ser operável (folha) — FR-016. Custom rule `IsOperableTenant` → 422 `"Vínculo só é aceito em tenants operáveis (folhas)."`
2. `tenant` MUST não estar soft-deleted → 422.
3. UNIQUE(`user_id`, `tenant_id`) MUST não estar violado → 422 `"Usuário já vinculado a este tenant."`

**Success (201/302)**: cria registro `tenant_user`, redirect com flash `success`. User passa a ver tenant no switcher.

**Hooks**:
- Eloquent `creating` no `TenantUser` confirma operability como defesa em profundidade.

---

## `PATCH /tenant/{tenant}/users/{tenantUser}`

Atualiza role de um vínculo existente.

| Campo | Tipo | Validação |
|-------|------|-----------|
| `role` | string, required, in:owner,admin,member,viewer | |

**Success (302)**: flash `success`.

---

## `DELETE /tenant/{tenant}/users/{tenantUser}`

Revoga vínculo. **Side effect**: na próxima request, se a sessão do user revogado aponta para `$tenant`, middleware invalida e redireciona (R2 — sem broadcast ativo).

**Validation**:
- Não permite revogar o próprio vínculo se o user é o último Owner do tenant (preservar regra existente do `MemberController`) → 422 `"Não é possível remover o último Owner."`

**Success (302)**: flash `success`, registro removido.

---

## Resumo

| Método | Rota | Form Request | Side effect |
|--------|------|--------------|-------------|
| POST | `/tenant/{tenant}/users` | `AttachTenantUserRequest` | switcher do user atualizado |
| PATCH | `/tenant/{tenant}/users/{tenantUser}` | `UpdateTenantUserRoleRequest` | — |
| DELETE | `/tenant/{tenant}/users/{tenantUser}` | `DetachTenantUserRequest` | sessão invalidada just-in-time |

---

## Diferença vs `Workspace::invite`

A rota antiga `POST /workspace/{workspace}/invite` aceitava email (eventualmente criando um `Invitation` token). **Removida**. Substituída por:

1. `POST /users` (US3, ver [user-creation.md](./user-creation.md)) — cria User global se não existir.
2. `POST /tenant/{tenant}/users` (este contrato) — atribui vínculo a User existente.

Os dois fluxos são **separados** por design (clarification 2026-05-05).
