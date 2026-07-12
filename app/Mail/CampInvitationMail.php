<?php

namespace App\Mail;

use App\Models\CampInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Sends a camp leader their personal join link. Sent synchronously during the
 * invite request (SMTP is bounded by the mail timeout), so no queue worker is
 * required for invitations to go out.
 */
class CampInvitationMail extends Mailable
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
