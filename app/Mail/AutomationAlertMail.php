<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

final class AutomationAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $companyName,
        public readonly string $alertTitle,
        public readonly string $alertMessage,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->alertTitle.' · '.$this->companyName);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.automation-alert');
    }
}
