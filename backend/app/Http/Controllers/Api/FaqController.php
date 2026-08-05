<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFaqRequest;
use App\Http\Requests\UpdateFaqRequest;
use App\Http\Resources\FaqResource;
use App\Models\Faq;
use App\Models\User;
use App\Services\EmbeddingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class FaqController extends Controller
{
    /**
     * Lista as perguntas frequentes do negócio.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Faq::class);

        $user = $request->user();
        abort_if(! $user instanceof User, 401);

        $faqs = Faq::query()->where('business_id', $user->business_id)->latest()->get();

        return FaqResource::collection($faqs);
    }

    /**
     * Cria uma pergunta frequente e computa seu embedding.
     */
    public function store(StoreFaqRequest $request, EmbeddingService $embeddings): JsonResponse
    {
        $this->authorize('create', Faq::class);

        $user = $request->user();
        abort_if(! $user instanceof User, 401);

        $question = $request->validated('question');
        $answer = $request->validated('answer');

        $faq = Faq::create([
            'business_id' => $user->business_id,
            'question' => $question,
            'answer' => $answer,
            'embedding' => $embeddings->embed("{$question}\n{$answer}"),
        ]);

        return FaqResource::make($faq)->response()->setStatusCode(201);
    }

    /**
     * Atualiza uma pergunta frequente e recomputa seu embedding.
     */
    public function update(UpdateFaqRequest $request, Faq $faq, EmbeddingService $embeddings): FaqResource
    {
        $this->authorize('update', $faq);

        $question = $request->validated('question');
        $answer = $request->validated('answer');

        $faq->update([
            'question' => $question,
            'answer' => $answer,
            'embedding' => $embeddings->embed("{$question}\n{$answer}"),
        ]);

        return FaqResource::make($faq);
    }

    /**
     * Remove uma pergunta frequente.
     */
    public function destroy(Faq $faq): Response
    {
        $this->authorize('delete', $faq);

        $faq->delete();

        return response()->noContent();
    }
}
