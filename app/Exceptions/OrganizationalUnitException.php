<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Symphony\Component\HttpFoundation\Response;

class OrganizationalUnitException extends Exception
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
            "Unidad Organizacional con id {$id} no encontrada.",
            Response::HTTP_NOT_FOUND
        );
    }

    public static function hasChildren(string $name): self
    {
        return new self(
            "No se puede eliminar la unidad organizacional {$name} porque tiene hijos. Eliminar los hijos primero.",
        );
    }

    public static function hasEmployees(string $name): self
    {
        return new self(
            "No se puede eliminar la unidad organizacional {$name} porque tiene empleados. Reasignar los empleados primero.",
        );
    }

    public static function hasAssets(string $name): self
    {
        return new self(
            "No se puede eliminar la unidad organizacional {$name} porque tiene activos tecnologicos. Reasignar los activos primero.",
        );
    }

    public static function circularReference(string $name): self
    {
        return new self(
            "No se puede establecer el padre de la unidad organizacional {$name} porque es una unidad hija.",
        );
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'message' => $this - getMessage(),
        ], $this->statusCode);
    }
}
