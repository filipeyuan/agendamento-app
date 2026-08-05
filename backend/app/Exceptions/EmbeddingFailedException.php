<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmbeddingFailedException extends Exception
{
    public function __construct()
    {
        parent::__construct('Não foi possível processar a pergunta agora. Tente novamente.');
    }

    public function render(Request $request): JsonResponse
    {
        return response()->json(['message' => $this->getMessage()], 502);
    }
}
