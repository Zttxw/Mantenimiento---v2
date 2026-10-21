<?php

namespace App\Exceptions\AgentDevice;

use Exception;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class AgentDeviceException extends Exception
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

    public static function notFound(string $uuid): self
    {
        return new self(
            "Dispositivo agente con UUID '{$uuid}' no encontrado.",
            Response::HTTP_NOT_FOUND
        );
    }

    public static function unauthorized(): self
    {
        return new self(
            'Token de agente inválido o dispositivo no autorizado.',
            Response::HTTP_UNAUTHORIZED
        );
    }

    public static function assetAlreadyRegistered(string $assetCode): self
    {
        return new self(
            "El activo '{$assetCode}' ya tiene un agente registrado."
        );
    }

    public static function assetCodeNotFound(string $assetCode): self
    {
        return new self(
            "No existe un activo con codigo interno o patrimonial '{$assetCode}'.",
            Response::HTTP_NOT_FOUND
        );
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
        ], $this->statusCode);
    }
}
