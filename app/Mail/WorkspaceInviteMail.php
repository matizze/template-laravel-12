<?php

namespace App\Mail;

use App\Models\Invitation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WorkspaceInviteMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Invitation $invitation) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            to: [$this->invitation->email],
            subject: "Convite para participar de {$this->invitation->workspace->name}",
        );
    }

    public function content(): Content
    {
        $acceptUrl = route('invitations.accept', ['token' => $this->invitation->token]);

        return new Content(
            markdown: 'emails.workspace-invite',
            with: [
                'invitation' => $this->invitation,
                'acceptUrl' => $acceptUrl,
            ],
        );
    }
}
