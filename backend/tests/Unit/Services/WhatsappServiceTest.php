<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Appointment;
use App\Models\User;
use App\Services\WhatsappService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class WhatsappServiceTest extends TestCase
{
    use RefreshDatabase;

    private function configureTwilio(): void
    {
        config([
            'services.twilio.account_sid' => 'AC_test',
            'services.twilio.auth_token' => 'token_test',
            'services.twilio.whatsapp_from' => '+14155238886',
        ]);
    }

    #[Test]
    public function it_sends_a_whatsapp_message_with_the_phone_normalized_to_e164(): void
    {
        $this->configureTwilio();
        Http::fake(['api.twilio.com/*' => Http::response(['sid' => 'SM123'])]);

        $client = User::factory()->create(['phone' => '(11) 98765-4321']);
        $appointment = Appointment::factory()->create(['user_id' => $client->id]);

        app(WhatsappService::class)->sendAppointmentReminder($appointment);

        Http::assertSent(fn ($request) => $request['To'] === 'whatsapp:+5511987654321'
            && $request['From'] === 'whatsapp:+14155238886'
            && str_contains($request['Body'], $appointment->service->name));
    }

    #[Test]
    public function it_keeps_an_already_present_country_code(): void
    {
        $this->configureTwilio();
        Http::fake(['api.twilio.com/*' => Http::response(['sid' => 'SM123'])]);

        $client = User::factory()->create(['phone' => '+55 11 98765-4321']);
        $appointment = Appointment::factory()->create(['user_id' => $client->id]);

        app(WhatsappService::class)->sendAppointmentReminder($appointment);

        Http::assertSent(fn ($request) => $request['To'] === 'whatsapp:+5511987654321');
    }

    #[Test]
    public function it_does_nothing_when_twilio_is_not_configured(): void
    {
        Http::fake();

        $client = User::factory()->create(['phone' => '11987654321']);
        $appointment = Appointment::factory()->create(['user_id' => $client->id]);

        app(WhatsappService::class)->sendAppointmentReminder($appointment);

        Http::assertNothingSent();
    }

    #[Test]
    public function it_does_nothing_when_the_client_has_no_phone(): void
    {
        $this->configureTwilio();
        Http::fake();

        $client = User::factory()->create(['phone' => null]);
        $appointment = Appointment::factory()->create(['user_id' => $client->id]);

        app(WhatsappService::class)->sendAppointmentReminder($appointment);

        Http::assertNothingSent();
    }

    #[Test]
    public function it_does_not_throw_when_the_twilio_call_fails(): void
    {
        $this->configureTwilio();
        Http::fake(['api.twilio.com/*' => Http::response(['message' => 'boom'], 500)]);

        $client = User::factory()->create(['phone' => '11987654321']);
        $appointment = Appointment::factory()->create(['user_id' => $client->id]);

        app(WhatsappService::class)->sendAppointmentReminder($appointment);

        Http::assertSent(fn ($request) => true);
    }
}
