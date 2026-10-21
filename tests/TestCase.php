<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $defaultConnection = config('database.default');
        $databaseName = config("database.connections.{$defaultConnection}.database");

        if ($defaultConnection !== 'sqlite' || $databaseName !== ':memory:') {
            throw new \RuntimeException(
                'SEGURIDAD: Las pruebas deben ejecutarse exclusivamente contra SQLite en memoria (:memory:). '.
                "Configuración actual: connection='{$defaultConnection}', database='{$databaseName}'."
            );
        }
    }
}
