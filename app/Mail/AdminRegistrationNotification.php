<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class AdminRegistrationNotification extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly array $data,
        public readonly array $record,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New Registration: ' . $this->data['full_name'],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.admin-registration-notification',
        );
    }

    public function failed(\Throwable $e): void
    {
        $registrationId = ($this->record[0]['id'] ?? null) ?? ($this->data['full_name'] . ' / ' . $this->data['phone_number']);
        Log::error('AdminRegistrationNotification failed to send', [
            'registration_id' => $registrationId,
            'error' => $e->getMessage(),
        ]);
    }
}
