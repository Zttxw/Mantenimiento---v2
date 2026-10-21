<?php

namespace App\Exceptions\Employee;

use Exception;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class EmployeeException extends Exception
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
            "Empleado con id {$id} no encontrado.",
            Response::HTTP_NOT_FOUND
        );
    }

    public static function hasAssignedAssets(string $name): self
    {
        return new self(
            "El empleado '{$name}' tiene activos asignados. Reasígnelos antes de eliminarlo."
        );
    }

    public static function hasActiveCases(string $name): self
    {
        return new self(
            "El empleado '{$name}' tiene casos de mantenimiento activos asignados."
        );
    }

    public static function alreadyHasUser(string $name): self
    {
        return new self(
            "El empleado '{$name}' ya tiene un usuario del sistema asociado."
        );
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
        ], $this->statusCode);
    }
}
