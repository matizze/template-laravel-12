<?php

namespace App\Http\Requests;

use App\Enums\WorkspaceRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InviteMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('invite', $this->workspace);
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'role' => ['required', Rule::enum(WorkspaceRole::class)],
        ];
    }
}
