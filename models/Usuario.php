<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

// Clase que encapsula la lógica de acceso y registro de usuarios del sistema.
class Usuario
{
    // Busca un usuario por su correo electrónico y devuelve la fila completa cuando existe.
    public static function buscarPorCorreo(string $correo): ?array
    {
        $conexion = obtenerConexionBaseDatos();
        $consulta = $conexion->prepare('SELECT * FROM usuario WHERE correo = :correo LIMIT 1');
        $consulta->execute([':correo' => trim(strtolower($correo))]);
        $usuario = $consulta->fetch();

        return $usuario !== false ? $usuario : null;
    }

    public static function generarCodigoRestablecimiento(string $correo): ?string
    {
        $conexion = obtenerConexionBaseDatos();
        $consultaUsuario = $conexion->prepare('SELECT id_usuario FROM usuario WHERE correo = :correo LIMIT 1');
        $consultaUsuario->execute([':correo' => trim(strtolower($correo))]);
        $usuario = $consultaUsuario->fetch();

        if ($usuario === false) {
            return null;
        }

        $codigo = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $hashCodigo = hash('sha256', (int) $usuario['id_usuario'] . ':' . $codigo);
        $fechaExpiracion = $conexion->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite'
            ? "datetime('now', '+15 minutes')"
            : 'DATE_ADD(NOW(), INTERVAL 15 MINUTE)';
        $consultaToken = $conexion->prepare(
            'UPDATE usuario
            SET token_reset = :token_reset, token_expira = ' . $fechaExpiracion . '
            WHERE id_usuario = :id_usuario'
        );
        $consultaToken->execute([
            ':token_reset' => $hashCodigo,
            ':id_usuario' => (int) $usuario['id_usuario'],
        ]);

        return $codigo;
    }

    public static function buscarPorCodigoRestablecimiento(string $correo, string $codigo): ?array
    {
        if (preg_match('/\A[0-9]{6}\z/', $codigo) !== 1) {
            return null;
        }

        $conexion = obtenerConexionBaseDatos();
        $consultaUsuario = $conexion->prepare(
            'SELECT id_usuario FROM usuario WHERE correo = :correo LIMIT 1'
        );
        $consultaUsuario->execute([':correo' => trim(strtolower($correo))]);
        $usuario = $consultaUsuario->fetch();

        if ($usuario === false) {
            return null;
        }

        $hashCodigo = hash('sha256', (int) $usuario['id_usuario'] . ':' . $codigo);
        return self::buscarPorHashCodigoRestablecimiento($correo, $hashCodigo);
    }

    public static function buscarPorHashCodigoRestablecimiento(string $correo, string $hashCodigo): ?array
    {
        if (preg_match('/\A[a-f0-9]{64}\z/', $hashCodigo) !== 1) {
            return null;
        }

        $conexion = obtenerConexionBaseDatos();
        $consultaUsuario = $conexion->prepare(
            'SELECT id_usuario FROM usuario WHERE correo = :correo LIMIT 1'
        );
        $consultaUsuario->execute([':correo' => trim(strtolower($correo))]);
        $usuario = $consultaUsuario->fetch();

        if ($usuario === false) {
            return null;
        }

        $fechaActual = $conexion->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite'
            ? "datetime('now')"
            : 'NOW()';
        $consulta = $conexion->prepare(
            'SELECT * FROM usuario
            WHERE id_usuario = :id_usuario
            AND token_reset = :token_reset
            AND token_expira > ' . $fechaActual . '
            LIMIT 1'
        );
        $consulta->execute([
            ':id_usuario' => (int) $usuario['id_usuario'],
            ':token_reset' => $hashCodigo,
        ]);
        $usuario = $consulta->fetch();

        return $usuario !== false ? $usuario : null;
    }

    public static function actualizarContrasena(int $idUsuario, string $codigo, string $contrasena): bool
    {
        if (preg_match('/\A[0-9]{6}\z/', $codigo) !== 1) {
            return false;
        }

        $contrasenaHash = password_hash($contrasena, PASSWORD_DEFAULT);

        if ($contrasenaHash === false) {
            throw new RuntimeException('No fue posible cifrar la contraseña.');
        }

        $hashCodigo = hash('sha256', $idUsuario . ':' . $codigo);
        return self::actualizarContrasenaConHashCodigo($idUsuario, $hashCodigo, $contrasenaHash);
    }

    public static function actualizarContrasenaConHashCodigo(
        int $idUsuario,
        string $hashCodigo,
        string $contrasenaHash
    ): bool {
        if (
            preg_match('/\A[a-f0-9]{64}\z/', $hashCodigo) !== 1
            || password_get_info($contrasenaHash)['algo'] === null
        ) {
            return false;
        }

        $conexion = obtenerConexionBaseDatos();
        $fechaActual = $conexion->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite'
            ? "datetime('now')"
            : 'NOW()';
        $consulta = $conexion->prepare(
            'UPDATE usuario
            SET contrasena = :contrasena, token_reset = NULL, token_expira = NULL
            WHERE id_usuario = :id_usuario
            AND token_reset = :token_reset
            AND token_expira > ' . $fechaActual
        );
        $consulta->execute([
            ':contrasena' => $contrasenaHash,
            ':id_usuario' => $idUsuario,
            ':token_reset' => $hashCodigo,
        ]);

        return $consulta->rowCount() === 1;
    }

    public static function invalidarCodigoRestablecimiento(string $correo): void
    {
        $conexion = obtenerConexionBaseDatos();
        $consulta = $conexion->prepare(
            'UPDATE usuario
            SET token_reset = NULL, token_expira = NULL
            WHERE correo = :correo'
        );
        $consulta->execute([':correo' => trim(strtolower($correo))]);
    }

    // Crea un usuario nuevo con nombre, correo, contraseña cifrada y rol válidos.
    public static function crear(...$argumentos): int
    {
        $datos = self::normalizarDatosUsuario($argumentos);
        $datos['nombre'] = trim($datos['nombre']);
        $datos['correo'] = strtolower(trim($datos['correo']));
        $datos['rol'] = ucfirst(strtolower(trim($datos['rol'])));

        if ($datos['nombre'] === '' || $datos['correo'] === '' || $datos['contrasena'] === '' || $datos['rol'] === '') {
            throw new InvalidArgumentException('El nombre, correo, contraseña y rol son obligatorios.');
        }

        if (filter_var($datos['correo'], FILTER_VALIDATE_EMAIL) === false) {
            throw new InvalidArgumentException('El correo electrónico no es válido.');
        }

        $posicionArroba = strrpos($datos['correo'], '@');
        $dominioCorreo = substr($datos['correo'], $posicionArroba + 1);
        if ($dominioCorreo !== 'gmail.com') {
            throw new InvalidArgumentException('El correo electrónico debe pertenecer a @gmail.com.');
        }

        if (!in_array($datos['rol'], ['Comprador', 'Vendedor'], true)) {
            throw new InvalidArgumentException('El rol seleccionado no es válido.');
        }

        if (self::buscarPorCorreo($datos['correo']) !== null) {
            throw new DomainException('El correo electrónico ya está registrado.');
        }

        $conexion = obtenerConexionBaseDatos();

        $consulta = $conexion->prepare(
            'INSERT INTO usuario (nombre, correo, contrasena, rol, fecha_registro) VALUES (:nombre, :correo, :contrasena, :rol, NOW())'
        );
        $consulta->execute([
            ':nombre' => $datos['nombre'],
            ':correo' => strtolower(trim($datos['correo'])),
            ':contrasena' => $datos['contrasena'],
            ':rol' => $datos['rol'],
        ]);

        return (int) $conexion->lastInsertId();
    }

    // Verifica las credenciales del usuario y devuelve su información cuando coinciden.
    public static function verificarCredenciales(string $correo, string $contrasena): ?array
    {
        $usuario = self::buscarPorCorreo($correo);

        if ($usuario === null) {
            return null;
        }

        if (!password_verify($contrasena, $usuario['contrasena'])) {
            return null;
        }

        return $usuario;
    }

    // Normaliza los datos del usuario para aceptar llamadas con arreglo o argumentos individuales.
    private static function normalizarDatosUsuario(array $argumentos): array
    {
        if (count($argumentos) === 1 && is_array($argumentos[0])) {
            $datos = $argumentos[0];

            return [
                'nombre' => (string) ($datos['nombre'] ?? ''),
                'correo' => (string) ($datos['correo'] ?? ''),
                'contrasena' => (string) ($datos['contrasena'] ?? ''),
                'rol' => (string) ($datos['rol'] ?? ''),
            ];
        }

        if (count($argumentos) >= 4) {
            return [
                'nombre' => (string) $argumentos[0],
                'correo' => (string) $argumentos[1],
                'contrasena' => (string) $argumentos[2],
                'rol' => (string) $argumentos[3],
            ];
        }

        throw new InvalidArgumentException('Los datos del usuario son inválidos.');
    }
}
