<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Enums\AppointmentStatus;
use App\Exceptions\AppointmentConflictException;
use App\Models\Service;
use App\Models\User;
use App\Services\BookingService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BookingServiceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_books_an_appointment_when_the_slot_is_free(): void
    {
        $client = User::factory()->create();
        $service = Service::factory()->create(['duration_minutes' => 30]);
        $startAt = Carbon::parse('next monday 10:00');

        $appointment = app(BookingService::class)->book($client, $service, $startAt, 'Primeira vez');

        $this->assertSame($client->id, $appointment->user_id);
        $this->assertSame($service->id, $appointment->service_id);
        $this->assertSame(AppointmentStatus::Pending, $appointment->status);
        $this->assertTrue($appointment->start_at->equalTo($startAt));
        $this->assertTrue($appointment->end_at->equalTo($startAt->clone()->addMinutes(30)));
    }

    #[Test]
    public function it_throws_a_conflict_exception_when_the_slot_overlaps_an_existing_appointment(): void
    {
        $service = Service::factory()->create(['duration_minutes' => 30]);
        $startAt = Carbon::parse('next monday 10:00');

        app(BookingService::class)->book(User::factory()->create(), $service, $startAt, null);

        $this->expectException(AppointmentConflictException::class);

        app(BookingService::class)->book(User::factory()->create(), $service, $startAt->clone()->addMinutes(15), null);
    }

    #[Test]
    public function it_allows_back_to_back_appointments_that_do_not_overlap(): void
    {
        $service = Service::factory()->create(['duration_minutes' => 30]);
        $startAt = Carbon::parse('next monday 10:00');

        app(BookingService::class)->book(User::factory()->create(), $service, $startAt, null);

        $secondAppointment = app(BookingService::class)->book(
            User::factory()->create(),
            $service,
            $startAt->clone()->addMinutes(30),
            null,
        );

        $this->assertSame(AppointmentStatus::Pending, $secondAppointment->status);
    }

    #[Test]
    public function a_cancelled_appointment_does_not_block_the_slot(): void
    {
        $service = Service::factory()->create(['duration_minutes' => 30]);
        $startAt = Carbon::parse('next monday 10:00');

        $cancelled = app(BookingService::class)->book(User::factory()->create(), $service, $startAt, null);
        $cancelled->update(['status' => AppointmentStatus::Cancelled]);

        $appointment = app(BookingService::class)->book(User::factory()->create(), $service, $startAt, null);

        $this->assertSame(AppointmentStatus::Pending, $appointment->status);
    }
}
