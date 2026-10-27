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
                fecha_registro TEXT NOT NULL,
                token_reset TEXT NULL,
                token_expira TEXT NULL
            )'
        );
    }

    public function testCodigoSeGuardaComoHashYExpiraEnQuinceMinutos(): void
    {
        $idUsuario = Usuario::crear([
            'nombre' => 'Ana',
            'correo' => 'ana@gmail.com',
            'contrasena' => password_hash('Secreto123', PASSWORD_DEFAULT),
            'rol' => 'comprador',
        ]);

        $codigo = Usuario::generarCodigoRestablecimiento('ana@gmail.com');
        $usuario = $this->conexion->query(
            'SELECT token_reset, token_expira FROM usuario WHERE id_usuario = ' . $idUsuario
        )->fetch();
        $vencimientoEsperado = $this->conexion->query("SELECT datetime('now', '+15 minutes')")->fetchColumn();

        self::assertIsString($codigo);
        self::assertMatchesRegularExpression('/\A[0-9]{6}\z/', $codigo);
        self::assertSame(hash('sha256', $idUsuario . ':' . $codigo), $usuario['token_reset']);
        self::assertSame($vencimientoEsperado, $usuario['token_expira']);
    }

    public function testCodigoValidoSeAceptaYCodigoIncorrectoOVencidoSeRechaza(): void
    {
        Usuario::crear([
            'nombre' => 'Ana',
            'correo' => 'ana@gmail.com',
            'contrasena' => password_hash('Secreto123', PASSWORD_DEFAULT),
            'rol' => 'comprador',
        ]);
        $codigo = Usuario::generarCodigoRestablecimiento('ana@gmail.com');

        self::assertNotNull(Usuario::buscarPorCodigoRestablecimiento('ana@gmail.com', $codigo));
        self::assertNull(Usuario::buscarPorCodigoRestablecimiento('ana@gmail.com', '123456'));
        self::assertNull(Usuario::buscarPorCodigoRestablecimiento('ana@gmail.com', '12345'));

        $this->conexion->exec("UPDATE usuario SET token_expira = '2000-01-01 00:00:00'");
        self::assertNull(Usuario::buscarPorCodigoRestablecimiento('ana@gmail.com', $codigo));
    }

    public function testCodigoNuevoReemplazaAlAnterior(): void
    {
        Usuario::crear([
            'nombre' => 'Ana',
            'correo' => 'ana@gmail.com',
            'contrasena' => password_hash('Secreto123', PASSWORD_DEFAULT),
            'rol' => 'comprador',
        ]);
        $codigoAnterior = Usuario::generarCodigoRestablecimiento('ana@gmail.com');
        $codigoNuevo = Usuario::generarCodigoRestablecimiento('ana@gmail.com');

        if ($codigoAnterior !== $codigoNuevo) {
            self::assertNull(Usuario::buscarPorCodigoRestablecimiento('ana@gmail.com', $codigoAnterior));
        }
        self::assertNotNull(Usuario::buscarPorCodigoRestablecimiento('ana@gmail.com', $codigoNuevo));
    }

    public function testActualizarContrasenaConsumeCodigoYGuardaHash(): void
    {
        $idUsuario = Usuario::crear([
            'nombre' => 'Ana',
            'correo' => 'ana@gmail.com',
            'contrasena' => password_hash('Secreto123', PASSWORD_DEFAULT),
            'rol' => 'comprador',
        ]);
        $codigo = Usuario::generarCodigoRestablecimiento('ana@gmail.com');

        self::assertTrue(Usuario::actualizarContrasena($idUsuario, $codigo, 'NuevaClave123'));

        $usuario = Usuario::buscarPorCorreo('ana@gmail.com');
        self::assertTrue(password_verify('NuevaClave123', $usuario['contrasena']));
        self::assertNull($usuario['token_reset']);
        self::assertNull($usuario['token_expira']);
        self::assertNull(Usuario::buscarPorCodigoRestablecimiento('ana@gmail.com', $codigo));
    }

    public function testInvalidarCodigoLimpiaElHashYLaExpiracion(): void
    {
        Usuario::crear([
            'nombre' => 'Ana',
            'correo' => 'ana@gmail.com',
            'contrasena' => password_hash('Secreto123', PASSWORD_DEFAULT),
            'rol' => 'comprador',
        ]);
        Usuario::generarCodigoRestablecimiento('ana@gmail.com');

        Usuario::invalidarCodigoRestablecimiento('ana@gmail.com');

        $usuario = Usuario::buscarPorCorreo('ana@gmail.com');
        self::assertNull($usuario['token_reset']);
        self::assertNull($usuario['token_expira']);
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
