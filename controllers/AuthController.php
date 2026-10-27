<?php

declare(strict_types=1);

require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/../services/CorreoServicio.php';
require_once __DIR__ . '/../helpers/Validaciones.php';

// Controlador que centraliza el registro, acceso y cierre de sesión del sistema.
class AuthController
{
    // Inicia la sesión de PHP si todavía no está activa.
    public static function iniciarSesion(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    // Redirige al usuario al login si no tiene una sesión activa.
    public static function requerirSesion(string $rutaLogin = 'index.php?accion=login'): void
    {
        self::iniciarSesion();

        if (empty($_SESSION['usuario_id'])) {
            header('Location: ' . $rutaLogin);
            exit;
        }
    }

    // Redirige al usuario cuando intenta entrar a una vista reservada para un rol específico.
    public static function requerirRol(string $rolEsperado, string $rutaLogin = 'index.php?accion=login'): void
    {
        self::iniciarSesion();

        $rolActual = strtolower((string) ($_SESSION['usuario_rol'] ?? ''));
        if ($rolActual !== strtolower($rolEsperado)) {
            header('Location: ' . $rutaLogin);
            exit;
        }
    }

    // Muestra la vista de inicio de sesión con mensajes de validación y valores previos.
    public function mostrarLogin(array $contexto = []): void
    {
        $correo = $contexto['correo'] ?? '';
        $errores = $contexto['errores'] ?? [];
        $mensajeExito = $contexto['mensaje_exito']
            ?? (($_GET['mensaje'] ?? '') === 'contrasena_actualizada'
                ? 'Contraseña actualizada. Ya puedes iniciar sesión.'
                : '');

        include __DIR__ . '/../views/login.php';
    }

    // Muestra la vista de registro con mensajes de validación y datos ya ingresados.
    public function mostrarRegistro(array $contexto = []): void
    {
        $correo = $contexto['correo'] ?? '';
        $rol = $contexto['rol'] ?? 'comprador';
        $errores = $contexto['errores'] ?? [];

        include __DIR__ . '/../views/registro.php';
    }

    public function solicitarRestablecimiento(): void
    {
        self::iniciarSesion();
        $correoEdicion = is_string($_SESSION['correo_recuperacion_edicion'] ?? null)
            ? $_SESSION['correo_recuperacion_edicion']
            : '';
        $errorCorreoEdicion = is_string($_SESSION['correo_recuperacion_error_edicion'] ?? null)
            ? $_SESSION['correo_recuperacion_error_edicion']
            : '';
        unset($_SESSION['correo_recuperacion_edicion']);
        unset($_SESSION['correo_recuperacion_error_edicion']);
        $esSolicitudPost = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
        $correoOriginal = $esSolicitudPost
            ? (is_string($_POST['correo'] ?? null) ? $_POST['correo'] : '')
            : $correoEdicion;
        $correo = normalizarCorreoGmail($correoOriginal) ?? $correoOriginal;
        $errores = [];

        if (!$esSolicitudPost) {
            self::limpiarEstadoRecuperacion();
            $this->mostrarFormularioRestablecimiento($correo, $errorCorreoEdicion === ''
                ? []
                : ['correo' => $errorCorreoEdicion]);
            return;
        }

        self::limpiarEstadoRecuperacion();
        $correoNormalizado = normalizarCorreoGmail($correoOriginal);
        if ($correoNormalizado === null) {
            $errores['correo'] = 'Ingresa un correo válido de Gmail (ejemplo: nombre@gmail.com)';
            $this->mostrarFormularioRestablecimiento($correo, $errores);
            return;
        }

        $correo = $correoNormalizado;
        if (!self::registrarSolicitudCodigo()) {
            $errores['general'] = 'Demasiados intentos. Espera unos minutos e inténtalo de nuevo.';
            $this->mostrarFormularioRestablecimiento($correo, $errores);
            return;
        }

        try {
            $usuario = Usuario::buscarPorCorreo($correo);
            if ($usuario === null) {
                $errores['correo'] = 'Este correo no está registrado. Verifica que sea el mismo con el que creaste tu cuenta.';
                $this->mostrarFormularioRestablecimiento($correo, $errores);
                return;
            }

            if (!$this->enviarCodigoRecuperacion($correo, $usuario)) {
                $errores['general'] = 'No pudimos procesar la solicitud. Intenta de nuevo en unos minutos.';
                $this->mostrarFormularioRestablecimiento($correo, $errores);
                return;
            }

            $_SESSION['recuperacion_correo'] = $correo;
            $_SESSION['recuperacion_solicitud_valida'] = true;
            $_SESSION['recuperacion_intentos'] = 0;
            $_SESSION['recuperacion_ultimo_envio'] = time();
        } catch (PDOException $excepcion) {
            error_log('Error de base de datos al solicitar recuperación: ' . $excepcion->getMessage());
            $errores['general'] = 'No pudimos procesar la solicitud. Intenta de nuevo en unos minutos.';
            $this->mostrarFormularioRestablecimiento($correo, $errores);
            return;
        } catch (Throwable $excepcion) {
            error_log('Error al procesar solicitud de recuperación: ' . $excepcion->getMessage());
            $errores['general'] = 'No pudimos procesar la solicitud. Intenta de nuevo en unos minutos.';
            $this->mostrarFormularioRestablecimiento($correo, $errores);
            return;
        }

        header('Location: index.php?accion=restablecer');
        return;
    }

    private function mostrarFormularioRestablecimiento(
        string $correo,
        array $errores = []
    ): void {
        include __DIR__ . '/../views/olvide_contrasena.php';
    }

    public function mostrarRestablecer(): void
    {
        self::iniciarSesion();
        $correo = is_string($_SESSION['recuperacion_correo'] ?? null)
            ? $_SESSION['recuperacion_correo']
            : '';

        if ($correo === '' || empty($_SESSION['recuperacion_solicitud_valida'])) {
            self::limpiarEstadoRecuperacion();
            header('Location: index.php?accion=olvide');
            return;
        }

        if (!empty($_SESSION['recuperacion_codigo_verificado'])) {
            header('Location: index.php?accion=nueva_contrasena');
            return;
        }

        $errorCodigo = is_string($_SESSION['recuperacion_error_codigo'] ?? null)
            ? $_SESSION['recuperacion_error_codigo']
            : '';
        unset($_SESSION['recuperacion_error_codigo']);
        $mensajeReenvio = is_string($_SESSION['recuperacion_mensaje_reenvio'] ?? null)
            ? $_SESSION['recuperacion_mensaje_reenvio']
            : '';
        unset($_SESSION['recuperacion_mensaje_reenvio']);
        include __DIR__ . '/../views/restablecer_contrasena.php';
    }

    public function verificarCodigoRecuperacion(): void
    {
        self::iniciarSesion();
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            header('Location: index.php?accion=restablecer');
            return;
        }

        $correo = is_string($_SESSION['recuperacion_correo'] ?? null)
            ? $_SESSION['recuperacion_correo']
            : '';
        $codigo = is_string($_POST['codigo'] ?? null) ? trim($_POST['codigo']) : '';

        if ($correo === '' || empty($_SESSION['recuperacion_solicitud_valida'])) {
            self::limpiarEstadoRecuperacion();
            header('Location: index.php?accion=olvide');
            return;
        }

        if (!empty($_SESSION['recuperacion_bloqueada'])) {
            $_SESSION['recuperacion_error_codigo'] = 'Código incorrecto o vencido. Solicita un código nuevo.';
            header('Location: index.php?accion=restablecer');
            return;
        }

        try {
            $usuario = Usuario::buscarPorCodigoRestablecimiento($correo, $codigo);

            if ($usuario === null) {
                $intentos = (int) ($_SESSION['recuperacion_intentos'] ?? 0) + 1;
                $_SESSION['recuperacion_intentos'] = $intentos;

                if ($intentos >= 5) {
                    Usuario::invalidarCodigoRestablecimiento($correo);
                    unset($_SESSION['recuperacion_hash_verificado'], $_SESSION['recuperacion_codigo_verificado']);
                    $_SESSION['recuperacion_bloqueada'] = true;
                }

                $_SESSION['recuperacion_error_codigo'] = 'Código incorrecto o vencido';
                header('Location: index.php?accion=restablecer');
                return;
            }

            $_SESSION['recuperacion_codigo_verificado'] = true;
            $_SESSION['recuperacion_hash_verificado'] = (string) $usuario['token_reset'];
            $_SESSION['recuperacion_intentos'] = 0;
            unset($_SESSION['recuperacion_error_codigo']);
            header('Location: index.php?accion=nueva_contrasena');
            return;
        } catch (PDOException $excepcion) {
            error_log('Error de base de datos al verificar código: ' . $excepcion->getMessage());
            $_SESSION['recuperacion_error_codigo'] = 'No pudimos verificar el código. Intenta de nuevo en unos minutos.';
            header('Location: index.php?accion=restablecer');
            return;
        } catch (Throwable $excepcion) {
            error_log('Error al verificar código: ' . $excepcion->getMessage());
            $_SESSION['recuperacion_error_codigo'] = 'No pudimos verificar el código. Intenta de nuevo en unos minutos.';
            header('Location: index.php?accion=restablecer');
            return;
        }
    }

    public function reenviarCodigoRecuperacion(): void
    {
        self::iniciarSesion();
        if (empty($_SESSION['recuperacion_solicitud_valida'])) {
            self::limpiarEstadoRecuperacion();
            header('Location: index.php?accion=olvide');
            return;
        }

        $correoOriginal = is_string($_SESSION['recuperacion_correo'] ?? null)
            ? $_SESSION['recuperacion_correo']
            : '';

        $correo = normalizarCorreoGmail($correoOriginal);
        if ($correo === null) {
            $_SESSION['correo_recuperacion_edicion'] = $correoOriginal;
            $_SESSION['correo_recuperacion_error_edicion'] = 'Ingresa un correo válido de Gmail (ejemplo: nombre@gmail.com)';
            self::limpiarEstadoRecuperacion();
            header('Location: index.php?accion=olvide');
            return;
        }
        $_SESSION['recuperacion_correo'] = $correo;

        $tiempoRestante = 60 - (time() - (int) ($_SESSION['recuperacion_ultimo_envio'] ?? 0));
        if ($tiempoRestante > 0) {
            $_SESSION['recuperacion_mensaje_reenvio'] = 'Podrás solicitar otro código en ' . $tiempoRestante . ' segundos.';
            header('Location: index.php?accion=restablecer');
            return;
        }

        if (!self::registrarSolicitudCodigo()) {
            $_SESSION['recuperacion_mensaje_reenvio'] = 'Demasiados intentos. Espera unos minutos e inténtalo de nuevo.';
            header('Location: index.php?accion=restablecer');
            return;
        }

        try {
            $usuario = Usuario::buscarPorCorreo($correo);
            if ($usuario === null) {
                $_SESSION['correo_recuperacion_edicion'] = $correo;
                $_SESSION['correo_recuperacion_error_edicion'] = 'Este correo no está registrado. Verifica que sea el mismo con el que creaste tu cuenta.';
                self::limpiarEstadoRecuperacion();
                header('Location: index.php?accion=olvide');
                return;
            }

            $envioExitoso = $this->enviarCodigoRecuperacion($correo, $usuario);
            $_SESSION['recuperacion_ultimo_envio'] = time();
            $_SESSION['recuperacion_intentos'] = 0;
            unset(
                $_SESSION['recuperacion_bloqueada'],
                $_SESSION['recuperacion_codigo_verificado'],
                $_SESSION['recuperacion_hash_verificado']
            );
            $_SESSION['recuperacion_mensaje_reenvio'] = $envioExitoso
                ? 'Te enviamos un código nuevo'
                : 'No pudimos enviar el código. Intenta de nuevo en unos minutos.';
        } catch (PDOException $excepcion) {
            error_log('Error de base de datos al reenviar código: ' . $excepcion->getMessage());
            $_SESSION['recuperacion_mensaje_reenvio'] = 'No pudimos enviar el código. Intenta de nuevo en unos minutos.';
        } catch (Throwable $excepcion) {
            error_log('Error al reenviar código: ' . $excepcion->getMessage());
            $_SESSION['recuperacion_mensaje_reenvio'] = 'No pudimos enviar el código. Intenta de nuevo en unos minutos.';
        }

        header('Location: index.php?accion=restablecer');
        return;
    }

    public function corregirCorreoRecuperacion(): void
    {
        self::iniciarSesion();
        $correoOriginal = is_string($_SESSION['recuperacion_correo'] ?? null)
            ? $_SESSION['recuperacion_correo']
            : '';
        $correo = normalizarCorreoGmail($correoOriginal);
        $_SESSION['correo_recuperacion_edicion'] = $correo ?? $correoOriginal;
        if ($correo === null) {
            $_SESSION['correo_recuperacion_error_edicion'] = 'Ingresa un correo válido de Gmail (ejemplo: nombre@gmail.com)';
        } else {
            unset($_SESSION['correo_recuperacion_error_edicion']);
        }
        self::limpiarEstadoRecuperacion();
        header('Location: index.php?accion=olvide');
        return;
    }

    public function mostrarNuevaContrasena(): void
    {
        self::iniciarSesion();
        if (empty($_SESSION['recuperacion_solicitud_valida'])) {
            self::limpiarEstadoRecuperacion();
            header('Location: index.php?accion=olvide');
            return;
        }

        $correo = is_string($_SESSION['recuperacion_correo'] ?? null)
            ? $_SESSION['recuperacion_correo']
            : '';
        $hashCodigo = is_string($_SESSION['recuperacion_hash_verificado'] ?? null)
            ? $_SESSION['recuperacion_hash_verificado']
            : '';

        try {
            if (
                $correo === ''
                || empty($_SESSION['recuperacion_codigo_verificado'])
                || Usuario::buscarPorHashCodigoRestablecimiento($correo, $hashCodigo) === null
            ) {
                unset($_SESSION['recuperacion_codigo_verificado'], $_SESSION['recuperacion_hash_verificado']);
                $_SESSION['recuperacion_error_codigo'] = 'Código incorrecto o vencido';
                header('Location: index.php?accion=restablecer');
                return;
            }

            $errores = [];
            include __DIR__ . '/../views/nueva_contrasena.php';
        } catch (PDOException $excepcion) {
            error_log('Error de base de datos al validar código verificado: ' . $excepcion->getMessage());
            unset($_SESSION['recuperacion_codigo_verificado'], $_SESSION['recuperacion_hash_verificado']);
            $_SESSION['recuperacion_error_codigo'] = 'No pudimos validar el código. Intenta de nuevo en unos minutos.';
            header('Location: index.php?accion=restablecer');
            return;
        } catch (Throwable $excepcion) {
            error_log('Error al validar código verificado: ' . $excepcion->getMessage());
            unset($_SESSION['recuperacion_codigo_verificado'], $_SESSION['recuperacion_hash_verificado']);
            $_SESSION['recuperacion_error_codigo'] = 'No pudimos validar el código. Intenta de nuevo en unos minutos.';
            header('Location: index.php?accion=restablecer');
            return;
        }
    }

    public function guardarNuevaContrasena(): void
    {
        self::iniciarSesion();
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            header('Location: index.php?accion=nueva_contrasena');
            return;
        }

        $correo = is_string($_SESSION['recuperacion_correo'] ?? null)
            ? $_SESSION['recuperacion_correo']
            : '';
        $hashCodigo = is_string($_SESSION['recuperacion_hash_verificado'] ?? null)
            ? $_SESSION['recuperacion_hash_verificado']
            : '';
        $contrasena = is_string($_POST['contrasena'] ?? null) ? $_POST['contrasena'] : '';
        $confirmarContrasena = is_string($_POST['confirmar_contrasena'] ?? null)
            ? $_POST['confirmar_contrasena']
            : '';

        if (
            $correo === ''
            || empty($_SESSION['recuperacion_solicitud_valida'])
            || empty($_SESSION['recuperacion_codigo_verificado'])
        ) {
            header('Location: index.php?accion=restablecer');
            return;
        }

        try {
            $usuario = Usuario::buscarPorHashCodigoRestablecimiento($correo, $hashCodigo);
            if ($usuario === null) {
                unset($_SESSION['recuperacion_codigo_verificado'], $_SESSION['recuperacion_hash_verificado']);
                $_SESSION['recuperacion_error_codigo'] = 'Código incorrecto o vencido';
                header('Location: index.php?accion=restablecer');
                return;
            }

            $errores = [];
            if ($contrasena === '') {
                $errores['contrasena'] = 'Ingresa una nueva contraseña.';
            } else {
                $errorContrasena = validarContrasenaSegura($contrasena);
                if ($errorContrasena !== '') {
                    $errores['contrasena'] = $errorContrasena;
                }
            }

            if ($confirmarContrasena === '') {
                $errores['confirmar_contrasena'] = 'Confirma la nueva contraseña.';
            } elseif ($contrasena !== $confirmarContrasena) {
                $errores['confirmar_contrasena'] = 'Las contraseñas no coinciden.';
            }

            if ($errores !== []) {
                include __DIR__ . '/../views/nueva_contrasena.php';
                return;
            }

            $contrasenaHash = password_hash($contrasena, PASSWORD_DEFAULT);
            if ($contrasenaHash === false || !Usuario::actualizarContrasenaConHashCodigo(
                (int) $usuario['id_usuario'],
                $hashCodigo,
                $contrasenaHash
            )) {
                unset($_SESSION['recuperacion_codigo_verificado'], $_SESSION['recuperacion_hash_verificado']);
                $_SESSION['recuperacion_error_codigo'] = 'Código incorrecto o vencido';
                header('Location: index.php?accion=restablecer');
                return;
            }

            self::limpiarEstadoRecuperacion();
            header('Location: index.php?accion=login&mensaje=contrasena_actualizada');
            return;
        } catch (PDOException $excepcion) {
            error_log('Error de base de datos al guardar contraseña: ' . $excepcion->getMessage());
            $errores = ['general' => 'No pudimos actualizar la contraseña. Intenta de nuevo en unos minutos.'];
            include __DIR__ . '/../views/nueva_contrasena.php';
        } catch (Throwable $excepcion) {
            error_log('Error al guardar contraseña: ' . $excepcion->getMessage());
            $errores = ['general' => 'No pudimos actualizar la contraseña. Intenta de nuevo en unos minutos.'];
            include __DIR__ . '/../views/nueva_contrasena.php';
        }
    }

    private function enviarCodigoRecuperacion(string $correo, array $usuario): bool
    {
        $codigo = Usuario::generarCodigoRestablecimiento($correo);
        if ($codigo === null) {
            return false;
        }

        $envioExitoso = CorreoServicio::enviarRecuperacion($correo, (string) $usuario['nombre'], $codigo);
        if (!$envioExitoso) {
            Usuario::invalidarCodigoRestablecimiento($correo);
        }

        return $envioExitoso;
    }

    // Limita a cinco las solicitudes de código realizadas durante los últimos quince minutos.
    private static function registrarSolicitudCodigo(): bool
    {
        $ahora = time();
        $solicitudes = $_SESSION['recuperacion_solicitudes'] ?? [];
        $solicitudes = is_array($solicitudes)
            ? array_values(array_filter(
                $solicitudes,
                static fn ($fecha): bool => is_int($fecha) && $fecha > $ahora - 900
            ))
            : [];

        if (count($solicitudes) >= 5) {
            $_SESSION['recuperacion_solicitudes'] = $solicitudes;
            return false;
        }

        $solicitudes[] = $ahora;
        $_SESSION['recuperacion_solicitudes'] = $solicitudes;
        return true;
    }

    private static function limpiarEstadoRecuperacion(): void
    {
        unset(
            $_SESSION['recuperacion_correo'],
            $_SESSION['recuperacion_solicitud_valida'],
            $_SESSION['recuperacion_intentos'],
            $_SESSION['recuperacion_ultimo_envio'],
            $_SESSION['recuperacion_bloqueada'],
            $_SESSION['recuperacion_codigo_verificado'],
            $_SESSION['recuperacion_hash_verificado'],
            $_SESSION['recuperacion_error_codigo'],
            $_SESSION['recuperacion_mensaje_reenvio']
        );
    }

    // Registra una cuenta nueva, valida corrreo único y cifra la contraseña con password_hash().
    public function registrar(): void
    {
        $correoOriginal = is_string($_POST['correo'] ?? null) ? $_POST['correo'] : '';
        $correo = normalizarCorreoGmail($correoOriginal) ?? trim($correoOriginal);
        $contrasena = (string) ($_POST['contrasena'] ?? '');
        $rol = trim((string) ($_POST['rol'] ?? ''));
        $errores = [];

        if ($correo === '' || $contrasena === '' || $rol === '') {
            $errores['general'] = 'Completa todos los campos obligatorios.';
        }

        $correoNormalizado = normalizarCorreoGmail($correoOriginal);
        if ($correo !== '' && $correoNormalizado === null) {
            $dominioCorreo = strrchr(strtolower(trim($correoOriginal)), '@');
            $errores['correo'] = $dominioCorreo !== false && $dominioCorreo !== '@gmail.com'
                ? 'El correo electrónico debe pertenecer a @gmail.com.'
                : 'El correo electrónico no es válido.';
        } elseif ($correoNormalizado !== null && Usuario::buscarPorCorreo($correoNormalizado) !== null) {
            $errores['correo'] = 'Este correo ya está registrado';
        }

        if (!empty($errores)) {
            $this->mostrarRegistro([
                'correo' => $correo,
                'rol' => $rol,
                'errores' => $errores,
            ]);
            return;
        }

        $nombre = strtolower(explode('@', $correo)[0]);
        $contrasenaCifrada = password_hash($contrasena, PASSWORD_DEFAULT);
        try {
            $idUsuario = Usuario::crear([
                'nombre' => $nombre,
                'correo' => $correoNormalizado,
                'contrasena' => $contrasenaCifrada,
                'rol' => strtolower($rol),
            ]);
        } catch (InvalidArgumentException $exception) {
            $campoCorreo = str_contains(strtolower($exception->getMessage()), 'correo');
            $this->mostrarRegistro([
                'correo' => $correo,
                'rol' => $rol,
                'errores' => $campoCorreo
                    ? ['correo' => $exception->getMessage()]
                    : ['general' => $exception->getMessage()],
            ]);
            return;
        } catch (DomainException $exception) {
            $this->mostrarRegistro([
                'correo' => $correo,
                'rol' => $rol,
                'errores' => ['correo' => 'Este correo ya está registrado.'],
            ]);
            return;
        } catch (PDOException $exception) {
            if ((string) $exception->getCode() === '23000') {
                $this->mostrarRegistro([
                    'correo' => $correo,
                    'rol' => $rol,
                    'errores' => ['correo' => 'Este correo ya está registrado.'],
                ]);
                return;
            }

            $this->mostrarRegistro([
                'correo' => $correo,
                'rol' => $rol,
                'errores' => ['general' => 'No fue posible crear la cuenta. Inténtalo nuevamente.'],
            ]);
            return;
        }

        if (strtolower($rol) === 'vendedor') {
            $conexion = obtenerConexionBaseDatos();
            $consultaVendedor = $conexion->prepare(
                'INSERT INTO vendedores (id_usuario, nombre_negocio, descripcion, horario_atencion) VALUES (:id_usuario, :nombre_negocio, :descripcion, :horario_atencion)'
            );
            $consultaVendedor->execute([
                ':id_usuario' => $idUsuario,
                ':nombre_negocio' => ucfirst($nombre) . ' Market',
                ':descripcion' => 'Vendedor activo en ITVOmarket',
                ':horario_atencion' => 'Lunes a domingo',
            ]);
        }

        header('Location: index.php?accion=login&mensaje=registro_exitoso');
        exit;
    }

    // Inicia sesión y redirige a la vista correspondiente según el rol del usuario.
    public function login(): void
    {
        $correo = trim(strtolower((string) ($_POST['correo'] ?? '')));
        $contrasena = (string) ($_POST['contrasena'] ?? '');

        if ($correo === '' || $contrasena === '') {
            $this->mostrarLogin([
                'correo' => $correo,
                'errores' => ['general' => 'Correo o contraseña incorrectos'],
            ]);
            return;
        }

        $usuario = Usuario::verificarCredenciales($correo, $contrasena);

        if ($usuario === null) {
            $this->mostrarLogin([
                'correo' => $correo,
                'errores' => ['general' => 'Correo o contraseña incorrectos'],
            ]);
            return;
        }

        session_start();
        $_SESSION['usuario_id'] = $usuario['id_usuario'];
        $_SESSION['usuario_nombre'] = $usuario['nombre'];
        $_SESSION['usuario_rol'] = ucfirst(strtolower((string) $usuario['rol']));

        $destino = strtolower((string) $usuario['rol']) === 'vendedor'
            ? 'panel.php?rol=vendedor'
            : 'index.php?accion=catalogo';

        header('Location: ' . $destino);
        exit;
    }

    // Cierra la sesión y regresa al login del sistema.
    public function cerrarSesion(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        session_destroy();
        header('Location: index.php?accion=login');
        exit;
    }

    // Cambia el rol del usuario y actualiza el valor guardado en la sesión.
    public function cambiarRol(): void
    {
        self::iniciarSesion();

        if (empty($_SESSION['usuario_id'])) {
            header('Location: index.php?accion=login');
            exit;
        }

        $rolNuevo = strtolower(trim((string) ($_POST['rol'] ?? '')));
        $rolesValidos = ['comprador', 'vendedor'];

        if (!in_array($rolNuevo, $rolesValidos, true)) {
            $rolNuevo = strtolower((string) ($_SESSION['usuario_rol'] ?? 'comprador'));
        }

        $conexion = obtenerConexionBaseDatos();
        $rolGuardado = ucfirst(strtolower($rolNuevo));
        $consulta = $conexion->prepare('UPDATE usuario SET rol = :rol WHERE id_usuario = :id_usuario');
        $consulta->execute([
            ':rol' => $rolGuardado,
            ':id_usuario' => (int) $_SESSION['usuario_id'],
        ]);

        if ($rolNuevo === 'vendedor') {
            $consultaVendedor = $conexion->prepare('SELECT id_vendedor FROM vendedores WHERE id_usuario = :id_usuario LIMIT 1');
            $consultaVendedor->execute([':id_usuario' => (int) $_SESSION['usuario_id']]);
            $vendedor = $consultaVendedor->fetch();

            if ($vendedor === false) {
                $nombreNegocio = ucfirst((string) ($_SESSION['usuario_nombre'] ?? 'Vendedor')) . ' Market';
                $insertarVendedor = $conexion->prepare(
                    'INSERT INTO vendedores (id_usuario, nombre_negocio, descripcion, horario_atencion) VALUES (:id_usuario, :nombre_negocio, :descripcion, :horario_atencion)'
                );
                $insertarVendedor->execute([
                    ':id_usuario' => (int) $_SESSION['usuario_id'],
                    ':nombre_negocio' => $nombreNegocio,
                    ':descripcion' => 'Vendedor activo en ITVOmarket',
                    ':horario_atencion' => 'Lunes a domingo',
                ]);
            }
        }

        $_SESSION['usuario_rol'] = $rolGuardado;

        $destino = $rolNuevo === 'vendedor'
            ? 'index.php?accion=publicar&rol_cambiado=1'
            : 'index.php?accion=catalogo&rol_cambiado=1';

        header('Location: ' . $destino);
        exit;
    }
}
