<?php

namespace Modules\Tenant\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Modules\Tenant\Models\Tenant;

class DeleteTenantRequest extends FormRequest
{
    public function authorize(): bool
    {
        $tenant = $this->route('tenant');

        if (! $tenant instanceof Tenant) {
            return false;
        }

        return $this->user()?->can('delete', $tenant) ?? false;
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
            /** @var Tenant|null $tenant */
            $tenant = $this->route('tenant');

            if (! $tenant instanceof Tenant) {
                return;
            }

            if ($tenant->children()->exists()) {
                $v->errors()->add('tenant', 'Tenant possui filhos; mova ou exclua os filhos antes.');

                return;
            }

            $linkCount = $tenant->users()->count();

            if ($linkCount > 0) {
                $v->errors()->add('tenant', 'Tenant possui '.$linkCount.' vínculo(s) ativo(s).');
            }
        });
    }
}
