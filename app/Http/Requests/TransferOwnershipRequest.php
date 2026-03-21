<?php

namespace App\Http\Requests;

use App\Models\Member;
use Illuminate\Foundation\Http\FormRequest;

class TransferOwnershipRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('transferOwnership', $this->route('workspace'));
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $workspace = $this->route('workspace');

        return [
            'user_id' => [
                'required',
                'exists:users,id',
                function (string $attribute, mixed $value, \Closure $fail) use ($workspace): void {
                    $isMember = Member::where('workspace_id', $workspace->id)
                        ->where('user_id', $value)
                        ->exists();

                    if (! $isMember) {
                        $fail('O usuário selecionado não é membro deste workspace.');
                    }
                },
            ],
        ];
    }
}
