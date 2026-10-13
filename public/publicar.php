<?php

declare(strict_types=1);

require_once __DIR__ . '/../controllers/ProductoController.php';

AuthController::requerirSesion();
AuthController::requerirRol('vendedor', 'panel.php?rol=comprador&mensaje=solo_vendedores');

$controlador = new ProductoController();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $controlador->publicar();
    return;
}

$_SESSION['usuario_rol'] = strtolower((string) ($_SESSION['usuario_rol'] ?? 'vendedor'));
$controlador->mostrarFormulario();
