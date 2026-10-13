<?php

declare(strict_types=1);

final class ZonaVentaTest extends ModeloTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->conexion->exec(
            'CREATE TABLE vendedores (
                id_vendedor INTEGER PRIMARY KEY AUTOINCREMENT,
                id_usuario INTEGER NOT NULL
            )'
        );
        $this->conexion->exec(
            'CREATE TABLE zona_venta (
                id_zona_venta INTEGER PRIMARY KEY AUTOINCREMENT,
                id_vendedor INTEGER NOT NULL,
                latitud REAL NOT NULL,
                longitud REAL NOT NULL,
                punto_referencia TEXT NOT NULL,
                estado_zona TEXT NOT NULL,
                fecha_hora TEXT NOT NULL
            )'
        );
        $this->conexion->exec('INSERT INTO vendedores (id_usuario) VALUES (10), (11)');
    }

    public function testGuardarUbicacionValida(): void
    {
        self::assertTrue(ZonaVenta::actualizarUbicacion([
            'id_vendedor' => 1,
            'latitud' => 17.0622,
            'longitud' => -96.7266,
            'punto_referencia' => 'Frente al teatro',
            'estado_zona' => 'Activo',
        ]));
        self::assertSame(1, (int) $this->conexion->query('SELECT COUNT(*) FROM zona_venta')->fetchColumn());
    }

    public function testGuardarUbicacionRechazaCoordenadasInvalidas(): void
    {
        $this->expectException(InvalidArgumentException::class);
        ZonaVenta::actualizarUbicacion([
            'id_vendedor' => 1,
            'latitud' => 91,
            'longitud' => -96.7266,
            'punto_referencia' => 'Fuera del rango',
            'estado_zona' => 'Activo',
        ]);
    }

    public function testObtenerActivosSoloDevuelveVendedoresActivos(): void
    {
        $this->conexion->exec(
            "INSERT INTO zona_venta (id_vendedor, latitud, longitud, punto_referencia, estado_zona, fecha_hora)
             VALUES (1, 17.0, -96.0, 'Puesto activo', 'Activo', '2026-10-12 10:00:00'),
                    (2, 18.0, -97.0, 'Puesto inactivo', 'Desactivo', '2026-10-12 11:00:00')"
        );

        $zonasActivas = ZonaVenta::obtenerActivos();

        self::assertCount(1, $zonasActivas);
        self::assertSame('Activo', $zonasActivas[0]['estado_zona']);
        self::assertSame(1, (int) $zonasActivas[0]['id_vendedor']);
    }

    public function testObtenerPorVendedorDevuelveUltimaUbicacionAunqueEsteInactiva(): void
    {
        $this->conexion->exec(
            "INSERT INTO zona_venta (id_vendedor, latitud, longitud, punto_referencia, estado_zona, fecha_hora)
             VALUES (1, 17.0622, -96.7266, 'Frente al teatro', 'Desactivo', '2026-10-12 10:00:00')"
        );

        $zona = ZonaVenta::obtenerPorVendedor(1);

        self::assertNotNull($zona);
        self::assertSame('Frente al teatro', $zona['punto_referencia']);
        self::assertSame('Desactivo', $zona['estado_zona']);
        self::assertSame(17.0622, (float) $zona['latitud']);
    }

    public function testObtenerPorVendedorDevuelveNuloSiNoTieneUbicacion(): void
    {
        self::assertNull(ZonaVenta::obtenerPorVendedor(1));
    }
}
