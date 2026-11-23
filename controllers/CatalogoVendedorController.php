<?php

declare(strict_types=1);

require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../controllers/CatalogoController.php';
require_once __DIR__ . '/../models/Producto.php';
require_once __DIR__ . '/../services/AlmacenImagenes.php';
require_once __DIR__ . '/../helpers/Validaciones.php';

class CatalogoVendedorController
{
    public function misProductos(): void
    {
        AuthController::requerirSesion();
        AuthController::requerirRol('vendedor');

        $productos = [];
        $mensajeExito = (string) ($_SESSION['mensaje_catalogo_vendedor'] ?? '');
        $error = (string) ($_SESSION['error_catalogo_vendedor'] ?? '');
        $errorCarga = false;
        unset($_SESSION['mensaje_catalogo_vendedor'], $_SESSION['error_catalogo_vendedor']);

        try {
            $idVendedor = $this->obtenerIdVendedorActual();
            if ($idVendedor !== null) {
                $productos = Producto::listarPorVendedor($idVendedor);
            }
        } catch (PDOException $excepcion) {
            error_log('No fue posible cargar los productos del vendedor.');
            $error = 'No fue posible cargar tus productos. Inténtalo nuevamente.';
            $errorCarga = true;
        }

        include __DIR__ . '/../views/mis_productos.php';
    }

    public function mostrarEdicion(): void
    {
        AuthController::requerirSesion();
        AuthController::requerirRol('vendedor');

        $idProducto = $this->validarIdProducto($_GET['id'] ?? null);
        if ($idProducto === null) {
            $this->mostrarNoDisponible();
            return;
        }

        try {
            $idVendedor = $this->obtenerIdVendedorActual();
            if ($idVendedor === null) {
                $this->mostrarNoDisponible();
                return;
            }

            $producto = Producto::obtenerPorIdYVendedor($idProducto, $idVendedor);
            if ($producto === null) {
                $this->mostrarNoDisponible();
                return;
            }

            $this->mostrarFormularioEdicion($producto);
        } catch (PDOException $excepcion) {
            error_log('No fue posible cargar el formulario de edición del producto.');
            $this->mostrarFormularioEdicion(null, [], 'No fue posible cargar esta publicación. Inténtalo nuevamente.');
        }
    }

    public function guardarEdicion(): void
    {
        AuthController::requerirSesion();
        AuthController::requerirRol('vendedor');

        $idProducto = $this->validarIdProducto($_POST['id_producto'] ?? null);
        if ($idProducto === null) {
            $this->mostrarNoDisponible();
            return;
        }

        try {
            $idVendedor = $this->obtenerIdVendedorActual();
            if ($idVendedor === null) {
                $this->mostrarNoDisponible();
                return;
            }

            $producto = Producto::obtenerPorIdYVendedor($idProducto, $idVendedor);
            if ($producto === null) {
                $this->mostrarNoDisponible();
                return;
            }

            $datos = $this->obtenerDatosFormulario();
            $categorias = Producto::listarCategorias();
            $errores = $this->validarDatosProducto($datos, $categorias);

            if (!$this->validarTokenCsrf()) {
                $errores['general'] = 'No fue posible guardar los cambios. Actualiza la página e inténtalo nuevamente.';
            }

            $archivoNuevo = $_FILES['imagen'] ?? null;
            $hayImagenNueva = is_array($archivoNuevo)
                && ($archivoNuevo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
            if ($hayImagenNueva) {
                $errorImagen = validarImagenProducto($archivoNuevo);
                if ($errorImagen !== '') {
                    $errores['imagen'] = $errorImagen;
                }
            }

            if ($errores !== []) {
                $this->mostrarFormularioEdicion(
                    array_merge($producto, $datos),
                    $errores,
                    $errores['general'] ?? ''
                );
                return;
            }

            $rutaImagenNueva = (string) ($producto['imagen_producto'] ?? '');
            if ($hayImagenNueva) {
                $rutaImagenNueva = AlmacenImagenes::guardar(
                    (string) $archivoNuevo['tmp_name'],
                    (string) $archivoNuevo['name']
                );
                if ($rutaImagenNueva === '') {
                    $errores['imagen'] = 'No fue posible guardar la imagen. Inténtalo nuevamente.';
                    $this->mostrarFormularioEdicion(array_merge($producto, $datos), $errores);
                    return;
                }
            }

            $datos['imagen_producto'] = $rutaImagenNueva;
            if (!Producto::actualizarProducto($idProducto, $idVendedor, $datos)) {
                if ($hayImagenNueva) {
                    eliminarImagenProducto($rutaImagenNueva);
                }
                $errores['general'] = 'No fue posible guardar los cambios. Inténtalo nuevamente.';
                $this->mostrarFormularioEdicion(
                    array_merge($producto, $datos),
                    $errores,
                    $errores['general']
                );
                return;
            }

            if ($hayImagenNueva) {
                eliminarImagenProducto((string) ($producto['imagen_producto'] ?? ''));
            }

            $_SESSION['mensaje_catalogo_vendedor'] =
                'Cambios guardados correctamente. ' . $datos['nombre'] . ' ya muestra la información actualizada.';
            header('Location: index.php?accion=mis_productos');
        } catch (PDOException $excepcion) {
            error_log('No fue posible guardar los cambios del producto.');
            $this->mostrarFormularioEdicion(
                isset($producto) && is_array($producto)
                    ? array_merge($producto, $this->obtenerDatosFormulario())
                    : null,
                [],
                'No fue posible guardar los cambios. Inténtalo nuevamente.'
            );
        }
    }

    public function confirmarEliminar(): void
    {
        AuthController::requerirSesion();
        AuthController::requerirRol('vendedor');

        $idProducto = $this->validarIdProducto($_GET['id'] ?? null);
        if ($idProducto === null) {
            $this->mostrarNoDisponible();
            return;
        }

        try {
            $idVendedor = $this->obtenerIdVendedorActual();
            if ($idVendedor === null) {
                $this->mostrarNoDisponible();
                return;
            }

            $producto = Producto::obtenerPorIdYVendedor($idProducto, $idVendedor);
            if ($producto === null) {
                $this->mostrarNoDisponible();
                return;
            }

            $tokenCsrf = $this->obtenerTokenCsrf();
            include __DIR__ . '/../views/eliminar_producto.php';
        } catch (PDOException $excepcion) {
            error_log('No fue posible cargar la confirmación de eliminación.');
            $_SESSION['error_catalogo_vendedor'] =
                'No fue posible cargar la confirmación. Inténtalo nuevamente.';
            header('Location: index.php?accion=mis_productos');
        }
    }

    public function eliminar(): void
    {
        AuthController::requerirSesion();
        AuthController::requerirRol('vendedor');

        $idProducto = $this->validarIdProducto($_POST['id_producto'] ?? null);
        if ($idProducto === null) {
            $this->mostrarNoDisponible();
            return;
        }

        try {
            $idVendedor = $this->obtenerIdVendedorActual();
            if ($idVendedor === null) {
                $this->mostrarNoDisponible();
                return;
            }

            $producto = Producto::obtenerPorIdYVendedor($idProducto, $idVendedor);
            if ($producto === null) {
                $this->mostrarNoDisponible();
                return;
            }

            if (!$this->validarTokenCsrf()) {
                $_SESSION['error_catalogo_vendedor'] =
                    'No fue posible eliminar la publicación. Actualiza la página e inténtalo nuevamente.';
                header('Location: index.php?accion=mis_productos');
                return;
            }

            if (!Producto::eliminarProducto($idProducto, $idVendedor)) {
                $_SESSION['error_catalogo_vendedor'] =
                    'No fue posible eliminar el producto. Inténtalo nuevamente.';
                header('Location: index.php?accion=mis_productos');
                return;
            }

            eliminarImagenProducto((string) ($producto['imagen_producto'] ?? ''));
            $_SESSION['mensaje_catalogo_vendedor'] = 'Producto eliminado correctamente.';
            header('Location: index.php?accion=mis_productos');
        } catch (PDOException $excepcion) {
            error_log('No fue posible eliminar el producto del catálogo.');
            $_SESSION['error_catalogo_vendedor'] =
                'No fue posible eliminar el producto. Inténtalo nuevamente.';
            header('Location: index.php?accion=mis_productos');
        }
    }

    private function obtenerIdVendedorActual(): ?int
    {
        $idUsuario = (int) ($_SESSION['usuario_id'] ?? 0);
        if ($idUsuario <= 0) {
            return null;
        }

        $conexion = obtenerConexionBaseDatos();
        $consulta = $conexion->prepare(
            'SELECT id_vendedor FROM vendedores WHERE id_usuario = :id_usuario LIMIT 1'
        );
        $consulta->execute([':id_usuario' => $idUsuario]);
        $resultado = $consulta->fetch();

        return $resultado !== false && isset($resultado['id_vendedor'])
            ? (int) $resultado['id_vendedor']
            : null;
    }

    private function validarIdProducto(mixed $id): ?int
    {
        $idValidado = filter_var($id, FILTER_VALIDATE_INT);

        return $idValidado !== false && $idValidado !== null && $idValidado > 0
            ? $idValidado
            : null;
    }

    private function obtenerDatosFormulario(): array
    {
        return [
            'nombre' => trim(is_string($_POST['nombre'] ?? null) ? $_POST['nombre'] : ''),
            'precio' => trim(is_string($_POST['precio'] ?? null) ? $_POST['precio'] : ''),
            'descripcion_producto' => trim(
                is_string($_POST['descripcion_producto'] ?? null) ? $_POST['descripcion_producto'] : ''
            ),
            'id_categoria' => trim(
                is_string($_POST['id_categoria'] ?? null) ? $_POST['id_categoria'] : ''
            ),
        ];
    }

    private function validarDatosProducto(array $datos, array $categorias): array
    {
        $errores = [];
        $errorNombre = validarNombreProducto($datos['nombre']);
        if ($errorNombre !== '') {
            $errores['nombre'] = $errorNombre;
        }

        if ($datos['precio'] === '') {
            $errores['precio'] = 'El precio es obligatorio.';
        } else {
            $errorPrecio = validarPrecioProducto($datos['precio']);
            if ($errorPrecio !== '') {
                $errores['precio'] = $errorPrecio;
            }
        }

        $categoriasDisponibles = [];
        foreach ($categorias as $categoria) {
            $categoriasDisponibles[(string) $categoria['id_categoria']] = true;
        }
        if (!isset($categoriasDisponibles[$datos['id_categoria']])) {
            $errores['id_categoria'] = 'Selecciona una categoría válida.';
        }

        return $errores;
    }

    private function validarTokenCsrf(): bool
    {
        $tokenSesion = $_SESSION['token_csrf_catalogo'] ?? null;
        $tokenFormulario = $_POST['token_csrf'] ?? null;

        return is_string($tokenSesion)
            && $tokenSesion !== ''
            && is_string($tokenFormulario)
            && hash_equals($tokenSesion, $tokenFormulario);
    }

    private function obtenerTokenCsrf(): string
    {
        if (!is_string($_SESSION['token_csrf_catalogo'] ?? null) || $_SESSION['token_csrf_catalogo'] === '') {
            $_SESSION['token_csrf_catalogo'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['token_csrf_catalogo'];
    }

    private function mostrarFormularioEdicion(
        ?array $producto,
        array $errores = [],
        string $errorGeneral = ''
    ): void {
        $categorias = [];
        $tokenCsrf = $this->obtenerTokenCsrf();
        $imagenActualUrl = $producto === null
            ? null
            : CatalogoController::obtenerUrlImagenProducto($producto['imagen_producto'] ?? null);

        if ($categorias === []) {
            try {
                $categorias = Producto::listarCategorias();
            } catch (PDOException $excepcion) {
                error_log('No fue posible cargar las categorías del producto.');
                $errorGeneral = 'No fue posible cargar las categorías. Inténtalo nuevamente.';
            }
        }

        include __DIR__ . '/../views/editar_producto.php';
    }

    private function mostrarNoDisponible(): void
    {
        include __DIR__ . '/../views/producto_no_disponible.php';
    }
}
