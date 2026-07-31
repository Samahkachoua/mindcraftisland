<?php

namespace Tests\Feature;

use App\Events\RegistrationCreated;
use App\Mail\AdminRegistrationNotification;
use App\Services\SupabaseService;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class RegistrationEmailTest extends TestCase
{
    private array $validPayload = [
        'registration_type'        => 'child',
        'full_name'                => 'Amira Karim Hassan',
        'phone_number'             => '03147852',
        'emergency_contact_number' => '03258963',
        'mother_name'              => 'Fatima Saeed',
        'medical_conditions'       => null,
        'field_of_interests'       => 'Reading, Board Games',
        'photo_video_consent'      => '1',
        'date_of_birth'            => '2015-06-15',
    ];

    private function mockSupabase(array $returnValue = []): void
    {
        $this->mock(SupabaseService::class, function ($mock) use ($returnValue) {
            $mock->shouldReceive('insertRegistration')
                ->once()
                ->andReturn($returnValue ?: [['id' => 'test-uuid-1234', 'full_name' => 'Amira Karim Hassan']]);
        });
    }

    public function test_admin_notification_is_queued_on_successful_registration(): void
    {
        Mail::fake();
        $this->mockSupabase();

        $this->post(route('register.store'), $this->validPayload);

        Mail::assertQueued(AdminRegistrationNotification::class, function ($mail) {
            return $mail->data['full_name'] === 'Amira Karim Hassan';
        });
    }

    public function test_admin_notification_contains_correct_registration_data(): void
    {
        Mail::fake();
        $this->mockSupabase([['id' => 'abc-123', 'full_name' => 'Amira Karim Hassan']]);

        $this->post(route('register.store'), $this->validPayload);

        Mail::assertQueued(AdminRegistrationNotification::class, function ($mail) {
            return $mail->data['phone_number'] === '03147852'
                && $mail->data['mother_name'] === 'Fatima Saeed'
                && $mail->record[0]['id'] === 'abc-123';
        });
    }

    public function test_no_email_queued_when_registration_is_duplicate(): void
    {
        Mail::fake();

        $this->mock(SupabaseService::class, function ($mock) {
            $mock->shouldReceive('insertRegistration')
                ->once()
                ->andThrow(new \RuntimeException('DUPLICATE_REGISTRATION'));
        });

        $this->post(route('register.store'), $this->validPayload);

        Mail::assertNothingQueued();
    }

    public function test_registration_event_is_dispatched_on_success(): void
    {
        Event::fake([RegistrationCreated::class]);
        $this->mockSupabase();

        $this->post(route('register.store'), $this->validPayload);

        Event::assertDispatched(RegistrationCreated::class, function ($event) {
            return $event->data['full_name'] === 'Amira Karim Hassan';
        });
    }
}
