<?php

namespace Modules\Tenant\Http\Requests;

use App\Support\Ability;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Modules\Tenant\Models\Tenant;
use Modules\Tenant\Rules\ParentHasNoActiveLinks;

class CreateTenantRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $name = $this->input('name');
        $slug = $this->input('slug');

        if (! $slug && \is_string($name) && $name !== '') {
            $this->merge(['slug' => Str::slug($name)]);
        }
    }

    public function authorize(): bool
    {
        $user = $this->user();
        if (! $user) {
            return false;
        }

        if ($user->can(Ability::TENANTS_CREATE)) {
            return true;
        }

        $parentId = $this->input('parent_id');
        if ($parentId === null) {
            return false;
        }

        $parent = Tenant::find($parentId);
        if (! $parent) {
            return false;
        }

        return $user->isMemberOf($parent);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:tenants,slug'],
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
