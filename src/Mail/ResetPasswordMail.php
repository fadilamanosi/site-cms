<?php

namespace SiteCms\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class ResetPasswordMail extends Mailable
{
    public function __construct(
        public readonly string $url,
        public readonly int $expireMinutes,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Reset your Site CMS password');
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'site-cms::mail.reset-password',
            with: [
                'url' => $this->url,
                'expireMinutes' => $this->expireMinutes,
            ],
        );
    }
}
