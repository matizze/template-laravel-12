<?php

namespace Modules\Tenant\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Modules\Tenant\Models\Tenant;

/**
 * Ensures the chosen parent tenant has no active user links (FR-017).
 *
 * Once a tenant has at least one user linked, it cannot become a grouper
 * (i.e. it cannot have children). To convert it, the user links must be
 * removed first.
 */
class ParentHasNoActiveLinks implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null) {
            return;
        }

        $parent = Tenant::find($value);

        if (! $parent) {
            return;
        }

        if ($parent->users()->exists()) {
            $fail('Não é possível adicionar filho a um tenant que possui vínculos ativos. Remova os vínculos primeiro.');
        }
    }
}
