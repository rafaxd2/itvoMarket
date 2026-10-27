<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ITVOmarket | Detalle del producto</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/itvomarket.css">
    <link rel="stylesheet" href="assets/css/clay.css">
    <link rel="stylesheet" href="assets/css/catalogo.css">
</head>
<body class="catalog-page">
    <?php include __DIR__ . '/header.php'; ?>

    <main class="container catalog-container">
        <?php if ($producto === null || $error !== ''): ?>
            <section class="catalog-empty clay-card clay-empty product-unavailable" aria-live="polite">
                <div class="catalog-empty-icon" aria-hidden="true"><i class="bi bi-basket2"></i></div>
                <h1>Esta publicación no está disponible</h1>
                <?php if ($error !== ''): ?>
                    <p><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
                <?php endif; ?>
                <a class="btn btn-primary clay-button" href="<?= htmlspecialchars($urlRegreso, ENT_QUOTES, 'UTF-8'); ?>">Volver al catálogo</a>
            </section>
        <?php else: ?>
            <?php $urlImagen = CatalogoController::obtenerUrlImagenProducto($producto['imagen_producto'] ?? null); ?>
            <a class="btn btn-primary clay-button product-back-link" href="<?= htmlspecialchars($urlRegreso, ENT_QUOTES, 'UTF-8'); ?>">← Volver al catálogo</a>
            <article class="product-detail clay-card">
                <div class="product-detail-image catalog-image">
                    <?php if ($urlImagen !== null): ?>
                        <img
                            src="<?= htmlspecialchars($urlImagen, ENT_QUOTES, 'UTF-8'); ?>"
                            alt="<?= htmlspecialchars($producto['nombre'], ENT_QUOTES, 'UTF-8'); ?>"
                            data-product-image>
                        <span class="catalog-image-placeholder" aria-hidden="true" hidden><i class="bi bi-image"></i></span>
                    <?php else: ?>
                        <span class="catalog-image-placeholder" aria-hidden="true"><i class="bi bi-image"></i></span>
                    <?php endif; ?>
                </div>
                <div class="product-detail-content">
                    <p class="product-detail-category"><?= htmlspecialchars($producto['categoria'], ENT_QUOTES, 'UTF-8'); ?></p>
                    <h1><?= htmlspecialchars($producto['nombre'], ENT_QUOTES, 'UTF-8'); ?></h1>
                    <p class="catalog-price product-detail-price">
                        $<?= number_format((float) $producto['precio'], 2, '.', ','); ?> MXN
                    </p>
                    <section class="product-detail-section">
                        <h2>Descripción</h2>
                        <p><?= nl2br(htmlspecialchars((string) ($producto['descripcion_producto'] ?? ''), ENT_QUOTES, 'UTF-8')); ?></p>
                    </section>
                    <p class="product-detail-vendor">
                        <span>Vendedor</span>
                        <?= htmlspecialchars($producto['nombre_vendedor'], ENT_QUOTES, 'UTF-8'); ?>
                    </p>
                    <?php if (!empty($producto['estado']) || !empty($producto['condicion'])): ?>
                        <section class="product-detail-status">
                            <h2>Estado y condición</h2>
                            <?php if (!empty($producto['estado'])): ?>
                                <p><span>Estado:</span> <?= htmlspecialchars($producto['estado'], ENT_QUOTES, 'UTF-8'); ?></p>
                            <?php endif; ?>
                            <?php if (!empty($producto['condicion'])): ?>
                                <p><span>Condición:</span> <?= htmlspecialchars($producto['condicion'], ENT_QUOTES, 'UTF-8'); ?></p>
                            <?php endif; ?>
                        </section>
                    <?php endif; ?>
                </div>
            </article>
        <?php endif; ?>
    </main>

    <footer class="site-footer">
        <p class="footer-text">ITVOmarket es informativo: publicaciones y ubicación entre estudiantes. Sin compras ni pagos.</p>
    </footer>
</body>
</html>
