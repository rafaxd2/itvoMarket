<?php

declare(strict_types=1);

require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../models/ZonaVenta.php';

class DisponibilidadController
{
    public function mostrar(): void
    {
        AuthController::requerirSesion();
        AuthController::requerirRol('vendedor');

        try {
            $idVendedor = $this->obtenerIdVendedorActual();
            $zona = $idVendedor !== null ? ZonaVenta::obtenerPorVendedor($idVendedor) : null;

            $this->mostrarVista($zona);
        } catch (PDOException $excepcion) {
            error_log('No fue posible consultar la disponibilidad del vendedor.');
            $this->mostrarVista(
                null,
                '',
                'No fue posible consultar tu disponibilidad. Inténtalo de nuevo.',
                true
            );
        }
    }

    public function actualizar(): void
    {
        AuthController::requerirSesion();
        AuthController::requerirRol('vendedor');

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            header('Location: index.php?accion=disponibilidad');
            exit;
        }

        $zona = null;

        try {
            $idVendedor = $this->obtenerIdVendedorActual();
            $zona = $idVendedor !== null ? ZonaVenta::obtenerPorVendedor($idVendedor) : null;

            if ($zona === null) {
                $this->mostrarVista(null);
                return;
            }

            $estadoRecibido = $_POST['estado_zona'] ?? null;
            if (!is_string($estadoRecibido) || !in_array($estadoRecibido, ['Activo', 'Inactivo'], true)) {
                $this->mostrarVista(
                    $zona,
                    'No fue posible actualizar tu disponibilidad. Inténtalo de nuevo.'
                );
                return;
            }

            if (!ZonaVenta::actualizarEstadoZona($idVendedor, $estadoRecibido)) {
                $this->mostrarVista(
                    $zona,
                    'No fue posible actualizar tu disponibilidad. Inténtalo de nuevo.'
                );
                return;
            }

            $zona['estado_zona'] = $estadoRecibido === 'Inactivo' ? 'Desactivo' : 'Activo';
            $mensaje = $estadoRecibido === 'Inactivo'
                ? 'Estado actualizado: ahora estás inactivo.'
                : 'Estado actualizado: ahora estás activo.';

            $this->mostrarVista($zona, '', $mensaje);
        } catch (PDOException $excepcion) {
            error_log('No fue posible actualizar la disponibilidad del vendedor.');
            $this->mostrarVista(
                $zona,
                'No fue posible actualizar tu disponibilidad. Inténtalo de nuevo.',
                '',
                $zona === null
            );
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

    private function mostrarVista(
        ?array $zona,
        string $error = '',
        string $mensajeExito = '',
        bool $errorConsulta = false
    ): void {
        $estadoActual = $zona === null
            ? 'Activo'
            : (($zona['estado_zona'] ?? '') === 'Activo' ? 'Activo' : 'Inactivo');
        $puntoReferencia = (string) ($zona['punto_referencia'] ?? '');

        include __DIR__ . '/../views/disponibilidad.php';
    }
}
