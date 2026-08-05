<?php

declare(strict_types=1);

namespace Tests\Feature\Appointments;

use App\Enums\AppointmentStatus;
use App\Mail\AppointmentReminderMail;
use App\Models\Appointment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SendAppointmentRemindersTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_sends_a_reminder_for_a_confirmed_appointment_inside_the_reminder_window(): void
    {
        Mail::fake();

        $appointment = Appointment::factory()->create([
            'status' => AppointmentStatus::Confirmed,
            'start_at' => now()->addHours(10),
            'end_at' => now()->addHours(10)->addMinutes(30),
        ]);

        $this->artisan('appointments:send-reminders')->assertSuccessful();

        Mail::assertSent(AppointmentReminderMail::class);
        $this->assertNotNull($appointment->fresh()->reminder_sent_at);
    }

    #[Test]
    public function it_does_not_send_a_reminder_outside_the_configured_window(): void
    {
        Mail::fake();

        $appointment = Appointment::factory()->create([
            'status' => AppointmentStatus::Confirmed,
            'start_at' => now()->addDays(3),
            'end_at' => now()->addDays(3)->addMinutes(30),
        ]);

        $this->artisan('appointments:send-reminders')->assertSuccessful();

        Mail::assertNothingSent();
        $this->assertNull($appointment->fresh()->reminder_sent_at);
    }

    #[Test]
    public function it_does_not_send_a_reminder_twice(): void
    {
        Mail::fake();

        $appointment = Appointment::factory()->create([
            'status' => AppointmentStatus::Confirmed,
            'start_at' => now()->addHours(10),
            'end_at' => now()->addHours(10)->addMinutes(30),
            'reminder_sent_at' => now()->subMinutes(5),
        ]);

        $this->artisan('appointments:send-reminders')->assertSuccessful();

        Mail::assertNothingSent();
    }

    #[Test]
    public function it_does_not_send_a_reminder_for_a_cancelled_appointment(): void
    {
        Mail::fake();

        Appointment::factory()->create([
            'status' => AppointmentStatus::Cancelled,
            'start_at' => now()->addHours(10),
            'end_at' => now()->addHours(10)->addMinutes(30),
        ]);

        $this->artisan('appointments:send-reminders')->assertSuccessful();

        Mail::assertNothingSent();
    }

    #[Test]
    public function it_does_not_send_a_reminder_for_a_pending_appointment(): void
    {
        Mail::fake();

        Appointment::factory()->create([
            'status' => AppointmentStatus::Pending,
            'start_at' => now()->addHours(10),
            'end_at' => now()->addHours(10)->addMinutes(30),
        ]);

        $this->artisan('appointments:send-reminders')->assertSuccessful();

        Mail::assertNothingSent();
    }

    #[Test]
    public function it_does_not_send_a_reminder_for_an_appointment_already_in_the_past(): void
    {
        Mail::fake();

        Appointment::factory()->create([
            'status' => AppointmentStatus::Confirmed,
            'start_at' => now()->subHour(),
            'end_at' => now()->subMinutes(30),
        ]);

        $this->artisan('appointments:send-reminders')->assertSuccessful();

        Mail::assertNothingSent();
    }
}
