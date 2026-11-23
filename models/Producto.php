<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/Validaciones.php';

// Clase que gestiona las operaciones de productos publicados por los vendedores.
class Producto
{
    // Guarda un nuevo producto con los datos mínimos requeridos para una publicación válida.
    public static function crear(...$argumentos): int
    {
        $datos = self::normalizarDatosProducto($argumentos);
        $datos['nombre'] = trim($datos['nombre']);

        $errorNombre = validarNombreProducto($datos['nombre']);
        if ($errorNombre !== '') {
            throw new InvalidArgumentException($errorNombre);
        }

        $errorPrecio = validarPrecioProducto($datos['precio']);
        if ($errorPrecio !== '') {
            throw new InvalidArgumentException($errorPrecio);
        }

        if ($datos['imagen_producto'] !== '' && !validarFormatoImagenProducto($datos['imagen_producto'])) {
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
            'SELECT p.*, c.tipo_categoria AS categoria
             FROM productos p
             LEFT JOIN categorias c ON c.id_categoria = p.id_categoria
             WHERE p.id_vendedor = :id_vendedor
             ORDER BY p.id_producto DESC'
        );
        $consulta->execute([':id_vendedor' => $idVendedor]);

        return $consulta->fetchAll();
    }

    public static function obtenerPorIdYVendedor(int $idProducto, int $idVendedor): ?array
    {
        if ($idProducto <= 0 || $idVendedor <= 0) {
            return null;
        }

        $conexion = obtenerConexionBaseDatos();
        $consulta = $conexion->prepare(
            'SELECT p.*, c.tipo_categoria AS categoria
             FROM productos p
             LEFT JOIN categorias c ON c.id_categoria = p.id_categoria
             WHERE p.id_producto = :id_producto AND p.id_vendedor = :id_vendedor
             LIMIT 1'
        );
        $consulta->execute([
            ':id_producto' => $idProducto,
            ':id_vendedor' => $idVendedor,
        ]);
        $producto = $consulta->fetch();

        return $producto !== false ? $producto : null;
    }

    public static function actualizarProducto(int $idProducto, int $idVendedor, array $datos): bool
    {
        if ($idProducto <= 0 || $idVendedor <= 0) {
            return false;
        }

        try {
            $conexion = obtenerConexionBaseDatos();
            $consultaCategoria = $conexion->prepare(
                'SELECT id_categoria FROM categorias WHERE id_categoria = :id_categoria LIMIT 1'
            );
            $consultaCategoria->execute([
                ':id_categoria' => (int) ($datos['id_categoria'] ?? 0),
            ]);
            if ($consultaCategoria->fetchColumn() === false) {
                return false;
            }

            $consultaExistencia = $conexion->prepare(
                'SELECT id_producto FROM productos WHERE id_producto = :id_producto AND id_vendedor = :id_vendedor LIMIT 1'
            );
            $consultaExistencia->execute([
                ':id_producto' => $idProducto,
                ':id_vendedor' => $idVendedor,
            ]);
            if ($consultaExistencia->fetchColumn() === false) {
                return false;
            }

            $consulta = $conexion->prepare(
                'UPDATE productos
                 SET nombre = :nombre,
                     precio = :precio,
                     descripcion_producto = :descripcion_producto,
                     id_categoria = :id_categoria,
                     imagen_producto = :imagen_producto
                 WHERE id_producto = :id_producto AND id_vendedor = :id_vendedor'
            );
            $consulta->execute([
                ':nombre' => (string) ($datos['nombre'] ?? ''),
                ':precio' => $datos['precio'] ?? 0,
                ':descripcion_producto' => (string) ($datos['descripcion_producto'] ?? ''),
                ':id_categoria' => (int) $datos['id_categoria'],
                ':imagen_producto' => (string) ($datos['imagen_producto'] ?? ''),
                ':id_producto' => $idProducto,
                ':id_vendedor' => $idVendedor,
            ]);

            return true;
        } catch (PDOException $excepcion) {
            error_log('No fue posible actualizar el producto.');
            return false;
        }
    }

    public static function eliminarProducto(int $idProducto, int $idVendedor): bool
    {
        if ($idProducto <= 0 || $idVendedor <= 0) {
            return false;
        }

        $conexion = obtenerConexionBaseDatos();

        try {
            $conexion->beginTransaction();

            $consultaExistencia = $conexion->prepare(
                'SELECT id_producto FROM productos WHERE id_producto = :id_producto AND id_vendedor = :id_vendedor LIMIT 1'
            );
            $consultaExistencia->execute([
                ':id_producto' => $idProducto,
                ':id_vendedor' => $idVendedor,
            ]);
            if ($consultaExistencia->fetchColumn() === false) {
                $conexion->rollBack();
                return false;
            }

            $consultaCalificaciones = $conexion->prepare(
                'DELETE FROM calificacion_producto WHERE id_producto = :id_producto'
            );
            $consultaCalificaciones->execute([':id_producto' => $idProducto]);

            $consultaProducto = $conexion->prepare(
                'DELETE FROM productos WHERE id_producto = :id_producto AND id_vendedor = :id_vendedor'
            );
            $consultaProducto->execute([
                ':id_producto' => $idProducto,
                ':id_vendedor' => $idVendedor,
            ]);
            if ($consultaProducto->rowCount() === 0) {
                $conexion->rollBack();
                return false;
            }

            $conexion->commit();
            return true;
        } catch (PDOException $excepcion) {
            if ($conexion->inTransaction()) {
                $conexion->rollBack();
            }
            error_log('No fue posible eliminar el producto.');
            return false;
        }
    }

    public static function listarCatalogo(?string $texto, ?int $idCategoria): array
    {
        $conexion = obtenerConexionBaseDatos();
        $condiciones = [];
        $parametros = [];

        if ($texto !== null && $texto !== '') {
            $textoEscapado = strtr($texto, [
                '!' => '!!',
                '%' => '!%',
                '_' => '!_',
                '\\' => '!\\',
            ]);
            $condiciones[] = '(p.nombre LIKE :texto_nombre ESCAPE \'!\' OR p.descripcion_producto LIKE :texto_descripcion ESCAPE \'!\')';
            $parametros[':texto_nombre'] = '%' . $textoEscapado . '%';
            $parametros[':texto_descripcion'] = '%' . $textoEscapado . '%';
        }

        if ($idCategoria !== null) {
            $condiciones[] = 'p.id_categoria = :id_categoria';
            $parametros[':id_categoria'] = $idCategoria;
        }

        $consultaSql = 'SELECT p.id_producto, p.nombre, p.descripcion_producto, p.precio, p.imagen_producto,
                c.id_categoria, c.tipo_categoria AS categoria, u.nombre AS nombre_vendedor
            FROM productos p
            INNER JOIN categorias c ON c.id_categoria = p.id_categoria
            INNER JOIN vendedores v ON v.id_vendedor = p.id_vendedor
            INNER JOIN usuario u ON u.id_usuario = v.id_usuario';

        if ($condiciones !== []) {
            $consultaSql .= ' WHERE ' . implode(' AND ', $condiciones);
        }

        $consultaSql .= ' ORDER BY p.id_producto DESC LIMIT 60';

        $consulta = $conexion->prepare($consultaSql);
        $consulta->execute($parametros);

        return $consulta->fetchAll();
    }

    // Busca los datos de lectura de una publicación y rechaza identificadores que no sean enteros positivos.
    public static function buscarDetalleCatalogo(mixed $idProducto): ?array
    {
        $idValidado = filter_var($idProducto, FILTER_VALIDATE_INT);

        if ($idValidado === false || $idValidado === null || $idValidado < 1) {
            return null;
        }

        $conexion = obtenerConexionBaseDatos();
        $consulta = $conexion->prepare(
            'SELECT p.id_producto, p.nombre, p.descripcion_producto, p.precio, p.imagen_producto,
                c.tipo_categoria AS categoria, u.nombre AS nombre_vendedor,
                e.nombre_estado AS estado, co.nombre_condicion AS condicion
            FROM productos p
            INNER JOIN categorias c ON c.id_categoria = p.id_categoria
            INNER JOIN vendedores v ON v.id_vendedor = p.id_vendedor
            INNER JOIN usuario u ON u.id_usuario = v.id_usuario
            LEFT JOIN estado_producto e ON e.id_estado = p.id_estado
            LEFT JOIN condicion_producto co ON co.id_condicion = p.id_condicion
            WHERE p.id_producto = :id_producto
            LIMIT 1'
        );
        $consulta->execute([':id_producto' => $idValidado]);
        $producto = $consulta->fetch();

        return $producto !== false ? $producto : null;
    }

    public static function hayPublicaciones(): bool
    {
        $conexion = obtenerConexionBaseDatos();
        $consulta = $conexion->prepare('SELECT EXISTS (SELECT 1 FROM productos LIMIT 1)');
        $consulta->execute();

        return (bool) $consulta->fetchColumn();
    }

    // Comprueba que el nombre del archivo tenga una extensión de imagen permitida.
    public static function validarFormatoImagen(string $nombreArchivo): bool
    {
        return validarFormatoImagenProducto($nombreArchivo);
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
