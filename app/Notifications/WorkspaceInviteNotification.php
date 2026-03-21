<?php

namespace App\Notifications;

use App\Enums\WorkspaceRole;
use App\Models\Invitation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WorkspaceInviteNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Invitation $invitation, public bool $isRegistered = true) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $redirectPath = route('invitation.accept', $this->invitation->token, false);
        $baseRoute = $this->isRegistered ? route('login') : route('register');
        $acceptUrl = $baseRoute.'?redirect='.urlencode($redirectPath);

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
