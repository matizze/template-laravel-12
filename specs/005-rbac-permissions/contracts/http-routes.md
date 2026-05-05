# Contract: HTTP Routes

**Phase**: 1 — Design & Contracts
**Owns**: `app-modules/permissions/routes/web.php`

## Convenções

- Todas as rotas sob middleware `auth`.
- Tenant ativo (`Tenant::current()`) é pré-requisito; rotas que não conseguem operar sem ele retornam 403 via Gate (cobertura natural do `Gate::before`).
- Métodos HTTP seguem REST padrão Laravel.
- Form Requests cuidam de `authorize()` (com `can('core.role.manage')` etc.) e validação.
- Flash messages: `success`, `error`, `warning`, `info` (PHPFlasher convention do projeto).

## Rotas

```php
// app-modules/permissions/routes/web.php
use Illuminate\Support\Facades\Route;
use Modules\Permissions\Http\Controllers\RoleController;
use Modules\Permissions\Http\Controllers\TenantUserRoleController;

Route::middleware('auth')->group(function () {

    Route::resource('roles', RoleController::class)
        ->except(['show'])
        ->names([
            'index' => 'roles.index',
            'create' => 'roles.create',
            'store' => 'roles.store',
            'edit' => 'roles.edit',
            'update' => 'roles.update',
            'destroy' => 'roles.destroy',
        ]);

    // Atualiza role_id de um vínculo tenant_user (atribuir/desatribuir/trocar)
    Route::patch('tenant-users/{tenantUser}/role', [TenantUserRoleController::class, 'update'])
        ->name('tenant-users.role.update');
});
```

## Mapa endpoint → guard → response

| Método | URI | Nome | Gate | Sucesso | Erros |
|--------|-----|------|------|---------|-------|
| GET | `/roles` | `roles.index` | `core.role.manage` | 200 + lista paginada (escopada por tenant atual) | 403 sem permissão |
| GET | `/roles/create` | `roles.create` | `core.role.manage` | 200 + form (catálogo do `config/permissions.php` populado como árvore de checkboxes) | 403 |
| POST | `/roles` | `roles.store` | `core.role.manage` (via `StoreRoleRequest::authorize`) | 302 redirect `roles.index` + flash `success` | 422 validação, 403 sem permissão |
| GET | `/roles/{role}/edit` | `roles.edit` | `core.role.manage` + tenant match | 200 + form preenchido | 403 ou 404 (cross-tenant) |
| PUT/PATCH | `/roles/{role}` | `roles.update` | `core.role.manage` + tenant match | 302 + flash `success` | 422, 403, 404 |
| DELETE | `/roles/{role}` | `roles.destroy` | `core.role.manage` + tenant match | 302 + flash `success` | 403, 404, 409 (se houver vínculos `tenant_user` apontando para essa role — `restrictOnDelete`) |
| PATCH | `/tenant-users/{tenantUser}/role` | `tenant-users.role.update` | `core.role.assign` (via `AssignTenantUserRoleRequest::authorize`) | 302 + flash `success` | 422, 403, 404 |

## Payloads

### `POST /roles` e `PUT /roles/{role}`

Form encoded ou JSON. Campos:

```
name:        string, required, max:255, unique per tenant
permissions: array (objeto JSON aninhado em 3 níveis)
permissions.{module}.{resource}: array of strings (actions)
```

Exemplo JSON:
```json
{
    "name": "Gestor Financeiro",
    "permissions": {
        "finance": {
            "order": ["view", "approve"]
        },
        "core": {
            "user": ["view"]
        }
    }
}
```

Exemplo form-encoded (vindo da árvore de checkboxes do form Blade):
```
name=Gestor+Financeiro
permissions[finance][order][]=view
permissions[finance][order][]=approve
permissions[core][user][]=view
```

> Validação aplicada pela rule `ValidPermissionsTree` confirma que cada combinação `module.resource.action` existe em `config/permissions.php`.

### `PATCH /tenant-users/{tenantUser}/role`

```
role_id: integer, required, exists:roles where tenant_id = Tenant::current()->id
```

Exemplo:
```
role_id=7
```

> O `tenantUser` vem da rota (route model binding); validações adicionais (cross-tenant) feitas pelo controller para defesa em profundidade.

## Tenant isolation enforcement

Em **todos** os endpoints que recebem `{role}` ou `{tenantUser}`:

- Form Request usa `Rule::exists('roles')->where('tenant_id', Tenant::current()?->id)` para `role_id`.
- Controller adicionalmente aborta com 404 se `$role->tenant_id !== Tenant::current()?->id` (defesa em profundidade — evita 403 que enumeraria existência cross-tenant).
- Para `{tenantUser}`: o controller verifica `$tenantUser->tenant_id === Tenant::current()?->id` antes de prosseguir.

## Flash messages

```php
return redirect()->route('roles.index')->with('success', 'Perfil criado.');
return back()->with('error', 'Não foi possível atribuir o perfil.');
```

> Per CLAUDE.md, NUNCA usar `->with('status', ...)` ou `->with('message', ...)` — PHPFlasher não intercepta essas chaves.

## Test points (PHPUnit feature)

| Cenário | Teste |
|---------|-------|
| US2.1 (criar role com permissões válidas) | POST /roles → 302, role + JSON persistido |
| US2.2 (atribuir role a vínculo) | PATCH /tenant-users/{tu}/role → 302, role_id atualizado |
| US2.3 (alterar permissions de uma role reflete no user) | PUT /roles/{role} muda JSON; user vinculado vê novas permissões na próxima checagem |
| US2.4 (cross-tenant invisível) | GET /roles/{otherTenantRole}/edit → 404 |
| US2.5 (vínculo desvinculado pelo módulo Tenant) | unit test do listener de cache invalida o `(user, tenant)` |
| US2.6 (sem core.role.manage) | POST /roles sem permissão → 403 |
| US2.7 (sem core.role.assign) | PATCH /tenant-users/.../role sem permissão → 403 |
| US2.8 (combinação inexistente) | POST /roles com `permissions: {finance: {foo: [bar]}}` → 422 com mensagem identificando a combinação inválida |
| Edge (delete role com vínculos) | DELETE /roles/{role} quando há tenant_user com esse role_id → 409 (RESTRICT) |
