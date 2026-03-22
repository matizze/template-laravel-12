---
name: dusk-screenshot-diagnosis
enabled: true
event: bash
conditions:
  - field: command
    operator: regex_match
    pattern: tests/Browser/screenshots/.*\.(png|jpg)
action: warn
---

**Diagnóstico rápido de screenshots Dusk:**

- **502 Bad Gateway (nginx)** → O Dusk está a usar o URL do Herd mas o site não está activo ou aponta para outro directório. Corrige o `APP_URL` no `.env.dusk.local`.
- **500 Server Error** → O `.env.dusk.local` está incompleto (falta DB_CONNECTION, SESSION_DRIVER, etc.). Recria com `cp .env .env.dusk.local` e só muda o APP_URL.
- **Página em branco** → Assets não estão compilados. Roda `npm run build` ou garante que o Vite dev server está activo.
- **419 CSRF Token Mismatch** → Sessão expirada ou domínio errado. Verifica SESSION_DOMAIN no `.env.dusk.local`.

**A solução é quase sempre: corrigir o APP_URL no `.env.dusk.local`.**
