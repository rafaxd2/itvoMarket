<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

// Clase que gestiona las operaciones de productos publicados por los vendedores.
class Producto
{
    // Guarda un nuevo producto con los datos mínimos requeridos para una publicación válida.
    public static function crear(...$argumentos): int
    {
        $datos = self::normalizarDatosProducto($argumentos);
        $datos['nombre'] = trim($datos['nombre']);

        if ($datos['nombre'] === '') {
            throw new InvalidArgumentException('El nombre del producto es obligatorio.');
        }

        if (!is_numeric($datos['precio']) || !is_finite((float) $datos['precio']) || (float) $datos['precio'] <= 0) {
            throw new InvalidArgumentException('El precio debe ser un número positivo.');
        }

        if (!self::validarFormatoImagen($datos['imagen_producto'])) {
            throw new InvalidArgumentException('La imagen debe tener formato jpg o png.');
        }

        $conexion = obtenerConexionBaseDatos();
        $idCategoria = self::obtenerIdCategoriaValida($conexion, $datos['id_categoria'] ?? null);

        $consulta = $conexion->prepare(
            'INSERT INTO productos (id_vendedor, id_categoria, id_estado, id_condicion, id_estado_ubicacion, nombre, descripcion_producto, precio, imagen_producto) VALUES (:id_vendedor, :id_categoria, :id_estado, :id_condicion, :id_estado_ubicacion, :nombre, :descripcion_producto, :precio, :imagen_producto)'
        );
        $consulta->execute([
            ':id_vendedor' => (int) $datos['id_vendedor'],
            ':id_categoria' => $idCategoria,
            ':id_estado' => (int) ($datos['id_estado'] ?? 1),
            ':id_condicion' => (int) ($datos['id_condicion'] ?? 1),
            ':id_estado_ubicacion' => (int) ($datos['id_estado_ubicacion'] ?? 1),
            ':nombre' => $datos['nombre'],
            ':descripcion_producto' => $datos['descripcion_producto'],
            ':precio' => (float) $datos['precio'],
            ':imagen_producto' => $datos['imagen_producto'],
        ]);

        return (int) $conexion->lastInsertId();
    }

    // Lista todos los productos publicados por un vendedor específico.
    public static function listarPorVendedor(int $idVendedor): array
    {
        $conexion = obtenerConexionBaseDatos();
        $consulta = $conexion->prepare(
            'SELECT * FROM productos WHERE id_vendedor = :id_vendedor ORDER BY id_producto DESC'
        );
        $consulta->execute([':id_vendedor' => $idVendedor]);

        return $consulta->fetchAll();
    }

    // Comprueba que el nombre del archivo tenga una extensión de imagen permitida.
    public static function validarFormatoImagen(string $nombreArchivo): bool
    {
        $extension = strtolower(pathinfo($nombreArchivo, PATHINFO_EXTENSION));

        return in_array($extension, ['jpg', 'jpeg', 'png'], true);
    }

    // Lista las categorías disponibles para el formulario de publicación.
    public static function listarCategorias(): array
    {
        $conexion = obtenerConexionBaseDatos();
        $consulta = $conexion->prepare(
            'SELECT id_categoria, tipo_categoria FROM categorias ORDER BY tipo_categoria ASC'
        );
        $consulta->execute();

        return $consulta->fetchAll();
    }

    // Devuelve una categoría válida para la relación de productos o crea la categoría por defecto si no existe.
    private static function obtenerIdCategoriaValida(PDO $conexion, mixed $idCategoria): int
    {
        $idCategoria = $idCategoria !== null ? (int) $idCategoria : 0;

        if ($idCategoria > 0) {
            $consultaCategoria = $conexion->prepare('SELECT id_categoria FROM categorias WHERE id_categoria = :id_categoria LIMIT 1');
            $consultaCategoria->execute([':id_categoria' => $idCategoria]);
            if ($consultaCategoria->fetch() !== false) {
                return $idCategoria;
            }
        }

        $consultaCategoriaBase = $conexion->query('SELECT id_categoria FROM categorias ORDER BY id_categoria ASC LIMIT 1');
        $categoriaBase = $consultaCategoriaBase !== false ? $consultaCategoriaBase->fetch() : false;

        if ($categoriaBase !== false && isset($categoriaBase['id_categoria'])) {
            return (int) $categoriaBase['id_categoria'];
        }

        $consultaInsertar = $conexion->prepare('INSERT INTO categorias (tipo_categoria) VALUES (:tipo_categoria)');
        $consultaInsertar->execute([':tipo_categoria' => 'General']);

        return (int) $conexion->lastInsertId();
    }

    // Normaliza los datos del producto para aceptar arreglo o argumentos separados.
    private static function normalizarDatosProducto(array $argumentos): array
    {
        if (count($argumentos) === 1 && is_array($argumentos[0])) {
            $datos = $argumentos[0];

            return [
                'id_vendedor' => $datos['id_vendedor'] ?? 0,
                'id_categoria' => $datos['id_categoria'] ?? null,
                'id_estado' => $datos['id_estado'] ?? 1,
                'id_condicion' => $datos['id_condicion'] ?? 1,
                'id_estado_ubicacion' => $datos['id_estado_ubicacion'] ?? 1,
                'nombre' => (string) ($datos['nombre'] ?? ''),
                'descripcion_producto' => (string) ($datos['descripcion_producto'] ?? ''),
                'precio' => $datos['precio'] ?? 0,
                'imagen_producto' => (string) ($datos['imagen_producto'] ?? ''),
            ];
        }

        if (count($argumentos) >= 9) {
            return [
                'id_vendedor' => $argumentos[0],
                'id_categoria' => $argumentos[1],
                'id_estado' => $argumentos[2],
                'id_condicion' => $argumentos[3],
                'id_estado_ubicacion' => $argumentos[4],
                'nombre' => (string) $argumentos[5],
                'descripcion_producto' => (string) $argumentos[6],
                'precio' => $argumentos[7],
                'imagen_producto' => (string) $argumentos[8],
            ];
        }

        throw new InvalidArgumentException('Los datos del producto son inválidos.');
    }
}
