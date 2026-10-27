<?php

declare(strict_types=1);

require_once __DIR__ . '/../models/Categoria.php';
require_once __DIR__ . '/../models/Producto.php';
require_once __DIR__ . '/AuthController.php';

class CatalogoController
{
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

        if (str_starts_with(strtolower($ruta), 'http')) {
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
