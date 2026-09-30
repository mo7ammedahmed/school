<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * A message a stranger typed into the public contact form.
 *
 * The visitor's address goes in `replyTo`, never in `from`: sending as the
 * visitor would fail SPF and DMARC at the school's own mail server, and the
 * school answers these by hitting reply.
 */
class ContactMessageMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $senderName,
        public readonly string $senderEmail,
        public readonly string $subjectLine,
        public readonly string $messageBody,
        public readonly ?string $schoolName = null,
    ) {}

    public function envelope(): Envelope
    {
        $visitor = new Address($this->senderEmail, $this->senderName);

        return new Envelope(
            replyTo: [$visitor],
            subject: $this->subjectLine,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.contact',
            with: [
                'senderName' => $this->senderName,
                'senderEmail' => $this->senderEmail,
                'messageBody' => $this->messageBody,
                'schoolName' => $this->schoolName ?? config('app.name'),
            ],
        );
    }
}
