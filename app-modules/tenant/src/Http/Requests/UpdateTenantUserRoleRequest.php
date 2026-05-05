<?php

namespace Modules\Tenant\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use Modules\Tenant\Enums\TenantRole;

class UpdateTenantUserRoleRequest extends FormRequest
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
        return [
            'role' => ['required', new Enum(TenantRole::class)],
        ];
    }
}
