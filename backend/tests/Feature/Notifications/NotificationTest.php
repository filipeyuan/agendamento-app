<?php

declare(strict_types=1);

namespace Tests\Feature\Notifications;

use App\Enums\AppointmentStatus;
use App\Enums\UserRole;
use App\Models\Appointment;
use App\Models\Service;
use App\Models\User;
use App\Notifications\AppointmentCancelledNotification;
use App\Notifications\AppointmentConfirmedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function confirming_an_appointment_notifies_the_client(): void
    {
        Notification::fake();

        $admin = User::factory()->admin()->create();
        $client = User::factory()->create(['role' => UserRole::Client]);
        $service = Service::factory()->create(['business_id' => $admin->business_id]);
        $appointment = Appointment::factory()->create([
            'user_id' => $client->id,
            'service_id' => $service->id,
            'status' => AppointmentStatus::Pending,
        ]);

        $this->actingAs($admin)->patchJson("/api/admin/appointments/{$appointment->id}/status", [
            'status' => AppointmentStatus::Confirmed->value,
        ])->assertOk();

        Notification::assertSentTo($client, AppointmentConfirmedNotification::class);
    }

    #[Test]
    public function cancelling_an_appointment_notifies_the_client(): void
    {
        Notification::fake();

        $admin = User::factory()->admin()->create();
        $client = User::factory()->create(['role' => UserRole::Client]);
        $service = Service::factory()->create(['business_id' => $admin->business_id]);
        $appointment = Appointment::factory()->create([
            'user_id' => $client->id,
            'service_id' => $service->id,
            'status' => AppointmentStatus::Pending,
        ]);

        $this->actingAs($admin)->patchJson("/api/admin/appointments/{$appointment->id}/status", [
            'status' => AppointmentStatus::Cancelled->value,
        ])->assertOk();

        Notification::assertSentTo($client, AppointmentCancelledNotification::class);
    }

    #[Test]
    public function a_client_can_list_their_notifications_with_an_unread_count(): void
    {
        $client = User::factory()->create();
        $appointment = Appointment::factory()->create(['user_id' => $client->id]);

        $client->notify(new AppointmentConfirmedNotification($appointment->load(['service', 'user'])));
        $client->notify(new AppointmentCancelledNotification($appointment->load(['service', 'user'])));

        $response = $this->actingAs($client)->getJson('/api/notifications');

        $response->assertOk();
        $response->assertJsonCount(2, 'data');
        $response->assertJsonPath('unread_count', 2);
    }

    #[Test]
    public function a_client_can_mark_a_notification_as_read(): void
    {
        $client = User::factory()->create();
        $appointment = Appointment::factory()->create(['user_id' => $client->id]);
        $client->notify(new AppointmentConfirmedNotification($appointment->load(['service', 'user'])));

        $notificationId = $client->notifications()->first()->id;

        $this->actingAs($client)->patchJson("/api/notifications/{$notificationId}/read")->assertNoContent();

        $this->assertNotNull($client->notifications()->first()->read_at);
    }

    #[Test]
    public function a_client_cannot_mark_another_users_notification_as_read(): void
    {
        $client = User::factory()->create();
        $otherClient = User::factory()->create();
        $appointment = Appointment::factory()->create(['user_id' => $otherClient->id]);
        $otherClient->notify(new AppointmentConfirmedNotification($appointment->load(['service', 'user'])));

        $notificationId = $otherClient->notifications()->first()->id;

        $this->actingAs($client)->patchJson("/api/notifications/{$notificationId}/read")->assertForbidden();
    }

    #[Test]
    public function a_client_can_mark_all_notifications_as_read(): void
    {
        $client = User::factory()->create();
        $appointment = Appointment::factory()->create(['user_id' => $client->id]);
        $client->notify(new AppointmentConfirmedNotification($appointment->load(['service', 'user'])));
        $client->notify(new AppointmentCancelledNotification($appointment->load(['service', 'user'])));

        $this->actingAs($client)->postJson('/api/notifications/mark-all-read')->assertNoContent();

        $this->assertSame(0, $client->unreadNotifications()->count());
    }
}
