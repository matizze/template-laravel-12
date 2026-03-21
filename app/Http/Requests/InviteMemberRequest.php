<?php

namespace App\Http\Requests;

use App\Enums\WorkspaceRole;
use App\Models\Invitation;
use App\Models\Member;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InviteMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manageMembers', $this->route('workspace'));
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $workspace = $this->route('workspace');

        return [
            'email' => [
                'required',
                'email',
                function (string $attribute, mixed $value, \Closure $fail) use ($workspace): void {
                    $pendingInvitation = Invitation::where('workspace_id', $workspace->id)
                        ->where('email', $value)
                        ->whereNull('accepted_at')
                        ->exists();

                    if ($pendingInvitation) {
                        $fail('Já existe um convite pendente para este e-mail neste workspace.');
                    }

                    $existingMember = User::where('email', $value)->first();
                    if ($existingMember) {
                        $isMember = Member::where('workspace_id', $workspace->id)
                            ->where('user_id', $existingMember->id)
                            ->exists();

                        if ($isMember) {
                            $fail('Este usuário já é membro deste workspace.');
                        }
                    }
                },
            ],
            'role' => [
                'required',
                Rule::enum(WorkspaceRole::class),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.required' => 'O campo e-mail é obrigatório.',
            'email.email' => 'Informe um e-mail válido.',
            'role.required' => 'O campo função é obrigatório.',
        ];
    }
}
