<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ITVOmarket | Mi ubicación</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
    <link rel="stylesheet" href="assets/css/itvomarket.css">
    <link rel="stylesheet" href="assets/css/clay.css">
</head>
<body class="bg-light">
    <?php include __DIR__ . '/header.php'; ?>
    <div class="container py-5">
        <div class="row g-4 align-items-stretch">
            <div class="col-lg-8">
                <div class="card border-0 h-100 map-card clay-card">
                    <div class="card-body p-4">
                        <h2 class="fw-bold mb-4">Mi ubicación</h2>
                        <div id="mapaUbicacion" class="clay-map" data-pin-guardado="<?= !empty($mensaje) ? 'true' : 'false'; ?>" style="height: 420px; width: 100%;"></div>

                        <div class="mt-4 d-flex gap-3 flex-wrap">
                            <button id="usarPosicionActual" type="button" class="btn btn-primary clay-button">Usar mi posición actual</button>
                            <button id="guardarUbicacion" type="button" class="btn btn-success clay-button">Guardar ubicación</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card border-0 h-100 map-card clay-card">
                    <div class="card-body p-4">
                        <h3 class="fw-bold mb-3">Datos de ubicación</h3>

                        <?php if (!empty($mensaje)): ?>
                            <div class="alert alert-success" role="alert"><?= htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8'); ?></div>
                        <?php endif; ?>

                        <?php if (!empty($errores['coordenadas'])): ?>
                            <div class="alert alert-danger" role="alert"><?= htmlspecialchars($errores['coordenadas'], ENT_QUOTES, 'UTF-8'); ?></div>
                        <?php endif; ?>

                        <div class="mb-3">
                            <label for="latitud" class="form-label">Latitud</label>
                            <input id="latitud" name="latitud" type="text" class="form-control clay-input <?= !empty($errores['coordenadas']) ? 'is-invalid' : ''; ?>" aria-invalid="<?= !empty($errores['coordenadas']) ? 'true' : 'false'; ?>" value="<?= htmlspecialchars((string) $latitud, ENT_QUOTES, 'UTF-8'); ?>">
                        </div>

                        <div class="mb-3">
                            <label for="longitud" class="form-label">Longitud</label>
                            <input id="longitud" name="longitud" type="text" class="form-control clay-input <?= !empty($errores['coordenadas']) ? 'is-invalid' : ''; ?>" aria-invalid="<?= !empty($errores['coordenadas']) ? 'true' : 'false'; ?>" value="<?= htmlspecialchars((string) $longitud, ENT_QUOTES, 'UTF-8'); ?>">
                        </div>

                        <div class="mb-3">
                            <label for="referencia" class="form-label">Referencias de dónde estás</label>
                            <input id="referencia" name="referencia" type="text" class="form-control clay-input" value="<?= htmlspecialchars((string) $referencia, ENT_QUOTES, 'UTF-8'); ?>">
                        </div>

                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="fw-semibold">Compartir mi ubicación</span>
                            <div class="form-check form-switch mb-0 location-toggle-wrapper">
                                <input id="estadoZona" class="form-check-input" type="checkbox" <?= strtolower((string) $estadoZona) === 'activo' ? 'checked' : ''; ?>>
                                <span id="pulsoEstadoZona" class="location-status-pulse" aria-hidden="true"></span>
                            </div>
                        </div>

                        <div class="small text-muted">Última actualización: <span id="ultimaActualizacion"><?= htmlspecialchars((string) $ultimaActualizacion, ENT_QUOTES, 'UTF-8') ?: '-'; ?></span></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer class="site-footer">
        <p class="footer-text">ITVOmarket es informativo: publicaciones y ubicación entre estudiantes. Sin compras ni pagos.</p>
    </footer>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin="" defer></script>
    <script src="assets/js/ubicacion.js" defer></script>
</body>
</html>
