# 🧠 Arquitetura de Permissões + Multi-Tenancy (Laravel)

## 📌 Visão Geral

Este documento descreve a arquitetura combinada de:

- Multi-Tenancy hierárquico
- Permissionamento baseado em Roles
- Permissões em árvore (JSON)
- Integração com Gate do Laravel

O objetivo é:

✔ Isolamento de dados por tenant  
✔ Flexibilidade de permissões  
✔ Simplicidade de uso (@can)  
✔ Escalabilidade sem complexidade excessiva

---

# 🧩 Parte 1 — Multi-Tenancy

## 🧠 Conceito

- Existe uma única tabela de tenants
- Um tenant pode ter um parent_id
- A estrutura forma uma árvore hierárquica

---

## 🧱 Estrutura

### 🧱 tenants

```sql
- id
- parent_id (nullable)
- name
```

---

## 🌳 Exemplo

```text
Empresa (agrupador)
├── Base A (operacional)
└── Base B (operacional)
```

---

## 🧠 Regras

### ✔ Tenant pode ser:

- Agrupador → possui filhos
- Operacional → não possui filhos (folha da árvore)

---

## ⚠️ Regra principal

👉 Somente tenants folha são operáveis diretamente

Mas:

👉 qualquer tenant pode ser usado como filtro

---

## 🎯 Comportamento esperado

| Seleção | Resultado                         |
| ------- | --------------------------------- |
| Base A  | dados da Base A                   |
| Base B  | dados da Base B                   |
| Empresa | NÃO mistura dados automaticamente |

👉 sempre isolamento por tenant selecionado

---

## 🧠 Regra de acesso

- Toda operação usa um tenant ativo
- Nunca misturar dados entre tenants

---

## 📌 Implementação

### Campo padrão nas tabelas

```sql
tenant_id
```

---

### Uso

- queries sempre filtram por tenant_id
- relacionamentos apontam para tenant específico

---

---

# 🔐 Parte 2 — Permissionamento

## 🧠 Conceito

- Usuário possui múltiplos perfis (roles)
- Cada role define permissões via JSON
- Permissões seguem estrutura hierárquica

---

## 📊 Relacionamentos

```text
User
├── belongsToMany Role

Role
├── permissions (json)
```

---

## 🧱 Estrutura

### 🧱 roles

```sql
- id
- name
- tenant_id
- permissions (json)
```

---

### 🧱 user_roles

```sql
- user_id
- role_id
```

---

# 🌳 Estrutura de Permissões

## 🧠 Formato

```text
node.node.node.action
```

---

## Exemplo

```text
rh.aso.exames.create
finance.order.approve
```

---

## 📁 Configuração

### 📁 config/permissions.php

```php
return [
    'tree' => [
        'rh' => [
            'aso' => [
                'exames' => [
                    'actions' => ['create', 'delete']
                ]
            ]
        ],
        'finance' => [
            'order' => [
                'actions' => ['view', 'approve']
            ]
        ],
    ],
];
```

---

## 🧠 Regras

- Profundidade livre
- Último nível sempre é action
- Estrutura é usada para:
    - UI
    - validação
    - organização

---

# ⚙️ PermissionService

## 📁 app/Services/PermissionService.php

Responsável por:

- Resolver permissões do usuário
- Combinar múltiplas roles
- Fazer flatten da árvore

---

# 🔗 Integração com Laravel Gate

## 📁 app/Providers/AuthServiceProvider.php

```php
use Illuminate\Support\Facades\Gate;
use App\Services\PermissionService;

public function boot()
{
    Gate::before(function ($user, $ability) {
        return app(PermissionService::class)->check($user, $ability);
    });
}
```

---

# 🚀 Uso

## Blade

```blade
@can('rh.aso.create')
    <button>Criar</button>
@endcan
```

---

## Controller

```php
$this->authorize('rh.aso.create');
```

---

## Middleware

```php
Route::middleware('can:rh.aso.create');
```

---

# ⚠️ Regras Importantes

## ✔ Permissões são acumulativas

Se o usuário possui múltiplas roles:

👉 todas as permissões são somadas

---

## ✔ Isolamento por tenant

- Permissões respeitam o tenant ativo
- Roles podem ser filtradas por tenant

---

## ✔ Sempre usar o PermissionService

Nunca acessar JSON diretamente

---

## ✔ Cache obrigatório

Melhora performance do @can

---

# 🧠 Fluxo completo

```text
User → Roles → Permissions(JSON)
        ↓
PermissionService
        ↓
Flatten
        ↓
Cache
        ↓
Gate
        ↓
@can / authorize
```

---

# 💥 Resultado Final

Sistema:

✔ Multi-tenant hierárquico  
✔ Permissões flexíveis em árvore  
✔ Uso simples (@can)  
✔ Sem complexidade de múltiplas tabelas  
✔ Alta performance com cache  
✔ Pronto para escalar
