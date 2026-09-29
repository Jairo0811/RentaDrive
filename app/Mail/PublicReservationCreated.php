<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Company;
use App\Models\Reservation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

final class PublicReservationCreated extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Company $company,
        public readonly Reservation $reservation,
        public readonly ?string $cancellationUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Reserva '.$this->reservation->code.' · '.$this->company->name,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.public-reservation-created',
        );
    }
}
