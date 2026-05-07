<?php

namespace Modules\Tenant\Http\Requests;

use App\Support\Ability;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Modules\Tenant\Models\Tenant;

class AttachTenantUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        $tenant = $this->route('tenant');

        if (! $tenant instanceof Tenant) {
            return false;
        }

        return $this->user()?->can(Ability::TENANTS_USERS_ATTACH, $tenant) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v): void {
            /** @var Tenant|null $tenant */
            $tenant = $this->route('tenant');

            if (! $tenant) {
                return;
            }

            if ($tenant->trashed() || ! $tenant->isOperable()) {
                $v->errors()->add('tenant', 'Vínculo só é aceito em tenants operáveis (folhas).');

                return;
            }

            $userId = $this->input('user_id');

            if ($userId && $tenant->users()->whereKey($userId)->exists()) {
                $v->errors()->add('user_id', 'Usuário já vinculado a este tenant.');
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'user_id.required' => 'Selecione um usuário.',
            'user_id.exists' => 'Usuário não encontrado.',
        ];
    }
}
