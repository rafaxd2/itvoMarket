<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ITVOmarket | Publicación no disponible</title>
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
        <section class="catalog-empty clay-card clay-empty product-unavailable" aria-live="polite">
            <div class="catalog-empty-icon" aria-hidden="true"><i class="bi bi-basket2"></i></div>
            <h1>Esta publicación no está disponible</h1>
            <a class="btn btn-primary clay-button" href="index.php?accion=mis_productos">Volver a mis productos</a>
        </section>
    </main>

    <footer class="site-footer">
        <p class="footer-text">ITVOmarket es informativo: publicaciones y ubicación entre estudiantes. Sin compras ni pagos.</p>
    </footer>
</body>
</html>
