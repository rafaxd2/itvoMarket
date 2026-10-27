<?php

declare(strict_types=1);

final class RecuperacionContrasenaTest extends ModeloTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->conexion->exec(
            'CREATE TABLE usuario (
                id_usuario INTEGER PRIMARY KEY AUTOINCREMENT,
                nombre TEXT NOT NULL,
                correo TEXT NOT NULL UNIQUE,
                contrasena TEXT NOT NULL,
                rol TEXT NOT NULL,
                fecha_registro TEXT NOT NULL,
                token_reset TEXT NULL,
                token_expira TEXT NULL
            )'
        );
        require_once __DIR__ . '/../controllers/AuthController.php';

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION = [];
        $_SERVER['REQUEST_METHOD'] = 'GET';
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
        parent::tearDown();
    }

    public function testCincoCodigosIncorrectosInvalidanElHashYSolicitanOtroCodigo(): void
    {
        Usuario::crear([
            'nombre' => 'Ana',
            'correo' => 'ana@gmail.com',
            'contrasena' => password_hash('Secreto123', PASSWORD_DEFAULT),
            'rol' => 'comprador',
        ]);
        $codigoCorrecto = Usuario::generarCodigoRestablecimiento('ana@gmail.com');
        $codigoIncorrecto = $codigoCorrecto === '000000' ? '000001' : '000000';
        $_SESSION['recuperacion_correo'] = 'ana@gmail.com';
        $_SESSION['recuperacion_intentos'] = 0;
        $_POST = [
            'codigo' => $codigoIncorrecto,
            'contrasena' => 'NuevaClave123',
            'confirmar_contrasena' => 'NuevaClave123',
        ];

        for ($intento = 0; $intento < 5; $intento++) {
            ob_start();
            (new AuthController())->guardarNuevaContrasena();
            $html = ob_get_clean();
        }

        $usuario = Usuario::buscarPorCorreo('ana@gmail.com');
        self::assertStringContainsString('Código incorrecto o vencido', $html);
        self::assertStringContainsString('Solicitar un código nuevo', $html);
        self::assertNull($usuario['token_reset']);
        self::assertNull($usuario['token_expira']);
        self::assertArrayNotHasKey('recuperacion_correo', $_SESSION);
        self::assertArrayNotHasKey('recuperacion_intentos', $_SESSION);
    }

    public function testCodigoIncorrectoNoRevelaSiElCorreoExiste(): void
    {
        $_POST = [
            'codigo' => '111111',
            'contrasena' => '',
            'confirmar_contrasena' => '',
        ];

        $_SESSION['recuperacion_correo'] = 'ana@gmail.com';
        ob_start();
        (new AuthController())->guardarNuevaContrasena();
        $respuestaCorreoInexistente = ob_get_clean();

        Usuario::crear([
            'nombre' => 'Ana',
            'correo' => 'ana@gmail.com',
            'contrasena' => password_hash('Secreto123', PASSWORD_DEFAULT),
            'rol' => 'comprador',
        ]);
        $_SESSION['recuperacion_correo'] = 'ana@gmail.com';
        $_SESSION['recuperacion_intentos'] = 0;
        ob_start();
        (new AuthController())->guardarNuevaContrasena();
        $respuestaCorreoExistente = ob_get_clean();

        self::assertStringContainsString('Código incorrecto o vencido', $respuestaCorreoInexistente);
        self::assertStringContainsString('Código incorrecto o vencido', $respuestaCorreoExistente);
        self::assertSame(
            substr($respuestaCorreoInexistente, strpos($respuestaCorreoInexistente, '<main')),
            substr($respuestaCorreoExistente, strpos($respuestaCorreoExistente, '<main'))
        );
    }

    public function testCorreoInexistenteRecibeElMismoAvisoNeutralYSeGuardaEnSesion(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = ['correo' => 'desconocido@gmail.com'];

        ob_start();
        (new AuthController())->solicitarRestablecimiento();
        $html = ob_get_clean();

        self::assertStringContainsString(
            'Si el correo está asociado a una cuenta, recibirás un código que vence en 15 minutos.',
            $html
        );
        self::assertStringContainsString('Ingresa tu código', $html);
        self::assertSame('desconocido@gmail.com', $_SESSION['recuperacion_correo']);
        self::assertSame(0, $_SESSION['recuperacion_intentos']);
    }
}
