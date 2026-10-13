<?php

declare(strict_types=1);

// Carga las variables de entorno desde el archivo .env ubicado en la raíz del proyecto.
function cargarVariablesEntorno(string $rutaArchivo): void
{
    if (!is_file($rutaArchivo)) {
        return;
    }

    $lineas = file($rutaArchivo, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lineas === false) {
        return;
    }

    foreach ($lineas as $linea) {
        $linea = trim($linea);

        if ($linea === '' || str_starts_with($linea, '#')) {
            continue;
        }

        $partes = explode('=', $linea, 2);
        if (count($partes) !== 2) {
            continue;
        }

        [$clave, $valor] = array_map('trim', $partes);
        $_ENV[$clave] = $valor;
        putenv($clave . '=' . $valor);
    }
}

// Lee el valor de una variable de entorno y devuelve un valor por defecto cuando no exista.
function obtenerValorEntorno(string $clave, string $valorPredeterminado = ''): string
{
    $valor = getenv($clave);

    if ($valor !== false) {
        return $valor;
    }

    if (array_key_exists($clave, $_ENV)) {
        return (string) $_ENV[$clave];
    }

    return $valorPredeterminado;
}

// Carga las variables de entorno antes de iniciar la conexión.
cargarVariablesEntorno(dirname(__DIR__) . '/.env');

// Lista las tablas del proyecto que ya existen en la base de datos y no deben crearse aquí.
function obtenerTablasRequeridas(): array
{
    return ['usuario', 'vendedores', 'productos', 'zona_venta'];
}

// Crea la conexión PDO con la base de datos del proyecto.
function obtenerConexionBaseDatos(): PDO
{
    if (isset($GLOBALS['conexionBaseDatosPruebas']) && $GLOBALS['conexionBaseDatosPruebas'] instanceof PDO) {
        return $GLOBALS['conexionBaseDatosPruebas'];
    }

    $host = obtenerValorEntorno('DB_HOST', 'localhost');
    $nombreBaseDatos = obtenerValorEntorno('DB_NAME', 'itvomarket');
    $usuario = obtenerValorEntorno('DB_USER', 'root');
    $contrasena = obtenerValorEntorno('DB_PASS', '');
    $charset = 'utf8mb4';

    $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', $host, $nombreBaseDatos, $charset);

    try {
        $conexion = new PDO($dsn, $usuario, $contrasena, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);

        return $conexion;
    } catch (PDOException $exception) {
        throw new PDOException('No fue posible conectar con la base de datos: ' . $exception->getMessage(), (int) $exception->getCode());
    }
}

// Permite inyectar una conexión aislada en las pruebas automatizadas.
function establecerConexionBaseDatosPruebas(?PDO $conexion): void
{
    if ($conexion === null) {
        unset($GLOBALS['conexionBaseDatosPruebas']);
        return;
    }

    $GLOBALS['conexionBaseDatosPruebas'] = $conexion;
}

// Alias compatible para conectar la base de datos con un nombre abreviado.
function conectarDB(): PDO
{
    return obtenerConexionBaseDatos();
}

// Verifica que la base de datos del proyecto contenga las tablas necesarias sin modificarlas.
function verificarTablasRequeridas(PDO $conexion): array
{
    $resultados = $conexion->query('SHOW TABLES');
    $tablasExistentes = [];

    if ($resultados !== false) {
        foreach ($resultados as $fila) {
            $tabla = $fila[array_key_first($fila)];
            if (is_string($tabla)) {
                $tablasExistentes[] = strtolower($tabla);
            }
        }
    }

    $faltantes = array_values(array_diff(obtenerTablasRequeridas(), $tablasExistentes));

    return [
        'existentes' => $tablasExistentes,
        'faltantes' => $faltantes,
    ];
}
