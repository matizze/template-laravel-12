<?php

namespace Modules\Tenant\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Modules\Tenant\Enums\TenantRole;
use Modules\Tenant\Models\Tenant;
use Modules\User\Models\User;

class DetachTenantUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manageTenantUsers', $this->route('tenant'));
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
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
            /** @var User|null $user */
            $user = $this->route('user');

            if (! $tenant || ! $user) {
                return;
            }

            $link = $tenant->tenantUsers()->where('user_id', $user->id)->first();

            if (! $link) {
                $v->errors()->add('user', 'Usuário não vinculado.');

                return;
            }

            if ($link->role === TenantRole::Owner) {
                $owners = $tenant->tenantUsers()->where('role', TenantRole::Owner)->count();

                if ($owners <= 1) {
                    $v->errors()->add('user', 'Não é possível remover o último proprietário.');
                }
            }
        });
    }
}
