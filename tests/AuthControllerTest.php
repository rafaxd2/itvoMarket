<?php

declare(strict_types=1);

final class AuthControllerTest extends ModeloTestCase
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
    }

    public function testRegistroConCorreoInvalidoMuestraErrorYConservaDatos(): void
    {
        $_POST = [
            'correo' => 'correo-invalido',
            'contrasena' => 'Secreto123',
            'rol' => 'vendedor',
        ];

        ob_start();
        (new AuthController())->registrar();
        $html = ob_get_clean();

        self::assertStringContainsString('El correo electrónico no es válido.', $html);
        self::assertStringContainsString('value="correo-invalido"', $html);
        self::assertStringContainsString('aria-invalid="true"', $html);
        self::assertStringContainsString('id="rolVendedor" value="vendedor" checked', $html);
    }

    public function testRegistroRechazaCorreoQueNoTerminaEnGmailCom(): void
    {
        $_POST = [
            'correo' => 'erainpluna@gmail.com.xd',
            'contrasena' => 'Secreto123',
            'rol' => 'comprador',
        ];

        ob_start();
        (new AuthController())->registrar();
        $html = ob_get_clean();

        self::assertStringContainsString('El correo electrónico debe pertenecer a @gmail.com.', $html);
        self::assertStringContainsString('value="erainpluna@gmail.com.xd"', $html);
        self::assertSame(0, (int) $this->conexion->query('SELECT COUNT(*) FROM usuario')->fetchColumn());
    }

    public function testRegistroConCorreoDuplicadoMuestraErrorEnCorreo(): void
    {
        Usuario::crear([
            'nombre' => 'Ana',
            'correo' => 'ana@gmail.com',
            'contrasena' => password_hash('Secreto123', PASSWORD_DEFAULT),
            'rol' => 'comprador',
        ]);
        $_POST = [
            'correo' => 'ana@gmail.com',
            'contrasena' => 'OtraClave123',
            'rol' => 'vendedor',
        ];

        ob_start();
        (new AuthController())->registrar();
        $html = ob_get_clean();

        self::assertStringContainsString('Este correo ya está registrado', $html);
        self::assertStringContainsString('value="ana@gmail.com"', $html);
        self::assertStringContainsString('id="rolVendedor" value="vendedor" checked', $html);
        self::assertSame(1, (int) $this->conexion->query('SELECT COUNT(*) FROM usuario')->fetchColumn());
    }
}
