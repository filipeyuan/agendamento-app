<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\EmbeddingFailedException;
use App\Models\Business;
use App\Models\Faq;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EmbeddingService
{
    private const OUTPUT_DIMENSIONALITY = 768;

    /**
     * @return array<int, float>
     */
    public function embed(string $text): array
    {
        $apiKey = config('services.gemini.api_key');
        $model = config('services.gemini.embedding_model');

        $response = Http::timeout(20)->post(
            "https://generativelanguage.googleapis.com/v1beta/models/{$model}:embedContent?key={$apiKey}",
            [
                'content' => ['parts' => [['text' => $text]]],
                'outputDimensionality' => self::OUTPUT_DIMENSIONALITY,
            ]
        );

        if ($response->failed()) {
            Log::warning('Falha ao gerar embedding via Gemini.', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new EmbeddingFailedException;
        }

        /** @var array<int, float> $values */
        $values = $response->json('embedding.values') ?? [];

        return $values;
    }

    /**
     * @param  array<int, float>  $a
     * @param  array<int, float>  $b
     */
    public function cosineSimilarity(array $a, array $b): float
    {
        $dotProduct = 0.0;
        $normA = 0.0;
        $normB = 0.0;

        foreach ($a as $i => $value) {
            $dotProduct += $value * ($b[$i] ?? 0);
            $normA += $value ** 2;
        }

        foreach ($b as $value) {
            $normB += $value ** 2;
        }

        if ($normA === 0.0 || $normB === 0.0) {
            return 0.0;
        }

        return $dotProduct / (sqrt($normA) * sqrt($normB));
    }

    /**
     * @return Collection<int, Faq>
     */
    public function searchFaqs(Business $business, string $query, int $topK = 3, float $threshold = 0.5): Collection
    {
        $queryEmbedding = $this->embed($query);

        return Faq::query()
            ->where('business_id', $business->id)
            ->whereNotNull('embedding')
            ->get()
            ->map(fn (Faq $faq) => [
                'faq' => $faq,
                'similarity' => $this->cosineSimilarity($queryEmbedding, $faq->embedding ?? []),
            ])
            ->filter(fn (array $entry) => $entry['similarity'] >= $threshold)
            ->sortByDesc('similarity')
            ->take($topK)
            ->map(fn (array $entry) => $entry['faq'])
            ->values();
    }
}
