<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

class Categoria
{
    public static function listarTodas(): array
    {
        $conexion = obtenerConexionBaseDatos();
        $consulta = $conexion->prepare(
            'SELECT id_categoria, tipo_categoria AS nombre FROM categorias ORDER BY tipo_categoria ASC'
        );
        $consulta->execute();

        return $consulta->fetchAll();
    }
}
