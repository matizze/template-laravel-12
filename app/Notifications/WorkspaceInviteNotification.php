<?php

namespace App\Notifications;

use App\Models\Invitation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WorkspaceInviteNotification extends Notification
{
    use Queueable;

    public function __construct(public Invitation $invitation) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $acceptUrl = route('invitations.accept', ['token' => $this->invitation->token]);

        return (new MailMessage)
            ->subject("Convite para participar de {$this->invitation->workspace->name}")
            ->greeting('Olá!')
            ->line("Você foi convidado para participar do workspace **{$this->invitation->workspace->name}** com a função de **{$this->invitation->role->label()}**.")
            ->action('Aceitar Convite', $acceptUrl)
            ->line('Se você não solicitou este convite, ignore este e-mail.');
    }
}
