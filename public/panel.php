<?php

declare(strict_types=1);

require_once __DIR__ . '/../controllers/AuthController.php';

AuthController::requerirSesion();

$rolUsuario = strtolower((string) ($_SESSION['usuario_rol'] ?? ($_GET['rol'] ?? 'comprador')));
$rolSolicitado = strtolower((string) ($_GET['rol'] ?? $rolUsuario));

if ($rolSolicitado === 'vendedor' && $rolUsuario !== 'vendedor') {
    header('Location: panel.php?rol=comprador');
    exit;
}
$mensajeAcceso = ($_GET['mensaje'] ?? '') === 'solo_vendedores'
    ? 'Esta sección es solo para vendedores. Cambia tu rol a Vendedor para acceder.'
    : '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ITVOmarket | Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/itvomarket.css">
    <link rel="stylesheet" href="assets/css/clay.css">
</head>
<body class="bg-light">
    <?php include __DIR__ . '/../views/header.php'; ?>
    <div class="container py-5">
        <div class="card clay-card">
            <div class="card-body p-5">
                <?php if ($mensajeAcceso !== ''): ?>
                    <div class="alert alert-warning" role="alert"><?= htmlspecialchars($mensajeAcceso, ENT_QUOTES, 'UTF-8'); ?></div>
                <?php endif; ?>
                <h1 class="mb-3">Bienvenido a ITVOmarket</h1>
                <p class="lead mb-4">Has iniciado sesión como <strong><?= htmlspecialchars($rolUsuario, ENT_QUOTES, 'UTF-8'); ?></strong>.</p>
                <div class="d-flex gap-3 flex-wrap">
                    <a href="index.php?accion=logout" class="btn btn-primary clay-button">Cerrar sesión</a>
                    <a href="index.php?accion=login" class="btn btn-outline-primary clay-button">Volver al login</a>
                </div>
            </div>
        </div>
    </div>

    <footer class="site-footer">
        <p class="footer-text">ITVOmarket es informativo: publicaciones y ubicación entre estudiantes. Sin compras ni pagos.</p>
    </footer>
</body>
</html>
