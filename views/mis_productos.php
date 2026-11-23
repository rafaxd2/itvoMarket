<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ITVOmarket | Mis productos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/itvomarket.css">
    <link rel="stylesheet" href="assets/css/clay.css">
    <link rel="stylesheet" href="assets/css/catalogo.css">
    <link rel="stylesheet" href="assets/css/mis-productos.css">
</head>
<body class="catalog-page">
    <?php include __DIR__ . '/header.php'; ?>

    <main class="container catalog-container seller-products-container">
        <header class="catalog-heading">
            <h1>Mis productos</h1>
        </header>

        <?php if ($mensajeExito !== ''): ?>
            <div class="seller-products-message clay-inset" role="status">
                <?= htmlspecialchars($mensajeExito, ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endif; ?>

        <?php if ($error !== ''): ?>
            <div class="alert alert-danger" role="alert">
                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endif; ?>

        <div class="seller-products-toolbar">
            <h2 class="catalog-count">
                <?= count($productos); ?>
                <?= count($productos) === 1 ? 'producto' : 'productos'; ?>
                en tu catálogo
            </h2>
            <a class="btn btn-primary clay-button" href="index.php?accion=publicar">Publicar producto</a>
        </div>

        <?php if ($productos === []): ?>
            <section class="catalog-empty clay-card clay-empty" aria-live="polite">
                <div class="catalog-empty-icon" aria-hidden="true"><i class="bi bi-basket2"></i></div>
                <h2>Aún no tienes productos publicados</h2>
                <p>Publica un producto para que otros estudiantes puedan conocerlo.</p>
                <a class="btn btn-primary clay-button" href="index.php?accion=publicar">Publicar producto</a>
            </section>
        <?php else: ?>
            <section class="catalog-grid seller-products-grid" aria-label="Mis productos publicados">
                <?php foreach ($productos as $producto): ?>
                    <?php $urlImagen = CatalogoController::obtenerUrlImagenProducto($producto['imagen_producto'] ?? null); ?>
                    <?php $idProducto = (int) $producto['id_producto']; ?>
                    <article class="catalog-product clay-card seller-product-card">
                        <div class="catalog-image">
                            <?php if ($urlImagen !== null): ?>
                                <img
                                    src="<?= htmlspecialchars($urlImagen, ENT_QUOTES, 'UTF-8'); ?>"
                                    alt="<?= htmlspecialchars((string) $producto['nombre'], ENT_QUOTES, 'UTF-8'); ?>"
                                    loading="lazy"
                                    data-product-image>
                                <span class="catalog-image-placeholder" aria-hidden="true" hidden><i class="bi bi-image"></i></span>
                            <?php else: ?>
                                <span class="catalog-image-placeholder" aria-hidden="true"><i class="bi bi-image"></i></span>
                            <?php endif; ?>
                        </div>
                        <div class="catalog-product-content">
                            <h3 title="<?= htmlspecialchars((string) $producto['nombre'], ENT_QUOTES, 'UTF-8'); ?>">
                                <?= htmlspecialchars((string) $producto['nombre'], ENT_QUOTES, 'UTF-8'); ?>
                            </h3>
                            <p class="catalog-price">
                                $<?= number_format((float) $producto['precio'], 2, '.', ','); ?> MXN
                            </p>
                            <p class="catalog-product-detail">
                                <?= htmlspecialchars((string) ($producto['categoria'] ?? 'Sin categoría'), ENT_QUOTES, 'UTF-8'); ?>
                            </p>
                            <p class="catalog-product-detail seller-product-description">
                                <?= htmlspecialchars((string) ($producto['descripcion_producto'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                            </p>
                            <div class="seller-product-actions">
                                <a
                                    class="btn btn-outline-primary clay-button"
                                    href="index.php?accion=editar_producto&amp;id=<?= $idProducto; ?>"
                                    aria-label="Editar <?= htmlspecialchars((string) $producto['nombre'], ENT_QUOTES, 'UTF-8'); ?>">
                                    Editar
                                </a>
                                <a
                                    class="btn btn-outline-secondary clay-button seller-product-delete"
                                    href="index.php?accion=confirmar_eliminar&amp;id=<?= $idProducto; ?>"
                                    aria-label="Eliminar <?= htmlspecialchars((string) $producto['nombre'], ENT_QUOTES, 'UTF-8'); ?>">
                                    Eliminar
                                </a>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </section>
        <?php endif; ?>
    </main>

    <footer class="site-footer">
        <p class="footer-text">ITVOmarket es informativo: publicaciones y ubicación entre estudiantes. Sin compras ni pagos.</p>
    </footer>
    <script src="assets/js/catalogo.js" defer></script>
</body>
</html>
