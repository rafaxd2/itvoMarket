<?php

declare(strict_types=1);

require_once __DIR__ . '/../models/Categoria.php';
require_once __DIR__ . '/../models/Producto.php';
require_once __DIR__ . '/../services/AlmacenImagenes.php';
require_once __DIR__ . '/AuthController.php';

class CatalogoController
{
    // Sirve una imagen privada del bucket solo para una clave estrictamente limitada a productos.
    public function mostrarImagen(): void
    {
        AuthController::requerirSesion();

        $clave = $_GET['k'] ?? null;
        if (!is_string($clave) || !AlmacenImagenes::esClaveProductoSegura($clave)) {
            http_response_code(404);
            header('Content-Type: text/plain; charset=UTF-8');
            echo 'Imagen no disponible.';
            return;
        }

        try {
            $imagen = AlmacenImagenes::obtenerImagenS3($clave);
            header('Content-Type: ' . $imagen['tipo_contenido']);
            header('Cache-Control: private, max-age=3600');
            header('X-Content-Type-Options: nosniff');
            header('Content-Length: ' . strlen($imagen['contenido']));
            echo $imagen['contenido'];
        } catch (Throwable $exception) {
            error_log('No fue posible recuperar una imagen privada de producto.');
            http_response_code(404);
            header('Content-Type: text/plain; charset=UTF-8');
            echo 'Imagen no disponible.';
        }
    }

    public function mostrarDetalle(): void
    {
        AuthController::requerirSesion();
        AuthController::requerirRol('comprador', 'panel.php?rol=vendedor');

        $idProducto = $_GET['id'] ?? null;
        $texto = $this->normalizarTexto($_GET['q'] ?? '');
        $categoriaSolicitada = filter_var($_GET['categoria'] ?? null, FILTER_VALIDATE_INT);
        $idCategoria = $categoriaSolicitada !== false && $categoriaSolicitada !== null && $categoriaSolicitada > 0
            ? $categoriaSolicitada
            : null;
        $producto = null;
        $error = '';
        $parametrosRegreso = ['accion' => 'catalogo'];

        if ($texto !== '') {
            $parametrosRegreso['q'] = $texto;
        }
        if ($idCategoria !== null) {
            $parametrosRegreso['categoria'] = $idCategoria;
        }
        $urlRegreso = 'index.php?' . http_build_query($parametrosRegreso);

        try {
            $producto = Producto::buscarDetalleCatalogo($idProducto);
        } catch (PDOException $exception) {
            $error = 'No fue posible cargar la publicación. Inténtalo nuevamente.';
        }

        include __DIR__ . '/../views/detalle_producto.php';
    }

    public function mostrar(): void
    {
        AuthController::requerirSesion();
        AuthController::requerirRol('comprador', 'panel.php?rol=vendedor');

        $texto = $this->normalizarTexto($_GET['q'] ?? '');
        $categorias = [];
        $publicaciones = [];
        $idCategoria = null;
        $nombreCategoria = '';
        $hayPublicaciones = false;
        $error = '';

        try {
            $categorias = Categoria::listarTodas();
            $idSolicitado = filter_var($_GET['categoria'] ?? null, FILTER_VALIDATE_INT);

            if ($idSolicitado !== false && $idSolicitado !== null && $idSolicitado > 0) {
                foreach ($categorias as $categoria) {
                    if ((int) $categoria['id_categoria'] === $idSolicitado) {
                        $idCategoria = $idSolicitado;
                        $nombreCategoria = $categoria['nombre'];
                        break;
                    }
                }
            }

            $hayPublicaciones = Producto::hayPublicaciones();
            $publicaciones = Producto::listarCatalogo($texto, $idCategoria);
        } catch (PDOException $exception) {
            $error = 'No fue posible cargar el catálogo. Inténtalo nuevamente.';
        }

        $tieneCriterios = $texto !== '' || $idCategoria !== null;

        include __DIR__ . '/../views/catalogo.php';
    }

    public static function obtenerUrlImagenProducto(?string $ruta): ?string
    {
        $ruta = trim((string) $ruta);

        if ($ruta === '') {
            return null;
        }

        if (AlmacenImagenes::esClaveProductoSegura($ruta)) {
            return 'index.php?accion=imagen&k=' . rawurlencode($ruta);
        }

        if (str_starts_with(strtolower($ruta), 'http')) {
            $partesUrl = parse_url($ruta);
            $configuracion = require __DIR__ . '/../config/config.php';
            $hostPublico = strtolower((string) parse_url($configuracion['S3_URL_PUBLICA'], PHP_URL_HOST));
            $hostEndpoint = strtolower((string) parse_url($configuracion['S3_ENDPOINT'], PHP_URL_HOST));
            $hostImagen = strtolower((string) ($partesUrl['host'] ?? ''));
            $rutaImagen = rawurldecode((string) ($partesUrl['path'] ?? ''));

            if ($hostImagen !== '' && in_array($hostImagen, [$hostPublico, $hostEndpoint], true)) {
                if (preg_match('#(?:^|/)productos/(producto_[A-Za-z0-9._-]+\.(?:jpg|jpeg|png))\z#i', $rutaImagen, $coincidencias) === 1
                    && AlmacenImagenes::esClaveProductoSegura($coincidencias[1])) {
                    return 'index.php?accion=imagen&k=' . rawurlencode($coincidencias[1]);
                }
            }

            return $ruta;
        }

        $ruta = ltrim(str_replace('\\', '/', $ruta), '/');

        if (str_starts_with($ruta, 'assets/uploads/')) {
            return $ruta;
        }

        return 'assets/uploads/' . $ruta;
    }

    private function normalizarTexto(mixed $valor): string
    {
        if (!is_string($valor)) {
            return '';
        }

        $texto = trim($valor);

        if (function_exists('mb_substr')) {
            return mb_substr($texto, 0, 100, 'UTF-8');
        }

        $caracteres = preg_split('//u', $texto, -1, PREG_SPLIT_NO_EMPTY);

        return $caracteres === false
            ? substr($texto, 0, 100)
            : implode('', array_slice($caracteres, 0, 100));
    }
}
