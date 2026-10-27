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

    public static function generarTokenRestablecimiento(string $correo): ?string
    {
        $conexion = obtenerConexionBaseDatos();
        $consultaUsuario = $conexion->prepare('SELECT id_usuario FROM usuario WHERE correo = :correo LIMIT 1');
        $consultaUsuario->execute([':correo' => trim(strtolower($correo))]);
        $usuario = $consultaUsuario->fetch();

        if ($usuario === false) {
            return null;
        }

        $token = bin2hex(random_bytes(32));
        $hashToken = hash('sha256', $token);
        $fechaExpiracion = $conexion->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite'
            ? "datetime('now', '+15 minutes')"
            : 'DATE_ADD(NOW(), INTERVAL 15 MINUTE)';
        $consultaToken = $conexion->prepare(
            'UPDATE usuario
            SET token_reset = :token_reset, token_expira = ' . $fechaExpiracion . '
            WHERE id_usuario = :id_usuario'
        );
        $consultaToken->execute([
            ':token_reset' => $hashToken,
            ':id_usuario' => (int) $usuario['id_usuario'],
        ]);

        return $token;
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
