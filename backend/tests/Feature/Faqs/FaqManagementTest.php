<?php

declare(strict_types=1);

namespace Tests\Feature\Faqs;

use App\Models\Faq;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FaqManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.gemini.api_key' => 'test-key']);
    }

    private function fakeSuccessfulEmbedding(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'embedding' => ['values' => [0.1, 0.2, 0.3]],
            ]),
        ]);
    }

    #[Test]
    public function guests_cannot_manage_faqs(): void
    {
        $response = $this->getJson('/api/admin/faq');

        $response->assertUnauthorized();
    }

    #[Test]
    public function clients_cannot_manage_faqs(): void
    {
        $client = User::factory()->create();

        $response = $this->actingAs($client)->getJson('/api/admin/faq');

        $response->assertForbidden();
    }

    #[Test]
    public function admin_can_create_a_faq_and_its_embedding_is_computed(): void
    {
        $this->fakeSuccessfulEmbedding();
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->postJson('/api/admin/faq', [
            'question' => 'Vocês têm estacionamento?',
            'answer' => 'Sim, gratuito para clientes.',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.question', 'Vocês têm estacionamento?');

        $faq = Faq::query()->where('business_id', $admin->business_id)->firstOrFail();
        $this->assertSame([0.1, 0.2, 0.3], $faq->embedding);
    }

    #[Test]
    public function admin_can_list_only_their_business_faqs(): void
    {
        $admin = User::factory()->admin()->create();
        $otherAdmin = User::factory()->admin()->create();

        Faq::create([
            'business_id' => $admin->business_id,
            'question' => 'Pergunta do meu negócio',
            'answer' => 'Resposta',
            'embedding' => [0.1],
        ]);

        Faq::create([
            'business_id' => $otherAdmin->business_id,
            'question' => 'Pergunta de outro negócio',
            'answer' => 'Resposta',
            'embedding' => [0.1],
        ]);

        $response = $this->actingAs($admin)->getJson('/api/admin/faq');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.question', 'Pergunta do meu negócio');
    }

    #[Test]
    public function admin_can_update_a_faq_and_recompute_its_embedding(): void
    {
        $this->fakeSuccessfulEmbedding();
        $admin = User::factory()->admin()->create();
        $faq = Faq::create([
            'business_id' => $admin->business_id,
            'question' => 'Pergunta antiga',
            'answer' => 'Resposta antiga',
            'embedding' => [0.9, 0.9, 0.9],
        ]);

        $response = $this->actingAs($admin)->putJson("/api/admin/faq/{$faq->id}", [
            'question' => 'Pergunta nova',
            'answer' => 'Resposta nova',
        ]);

        $response->assertOk();
        $faq->refresh();
        $this->assertSame('Pergunta nova', $faq->question);
        $this->assertSame([0.1, 0.2, 0.3], $faq->embedding);
    }

    #[Test]
    public function admin_cannot_update_a_faq_from_another_business(): void
    {
        $admin = User::factory()->admin()->create();
        $otherAdmin = User::factory()->admin()->create();
        $faq = Faq::create([
            'business_id' => $otherAdmin->business_id,
            'question' => 'Pergunta',
            'answer' => 'Resposta',
            'embedding' => [0.1],
        ]);

        $response = $this->actingAs($admin)->putJson("/api/admin/faq/{$faq->id}", [
            'question' => 'Tentativa',
            'answer' => 'Tentativa',
        ]);

        $response->assertForbidden();
    }

    #[Test]
    public function admin_can_delete_a_faq(): void
    {
        $admin = User::factory()->admin()->create();
        $faq = Faq::create([
            'business_id' => $admin->business_id,
            'question' => 'Pergunta',
            'answer' => 'Resposta',
            'embedding' => [0.1],
        ]);

        $response = $this->actingAs($admin)->deleteJson("/api/admin/faq/{$faq->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('business_faqs', ['id' => $faq->id]);
    }

    #[Test]
    public function creating_a_faq_fails_with_a_502_when_the_embedding_call_fails(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response(['error' => 'boom'], 500),
        ]);

        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->postJson('/api/admin/faq', [
            'question' => 'Pergunta',
            'answer' => 'Resposta',
        ]);

        $response->assertStatus(502);
        $this->assertDatabaseCount('business_faqs', 0);
    }
}
