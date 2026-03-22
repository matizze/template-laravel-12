# Data Model: Modular Monolith Migration

**Feature**: 003-modular-migration
**Date**: 2026-03-21

## Overview

Esta migração não altera o schema da base de dados. As entidades existentes são reorganizadas em módulos. A única mudança é a localização dos ficheiros de model e migrations.

## Entity → Module Mapping

### Módulo Core

Sem entidades de dados. Contém apenas infraestrutura (providers, views, componentes).

### Módulo User

| Entity | Namespace | Tabela | Notas |
|--------|-----------|--------|-------|
| `User` | `Modules\User\Models\User` | `users` | Limpo — sem métodos de workspace. Extensível via traits. |

**Campos (sem alteração)**:
- `id`, `name`, `email`, `password`, `role` (enum: admin/member), `email_verified_at`, `remember_token`, `timestamps`

**Migrations movidas**: `create_users_table`, `create_password_reset_tokens_table`

### Módulo Auth

Sem entidades próprias. Usa `Modules\User\Models\User` para autenticação.

### Módulo Workspace

| Entity | Namespace | Tabela | Notas |
|--------|-----------|--------|-------|
| `Workspace` | `Modules\Workspace\Models\Workspace` | `workspaces` | Tem owner (user_id) e membros via pivot |
| `Member` | `Modules\Workspace\Models\Member` | `members` | Pivot model (user_id, workspace_id, role) |
| `Invitation` | `Modules\Workspace\Models\Invitation` | `invitations` | Convites pendentes |

**Extensão dinâmica do User**:
- Relationships `workspaces()` e `ownedWorkspaces()` registados dinamicamente via `User::resolveRelationUsing()` no `WorkspaceServiceProvider::boot()` — User model NÃO importa nada do módulo Workspace
- `HasWorkspaces` trait mantida apenas com métodos utilitários (`roleIn()`, `isMemberOf()`) usados internamente pelo módulo Workspace (ex: policies)

**Enums**:
- `WorkspaceRole` — `Modules\Workspace\Enums\WorkspaceRole` (admin/member)

**Migrations movidas**: `create_workspaces_table`, `create_members_table`, `create_invitations_table`

## Relationships (sem alteração)

```
User 1──N Workspace (owner via user_id)
User N──N Workspace (membership via members pivot)
Workspace 1──N Member
Workspace 1──N Invitation
Member N──1 User
```

## Cross-Module Access Rules

| From → To | Allowed | Mechanism |
|-----------|---------|-----------|
| Core → qualquer | Proibido | Core é puro |
| User → Core | Sim | Imports directos |
| Auth → Core | Sim | Imports directos |
| Auth → User | Sim | `use Modules\User\Models\User` |
| Workspace → Core | Sim | Imports directos |
| Workspace → User | Sim | `use Modules\User\Models\User` |
| User → Workspace | Proibido | Workspace regista relationships no User via resolveRelationUsing() — User não tem nenhum import de Workspace |
| Auth → Workspace | Proibido | Sem dependência |
| Workspace → Auth | Proibido | Sem dependência |

## Config Changes

### `config/auth.php`

```php
'providers' => [
    'users' => [
        'driver' => 'eloquent',
        'model' => Modules\User\Models\User::class,
    ],
],
```
