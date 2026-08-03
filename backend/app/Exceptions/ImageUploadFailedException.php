<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ImageUploadFailedException extends Exception
{
    public function __construct()
    {
        parent::__construct('Não foi possível enviar a imagem. Tente novamente.');
    }

    public function render(Request $request): JsonResponse
    {
        return response()->json(['message' => $this->getMessage()], 502);
    }
}
