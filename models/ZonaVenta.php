<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

// Clase que gestiona la ubicación geográfica y el estado de visibilidad de cada vendedor.
class ZonaVenta
{
    // Guarda o actualiza la ubicación del vendedor y el estado de publicación de su zona.
    public static function actualizarUbicacion(...$argumentos): bool
    {
        $datos = self::normalizarDatosZona($argumentos);
        self::validarDatosUbicacion($datos);
        $datos['estado_zona'] = str_replace('Inactivo', 'Desactivo', (string) $datos['estado_zona']);
        $conexion = obtenerConexionBaseDatos();
        $consultaExistencia = $conexion->prepare('SELECT id_zona_venta FROM zona_venta WHERE id_vendedor = :id_vendedor LIMIT 1');
        $consultaExistencia->execute([':id_vendedor' => (int) $datos['id_vendedor']]);
        $zonaActual = $consultaExistencia->fetch();

        if ($zonaActual !== false) {
            $consulta = $conexion->prepare(
                'UPDATE zona_venta SET latitud = :latitud, longitud = :longitud, punto_referencia = :punto_referencia, estado_zona = :estado_zona, fecha_hora = NOW() WHERE id_zona_venta = :id_zona_venta'
            );
            $consulta->execute([
                ':latitud' => (float) $datos['latitud'],
                ':longitud' => (float) $datos['longitud'],
                ':punto_referencia' => $datos['punto_referencia'],
                ':estado_zona' => $datos['estado_zona'],
                ':id_zona_venta' => (int) $zonaActual['id_zona_venta'],
            ]);

            return $consulta->rowCount() > 0;
        }

        $consulta = $conexion->prepare(
            'INSERT INTO zona_venta (id_vendedor, latitud, longitud, punto_referencia, estado_zona, fecha_hora) VALUES (:id_vendedor, :latitud, :longitud, :punto_referencia, :estado_zona, NOW())'
        );
        $consulta->execute([
            ':id_vendedor' => (int) $datos['id_vendedor'],
            ':latitud' => (float) $datos['latitud'],
            ':longitud' => (float) $datos['longitud'],
            ':punto_referencia' => $datos['punto_referencia'],
            ':estado_zona' => $datos['estado_zona'],
        ]);

        return $consulta->rowCount() > 0;
    }

    // Devuelve únicamente las zonas activas para mostrarlas en el mapa del comprador.
    public static function obtenerActivos(): array
    {
        $conexion = obtenerConexionBaseDatos();
        $consulta = $conexion->prepare(
            'SELECT z.*, v.id_usuario FROM zona_venta z INNER JOIN vendedores v ON z.id_vendedor = v.id_vendedor WHERE z.estado_zona = :estado_zona ORDER BY z.fecha_hora DESC'
        );
        $consulta->execute([':estado_zona' => 'Activo']);

        return $consulta->fetchAll();
    }

    // Devuelve la última ubicación guardada por un vendedor, sin importar si está activa.
    public static function obtenerPorVendedor(int $idVendedor): ?array
    {
        $conexion = obtenerConexionBaseDatos();
        $consulta = $conexion->prepare(
            'SELECT * FROM zona_venta WHERE id_vendedor = :id_vendedor LIMIT 1'
        );
        $consulta->execute([':id_vendedor' => $idVendedor]);
        $zona = $consulta->fetch();

        return $zona !== false ? $zona : null;
    }

    // Normaliza los datos de ubicación para aceptar arreglo o argumentos separados.
    private static function normalizarDatosZona(array $argumentos): array
    {
        if (count($argumentos) === 1 && is_array($argumentos[0])) {
            $datos = $argumentos[0];

            return [
                'id_vendedor' => $datos['id_vendedor'] ?? 0,
                'latitud' => $datos['latitud'] ?? null,
                'longitud' => $datos['longitud'] ?? null,
                'punto_referencia' => (string) ($datos['punto_referencia'] ?? ''),
                'estado_zona' => str_replace('Inactivo', 'Desactivo', (string) ($datos['estado_zona'] ?? 'Activo')),
            ];
        }

        if (count($argumentos) >= 5) {
            return [
                'id_vendedor' => $argumentos[0],
                'latitud' => $argumentos[1],
                'longitud' => $argumentos[2],
                'punto_referencia' => (string) $argumentos[3],
                'estado_zona' => str_replace('Inactivo', 'Desactivo', (string) $argumentos[4]),
            ];
        }

        throw new InvalidArgumentException('Los datos de la ubicación son inválidos.');
    }

    // Rechaza identificadores o coordenadas fuera de los rangos geográficos válidos.
    private static function validarDatosUbicacion(array $datos): void
    {
        if ((int) $datos['id_vendedor'] <= 0) {
            throw new InvalidArgumentException('El identificador del vendedor no es válido.');
        }

        if (
            !is_numeric($datos['latitud'])
            || !is_finite((float) $datos['latitud'])
            || (float) $datos['latitud'] < -90
            || (float) $datos['latitud'] > 90
        ) {
            throw new InvalidArgumentException('La latitud debe estar entre -90 y 90.');
        }

        if (
            !is_numeric($datos['longitud'])
            || !is_finite((float) $datos['longitud'])
            || (float) $datos['longitud'] < -180
            || (float) $datos['longitud'] > 180
        ) {
            throw new InvalidArgumentException('La longitud debe estar entre -180 y 180.');
        }
    }
}
