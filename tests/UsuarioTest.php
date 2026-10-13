<?php

declare(strict_types=1);

final class UsuarioTest extends ModeloTestCase
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
                fecha_registro TEXT NOT NULL
            )'
        );
    }

    public function testCrearUsuarioConDatosValidos(): void
    {
        $contrasena = password_hash('Secreto123', PASSWORD_DEFAULT);
        $idUsuario = Usuario::crear([
            'nombre' => '  Ana  ',
            'correo' => ' ANA@GMAIL.COM ',
            'contrasena' => $contrasena,
            'rol' => 'comprador',
        ]);

        self::assertGreaterThan(0, $idUsuario);
        self::assertSame('ana@gmail.com', Usuario::buscarPorCorreo('ana@gmail.com')['correo']);
    }

    public function testCrearUsuarioRechazaCorreoDuplicado(): void
    {
        $datos = [
            'nombre' => 'Ana',
            'correo' => 'ana@gmail.com',
            'contrasena' => password_hash('Secreto123', PASSWORD_DEFAULT),
            'rol' => 'comprador',
        ];
        Usuario::crear($datos);

        $this->expectException(DomainException::class);
        Usuario::crear($datos);
    }

    public function testCrearUsuarioRechazaCamposFaltantes(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Usuario::crear([
            'nombre' => '',
            'correo' => '',
            'contrasena' => '',
            'rol' => 'comprador',
        ]);
    }

    public function testCrearUsuarioRechazaRolFaltante(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Usuario::crear([
            'nombre' => 'Ana',
            'correo' => 'ana@gmail.com',
            'contrasena' => password_hash('Secreto123', PASSWORD_DEFAULT),
        ]);
    }

    public function testCrearUsuarioRechazaCorreoInvalido(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Usuario::crear([
            'nombre' => 'Ana',
            'correo' => 'correo-invalido',
            'contrasena' => password_hash('Secreto123', PASSWORD_DEFAULT),
            'rol' => 'comprador',
        ]);
    }

    public function testCrearUsuarioRechazaDominioQueSoloContieneGmailComComoSufijo(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('El correo electrónico debe pertenecer a @gmail.com.');

        Usuario::crear([
            'nombre' => 'Ana',
            'correo' => 'erainpluna@gmail.com.xd',
            'contrasena' => password_hash('Secreto123', PASSWORD_DEFAULT),
            'rol' => 'comprador',
        ]);
    }

    public function testCrearUsuarioRechazaOtrosDominiosValidos(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('El correo electrónico debe pertenecer a @gmail.com.');

        Usuario::crear([
            'nombre' => 'Ana',
            'correo' => 'ana@example.com',
            'contrasena' => password_hash('Secreto123', PASSWORD_DEFAULT),
            'rol' => 'comprador',
        ]);
    }

    public function testLoginAceptaCredencialesCorrectas(): void
    {
        Usuario::crear([
            'nombre' => 'Ana',
            'correo' => 'ana@gmail.com',
            'contrasena' => password_hash('Secreto123', PASSWORD_DEFAULT),
            'rol' => 'comprador',
        ]);

        self::assertNotNull(Usuario::verificarCredenciales('ana@gmail.com', 'Secreto123'));
    }

    public function testLoginRechazaCredencialesIncorrectas(): void
    {
        Usuario::crear([
            'nombre' => 'Ana',
            'correo' => 'ana@gmail.com',
            'contrasena' => password_hash('Secreto123', PASSWORD_DEFAULT),
            'rol' => 'comprador',
        ]);

        self::assertNull(Usuario::verificarCredenciales('ana@gmail.com', 'incorrecta'));
        self::assertNull(Usuario::verificarCredenciales('inexistente@gmail.com', 'Secreto123'));
    }
}
