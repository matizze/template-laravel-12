# Quickstart: RBAC Permissions System

**Feature**: 005-rbac-permissions
**Audience**: Desenvolvedores que vão consumir o sistema RBAC após implementação.

> Este guia descreve como **usar** a feature uma vez que ela esteja implementada. Para implementar, ver `plan.md`, `data-model.md`, `contracts/`.

## 1. Verificar uma permissão em runtime

Use **APIs Laravel idiomáticas**. Não há helper global novo.

### Em Blade

```blade
@can('finance.order.create')
    <x-button href="{{ route('orders.create') }}">Criar Pedido</x-button>
@endcan
```

### Em Controller (recomendado: gera 403 automático)

```php
public function store(Request $request)
{
    $this->authorize('finance.order.create');

    // ...lógica de criação...
}
```

ou:
```php
abort_if(! $request->user()->can('finance.order.create'), 403);
```

### Em rota (middleware)

```php
Route::post('/orders', [OrderController::class, 'store'])
    ->middleware(['auth', 'can:finance.order.create']);
```

### Direto via Gate facade

```php
if (Gate::allows('finance.order.create')) {
    // ...
}
```

> **Comportamento**: retorna `false` em todos os casos onde:
> - usuário não está autenticado
> - não há `Tenant::current()` ativo
> - usuário não tem vínculo `tenant_user` no tenant ativo
> - vínculo tem `role_id` NULL
> - permissão não existe na role do user
> - permissão consultada não está em `config/permissions.php`
>
> Sem exceções. Falha segura.

## 2. Adicionar uma combinação ao catálogo

Editar `config/permissions.php`:

```php
return [
    // ... existentes ...
    'finance' => [
        'order' => ['view', 'create', 'update', 'delete', 'approve', 'cancel'],
        'invoice' => ['view', 'send', 'refund'], // NOVA
    ],
];
```

Submeter PR. Após merge + deploy, a combinação `finance.invoice.send` (etc.) está disponível para ser adicionada a roles.

## 3. Criar um perfil (role) dentro do app

**Pré-requisito**: usuário logado precisa ter `core.role.manage` no tenant ativo.

### Via UI

1. Navegar para `/roles` (link `roles.index`).
2. Clicar em "Novo Perfil".
3. Preencher nome + selecionar permissões disponíveis (árvore de checkboxes carregada do `config/permissions.php`).
4. Salvar.

### Via teste / programaticamente (em factory state)

```php
$role = Role::create([
    'tenant_id' => Tenant::current()->id,
    'name' => 'Gestor Financeiro',
    'permissions' => [
        'finance' => [
            'order' => ['view', 'approve'],
        ],
        'core' => [
            'user' => ['view'],
        ],
    ],
]);
```

## 4. Atribuir um perfil a um usuário (via vínculo `tenant_user`)

**Pré-requisito**: usuário logado precisa ter `core.role.assign` no tenant ativo. O usuário-alvo precisa ter um vínculo `tenant_user` no tenant atual (criado pelo módulo Tenant via FR-015a do 004).

### Via UI

1. Navegar para a página do vínculo `tenant_user` do usuário (módulo Tenant: `/tenant-users/{id}`).
2. Selecionar perfil disponível e salvar.
3. Por baixo dos panos: PATCH em `/tenant-users/{id}/role` com `role_id`.

### Via teste / programaticamente

```php
$tenantUser = $user->tenantUsers()
    ->where('tenant_id', Tenant::current()->id)
    ->first();

$tenantUser->update(['role_id' => $role->id]);
```

> O listener `InvalidatePermissionCache` invalida automaticamente o cache do usuário no tenant.

## 5. Roles default (criadas automaticamente em cada tenant novo)

Quando um novo tenant é criado (via `Tenant::create()` no módulo Tenant), o `DefaultRolesSeeder` cria 4 roles:

| Role | Permissões |
|------|------------|
| **Owner** | TODO o catálogo (`config/permissions.php`) |
| **Admin** | `core.role.manage`, `core.role.assign`, e todas exceto delete sensitivas |
| **Member** | leitura + criação básica |
| **Viewer** | apenas `view`/`list` |

O criador do tenant (que invocou `Tenant::create()`) recebe automaticamente o vínculo `tenant_user` com a role **Owner**. Substitui o enum `TenantRole` que o 004 entregou como placeholder.

> **Em testes**: factory `RoleFactory::withFullCatalog()` para Owner; ou rodar `DefaultRolesSeeder` no `setUp()`.

## 6. Limitações conhecidas

- **Sem wildcards**: `can('finance.*')` retorna `false`. Use o nome exato.
- **Sem hierarquia de roles**: roles não herdam.
- **1 role por vínculo `tenant_user`**: composição via "role agregada" (criar uma role com todas as permissões necessárias).
- **Sem permissões diretas a usuário**: sempre via role atribuída ao vínculo.
- **Janela de inconsistência ≤ 60s**: TTL é o teto se um listener falhar.
- **Catálogo via PR + deploy**: adicionar permissão nova exige código.

## 7. Troubleshooting

### "Usuário deveria ter permissão mas `can()` retorna false"

Verificar nesta ordem:

1. **`Tenant::current()` está populado?** Sem isso, retorna `false`. Cheque o middleware `SetCurrentTenant` (do módulo Tenant).
2. **`auth()->check()` é true?** Sem auth, retorna `false`.
3. **A permissão está em `config/permissions.php`?**
   ```php
   array_key_exists('finance', config('permissions'))
       && array_key_exists('order', config('permissions.finance'))
       && in_array('create', config('permissions.finance.order'), true)
   ```
4. **O usuário tem vínculo `tenant_user` com `role_id` no tenant ativo?**
   ```php
   $user->tenantUsers()
       ->where('tenant_id', Tenant::current()->id)
       ->whereNotNull('role_id')
       ->with('role')
       ->first();
   ```
5. **A role do vínculo tem a permissão no JSON?**
   ```php
   $tenantUser->role->permissions['finance']['order'] ?? []; // espera array com 'create'
   ```
6. **Cache obsoleto?** Forçar flush:
   ```php
   app(PermissionResolver::class)->flushUser($user->id, Tenant::current()->id);
   ```

### "Adicionei permissão no config mas validação rejeita"

- O config é carregado via cache em produção (`config:cache`). Rodar `php artisan config:clear` ou redeployar.
- Em testes, garantir que o config está sendo carregado pelo `TestCase`.

### "Em testes, `can()` sempre retorna false"

Provável causa: `setUp()` não popula `Tenant::current()` OU não cria vínculo `tenant_user`. Em feature tests:

```php
$tenant = Tenant::factory()->create();
$role = Role::factory()->for($tenant)->withFullCatalog()->create();
$user = User::factory()->create();
$user->tenantUsers()->create([
    'tenant_id' => $tenant->id,
    'role_id' => $role->id,
]);
Tenant::setCurrent($tenant->id);
$this->actingAs($user);
```

### "Cache não invalida quando edito role"

- Verificar se `InvalidatePermissionCache` está registrado em `PermissionsServiceProvider::boot()`.
- Verificar se o evento `eloquent.saved: Modules\Permissions\Models\Role` está sendo disparado (não usar mass `Role::query()->update(...)` — não dispara eventos).
