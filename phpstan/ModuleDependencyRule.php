<?php

namespace App\PHPStan;

use PhpParser\Node;
use PhpParser\Node\Stmt\Use_;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Enforces module boundaries at two levels:
 *
 *  1. Module-level dependency direction (ALLOWED_DEPENDENCIES).
 *     Module X may only `use` classes from module Y if Y is declared as
 *     a dependency of X.
 *
 *  2. Per-module public API surface (PUBLIC_API).
 *     Each target module exposes a minimal, explicit list of classes that
 *     other modules are allowed to import. Anything outside that list is
 *     considered internal and cannot be referenced from another module.
 *     This prevents implementation details (controllers, requests, internal
 *     services) from leaking across module boundaries.
 *
 * Rules of thumb for editing PUBLIC_API:
 *  - Keep the surface minimal — only add a class when another module actually
 *    needs it.
 *  - Prefer exposing models, traits, enums and events over internal services.
 *  - Controllers, Form Requests, Policies, Middleware and Providers must
 *    never appear in PUBLIC_API.
 *
 * @implements Rule<Use_>
 */
class ModuleDependencyRule implements Rule
{
    /**
     * High-level dependency direction between modules.
     *
     * @var array<string, list<string>>
     */
    private const ALLOWED_DEPENDENCIES = [
        'Auth' => ['User'],
        'User' => ['Permission', 'Tenant'],
        'Permission' => ['User', 'Tenant'],
        'Tenant' => ['User'],
    ];

    /**
     * Public API surface per module. Only the FQCNs listed here may be
     * imported by other modules. Everything else inside `Modules\<X>\` is
     * considered internal.
     *
     * @var array<string, list<string>>
     */
    private const PUBLIC_API = [
        // Auth is an edge module — it only consumes User and exposes nothing.
        'Auth' => [],

        // User exposes its identity model and lifecycle event.
        'User' => [
            'Modules\\User\\Models\\User',
            'Modules\\User\\Events\\UserDeleting',
        ],

        // Permission exposes the role-attaching service and the trait that
        // wires `roles()` onto consumers (User).
        'Permission' => [
            'Modules\\Permission\\Services\\RoleAssigner',
            'Modules\\Permission\\Traits\\HasRoles',
        ],

        // Tenant exposes its aggregate root and the trait that wires
        // `tenants()` onto consumers (User).
        'Tenant' => [
            'Modules\\Tenant\\Models\\Tenant',
            'Modules\\Tenant\\Traits\\HasTenants',
        ],
    ];

    public function getNodeType(): string
    {
        return Use_::class;
    }

    /** @return list<RuleError> */
    public function processNode(Node $node, Scope $scope): array
    {
        $errors = [];
        $currentFile = $scope->getFile();
        $currentModule = $this->getModuleFromPath($currentFile);

        if ($currentModule === null || str_contains($currentFile, '/tests/')) {
            return [];
        }

        foreach ($node->uses as $use) {
            $usedName = $use->name->toString();

            if (! str_starts_with($usedName, 'Modules\\')) {
                continue;
            }

            $importedModule = $this->getModuleFromNamespace($usedName);
            if ($importedModule === null || $importedModule === $currentModule) {
                continue;
            }

            $allowedDeps = self::ALLOWED_DEPENDENCIES[$currentModule] ?? [];
            if (! \in_array($importedModule, $allowedDeps, true)) {
                $errors[] = RuleErrorBuilder::message(
                    "Module '{$currentModule}' cannot depend on module '{$importedModule}'. Allowed: [".implode(', ', $allowedDeps).'].'
                )->build();

                continue;
            }

            $publicApi = self::PUBLIC_API[$importedModule] ?? [];
            if (! \in_array($usedName, $publicApi, true)) {
                $errors[] = RuleErrorBuilder::message(
                    "Cannot import internal class '{$usedName}' from module '{$importedModule}'. Only the public API is exportable; allowed: [".implode(', ', $publicApi).'].'
                )->build();
            }
        }

        return $errors;
    }

    private function getModuleFromPath(string $filePath): ?string
    {
        return preg_match('#app-modules/(\w+)/#', $filePath, $m) ? ucfirst($m[1]) : null;
    }

    private function getModuleFromNamespace(string $namespace): ?string
    {
        return preg_match('#^Modules\\\\(\w+)#', $namespace, $m) ? $m[1] : null;
    }
}
