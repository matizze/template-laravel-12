<?php

namespace Modules\Workspace\Http\Requests;

use Modules\Workspace\Enums\WorkspaceRole;
use Modules\User\Models\User;
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
                    $pendingInvitation = $workspace->invitations()
                        ->where('email', $value)
                        ->whereNull('accepted_at')
                        ->exists();

                    if ($pendingInvitation) {
                        $fail('Já existe um convite pendente para este e-mail neste workspace.');
                    }

                    $existingMember = User::where('email', $value)->first();
                    if ($existingMember) {
                        $isMember = $workspace->memberships()
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
