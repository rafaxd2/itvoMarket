<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

abstract class ModeloTestCase extends TestCase
{
    protected PDO $conexion;

    protected function setUp(): void
    {
        parent::setUp();
        $this->conexion = new PDO('sqlite::memory:');
        $this->conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->conexion->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $this->conexion->sqliteCreateFunction('NOW', static fn (): string => date('Y-m-d H:i:s'));
        establecerConexionBaseDatosPruebas($this->conexion);
    }

    protected function tearDown(): void
    {
        establecerConexionBaseDatosPruebas(null);
        parent::tearDown();
    }
}
