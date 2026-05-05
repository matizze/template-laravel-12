<?php

namespace Modules\Tenant\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Modules\Tenant\Models\Tenant;

class RestoreTenantRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if (! $user) {
            return false;
        }

        $tenantId = (int) $this->route('tenantId');
        $tenant = Tenant::onlyTrashed()->find($tenantId);

        if (! $tenant) {
            return false;
        }

        return $user->can('restore', $tenant);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v): void {
            $tenantId = (int) $this->route('tenantId');
            $tenant = Tenant::onlyTrashed()->find($tenantId);

            if (! $tenant) {
                return;
            }

            $parentId = $tenant->getAttribute('parent_id');

            if ($parentId !== null) {
                /** @var Tenant|null $parent */
                $parent = Tenant::withTrashed()->find($parentId);

                if ($parent && $parent->trashed()) {
                    $v->errors()->add('tenant', 'Pai está excluído. Restaure o pai primeiro.');
                }
            }
        });
    }
}
