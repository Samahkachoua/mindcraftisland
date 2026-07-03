<?php

namespace App\Listeners;

use App\Events\RegistrationCreated;
use App\Mail\AdminRegistrationNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendRegistrationNotifications implements ShouldQueue
{
    public function handle(RegistrationCreated $event): void
    {
        $data = $event->data;
        $registrationId = ($event->record[0]['id'] ?? null) ?? ($data['full_name'] . ' / ' . $data['phone_number']);

        try {
            Mail::to(config('mail.admin_notification_email'))
                ->send(new AdminRegistrationNotification($data, $event->record));
        } catch (\Throwable $e) {
            Log::error('Failed to queue admin registration notification', [
                'registration_id' => $registrationId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
