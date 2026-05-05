# Contract: Tenant CRUD

Rotas de criação, atualização, listagem e exclusão de tenants. **Autorização**: deferida para spec 005 (esta spec usa Gate stub `tenant.manage`).

---

## `POST /tenant`
Cria novo tenant. Pode ser raiz (`parent_id = null`) ou filho de tenant existente.

| Campo | Tipo | Validação |
|-------|------|-----------|
| `name` | string, required, max:255 | livre — sem unique (FR-009a) |
| `parent_id` | int, nullable, exists:tenants,id | se informado, parent NÃO pode ter `tenant_user` ativo (FR-017); se parent é folha, vira agrupador automaticamente |
| `slug` | string, optional, unique:tenants,slug | gerado de `name` se omitido |

**Validation errors (422)**:
- `parent_id` referencia tenant com vínculos ativos: `"Não é possível adicionar filho a tenant com vínculos. Remova os vínculos primeiro."`
- `parent_id` referencia tenant soft-deleted: `"Tenant pai não está disponível."`

**Success (201/302)**: redirect para `/dashboard` com flash `success`.

**Side effects**: se parent existia como folha, sessões de users com `tenant_id = parent_id` são invalidadas just-in-time na próxima request (R2).

---

## `PATCH /tenant/{tenant}/settings`
Atualiza atributos do tenant (`name`, `description`, `logo_path`).

| Campo | Tipo | Validação |
|-------|------|-----------|
| `name` | string, required, max:255 | |
| `description` | string, nullable, max:1000 | |
| `logo` | file, nullable, image | upload via storage |

Não permite mudar `parent_id` (FR fora de escopo — admin tools).

---

## `DELETE /tenant/{tenant}`
Soft-delete. Falha em qualquer condição que viole FR-021.

**Validation pipeline** (in `DeleteTenantRequest::authorize` + `TenantObserver::deleting`):

1. `$tenant->children()->exists()` → 422 `"Tenant possui filhos; mova ou exclua os filhos antes."`
2. `$tenant->users()->exists()` → 422 `"Tenant possui {N} vínculos ativos."`
3. Possui dados de domínio (qualquer model com `BelongsToTenant` apontando para ele) → 422 `"Tenant possui {N} registros de dados."`

**Success (302)**: redirect com flash `success`. Tenant some de listings/switcher (SoftDeletes scope).

---

## `POST /tenant/{tenant}/restore`
Restaura tenant soft-deletado.

**Validation**:
- Tenant pai (se existir) NÃO pode estar soft-deleted → 422 `"Pai está excluído. Restaure o pai primeiro."`

**Success (302)**: tenant volta para listings.

---

## `GET /tenant/{tenant}/members` (renomeado para `/tenant/{tenant}/users`)
Lista users vinculados ao tenant via `tenant_user`. Read-only.

**Response**: view `tenant::tenant-users.index` com tabela paginada.

---

## Resumo

| Método | Rota | Form Request | View/Redirect |
|--------|------|--------------|---------------|
| POST | `/tenant` | `CreateTenantRequest` | redirect dashboard |
| PATCH | `/tenant/{tenant}/settings` | `UpdateTenantSettingsRequest` | redirect settings |
| DELETE | `/tenant/{tenant}` | `DeleteTenantRequest` | redirect dashboard |
| POST | `/tenant/{tenant}/restore` | `RestoreTenantRequest` | redirect listings |
| GET | `/tenant/{tenant}/users` | — | view |
