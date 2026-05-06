<?php

namespace Modules\Tenant\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Tenant\Models\Tenant;

class UpdateTenantSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $tenant = $this->route('tenant');

        if (! $tenant instanceof Tenant) {
            return false;
        }

        return $this->user()?->can('update', $tenant) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', Rule::unique('tenants', 'slug')->ignore($this->route('tenant'))],
            'description' => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'O campo nome é obrigatório.',
            'name.max' => 'O nome não pode ter mais de 255 caracteres.',
            'slug.required' => 'O campo slug é obrigatório.',
            'slug.unique' => 'Este slug já está em uso.',
        ];
    }
}
