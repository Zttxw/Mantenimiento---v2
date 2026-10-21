<?php

namespace App\Exceptions\MaintenanceCase;

use Exception;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class MaintenanceCaseException extends Exception
{
    public function __construct(
        string $message,
        private readonly int $statusCode = Response::HTTP_UNPROCESSABLE_ENTITY,
    ) {
        parent::__construct($message);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public static function notFound(int $id): self
    {
        return new self(
            "Caso de mantenimiento con id {$id} no encontrado.",
            Response::HTTP_NOT_FOUND
        );
    }

    public static function alreadyClosed(string $code): self
    {
        return new self(
            "El caso '{$code}' ya está cerrado y no puede modificarse."
        );
    }

    public static function invalidStatusTransition(string $from, string $to): self
    {
        return new self(
            "No se puede cambiar el estado de '{$from}' a '{$to}'."
        );
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
        ], $this->statusCode);
    }
}
