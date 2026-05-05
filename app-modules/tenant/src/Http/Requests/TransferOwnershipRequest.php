<?php

namespace Modules\Tenant\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class TransferOwnershipRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('transferOwnership', $this->route('tenant'));
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $tenant = $this->route('tenant');

        return [
            'user_id' => [
                'required',
                'exists:users,id',
                function (string $attribute, mixed $value, \Closure $fail) use ($tenant): void {
                    $isMember = $tenant->tenantUsers()
                        ->where('user_id', $value)
                        ->exists();

                    if (! $isMember) {
                        $fail('O usuário selecionado não é membro deste tenant.');
                    }
                },
            ],
        ];
    }
}
