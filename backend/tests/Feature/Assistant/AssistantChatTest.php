<?php

declare(strict_types=1);

namespace Tests\Feature\Assistant;

use App\Enums\AppointmentStatus;
use App\Enums\UserRole;
use App\Mail\AppointmentCancelledMail;
use App\Mail\AppointmentRescheduledMail;
use App\Models\Appointment;
use App\Models\Business;
use App\Models\BusinessHour;
use App\Models\Faq;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AssistantChatTest extends TestCase
{
    use RefreshDatabase;

    protected Business $business;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.gemini.api_key' => 'test-key']);

        $this->business = Business::factory()->pro()->create();

        foreach (range(0, 6) as $dayOfWeek) {
            BusinessHour::factory()->create(['business_id' => $this->business->id, 'day_of_week' => $dayOfWeek]);
        }
    }

    private function textResponse(string $text): array
    {
        return ['candidates' => [['content' => ['role' => 'model', 'parts' => [['text' => $text]]]]]];
    }

    /**
     * @param  array<string, mixed>  $args
     */
    private function functionCallResponse(string $name, array $args = []): array
    {
        return ['candidates' => [['content' => ['role' => 'model', 'parts' => [['functionCall' => ['name' => $name, 'args' => $args]]]]]]];
    }

    #[Test]
    public function guests_cannot_use_the_assistant(): void
    {
        $response = $this->postJson('/api/assistant/chat', [
            'business' => $this->business->slug,
            'messages' => [['role' => 'user', 'content' => 'Oi']],
        ]);

        $response->assertUnauthorized();
    }

    #[Test]
    public function returns_a_friendly_message_when_gemini_is_not_configured(): void
    {
        config(['services.gemini.api_key' => null]);
        $client = User::factory()->create();

        $response = $this->actingAs($client)->postJson('/api/assistant/chat', [
            'business' => $this->business->slug,
            'messages' => [['role' => 'user', 'content' => 'Oi']],
        ]);

        $response->assertOk();
        $this->assertStringContainsString('não foi configurado', $response->json('message'));
        Http::assertNothingSent();
    }

    #[Test]
    public function assistant_replies_with_plain_text_when_no_tool_is_needed(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response($this->textResponse('Olá! Como posso ajudar?')),
        ]);

        $client = User::factory()->create();

        $response = $this->actingAs($client)->postJson('/api/assistant/chat', [
            'business' => $this->business->slug,
            'messages' => [['role' => 'user', 'content' => 'Oi']],
        ]);

        $response->assertOk();
        $this->assertEquals('Olá! Como posso ajudar?', $response->json('message'));
    }

    #[Test]
    public function assistant_lists_services_using_a_tool_call(): void
    {
        Service::factory()->create(['business_id' => $this->business->id, 'name' => 'Corte de cabelo', 'active' => true]);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::sequence()
                ->push($this->functionCallResponse('list_services'))
                ->push($this->textResponse('Temos o Corte de cabelo disponível.')),
        ]);

        $client = User::factory()->create();

        $response = $this->actingAs($client)->postJson('/api/assistant/chat', [
            'business' => $this->business->slug,
            'messages' => [['role' => 'user', 'content' => 'Quais serviços vocês têm?']],
        ]);

        $response->assertOk();
        $this->assertEquals('Temos o Corte de cabelo disponível.', $response->json('message'));
    }

    #[Test]
    public function assistant_books_an_appointment_using_a_tool_call(): void
    {
        $service = Service::factory()->create(['business_id' => $this->business->id, 'duration_minutes' => 30]);
        $startAt = now()->addDay()->setTime(10, 0);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::sequence()
                ->push($this->functionCallResponse('book_appointment', [
                    'service_id' => $service->id,
                    'start_at' => $startAt->format('Y-m-d H:i'),
                ]))
                ->push($this->textResponse('Prontinho, agendei pra você!')),
        ]);

        $client = User::factory()->create();

        $response = $this->actingAs($client)->postJson('/api/assistant/chat', [
            'business' => $this->business->slug,
            'messages' => [['role' => 'user', 'content' => 'Quero agendar amanhã às 10h']],
        ]);

        $response->assertOk();
        $this->assertEquals('Prontinho, agendei pra você!', $response->json('message'));
        $this->assertDatabaseHas('appointments', [
            'user_id' => $client->id,
            'service_id' => $service->id,
            'source' => 'ai_chat',
        ]);
    }

    #[Test]
    public function assistant_handles_a_booking_conflict_gracefully(): void
    {
        $service = Service::factory()->create(['business_id' => $this->business->id, 'duration_minutes' => 30]);
        $startAt = now()->addDay()->setTime(10, 0);

        Appointment::factory()->create([
            'service_id' => $service->id,
            'start_at' => $startAt,
            'end_at' => $startAt->clone()->addMinutes(30),
        ]);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::sequence()
                ->push($this->functionCallResponse('book_appointment', [
                    'service_id' => $service->id,
                    'start_at' => $startAt->format('Y-m-d H:i'),
                ]))
                ->push($this->textResponse('Esse horário já foi ocupado, quer tentar outro?')),
        ]);

        $client = User::factory()->create();

        $response = $this->actingAs($client)->postJson('/api/assistant/chat', [
            'business' => $this->business->slug,
            'messages' => [['role' => 'user', 'content' => 'Quero agendar amanhã às 10h']],
        ]);

        $response->assertOk();
        $this->assertEquals('Esse horário já foi ocupado, quer tentar outro?', $response->json('message'));
    }

    #[Test]
    public function assistant_includes_assigned_staff_when_listing_services(): void
    {
        $staff = User::factory()->create(['role' => UserRole::Admin, 'business_id' => $this->business->id, 'name' => 'Ana']);
        $service = Service::factory()->create(['business_id' => $this->business->id, 'name' => 'Corte de cabelo']);
        $service->staff()->attach($staff);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::sequence()
                ->push($this->functionCallResponse('list_services'))
                ->push($this->textResponse('Temos o Corte de cabelo com a Ana.')),
        ]);

        $client = User::factory()->create();

        $this->actingAs($client)->postJson('/api/assistant/chat', [
            'business' => $this->business->slug,
            'messages' => [['role' => 'user', 'content' => 'Quais serviços vocês têm?']],
        ]);

        Http::assertSent(function ($request) use ($service, $staff) {
            $body = $request->data();

            return isset($body['contents'][2]['parts'][0]['functionResponse']['response']['services'][0]['staff'][0]['id'])
                && $body['contents'][2]['parts'][0]['functionResponse']['response']['services'][0]['id'] === $service->id
                && $body['contents'][2]['parts'][0]['functionResponse']['response']['services'][0]['staff'][0]['id'] === $staff->id;
        });
    }

    #[Test]
    public function assistant_books_an_appointment_with_the_chosen_staff_member(): void
    {
        $staff = User::factory()->create(['role' => UserRole::Admin, 'business_id' => $this->business->id]);
        $service = Service::factory()->create(['business_id' => $this->business->id, 'duration_minutes' => 30]);
        $service->staff()->attach($staff);
        $startAt = now()->addDay()->setTime(10, 0);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::sequence()
                ->push($this->functionCallResponse('book_appointment', [
                    'service_id' => $service->id,
                    'staff_id' => $staff->id,
                    'start_at' => $startAt->format('Y-m-d H:i'),
                ]))
                ->push($this->textResponse('Prontinho, agendei com ela pra você!')),
        ]);

        $client = User::factory()->create();

        $response = $this->actingAs($client)->postJson('/api/assistant/chat', [
            'business' => $this->business->slug,
            'messages' => [['role' => 'user', 'content' => 'Quero agendar amanhã às 10h com a Ana']],
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('appointments', [
            'user_id' => $client->id,
            'service_id' => $service->id,
            'staff_id' => $staff->id,
        ]);
    }

    #[Test]
    public function assistant_lists_only_the_authenticated_clients_upcoming_appointments(): void
    {
        $service = Service::factory()->create(['business_id' => $this->business->id, 'name' => 'Corte de cabelo']);
        $client = User::factory()->create();
        $otherClient = User::factory()->create();

        $mine = Appointment::factory()->create([
            'user_id' => $client->id,
            'service_id' => $service->id,
            'start_at' => now()->addDay(),
            'end_at' => now()->addDay()->addMinutes(30),
        ]);

        Appointment::factory()->create([
            'user_id' => $otherClient->id,
            'service_id' => $service->id,
            'start_at' => now()->addDay(),
            'end_at' => now()->addDay()->addMinutes(30),
        ]);

        Http::fake([
            '*generateContent*' => Http::sequence()
                ->push($this->functionCallResponse('list_my_appointments'))
                ->push($this->textResponse('Você tem um agendamento de Corte de cabelo.')),
        ]);

        $this->actingAs($client)->postJson('/api/assistant/chat', [
            'business' => $this->business->slug,
            'messages' => [['role' => 'user', 'content' => 'Quais são meus agendamentos?']],
        ]);

        Http::assertSent(function ($request) use ($mine) {
            $body = $request->data();
            $appointments = $body['contents'][2]['parts'][0]['functionResponse']['response']['appointments'] ?? null;

            return $appointments !== null
                && count($appointments) === 1
                && $appointments[0]['appointment_id'] === $mine->id;
        });
    }

    #[Test]
    public function assistant_cancels_an_appointment_and_sends_the_notification(): void
    {
        Mail::fake();

        $service = Service::factory()->create(['business_id' => $this->business->id]);
        $client = User::factory()->create();
        $appointment = Appointment::factory()->create([
            'user_id' => $client->id,
            'service_id' => $service->id,
            'status' => AppointmentStatus::Confirmed,
            'start_at' => now()->addDay(),
            'end_at' => now()->addDay()->addMinutes(30),
        ]);

        Http::fake([
            '*generateContent*' => Http::sequence()
                ->push($this->functionCallResponse('cancel_appointment', ['appointment_id' => $appointment->id]))
                ->push($this->textResponse('Cancelado!')),
        ]);

        $response = $this->actingAs($client)->postJson('/api/assistant/chat', [
            'business' => $this->business->slug,
            'messages' => [['role' => 'user', 'content' => 'Cancela meu agendamento de amanhã']],
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => AppointmentStatus::Cancelled->value,
        ]);
        Mail::assertSent(AppointmentCancelledMail::class);
    }

    #[Test]
    public function assistant_cannot_cancel_another_clients_appointment(): void
    {
        $service = Service::factory()->create(['business_id' => $this->business->id]);
        $client = User::factory()->create();
        $otherClient = User::factory()->create();
        $appointment = Appointment::factory()->create([
            'user_id' => $otherClient->id,
            'service_id' => $service->id,
            'status' => AppointmentStatus::Confirmed,
            'start_at' => now()->addDay(),
        ]);

        Http::fake([
            '*generateContent*' => Http::sequence()
                ->push($this->functionCallResponse('cancel_appointment', ['appointment_id' => $appointment->id]))
                ->push($this->textResponse('Não encontrei esse agendamento.')),
        ]);

        $this->actingAs($client)->postJson('/api/assistant/chat', [
            'business' => $this->business->slug,
            'messages' => [['role' => 'user', 'content' => 'Cancela o agendamento '.$appointment->id]],
        ]);

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => AppointmentStatus::Confirmed->value,
        ]);
    }

    #[Test]
    public function assistant_reschedules_an_appointment_and_sends_the_notification(): void
    {
        Mail::fake();

        $service = Service::factory()->create(['business_id' => $this->business->id, 'duration_minutes' => 30]);
        $client = User::factory()->create();
        $appointment = Appointment::factory()->create([
            'user_id' => $client->id,
            'service_id' => $service->id,
            'status' => AppointmentStatus::Confirmed,
            'start_at' => now()->addDay()->setTime(10, 0),
            'end_at' => now()->addDay()->setTime(10, 30),
        ]);

        $newStart = now()->addDay()->setTime(14, 0);

        Http::fake([
            '*generateContent*' => Http::sequence()
                ->push($this->functionCallResponse('reschedule_appointment', [
                    'appointment_id' => $appointment->id,
                    'new_start_at' => $newStart->format('Y-m-d H:i'),
                ]))
                ->push($this->textResponse('Remarcado!')),
        ]);

        $response = $this->actingAs($client)->postJson('/api/assistant/chat', [
            'business' => $this->business->slug,
            'messages' => [['role' => 'user', 'content' => 'Remarca pra 14h amanhã']],
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'start_at' => $newStart->toDateTimeString(),
        ]);
        Mail::assertSent(AppointmentRescheduledMail::class);
    }

    #[Test]
    public function assistant_answers_from_the_knowledge_base_when_a_faq_matches(): void
    {
        Faq::create([
            'business_id' => $this->business->id,
            'question' => 'Vocês têm estacionamento?',
            'answer' => 'Sim, estacionamento gratuito para clientes.',
            'embedding' => [1, 0, 0],
        ]);

        Http::fake([
            '*generateContent*' => Http::sequence()
                ->push($this->functionCallResponse('search_knowledge_base', ['query' => 'tem estacionamento?']))
                ->push($this->textResponse('Sim, temos estacionamento gratuito!')),
            '*embedContent*' => Http::response(['embedding' => ['values' => [1, 0, 0]]]),
        ]);

        $client = User::factory()->create();

        $response = $this->actingAs($client)->postJson('/api/assistant/chat', [
            'business' => $this->business->slug,
            'messages' => [['role' => 'user', 'content' => 'Vocês têm estacionamento?']],
        ]);

        $response->assertOk();
        $this->assertEquals('Sim, temos estacionamento gratuito!', $response->json('message'));
    }

    #[Test]
    public function assistant_falls_back_to_confirming_with_the_establishment_when_nothing_matches(): void
    {
        Http::fake([
            '*generateContent*' => Http::sequence()
                ->push($this->functionCallResponse('search_knowledge_base', ['query' => 'vocês vendem produtos?']))
                ->push($this->textResponse('Isso precisa ser confirmado direto com o estabelecimento.')),
            '*embedContent*' => Http::response(['embedding' => ['values' => [1, 0, 0]]]),
        ]);

        $client = User::factory()->create();

        $response = $this->actingAs($client)->postJson('/api/assistant/chat', [
            'business' => $this->business->slug,
            'messages' => [['role' => 'user', 'content' => 'Vocês vendem produtos?']],
        ]);

        $response->assertOk();
        Http::assertSent(function ($request) {
            $body = $request->data();
            $results = $body['contents'][2]['parts'][0]['functionResponse']['response']['results'] ?? null;

            return $results !== null && count($results) === 0;
        });
    }
}
