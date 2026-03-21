<?php

namespace Modules\Workspace\Notifications;

use Modules\Workspace\Enums\WorkspaceRole;
use Modules\Workspace\Models\Invitation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WorkspaceInviteNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Invitation $invitation) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $acceptUrl = route('invitation.accept', $this->invitation->token);

        $workspaceName = $this->invitation->workspace?->name ?? 'Workspace';

        /** @var WorkspaceRole $role */
        $role = $this->invitation->role;
        $roleName = $role->value;

        return (new MailMessage)
            ->subject("Convite para o workspace {$workspaceName}")
            ->greeting('Olá!')
            ->line("Você foi convidado para participar do workspace **{$workspaceName}**.")
            ->line("Sua função será: **{$roleName}**.")
            ->action('Aceitar convite', $acceptUrl)
            ->line('Este convite expira em 7 dias.');
    }
}
