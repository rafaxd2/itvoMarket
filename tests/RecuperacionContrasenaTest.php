<?php

declare(strict_types=1);

final class RecuperacionContrasenaTest extends ModeloTestCase
{
    private string|false $modoCorreoAnterior = false;

    protected function setUp(): void
    {
        parent::setUp();
        $this->modoCorreoAnterior = getenv('MAIL_MODO');
        putenv('MAIL_MODO=log');
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
        if ($this->modoCorreoAnterior === false) {
            putenv('MAIL_MODO');
        } else {
            putenv('MAIL_MODO=' . $this->modoCorreoAnterior);
        }
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
        $_SESSION['recuperacion_solicitud_valida'] = true;
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
        $_SESSION['recuperacion_solicitud_valida'] = true;
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
        $_SESSION['recuperacion_solicitud_valida'] = true;
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
        $_SESSION['recuperacion_solicitud_valida'] = true;

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
        $_SESSION['recuperacion_solicitud_valida'] = true;
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
        $_SESSION['recuperacion_solicitud_valida'] = true;
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

    public function testSolicitarRestablecimientoRechazaCorreoInvalidoSinConsultarLaBase(): void
    {
        $this->conexion->exec('DROP TABLE usuario');
        $_POST = ['correo' => 'a@gmail.com.xd'];

        ob_start();
        (new AuthController())->solicitarRestablecimiento();
        $html = ob_get_clean();

        self::assertStringContainsString(
            'Ingresa un correo válido de Gmail (ejemplo: nombre@gmail.com)',
            $html
        );
        self::assertStringContainsString('value="a@gmail.com.xd"', $html);
        self::assertArrayNotHasKey('recuperacion_correo', $_SESSION);
    }

    public function testReenviarCodigoRechazaCorreoDeSesionInvalidoSinConsultarLaBase(): void
    {
        $this->conexion->exec('DROP TABLE usuario');
        $_SESSION['recuperacion_correo'] = 'a@gmail.com.xd';
        $_SESSION['recuperacion_solicitud_valida'] = true;

        (new AuthController())->reenviarCodigoRecuperacion();

        self::assertSame('a@gmail.com.xd', $_SESSION['correo_recuperacion_edicion']);
        self::assertSame(
            'Ingresa un correo válido de Gmail (ejemplo: nombre@gmail.com)',
            $_SESSION['correo_recuperacion_error_edicion']
        );
        self::assertArrayNotHasKey('recuperacion_correo', $_SESSION);
    }

    public function testCorregirCorreoInvalidoMuestraErrorYConservaElValor(): void
    {
        $_SESSION['recuperacion_correo'] = 'a@gmail.com.xd';

        (new AuthController())->corregirCorreoRecuperacion();
        $_SERVER['REQUEST_METHOD'] = 'GET';
        ob_start();
        (new AuthController())->solicitarRestablecimiento();
        $html = ob_get_clean();

        self::assertStringContainsString(
            'Ingresa un correo válido de Gmail (ejemplo: nombre@gmail.com)',
            $html
        );
        self::assertStringContainsString('value="a@gmail.com.xd"', $html);
    }

    public function testCorreoRegistradoGeneraCodigoYAvanzaALaPantallaDeCodigo(): void
    {
        Usuario::crear([
            'nombre' => 'Ana',
            'correo' => 'ana@gmail.com',
            'contrasena' => password_hash('Secreto123', PASSWORD_DEFAULT),
            'rol' => 'comprador',
        ]);
        $_POST = ['correo' => 'Ana@Gmail.com '];
        (new AuthController())->solicitarRestablecimiento();

        self::assertSame('ana@gmail.com', $_SESSION['recuperacion_correo']);
        self::assertTrue($_SESSION['recuperacion_solicitud_valida']);
        self::assertNotNull(Usuario::buscarPorCorreo('ana@gmail.com')['token_reset']);

        ob_start();
        (new AuthController())->mostrarRestablecer();
        $html = ob_get_clean();

        self::assertStringContainsString('Revisa tu correo', $html);
        self::assertStringContainsString(
            'Enviamos un código de 6 dígitos a este correo. Ingrésalo aquí para crear una nueva contraseña.',
            $html
        );
    }

    public function testCorreoNoRegistradoNoEnviaCorreoNiGeneraCodigo(): void
    {
        $_POST = ['correo' => 'desconocido@gmail.com'];

        ob_start();
        (new AuthController())->solicitarRestablecimiento();
        $html = ob_get_clean();

        self::assertStringContainsString(
            'Este correo no está registrado. Verifica que sea el mismo con el que creaste tu cuenta.',
            $html
        );
        self::assertStringContainsString('value="desconocido@gmail.com"', $html);
        self::assertArrayNotHasKey('recuperacion_correo', $_SESSION);
        self::assertArrayNotHasKey('recuperacion_solicitud_valida', $_SESSION);
        self::assertSame(0, (int) $this->conexion->query('SELECT COUNT(*) FROM usuario')->fetchColumn());
    }

    public function testCorreoCorregidoNoRegistradoNoCreaUnaSolicitudDeRecuperacion(): void
    {
        $_SESSION['recuperacion_correo'] = 'ana@gmail.com';
        $_SESSION['recuperacion_solicitud_valida'] = true;
        (new AuthController())->corregirCorreoRecuperacion();
        $_POST = ['correo' => 'desconocido@gmail.com'];

        ob_start();
        (new AuthController())->solicitarRestablecimiento();
        $html = ob_get_clean();

        self::assertStringContainsString(
            'Este correo no está registrado. Verifica que sea el mismo con el que creaste tu cuenta.',
            $html
        );
        self::assertArrayNotHasKey('recuperacion_correo', $_SESSION);
        self::assertArrayNotHasKey('recuperacion_solicitud_valida', $_SESSION);
    }

    public function testErrorDeBaseDeDatosMuestraMensajeGenericoSinAvanzar(): void
    {
        $this->conexion->exec('DROP TABLE usuario');
        $_POST = ['correo' => 'ana@gmail.com'];

        ob_start();
        (new AuthController())->solicitarRestablecimiento();
        $html = ob_get_clean();

        self::assertStringContainsString(
            'No pudimos procesar la solicitud. Intenta de nuevo en unos minutos.',
            $html
        );
        self::assertStringContainsString('value="ana@gmail.com"', $html);
        self::assertArrayNotHasKey('recuperacion_solicitud_valida', $_SESSION);
    }

    public function testLimitaACincoSolicitudesDeCodigoCadaQuinceMinutos(): void
    {
        $_SESSION['recuperacion_solicitudes'] = array_fill(0, 5, time() - 10);
        $_POST = ['correo' => 'ana@gmail.com'];

        ob_start();
        (new AuthController())->solicitarRestablecimiento();
        $html = ob_get_clean();

        self::assertStringContainsString(
            'Demasiados intentos. Espera unos minutos e inténtalo de nuevo.',
            $html
        );
        self::assertSame(5, count($_SESSION['recuperacion_solicitudes']));
        self::assertArrayNotHasKey('recuperacion_correo', $_SESSION);
        self::assertSame(0, (int) $this->conexion->query('SELECT COUNT(*) FROM usuario')->fetchColumn());
    }

    public function testAccesoDirectoALaPantallaDeCodigoSinSolicitudValidaRegresaAlFormulario(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';

        ob_start();
        (new AuthController())->mostrarRestablecer();
        $html = ob_get_clean();

        self::assertSame('', $html);
        self::assertArrayNotHasKey('recuperacion_correo', $_SESSION);
    }

    public function testCodigoIncorrectoMuestraMensajeNeutralSinRevelarLaCuenta(): void
    {
        $_SESSION['recuperacion_correo'] = 'ana@gmail.com';
        $_SESSION['recuperacion_solicitud_valida'] = true;
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
        $_SESSION['recuperacion_solicitud_valida'] = true;
        $_SESSION['recuperacion_intentos'] = 0;
        $_POST = ['codigo' => '123456'];

        (new AuthController())->verificarCodigoRecuperacion();

        self::assertSame(0, $_SESSION['recuperacion_intentos']);
    }

    public function testReglasDeContrasenaAceptanUnaConLetraYNumeroDeOchoCaracteres(): void
    {
        self::assertSame('', validarContrasenaSegura('Abcdefg1'));
    }

    public function testReglasDeContrasenaRechazanContrasenaCortaSinLetraOSinNumero(): void
    {
        $mensajeEsperado = 'La contraseña debe tener al menos 8 caracteres, una letra y un número.';

        self::assertSame($mensajeEsperado, validarContrasenaSegura('Abc1234'));
        self::assertSame($mensajeEsperado, validarContrasenaSegura('12345678'));
        self::assertSame($mensajeEsperado, validarContrasenaSegura('abcdefgh'));
    }
}
