<?php

namespace App\Exceptions\Asset;

use Exception;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class AssetException extends Exception
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
            "Activo con id {$id} no encontrado.",
            Response::HTTP_NOT_FOUND
        );
    }

    public static function hasActiveMaintenanceCases(string $name): self
    {
        return new self(
            "El activo '{$name}' tiene casos de mantenimiento activos. Ciérrelos antes de eliminarlo."
        );
    }

    public static function alreadyAssigned(string $name): self
    {
        return new self(
            "El activo '{$name}' ya está asignado a esta campaña."
        );
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
        ], $this->statusCode);
    }
}
