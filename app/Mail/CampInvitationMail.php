<?php

namespace App\Mail;

use App\Models\CampInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Sends a camp leader their personal join link. Queued so a slow/unavailable
 * SMTP server never blocks the HTTP request that created the invitation.
 */
class CampInvitationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public CampInvitation $invitation) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('Invitation to :camp', ['camp' => $this->invitation->camp->name]),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.camp-invitation',
            with: [
                'campName' => $this->invitation->camp->name,
                'inviterName' => $this->invitation->inviter?->name,
                'acceptUrl' => route('invitations.show', $this->invitation->token),
            ],
        );
    }
}
