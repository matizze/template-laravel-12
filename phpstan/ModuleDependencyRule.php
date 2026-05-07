<?php

namespace App\PHPStan;

use PhpParser\Node;
use PhpParser\Node\Stmt\Use_;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<Use_>
 */
class ModuleDependencyRule implements Rule
{
    /** @var array<string, list<string>> */
    private const ALLOWED_DEPENDENCIES = [
        'User' => [],
        'Permission' => [],
        'Auth' => ['User'],
        'Tenant' => ['User'],
    ];

    /** @var list<string> Allowed cross-module imports (documented extension points) */
    private const ALLOWED_IMPORTS = [
        'Modules\\Tenant\\Traits\\HasTenants',
        'Modules\\Tenant\\Enums\\TenantRole',
        'Modules\\Tenant\\Models\\TenantUser',
        'Modules\\Tenant\\Models\\Tenant',
        // User module attaches roles via the RoleAssigner service and HasRoles trait.
        'Modules\\Permission\\Traits\\HasRoles',
        'Modules\\Permission\\Services\\RoleAssigner',
        // Permission still imports User (BelongsToMany inverse). Documented one-way edge.
        'Modules\\User\\Models\\User',
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

            if (\in_array($usedName, self::ALLOWED_IMPORTS, true)) {
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
