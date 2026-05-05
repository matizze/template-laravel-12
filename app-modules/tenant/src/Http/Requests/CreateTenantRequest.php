<?php

namespace Modules\Tenant\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Modules\Tenant\Enums\TenantRole;
use Modules\Tenant\Models\Tenant;
use Modules\Tenant\Rules\ParentHasNoActiveLinks;

class CreateTenantRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if (! $user) {
            return false;
        }

        $parentId = $this->input('parent_id');
        if ($parentId === null) {
            // Bootstrap (no tenants exist yet) OR creating a root tenant — any
            // authenticated user can do it. Granular RBAC is deferred to spec 005.
            return true;
        }

        $parent = Tenant::find($parentId);
        if (! $parent) {
            return true; // exists rule will reject; let validation produce the 422
        }

        // To create a child under an existing tenant, the user must either
        // (a) currently hold Owner/Admin role on the parent, OR
        // (b) be the original creator of that parent (`tenants.user_id`).
        //
        // Option (b) is required because FR-017 forces the parent to have zero
        // active links before becoming a grouper — meaning the creator may have
        // already detached their own link, leaving (a) impossible while still
        // intending to evolve their own hierarchy. This closes the C3 vector
        // (stranger hijacking children under someone else's tenant) without
        // breaking the legitimate creator flow.
        $role = $user->roleIn($parent);
        if ($role === TenantRole::Owner || $role === TenantRole::Admin) {
            return true;
        }

        return $parent->getAttribute('user_id') === $user->getAttribute('id');
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:tenants,slug'],
            'description' => ['nullable', 'string'],
            'parent_id' => ['nullable', 'integer', 'exists:tenants,id', new ParentHasNoActiveLinks],
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
            'slug.unique' => 'Este slug já está em uso.',
            'parent_id.exists' => 'O tenant pai selecionado não existe.',
        ];
    }
}
