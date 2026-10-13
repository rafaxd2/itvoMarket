<?php

declare(strict_types=1);

require_once __DIR__ . '/../models/ZonaVenta.php';
require_once __DIR__ . '/../controllers/AuthController.php';

// Controlador que valida y guarda la ubicación del vendedor en la zona de venta.
class UbicacionController
{
    // Muestra el formulario de ubicación del vendedor con el mapa y el estado del toggle.
    public function mostrarFormulario(array $contexto = []): void
    {
        AuthController::requerirSesion();
        AuthController::requerirRol('vendedor');

        $latitud = $contexto['latitud'] ?? '';
        $longitud = $contexto['longitud'] ?? '';
        $referencia = $contexto['referencia'] ?? '';
        $estadoZona = $contexto['estado_zona'] ?? 'Activo';
        $mensaje = $contexto['mensaje'] ?? '';
        $errores = $contexto['errores'] ?? [];
        $googleMapsApiKey = obtenerValorEntorno('GOOGLE_MAPS_API_KEY', '');

        include __DIR__ . '/../views/ubicacion.php';
    }

    // Guarda la ubicación del vendedor con validación de coordenadas y estado.
    public function guardar(): void
    {
        AuthController::requerirSesion();
        AuthController::requerirRol('vendedor');

        $latitud = trim((string) ($_POST['latitud'] ?? ''));
        $longitud = trim((string) ($_POST['longitud'] ?? ''));
        $referencia = trim((string) ($_POST['referencia'] ?? ''));
        $estadoZona = trim((string) ($_POST['estado_zona'] ?? 'Activo'));
        if ($estadoZona === 'Inactivo') {
            $estadoZona = 'Desactivo';
        }
        $errores = [];

        if ($latitud === '' || $longitud === '') {
            $errores['coordenadas'] = 'Las coordenadas son obligatorias.';
        } elseif (!is_numeric($latitud) || !is_numeric($longitud)) {
            $errores['coordenadas'] = 'Las coordenadas deben ser numéricas.';
        } elseif ((float) $latitud < -90 || (float) $latitud > 90 || (float) $longitud < -180 || (float) $longitud > 180) {
            $errores['coordenadas'] = 'Las coordenadas están fuera de los rangos válidos.';
        }

        if ($estadoZona !== 'Activo' && $estadoZona !== 'Desactivo') {
            $estadoZona = 'Activo';
        }

        if (!empty($errores)) {
            $this->mostrarFormulario([
                'latitud' => $latitud,
                'longitud' => $longitud,
                'referencia' => $referencia,
                'estado_zona' => $estadoZona,
                'errores' => $errores,
            ]);
            return;
        }

        $idVendedor = $this->obtenerIdVendedorActual();
        ZonaVenta::actualizarUbicacion([
            'id_vendedor' => $idVendedor,
            'latitud' => (float) $latitud,
            'longitud' => (float) $longitud,
            'punto_referencia' => $referencia,
            'estado_zona' => $estadoZona,
        ]);

        $this->mostrarFormulario([
            'latitud' => $latitud,
            'longitud' => $longitud,
            'referencia' => $referencia,
            'estado_zona' => $estadoZona,
            'mensaje' => 'Ubicación guardada correctamente.',
        ]);
    }

    // Devuelve los vendedores activos para el mapa del comprador.
    public function obtenerVendedoresActivos(): array
    {
        return ZonaVenta::obtenerActivos();
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
