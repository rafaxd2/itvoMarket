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
        unset($_SESSION['correo_recuperacion_edicion']);
        $correo = is_string($_POST['correo'] ?? null)
            ? trim(strtolower($_POST['correo']))
            : $correoEdicion;
        $errores = [];

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            self::limpiarEstadoRecuperacion();
            $this->mostrarFormularioRestablecimiento($correo);
            return;
        }

        if ($correo === '' || filter_var($correo, FILTER_VALIDATE_EMAIL) === false) {
            $errores['correo'] = $correo === ''
                ? 'Ingresa tu correo registrado.'
                : 'Ingresa un correo electrónico válido.';
            $this->mostrarFormularioRestablecimiento($correo, $errores);
            return;
        }

        $_SESSION['recuperacion_correo'] = $correo;
        $_SESSION['recuperacion_intentos'] = 0;
        unset(
            $_SESSION['recuperacion_bloqueada'],
            $_SESSION['recuperacion_codigo_verificado'],
            $_SESSION['recuperacion_hash_verificado']
        );
        $_SESSION['recuperacion_ultimo_envio'] = time();

        try {
            $this->enviarCodigoRecuperacion($correo);
        } catch (PDOException $excepcion) {
            error_log('Error de base de datos al solicitar recuperación: ' . $excepcion->getMessage());
        } catch (Throwable $excepcion) {
            error_log('Error al procesar solicitud de recuperación: ' . $excepcion->getMessage());
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

        if ($correo === '') {
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

        if ($correo === '') {
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
        $correo = is_string($_SESSION['recuperacion_correo'] ?? null)
            ? $_SESSION['recuperacion_correo']
            : '';

        if ($correo === '') {
            header('Location: index.php?accion=olvide');
            return;
        }

        $tiempoRestante = 60 - (time() - (int) ($_SESSION['recuperacion_ultimo_envio'] ?? 0));
        if ($tiempoRestante > 0) {
            $_SESSION['recuperacion_mensaje_reenvio'] = 'Podrás solicitar otro código en ' . $tiempoRestante . ' segundos.';
            header('Location: index.php?accion=restablecer');
            return;
        }

        try {
            $envioExitoso = $this->enviarCodigoRecuperacion($correo);
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
        $_SESSION['correo_recuperacion_edicion'] = is_string($_SESSION['recuperacion_correo'] ?? null)
            ? $_SESSION['recuperacion_correo']
            : '';
        self::limpiarEstadoRecuperacion();
        header('Location: index.php?accion=olvide');
        return;
    }

    public function mostrarNuevaContrasena(): void
    {
        self::iniciarSesion();
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

        if ($correo === '' || empty($_SESSION['recuperacion_codigo_verificado'])) {
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

    private function enviarCodigoRecuperacion(string $correo): bool
    {
        $codigo = Usuario::generarCodigoRestablecimiento($correo);
        if ($codigo === null) {
            return true;
        }

        $usuario = Usuario::buscarPorCorreo($correo);
        if ($usuario !== null) {
            return CorreoServicio::enviarRecuperacion($correo, (string) $usuario['nombre'], $codigo);
        }

        return true;
    }

    private static function limpiarEstadoRecuperacion(): void
    {
        unset(
            $_SESSION['recuperacion_correo'],
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
        $correo = trim((string) ($_POST['correo'] ?? ''));
        $contrasena = (string) ($_POST['contrasena'] ?? '');
        $rol = trim((string) ($_POST['rol'] ?? ''));
        $errores = [];

        if ($correo === '' || $contrasena === '' || $rol === '') {
            $errores['general'] = 'Completa todos los campos obligatorios.';
        }

        if ($correo !== '' && Usuario::buscarPorCorreo($correo) !== null) {
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
                'correo' => strtolower($correo),
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
