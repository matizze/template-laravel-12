---
name: dusk-url-check
enabled: true
event: bash
pattern: artisan\s+dusk|phpunit.*Browser|phpunit.*Dusk
action: warn
---

**Antes de rodar Dusk, verifica:**

1. **APP_URL** no `.env.dusk.local` — deve apontar para o server que está rodando:
   - `composer dev` → `APP_URL=http://localhost:8000`
   - Herd → usa `mcp__herd__get_site_information` para descobrir a URL correcta
2. **`.env.dusk.local` deve ser COMPLETO** — copiar do `.env` e só mudar o APP_URL (`cp .env .env.dusk.local && sed -i '' 's|APP_URL=.*|APP_URL=http://localhost:8000|' .env.dusk.local`)
3. **Server deve estar rodando** — confirma que `curl -s -o /dev/null -w "%{http_code}" $APP_URL/auth/login` retorna 200
4. **Chromedriver** — se falhar com "Invalid path to Chromedriver", rodar `php artisan dusk:chrome-driver --detect`
5. **Em worktree** — o Herd aponta para o repo principal, não a worktree. Usa `php artisan serve` ou `composer dev` na worktree.
6. **Rodar Dusk via `php artisan dusk`** (não `vendor/bin/phpunit`) — só o artisan faz o swap do `.env` para `.env.dusk.local`
