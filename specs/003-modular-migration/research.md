# Research: Modular Monolith Migration

**Feature**: 003-modular-migration
**Date**: 2026-03-21

## R1: Modularização sem dependências externas

**Decision**: Usar Composer path repositories + Laravel package discovery nativos. Zero libs externas.

**Rationale**: InterNACHI/modular não suporta Laravel 13. As funcionalidades que ele fornece (path repos, PSR-4, provider discovery) são todas nativas do Composer e Laravel. O overhead de manter compatibilidade com uma lib externa não compensa para 4 módulos fixos.

**Alternatives considered**:
- InterNACHI/modular: Incompatível com Laravel 13. Adicionaria dependência externa desnecessária.
- nWidart/laravel-modules: Comandos proprietários, não segue convenções Laravel.
- caffeinated/modules: Menos mantido, sem vantagens.

## R2: Composer Path Repositories

**Decision**: Cada módulo tem o seu `composer.json` em `app-modules/{module}/` e é registado como path repository no root `composer.json`.

**Rationale**: É a forma standard do Composer para packages locais. O autoloading PSR-4 é resolvido nativamente pelo Composer. `composer dump-autoload` é tudo o que é preciso.

**Implementation**:
```json
// Root composer.json
{
    "repositories": [
        {"type": "path", "url": "app-modules/core"},
        {"type": "path", "url": "app-modules/user"},
        {"type": "path", "url": "app-modules/auth"},
        {"type": "path", "url": "app-modules/workspace"}
    ],
    "require": {
        "modules/core": "*@dev",
        "modules/user": "*@dev",
        "modules/auth": "*@dev",
        "modules/workspace": "*@dev"
    }
}
```

## R3: Laravel Package Discovery

**Decision**: Cada módulo declara o seu ServiceProvider em `extra.laravel.providers` do `composer.json` do módulo. O Laravel auto-descobre via package discovery nativo.

**Rationale**: É o mecanismo standard que packages como Debugbar, Sanctum, etc. usam. Não requer registo manual em `bootstrap/providers.php`.

**Implementation**:
```json
// app-modules/core/composer.json
{
    "name": "modules/core",
    "autoload": {
        "psr-4": {"Modules\\Core\\": "src/"}
    },
    "extra": {
        "laravel": {
            "providers": ["Modules\\Core\\Providers\\CoreServiceProvider"]
        }
    }
}
```

## R4: ServiceProvider carrega tudo

**Decision**: Cada módulo tem um ServiceProvider que carrega migrations, views, routes, componentes e commands no `boot()`.

**Rationale**: É o padrão Laravel para packages. Cada ServiceProvider é responsável por registar os recursos do seu módulo.

**Implementation**:
```php
// CoreServiceProvider::boot()
public function boot(): void
{
    // Componentes anónimos sem prefixo (Core é global)
    Blade::anonymousComponentPath(__DIR__.'/../../resources/components');

    // Views
    $this->loadViewsFrom(__DIR__.'/../../resources/views', 'core');
}

// WorkspaceServiceProvider::boot()
public function boot(): void
{
    // Migrations
    $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');

    // Views com namespace
    $this->loadViewsFrom(__DIR__.'/../../resources/views', 'workspace');

    // Componentes com prefixo <x-workspace::*>
    Blade::anonymousComponentNamespace('workspace', 'workspace');

    // Routes
    $this->loadRoutesFrom(__DIR__.'/../../routes/web.php');

    // Commands
    if ($this->app->runningInConsole()) {
        $this->commands([...]);
    }
}
```

## R5: Blade Components — Core global vs módulos com prefixo

**Decision**: Core regista componentes sem prefixo via `Blade::anonymousComponentPath()`. Módulos de feature usam prefixo (`<x-workspace::*>`).

**Rationale**: Evita alterar centenas de referências Blade existentes. Core é infraestrutura global por definição.

**Key detail**: `Blade::anonymousComponentPath($path)` sem segundo argumento regista componentes sem namespace. O Laravel resolve `<x-button>` a partir desse path antes de procurar em `resources/views/components/`.

## R6: Migrations — mover sem re-executar

**Decision**: Mover ficheiros de migration para `app-modules/{module}/database/migrations/`. Usar `$this->loadMigrationsFrom()` no ServiceProvider.

**Rationale**: A tabela `migrations` do Laravel regista pelo nome do ficheiro, não pelo path. Mover o ficheiro é seguro — o Laravel não tenta re-executar.

## R7: Testes em módulos

**Decision**: Testes dentro de cada módulo em `app-modules/{module}/tests/`. Actualizar `phpunit.xml` com test suites adicionais.

**Implementation**: Adicionar ao phpunit.xml:
```xml
<testsuite name="Core">
    <directory>app-modules/core/tests</directory>
</testsuite>
<testsuite name="User">
    <directory>app-modules/user/tests</directory>
</testsuite>
<testsuite name="Auth">
    <directory>app-modules/auth/tests</directory>
</testsuite>
<testsuite name="Workspace">
    <directory>app-modules/workspace/tests</directory>
</testsuite>
```

## R8: Dependency Enforcement

**Decision**: Script CI + regra PHPStan.

**Implementation**:
- Script bash que grep `use Modules\` statements e valida contra regras permitidas
- Regra PHPStan customizada para proibir imports ilegais
- Core não pode ter `use Modules\User\*`, `use Modules\Auth\*`, `use Modules\Workspace\*`
- User não pode ter `use Modules\Auth\*`, `use Modules\Workspace\*`
