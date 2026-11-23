<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ITVOmarket | Mi disponibilidad</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/itvomarket.css">
    <link rel="stylesheet" href="assets/css/clay.css">
    <link rel="stylesheet" href="assets/css/disponibilidad.css">
</head>
<body class="bg-light">
    <?php include __DIR__ . '/header.php'; ?>

    <main class="container availability-container py-5">
        <header class="availability-heading mb-4">
            <h1 class="fw-bold">Mi disponibilidad</h1>
            <p>Controla cuándo apareces en el mapa de compradores.</p>
        </header>

        <?php if ($mensajeExito !== ''): ?>
            <div class="availability-confirmation clay-inset" role="status">
                <?= htmlspecialchars($mensajeExito, ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endif; ?>

        <?php if ($error !== '' && !$errorConsulta): ?>
            <div class="availability-error" role="alert">
                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endif; ?>

        <?php if ($errorConsulta): ?>
            <section class="availability-card availability-error-card clay-card" role="alert">
                <p class="mb-0">No fue posible consultar tu disponibilidad. Inténtalo de nuevo.</p>
            </section>
        <?php elseif ($zona === null): ?>
            <section class="availability-card availability-empty clay-card clay-inset">
                <h2>Primero registra tu ubicación</h2>
                <p>Para controlar tu disponibilidad necesitas una ubicación registrada.</p>
                <a class="btn btn-primary clay-button" href="index.php?accion=ubicacion">Actualizar mi ubicación</a>
            </section>
        <?php else: ?>
            <?php
            $estaActivo = $estadoActual === 'Activo';
            $etiquetaEstado = $estaActivo
                ? 'Activo · visible en el mapa'
                : 'Inactivo · oculto en el mapa';
            $notaEstado = $estaActivo
                ? 'Los compradores ya pueden verte en el mapa.'
                : 'Los compradores ya no te ven en el mapa. Tus publicaciones se conservan.';
            ?>
            <section class="availability-card clay-card" aria-labelledby="availability-status-heading">
                <h2 id="availability-status-heading">Estado actual: <?= $estaActivo ? 'Activo' : 'Inactivo'; ?></h2>

                <form method="post" action="index.php?accion=actualizar_disponibilidad" data-availability-form>
                    <input type="hidden" name="estado_zona" value="Inactivo">
                    <div class="availability-switch-row">
                        <label class="availability-switch" for="estadoDisponibilidad">
                            <input
                                id="estadoDisponibilidad"
                                class="availability-switch-input"
                                type="checkbox"
                                name="estado_zona"
                                value="Activo"
                                role="switch"
                                aria-checked="<?= $estaActivo ? 'true' : 'false'; ?>"
                                aria-labelledby="availability-switch-label"
                                <?= $estaActivo ? 'checked' : ''; ?>>
                            <span class="availability-switch-track" aria-hidden="true"></span>
                        </label>
                        <span id="availability-switch-label" class="availability-switch-label">
                            <?= htmlspecialchars($etiquetaEstado, ENT_QUOTES, 'UTF-8'); ?>
                        </span>
                    </div>
                    <p class="availability-note"><?= htmlspecialchars($notaEstado, ENT_QUOTES, 'UTF-8'); ?></p>
                    <button class="btn btn-primary clay-button availability-submit" type="submit" data-availability-submit>
                        Guardar estado
                    </button>
                </form>
            </section>

            <section class="availability-card clay-card mt-4" aria-labelledby="availability-location-heading">
                <h2 id="availability-location-heading">Ubicación registrada</h2>
                <p class="availability-reference">
                    <?= htmlspecialchars($puntoReferencia !== '' ? $puntoReferencia : 'Sin referencia registrada', ENT_QUOTES, 'UTF-8'); ?>
                </p>
                <div class="availability-actions">
                    <a class="btn btn-outline-primary clay-button" href="index.php?accion=ubicacion">Actualizar mi ubicación</a>
                    <a class="btn btn-primary clay-button" href="index.php?accion=publicar">Ir a mis productos</a>
                </div>
            </section>
        <?php endif; ?>
    </main>

    <footer class="site-footer">
        <p class="footer-text">ITVOmarket es informativo: publicaciones y ubicación entre estudiantes. Sin compras ni pagos.</p>
    </footer>
    <script src="assets/js/estado-disponibilidad.js" defer></script>
</body>
</html>
