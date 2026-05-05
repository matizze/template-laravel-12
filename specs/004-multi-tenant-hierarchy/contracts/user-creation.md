# Contract: User Creation (US3)

Substitui o fluxo de Invitation. Cria User como entidade global (sem nenhum vínculo `tenant_user`) e dispara reset de senha.

---

## `POST /users`

| Campo | Tipo | Validação |
|-------|------|-----------|
| `name` | string, required, max:255 | |
| `email` | string, required, email, unique:users,email | FR-013 |

**Authorization**: Gate `users.create` (stub nesta spec — definição em 005). Throttle: `throttle:5,1` (FR-014, alinhado com login).

**Success flow**:
1. Valida payload via `CreateUserRequest`.
2. Cria User com password aleatório (`Str::random(40)`) — usuário NUNCA usará essa senha.
3. Dispara reset:
   ```php
   Password::broker()->sendResetLink(['email' => $user->email]);
   ```
4. Redirect com flash `success`: `"Usuário criado. E-mail de definição de senha enviado para {email}."`
5. **Sem criação de `tenant_user`** (FR-010) — fluxo separado em [tenant-user-link.md](./tenant-user-link.md).

**Validation errors (422)**:
- `email` já existe: `"Já existe um usuário com este e-mail."`
- `email` formato inválido: `"E-mail inválido."`

**Side effects**:
- Job `SendPasswordResetNotification` enfileirado (broker padrão).
- **Não há rollback transacional** se o e-mail falhar — clarificação 2026-05-05 explicitamente difere isso para evolução futura. Errors do mailer são logados, User permanece criado.

---

## Reset password (existente)

Reaproveitamento integral das rotas existentes no módulo `auth`:

| Rota | Origem |
|------|--------|
| `password.email` (POST `/forgot-password`) | trigger manual de reset |
| `password.reset` (GET `/reset-password/{token}`) | view |
| `password.update` (POST `/reset-password`) | submit |

**Esta spec não modifica nenhuma dessas rotas.**

---

## Diagrama de fluxo

```
Admin                    Sistema                    Novo User
  │  POST /users           │                          │
  ├──────────────────────► │                          │
  │                        │  cria User (senha rand)  │
  │                        │  Password::sendResetLink │
  │                        ├─────────────► email ────►│
  │  ◄────────── 302       │                          │
  │  flash success         │                          │
  │                        │                          │ clica link
  │                        │  GET /reset-password/{t} │
  │                        │  ◄───────────────────────┤
  │                        │                          │
  │                        │  POST /reset-password    │
  │                        │  ◄───────────────────────┤
  │                        │  define senha + login    │
  │                        │                          │ ┌──────────────────┐
  │                        │                          │ │ "Aguarde tenant" │
  │                        │                          │ │ até admin atribuir│
  │                        │                          │ └──────────────────┘
```

Após o user logar pela primeira vez, ele cai na tela "no-access" (R5) até receber pelo menos um vínculo via [tenant-user-link.md](./tenant-user-link.md).
