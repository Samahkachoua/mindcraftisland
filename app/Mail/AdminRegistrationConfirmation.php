<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class AdminRegistrationConfirmation extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly array $data,
        public readonly string $locale,
    ) {}

    public function envelope(): Envelope
    {
        $subject = $this->locale === 'ar'
            ? 'تأكيد التسجيل — ' . $this->data['full_name']
            : 'Registration Confirmation — ' . $this->data['full_name'];

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.admin-registration-confirmation',
        );
    }

    public function failed(\Throwable $e): void
    {
        $registrationId = $this->data['full_name'] . ' / ' . $this->data['phone_number'];
        Log::error('AdminRegistrationConfirmation failed to send', [
            'registration_id' => $registrationId,
            'locale' => $this->locale,
            'error' => $e->getMessage(),
        ]);
    }
}
