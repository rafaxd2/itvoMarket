<?php

declare(strict_types=1);

require_once __DIR__ . '/../models/Producto.php';
require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../services/AlmacenImagenes.php';

// Controlador encargado de publicar productos desde la cuenta de un vendedor.
class ProductoController
{
    // Muestra el formulario de publicación y el catálogo actual del vendedor.
    public function mostrarFormulario(array $contexto = []): void
    {
        $nombre = $contexto['nombre'] ?? '';
        $precio = $contexto['precio'] ?? '';
        $descripcion = $contexto['descripcion'] ?? '';
        $idCategoria = $contexto['id_categoria'] ?? '';
        $errores = $contexto['errores'] ?? [];
        $mensajeExito = $contexto['mensaje_exito'] ?? '';
        $productoRecienteId = $contexto['producto_reciente_id'] ?? null;
        $productos = $contexto['productos'] ?? $this->listarProductosDelVendedor();
        $categorias = $contexto['categorias'] ?? Producto::listarCategorias();

        include __DIR__ . '/../views/publicar.php';
    }

    // Valida y guarda un producto nuevo con almacenamiento S3 y respaldo local.
    public function publicar(): void
    {
        AuthController::requerirSesion();
        AuthController::requerirRol('vendedor');

        $nombre = trim((string) ($_POST['nombre'] ?? ''));
        $precio = trim((string) ($_POST['precio'] ?? ''));
        $descripcion = trim((string) ($_POST['descripcion_producto'] ?? ''));
        $idCategoria = trim((string) ($_POST['id_categoria'] ?? ''));
        $errores = [];

        $categorias = Producto::listarCategorias();
        $categoriasDisponibles = [];
        foreach ($categorias as $categoria) {
            $categoriasDisponibles[(string) $categoria['id_categoria']] = true;
        }

        if ($nombre === '') {
            $errores['nombre'] = 'El nombre del producto es obligatorio.';
        }

        if ($idCategoria === '' || !isset($categoriasDisponibles[$idCategoria])) {
            $errores['id_categoria'] = 'Selecciona una categoría válida.';
        }

        if ($precio === '') {
            $errores['precio'] = 'El precio es obligatorio.';
        } elseif (!is_numeric($precio) || !is_finite((float) $precio) || (float) $precio <= 0) {
            $errores['precio'] = 'El precio debe ser un número positivo.';
        }

        if (!isset($_FILES['imagen']) || $_FILES['imagen']['error'] !== UPLOAD_ERR_OK) {
            $errores['imagen'] = 'Debes seleccionar una imagen en formato jpg o png.';
        } elseif (!Producto::validarFormatoImagen((string) $_FILES['imagen']['name'])) {
            $errores['imagen'] = 'La imagen debe tener formato jpg o png.';
        }

        if (!empty($errores)) {
            $this->mostrarFormulario([
                'nombre' => $nombre,
                'precio' => $precio,
                'descripcion' => $descripcion,
                'id_categoria' => $idCategoria,
                'errores' => $errores,
                'productos' => $this->listarProductosDelVendedor(),
                'categorias' => $categorias,
            ]);
            return;
        }

        $idVendedor = $this->obtenerIdVendedorActual();
        $archivoImagen = $_FILES['imagen'];
        $rutaDestino = AlmacenImagenes::guardar(
            (string) $archivoImagen['tmp_name'],
            (string) $archivoImagen['name']
        );

        $productoRecienteId = Producto::crear([
            'id_vendedor' => $idVendedor,
            'id_categoria' => (int) $idCategoria,
            'id_estado' => 1,
            'id_condicion' => 1,
            'id_estado_ubicacion' => 1,
            'nombre' => $nombre,
            'descripcion_producto' => $descripcion,
            'precio' => (float) $precio,
            'imagen_producto' => $rutaDestino,
        ]);

        $this->mostrarFormulario([
            'mensaje_exito' => 'Producto publicado',
            'producto_reciente_id' => $productoRecienteId,
            'productos' => $this->listarProductosDelVendedor(),
            'categorias' => $categorias,
        ]);
    }

    // Devuelve los productos del vendedor activo para mostrarlos en el catálogo.
    public function listarProductosDelVendedor(): array
    {
        AuthController::requerirSesion();
        AuthController::requerirRol('vendedor');

        $idVendedor = $this->obtenerIdVendedorActual();

        return Producto::listarPorVendedor($idVendedor);
    }

    // Obtiene el identificador del vendedor asociado a la sesión activa.
    private function obtenerIdVendedorActual(): int
    {
        $idUsuario = (int) ($_SESSION['usuario_id'] ?? 0);
        $conexion = obtenerConexionBaseDatos();
        $consulta = $conexion->prepare('SELECT id_vendedor FROM vendedores WHERE id_usuario = :id_usuario LIMIT 1');
        $consulta->execute([':id_usuario' => $idUsuario]);
        $resultado = $consulta->fetch();

        if ($resultado !== false && isset($resultado['id_vendedor'])) {
            return (int) $resultado['id_vendedor'];
        }

        $consultaInsertar = $conexion->prepare(
            'INSERT INTO vendedores (id_usuario, nombre_negocio, descripcion, horario_atencion) VALUES (:id_usuario, :nombre_negocio, :descripcion, :horario_atencion)'
        );
        $consultaInsertar->execute([
            ':id_usuario' => $idUsuario,
            ':nombre_negocio' => 'Mi negocio',
            ':descripcion' => 'Vendedor activo en ITVOmarket',
            ':horario_atencion' => 'Lunes a domingo',
        ]);

        return (int) $conexion->lastInsertId();
    }

}
