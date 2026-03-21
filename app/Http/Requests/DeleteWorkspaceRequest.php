<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DeleteWorkspaceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('delete', $this->workspace);
    }

    public function rules(): array
    {
        return [
            'confirm' => ['required', 'accepted'],
        ];
    }
}
