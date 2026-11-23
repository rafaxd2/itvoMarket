<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ITVOmarket | Eliminar producto</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/itvomarket.css">
    <link rel="stylesheet" href="assets/css/clay.css">
    <link rel="stylesheet" href="assets/css/catalogo.css">
</head>
<body class="catalog-page">
    <?php include __DIR__ . '/header.php'; ?>

    <main class="container catalog-container seller-product-delete-container">
        <header class="catalog-heading">
            <h1>Eliminar producto</h1>
            <p>Confirma antes de eliminar esta publicación.</p>
        </header>

        <section class="clay-card seller-product-delete-card" aria-labelledby="seller-product-delete-heading">
            <h2 id="seller-product-delete-heading">
                ¿Eliminar <?= htmlspecialchars((string) $producto['nombre'], ENT_QUOTES, 'UTF-8'); ?>?
            </h2>
            <p class="seller-product-delete-price">
                Precio informativo: $<?= number_format((float) $producto['precio'], 2, '.', ','); ?> MXN
            </p>
            <p class="seller-product-delete-warning">
                La publicación se quitará de tu catálogo. Esta acción es definitiva y no se puede deshacer.
            </p>

            <div class="seller-product-delete-actions">
                <a class="btn btn-outline-primary clay-button" href="index.php?accion=mis_productos">
                    Cancelar y conservar producto
                </a>
                <form method="post" action="index.php?accion=eliminar_producto">
                    <input type="hidden" name="id_producto" value="<?= (int) $producto['id_producto']; ?>">
                    <input type="hidden" name="token_csrf" value="<?= htmlspecialchars($tokenCsrf, ENT_QUOTES, 'UTF-8'); ?>">
                    <button class="btn btn-danger clay-button" type="submit">Sí, eliminar producto</button>
                </form>
            </div>
        </section>
    </main>

    <footer class="site-footer">
        <p class="footer-text">ITVOmarket es informativo: publicaciones y ubicación entre estudiantes. Sin compras ni pagos.</p>
    </footer>
</body>
</html>
