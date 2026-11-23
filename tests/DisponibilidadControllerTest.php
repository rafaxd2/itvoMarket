<?php

declare(strict_types=1);

require_once __DIR__ . '/../controllers/DisponibilidadController.php';

final class DisponibilidadControllerTest extends ModeloTestCase
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
        $this->conexion->exec('INSERT INTO vendedores (id_vendedor, id_usuario) VALUES (1, 10), (2, 20)');

        AuthController::iniciarSesion();
        $_SESSION = ['usuario_id' => 10, 'usuario_rol' => 'vendedor'];
        $_GET = ['accion' => 'disponibilidad'];
        $_POST = [];
        $_SERVER['REQUEST_METHOD'] = 'GET';
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
        $_GET = [];
        $_POST = [];
        $_SERVER['REQUEST_METHOD'] = 'GET';
        parent::tearDown();
    }

    public function testMostrarPresentaEstadoYUbicacionGuardados(): void
    {
        $this->insertarZona(1, 'Activo', 'Entrada principal');

        $html = $this->capturarVista(static function (): void {
            (new DisponibilidadController())->mostrar();
        });

        self::assertStringContainsString('Estado actual: Activo', $html);
        self::assertStringContainsString('Activo · visible en el mapa', $html);
        self::assertStringContainsString('Entrada principal', $html);
        self::assertStringContainsString('aria-checked="true"', $html);
    }

    public function testActualizarAActivoPersisteElEstadoYConfirmaElCambio(): void
    {
        $this->insertarZona(1, 'Desactivo');
        $this->prepararSolicitud('Activo');

        $html = $this->capturarVista(static function (): void {
            (new DisponibilidadController())->actualizar();
        });

        self::assertSame('Activo', ZonaVenta::obtenerEstadoPorVendedor(1));
        self::assertStringContainsString('Estado actualizado: ahora estás activo.', $html);
        self::assertStringContainsString('Activo · visible en el mapa', $html);
    }

    public function testActualizarAInactivoGuardaDesactivoYConservaLasPublicaciones(): void
    {
        $this->insertarZona(1, 'Activo');
        $this->prepararSolicitud('Inactivo');

        $html = $this->capturarVista(static function (): void {
            (new DisponibilidadController())->actualizar();
        });

        self::assertSame('Desactivo', ZonaVenta::obtenerEstadoPorVendedor(1));
        self::assertStringContainsString('Estado actualizado: ahora estás inactivo.', $html);
        self::assertStringContainsString('Inactivo · oculto en el mapa', $html);
        self::assertStringContainsString('Tus publicaciones se conservan.', $html);
    }

    public function testVendedorSinZonaVeLaIndicacionDeRegistrarUbicacionYNoActualiza(): void
    {
        $this->prepararSolicitud('Inactivo');

        $html = $this->capturarVista(static function (): void {
            (new DisponibilidadController())->actualizar();
        });

        self::assertStringContainsString('Primero registra tu ubicación', $html);
        self::assertStringContainsString('Actualizar mi ubicación', $html);
        self::assertSame(0, (int) $this->conexion->query('SELECT COUNT(*) FROM zona_venta')->fetchColumn());
    }

    public function testEstadoInvalidoConservaElEstadoGuardado(): void
    {
        $this->insertarZona(1, 'Activo');
        $this->prepararSolicitud('Disponible');

        $html = $this->capturarVista(static function (): void {
            (new DisponibilidadController())->actualizar();
        });

        self::assertSame('Activo', ZonaVenta::obtenerEstadoPorVendedor(1));
        self::assertStringContainsString(
            'No fue posible actualizar tu disponibilidad. Inténtalo de nuevo.',
            $html
        );
        self::assertStringContainsString('Estado actual: Activo', $html);
    }

    public function testVendedorSoloActualizaSuZonaAunqueEnvieOtroIdentificador(): void
    {
        $this->insertarZona(1, 'Activo');
        $this->insertarZona(2, 'Activo');
        $this->prepararSolicitud('Inactivo');
        $_POST['id_vendedor'] = '2';

        $this->capturarVista(static function (): void {
            (new DisponibilidadController())->actualizar();
        });

        self::assertSame('Desactivo', ZonaVenta::obtenerEstadoPorVendedor(1));
        self::assertSame('Activo', ZonaVenta::obtenerEstadoPorVendedor(2));
    }

    public function testErrorDeBaseDeDatosMuestraMensajeGenericoYConservaElEstado(): void
    {
        $this->insertarZona(1, 'Activo');
        $this->conexion->exec(
            "CREATE TRIGGER rechazar_actualizacion_estado
             BEFORE UPDATE ON zona_venta
             BEGIN
                 SELECT RAISE(FAIL, 'detalle técnico de prueba');
             END"
        );
        $this->prepararSolicitud('Inactivo');

        $html = $this->capturarVista(static function (): void {
            (new DisponibilidadController())->actualizar();
        });

        self::assertSame('Activo', ZonaVenta::obtenerEstadoPorVendedor(1));
        self::assertStringContainsString(
            'No fue posible actualizar tu disponibilidad. Inténtalo de nuevo.',
            $html
        );
        self::assertStringNotContainsString('detalle técnico de prueba', $html);
    }

    private function insertarZona(int $idVendedor, string $estado, string $referencia = 'Puesto'): void
    {
        $consulta = $this->conexion->prepare(
            'INSERT INTO zona_venta (id_vendedor, latitud, longitud, punto_referencia, estado_zona, fecha_hora)
             VALUES (:id_vendedor, 17.0, -96.0, :referencia, :estado_zona, :fecha_hora)'
        );
        $consulta->execute([
            ':id_vendedor' => $idVendedor,
            ':referencia' => $referencia,
            ':estado_zona' => $estado,
            ':fecha_hora' => '2026-10-12 10:00:00',
        ]);
    }

    private function prepararSolicitud(string $estado): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = ['estado_zona' => $estado];
    }

    private function capturarVista(callable $accion): string
    {
        ob_start();
        $accion();

        return (string) ob_get_clean();
    }
}
