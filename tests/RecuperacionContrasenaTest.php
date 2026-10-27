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
        $_POST = [];
        $_SERVER['REQUEST_METHOD'] = 'POST';
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
        $_POST = ['codigo' => $codigoIncorrecto];

        for ($intento = 0; $intento < 5; $intento++) {
            (new AuthController())->verificarCodigoRecuperacion();
        }

        $usuario = Usuario::buscarPorCorreo('ana@gmail.com');
        self::assertSame(5, $_SESSION['recuperacion_intentos']);
        self::assertTrue($_SESSION['recuperacion_bloqueada']);
        self::assertSame('ana@gmail.com', $_SESSION['recuperacion_correo']);
        self::assertNull($usuario['token_reset']);
        self::assertNull($usuario['token_expira']);
    }

    public function testCodigoValidoMarcaLaSesionComoVerificadaSinGuardarloEnClaro(): void
    {
        Usuario::crear([
            'nombre' => 'Ana',
            'correo' => 'ana@gmail.com',
            'contrasena' => password_hash('Secreto123', PASSWORD_DEFAULT),
            'rol' => 'comprador',
        ]);
        $codigo = Usuario::generarCodigoRestablecimiento('ana@gmail.com');
        $_SESSION['recuperacion_correo'] = 'ana@gmail.com';
        $_POST = ['codigo' => $codigo];

        (new AuthController())->verificarCodigoRecuperacion();

        self::assertTrue($_SESSION['recuperacion_codigo_verificado']);
        self::assertSame(
            hash('sha256', 1 . ':' . $codigo),
            $_SESSION['recuperacion_hash_verificado']
        );
        self::assertArrayNotHasKey('codigo', $_SESSION);
    }

    public function testNuevaContrasenaSoloSeGuardaTrasVerificarElCodigo(): void
    {
        $idUsuario = Usuario::crear([
            'nombre' => 'Ana',
            'correo' => 'ana@gmail.com',
            'contrasena' => password_hash('Secreto123', PASSWORD_DEFAULT),
            'rol' => 'comprador',
        ]);
        $codigo = Usuario::generarCodigoRestablecimiento('ana@gmail.com');
        $hashCodigo = hash('sha256', $idUsuario . ':' . $codigo);
        $_SESSION['recuperacion_correo'] = 'ana@gmail.com';
        $_SESSION['recuperacion_codigo_verificado'] = true;
        $_SESSION['recuperacion_hash_verificado'] = $hashCodigo;
        $_POST = [
            'contrasena' => 'NuevaClave123',
            'confirmar_contrasena' => 'NuevaClave123',
        ];

        (new AuthController())->guardarNuevaContrasena();

        $usuario = Usuario::buscarPorCorreo('ana@gmail.com');
        self::assertTrue(password_verify('NuevaClave123', $usuario['contrasena']));
        self::assertNull($usuario['token_reset']);
        self::assertNull($usuario['token_expira']);
        self::assertArrayNotHasKey('recuperacion_correo', $_SESSION);
        self::assertArrayNotHasKey('recuperacion_codigo_verificado', $_SESSION);
    }

    public function testPantallaCodigoTieneSeisCasillasYCorreoIndicado(): void
    {
        $_SESSION['recuperacion_correo'] = 'ana@gmail.com';

        ob_start();
        (new AuthController())->mostrarRestablecer();
        $html = ob_get_clean();

        self::assertStringContainsString('Revisa tu correo', $html);
        self::assertStringContainsString('ana@gmail.com', $html);
        self::assertSame(6, substr_count($html, 'data-code-digit'));
        self::assertStringContainsString('inputmode="numeric"', $html);
        self::assertStringContainsString('autocomplete="one-time-code"', $html);
        self::assertStringContainsString('<noscript>', $html);
        self::assertStringContainsString('name="codigo"', $html);
        self::assertStringContainsString('Reenviar código', $html);
        self::assertStringContainsString('Corregir correo', $html);
    }

    public function testPantallaNuevaContrasenaRevalidaCodigoEnBaseDeDatos(): void
    {
        $idUsuario = Usuario::crear([
            'nombre' => 'Ana',
            'correo' => 'ana@gmail.com',
            'contrasena' => password_hash('Secreto123', PASSWORD_DEFAULT),
            'rol' => 'comprador',
        ]);
        $codigo = Usuario::generarCodigoRestablecimiento('ana@gmail.com');
        $_SESSION['recuperacion_correo'] = 'ana@gmail.com';
        $_SESSION['recuperacion_codigo_verificado'] = true;
        $_SESSION['recuperacion_hash_verificado'] = hash('sha256', $idUsuario . ':' . $codigo);

        ob_start();
        (new AuthController())->mostrarNuevaContrasena();
        $html = ob_get_clean();

        self::assertStringContainsString('Crea tu nueva contraseña', $html);

        $this->conexion->exec("UPDATE usuario SET token_expira = '2000-01-01 00:00:00'");
        ob_start();
        (new AuthController())->mostrarNuevaContrasena();
        $htmlVencido = ob_get_clean();

        self::assertSame('', $htmlVencido);
        self::assertArrayNotHasKey('recuperacion_codigo_verificado', $_SESSION);
        self::assertSame('Código incorrecto o vencido', $_SESSION['recuperacion_error_codigo']);
    }

    public function testReenvioRespetaElIntervaloMinimoDeSesentaSegundos(): void
    {
        Usuario::crear([
            'nombre' => 'Ana',
            'correo' => 'ana@gmail.com',
            'contrasena' => password_hash('Secreto123', PASSWORD_DEFAULT),
            'rol' => 'comprador',
        ]);
        $codigo = Usuario::generarCodigoRestablecimiento('ana@gmail.com');
        $hashOriginal = hash('sha256', 1 . ':' . $codigo);
        $_SESSION['recuperacion_correo'] = 'ana@gmail.com';
        $_SESSION['recuperacion_ultimo_envio'] = time();

        (new AuthController())->reenviarCodigoRecuperacion();

        $usuario = Usuario::buscarPorCorreo('ana@gmail.com');
        self::assertSame($hashOriginal, $usuario['token_reset']);
        self::assertStringContainsString('Podrás solicitar otro código', $_SESSION['recuperacion_mensaje_reenvio']);
    }

    public function testCorregirCorreoLimpiaEstadoYConservaElValorParaElFormulario(): void
    {
        $_SESSION['recuperacion_correo'] = 'ana@gmail.com';
        $_SESSION['recuperacion_intentos'] = 3;
        $_SESSION['recuperacion_codigo_verificado'] = true;

        (new AuthController())->corregirCorreoRecuperacion();

        self::assertArrayNotHasKey('recuperacion_correo', $_SESSION);
        self::assertArrayNotHasKey('recuperacion_intentos', $_SESSION);
        self::assertArrayNotHasKey('recuperacion_codigo_verificado', $_SESSION);
        self::assertSame('ana@gmail.com', $_SESSION['correo_recuperacion_edicion']);
    }

    public function testCorreoInexistentePasaALaPantallaDeCodigoConAvisoNeutral(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = ['correo' => 'desconocido@gmail.com'];
        (new AuthController())->solicitarRestablecimiento();

        ob_start();
        (new AuthController())->mostrarRestablecer();
        $html = ob_get_clean();

        self::assertStringContainsString('Revisa tu correo', $html);
        self::assertStringContainsString('Si está asociado a una cuenta, recibirás un mensaje con un código', $html);
        self::assertSame('desconocido@gmail.com', $_SESSION['recuperacion_correo']);
    }

    public function testCodigoIncorrectoMuestraMensajeNeutralSinRevelarLaCuenta(): void
    {
        $_SESSION['recuperacion_correo'] = 'ana@gmail.com';
        $_POST = ['codigo' => '123456'];
        (new AuthController())->verificarCodigoRecuperacion();
        $mensajeCorreoInexistente = $_SESSION['recuperacion_error_codigo'];

        Usuario::crear([
            'nombre' => 'Ana',
            'correo' => 'ana@gmail.com',
            'contrasena' => password_hash('Secreto123', PASSWORD_DEFAULT),
            'rol' => 'comprador',
        ]);
        $codigoCorrecto = Usuario::generarCodigoRestablecimiento('ana@gmail.com');
        $_POST = ['codigo' => $codigoCorrecto === '123456' ? '123455' : '123456'];
        $_SESSION['recuperacion_intentos'] = 0;
        (new AuthController())->verificarCodigoRecuperacion();

        self::assertSame('Código incorrecto o vencido', $mensajeCorreoInexistente);
        self::assertSame($mensajeCorreoInexistente, $_SESSION['recuperacion_error_codigo']);
    }

    public function testAccesoGetAlVerificadorNoConsumeIntentos(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SESSION['recuperacion_correo'] = 'ana@gmail.com';
        $_SESSION['recuperacion_intentos'] = 0;
        $_POST = ['codigo' => '123456'];

        (new AuthController())->verificarCodigoRecuperacion();

        self::assertSame(0, $_SESSION['recuperacion_intentos']);
    }
}
