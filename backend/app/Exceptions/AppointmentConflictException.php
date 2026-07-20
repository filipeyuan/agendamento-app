<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AppointmentConflictException extends Exception
{
    public function __construct()
    {
        parent::__construct('Esse horário acabou de ser ocupado. Escolha outro horário.');
    }

    public function render(Request $request): JsonResponse
    {
        return response()->json(['message' => $this->getMessage()], 409);
    }
}
