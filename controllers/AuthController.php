<?php

declare(strict_types=1);

require_once __DIR__ . '/../models/Usuario.php';

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
        $mensajeExito = $contexto['mensaje_exito'] ?? '';

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
