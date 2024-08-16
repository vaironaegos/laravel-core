<?php

declare(strict_types=1);

namespace Astrotech\Core\Laravel\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

final class MailTestMessage extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        private readonly string $fromEmail,
        private readonly string $fromName,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address($this->fromEmail, $this->fromName),
            subject: 'Teste de Envio de E-mail',
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.test-mail');
    }
}
