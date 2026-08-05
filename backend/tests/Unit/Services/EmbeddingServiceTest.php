<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Exceptions\EmbeddingFailedException;
use App\Models\Business;
use App\Models\Faq;
use App\Services\EmbeddingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EmbeddingServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.gemini.api_key' => 'test-key']);
    }

    #[Test]
    public function cosine_similarity_of_identical_vectors_is_one(): void
    {
        $service = app(EmbeddingService::class);

        $this->assertEqualsWithDelta(1.0, $service->cosineSimilarity([1, 0, 0], [1, 0, 0]), 0.0001);
    }

    #[Test]
    public function cosine_similarity_of_orthogonal_vectors_is_zero(): void
    {
        $service = app(EmbeddingService::class);

        $this->assertEqualsWithDelta(0.0, $service->cosineSimilarity([1, 0], [0, 1]), 0.0001);
    }

    #[Test]
    public function cosine_similarity_of_opposite_vectors_is_negative_one(): void
    {
        $service = app(EmbeddingService::class);

        $this->assertEqualsWithDelta(-1.0, $service->cosineSimilarity([1, 0], [-1, 0]), 0.0001);
    }

    #[Test]
    public function embed_throws_when_the_gemini_call_fails(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response(['error' => 'boom'], 500),
        ]);

        $this->expectException(EmbeddingFailedException::class);

        app(EmbeddingService::class)->embed('oi');
    }

    #[Test]
    public function search_faqs_returns_only_matches_above_the_threshold_ordered_by_similarity(): void
    {
        $business = Business::factory()->create();

        $closeMatch = Faq::create([
            'business_id' => $business->id,
            'question' => 'Vocês têm estacionamento?',
            'answer' => 'Sim, temos estacionamento gratuito.',
            'embedding' => [1, 0, 0],
        ]);

        $farMatch = Faq::create([
            'business_id' => $business->id,
            'question' => 'Vocês aceitam pix?',
            'answer' => 'Sim.',
            'embedding' => [0.9, 0.1, 0],
        ]);

        Faq::create([
            'business_id' => $business->id,
            'question' => 'Assunto totalmente diferente',
            'answer' => 'Nada a ver.',
            'embedding' => [0, 1, 0],
        ]);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'embedding' => ['values' => [1, 0, 0]],
            ]),
        ]);

        $results = app(EmbeddingService::class)->searchFaqs($business, 'estacionamento', topK: 3, threshold: 0.5);

        $this->assertCount(2, $results);
        $this->assertSame($closeMatch->id, $results->first()->id);
        $this->assertSame($farMatch->id, $results->last()->id);
    }
}
