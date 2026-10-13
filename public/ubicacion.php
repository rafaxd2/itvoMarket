<?php

declare(strict_types=1);

require_once __DIR__ . '/../controllers/UbicacionController.php';

AuthController::requerirSesion();
AuthController::requerirRol('vendedor', 'panel.php?rol=comprador&mensaje=solo_vendedores');

$controlador = new UbicacionController();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $controlador->guardar();
    return;
}

$controlador->mostrarFormulario();
