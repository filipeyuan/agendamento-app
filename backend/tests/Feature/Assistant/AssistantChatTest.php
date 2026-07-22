<?php

declare(strict_types=1);

namespace Tests\Feature\Assistant;

use App\Models\Appointment;
use App\Models\BusinessHour;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AssistantChatTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.gemini.api_key' => 'test-key']);

        foreach (range(0, 6) as $dayOfWeek) {
            BusinessHour::factory()->create(['day_of_week' => $dayOfWeek]);
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
            'messages' => [['role' => 'user', 'content' => 'Oi']],
        ]);

        $response->assertOk();
        $this->assertEquals('Olá! Como posso ajudar?', $response->json('message'));
    }

    #[Test]
    public function assistant_lists_services_using_a_tool_call(): void
    {
        Service::factory()->create(['name' => 'Corte de cabelo', 'active' => true]);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::sequence()
                ->push($this->functionCallResponse('list_services'))
                ->push($this->textResponse('Temos o Corte de cabelo disponível.')),
        ]);

        $client = User::factory()->create();

        $response = $this->actingAs($client)->postJson('/api/assistant/chat', [
            'messages' => [['role' => 'user', 'content' => 'Quais serviços vocês têm?']],
        ]);

        $response->assertOk();
        $this->assertEquals('Temos o Corte de cabelo disponível.', $response->json('message'));
    }

    #[Test]
    public function assistant_books_an_appointment_using_a_tool_call(): void
    {
        $service = Service::factory()->create(['duration_minutes' => 30]);
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
        $service = Service::factory()->create(['duration_minutes' => 30]);
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
            'messages' => [['role' => 'user', 'content' => 'Quero agendar amanhã às 10h']],
        ]);

        $response->assertOk();
        $this->assertEquals('Esse horário já foi ocupado, quer tentar outro?', $response->json('message'));
    }
}
