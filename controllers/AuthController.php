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
        $correo = is_string($_POST['correo'] ?? null)
            ? trim(strtolower($_POST['correo']))
            : '';
        $errores = [];

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            unset($_SESSION['recuperacion_correo'], $_SESSION['recuperacion_intentos']);
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

        try {
            $codigo = Usuario::generarCodigoRestablecimiento($correo);

            if ($codigo !== null) {
                $usuario = Usuario::buscarPorCorreo($correo);
                if ($usuario !== null) {
                    CorreoServicio::enviarRecuperacion(
                        $correo,
                        (string) $usuario['nombre'],
                        $codigo
                    );
                }
            }
        } catch (PDOException $excepcion) {
            error_log('Error de base de datos al solicitar recuperación: ' . $excepcion->getMessage());
        } catch (Throwable $excepcion) {
            error_log('Error al procesar solicitud de recuperación: ' . $excepcion->getMessage());
        }

        $codigo = '';
        $errores = [];
        $errorValidacion = '';
        $solicitudRecibida = true;
        include __DIR__ . '/../views/restablecer_contrasena.php';
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
        $codigo = '';
        $errores = [];
        $errorValidacion = '';
        $solicitudRecibida = false;

        if ($correo === '') {
            include __DIR__ . '/../views/enlace_invalido.php';
            return;
        }

        include __DIR__ . '/../views/restablecer_contrasena.php';
    }

    public function guardarNuevaContrasena(): void
    {
        self::iniciarSesion();
        $correo = is_string($_SESSION['recuperacion_correo'] ?? null)
            ? $_SESSION['recuperacion_correo']
            : '';
        $codigo = is_string($_POST['codigo'] ?? null)
            ? trim($_POST['codigo'])
            : '';
        $contrasena = is_string($_POST['contrasena'] ?? null)
            ? $_POST['contrasena']
            : '';
        $confirmarContrasena = is_string($_POST['confirmar_contrasena'] ?? null)
            ? $_POST['confirmar_contrasena']
            : '';

        if ($correo === '') {
            include __DIR__ . '/../views/enlace_invalido.php';
            return;
        }

        try {
            $usuario = Usuario::buscarPorCodigoRestablecimiento($correo, $codigo);

            if ($usuario === null) {
                $intentos = (int) ($_SESSION['recuperacion_intentos'] ?? 0) + 1;
                $_SESSION['recuperacion_intentos'] = $intentos;

                if ($intentos >= 5) {
                    Usuario::invalidarCodigoRestablecimiento($correo);
                    unset($_SESSION['recuperacion_correo'], $_SESSION['recuperacion_intentos']);
                    $errorValidacion = 'Código incorrecto o vencido. Solicita un código nuevo para continuar.';
                    include __DIR__ . '/../views/enlace_invalido.php';
                    return;
                }

                $errores = ['codigo' => 'Código incorrecto o vencido.'];
                $solicitudRecibida = false;
                include __DIR__ . '/../views/restablecer_contrasena.php';
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
                $errorValidacion = '';
                $solicitudRecibida = false;
                include __DIR__ . '/../views/restablecer_contrasena.php';
                return;
            }

            if (!Usuario::actualizarContrasena((int) $usuario['id_usuario'], $codigo, $contrasena)) {
                $intentos = (int) ($_SESSION['recuperacion_intentos'] ?? 0) + 1;
                $_SESSION['recuperacion_intentos'] = $intentos;

                if ($intentos >= 5) {
                    Usuario::invalidarCodigoRestablecimiento($correo);
                    unset($_SESSION['recuperacion_correo'], $_SESSION['recuperacion_intentos']);
                    $errorValidacion = 'Código incorrecto o vencido. Solicita un código nuevo para continuar.';
                    include __DIR__ . '/../views/enlace_invalido.php';
                    return;
                }

                $errores = ['codigo' => 'Código incorrecto o vencido.'];
                $solicitudRecibida = false;
                include __DIR__ . '/../views/restablecer_contrasena.php';
                return;
            }

            unset($_SESSION['recuperacion_correo'], $_SESSION['recuperacion_intentos']);
            header('Location: index.php?accion=login&mensaje=contrasena_actualizada');
            exit;
        } catch (PDOException $excepcion) {
            error_log('Error de base de datos al guardar contraseña: ' . $excepcion->getMessage());
            $errores = ['general' => 'No pudimos validar el código. Intenta de nuevo en unos minutos.'];
            $errorValidacion = '';
            $solicitudRecibida = false;
            include __DIR__ . '/../views/restablecer_contrasena.php';
        } catch (Throwable $excepcion) {
            error_log('Error al guardar contraseña: ' . $excepcion->getMessage());
            $errores = ['general' => 'No pudimos validar el código. Intenta de nuevo en unos minutos.'];
            $errorValidacion = '';
            $solicitudRecibida = false;
            include __DIR__ . '/../views/restablecer_contrasena.php';
        }
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
