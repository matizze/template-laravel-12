<x-mail::message>
# Convite para participar de {{ $invitation->workspace->name }}

Olá,

Você foi convidado para participar do workspace **{{ $invitation->workspace->name }}** com a função de **{{ $invitation->role->label() }}**.

<x-mail::button :url="$acceptUrl">
Aceitar Convite
</x-mail::button>

Se você não solicitou este convite, ignore este e-mail.

Obrigado,<br>
{{ config('app.name') }}
</x-mail::message>
