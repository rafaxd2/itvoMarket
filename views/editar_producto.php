<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ITVOmarket | Editar producto</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/itvomarket.css">
    <link rel="stylesheet" href="assets/css/clay.css">
    <link rel="stylesheet" href="assets/css/catalogo.css">
</head>
<body class="catalog-page">
    <?php include __DIR__ . '/header.php'; ?>

    <main class="container catalog-container seller-product-form-container">
        <header class="catalog-heading">
            <h1>Editar producto</h1>
            <p>Actualiza los datos de tu publicación.</p>
        </header>

        <?php if ($errorGeneral !== '' || !empty($errores['general'])): ?>
            <div class="alert alert-danger" role="alert">
                <?= htmlspecialchars($errorGeneral !== '' ? $errorGeneral : $errores['general'], ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endif; ?>

        <?php if ($producto === null): ?>
            <section class="catalog-empty clay-card clay-empty">
                <p>No fue posible cargar el formulario. Inténtalo nuevamente.</p>
                <a class="btn btn-primary clay-button" href="index.php?accion=mis_productos">Volver a mis productos</a>
            </section>
        <?php else: ?>
            <section class="clay-card seller-product-form-card">
                <form method="post" action="index.php?accion=editar_producto" enctype="multipart/form-data" novalidate>
                    <input type="hidden" name="id_producto" value="<?= (int) $producto['id_producto']; ?>">
                    <input type="hidden" name="token_csrf" value="<?= htmlspecialchars($tokenCsrf, ENT_QUOTES, 'UTF-8'); ?>">

                    <div class="mb-3">
                        <label for="nombre" class="form-label">Nombre</label>
                        <input
                            id="nombre"
                            name="nombre"
                            type="text"
                            value="<?= htmlspecialchars((string) ($producto['nombre'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                            class="form-control clay-input <?= !empty($errores['nombre']) ? 'is-invalid' : ''; ?>"
                            aria-invalid="<?= !empty($errores['nombre']) ? 'true' : 'false'; ?>"
                            required>
                        <?php if (!empty($errores['nombre'])): ?>
                            <div class="invalid-feedback d-block"><?= htmlspecialchars($errores['nombre'], ENT_QUOTES, 'UTF-8'); ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label for="precio" class="form-label">Precio</label>
                        <input
                            id="precio"
                            name="precio"
                            type="number"
                            min="0.01"
                            max="9999.99"
                            step="0.01"
                            value="<?= htmlspecialchars((string) ($producto['precio'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                            class="form-control clay-input <?= !empty($errores['precio']) ? 'is-invalid' : ''; ?>"
                            aria-invalid="<?= !empty($errores['precio']) ? 'true' : 'false'; ?>"
                            required>
                        <small class="form-text text-muted">Máximo $9,999.99 MXN.</small>
                        <?php if (!empty($errores['precio'])): ?>
                            <div class="invalid-feedback d-block"><?= htmlspecialchars($errores['precio'], ENT_QUOTES, 'UTF-8'); ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label for="descripcion_producto" class="form-label">Descripción</label>
                        <textarea
                            id="descripcion_producto"
                            name="descripcion_producto"
                            class="form-control clay-input"
                            rows="4"><?= htmlspecialchars((string) ($producto['descripcion_producto'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="id_categoria" class="form-label">Categoría</label>
                        <select
                            id="id_categoria"
                            name="id_categoria"
                            class="form-select clay-input <?= !empty($errores['id_categoria']) ? 'is-invalid' : ''; ?>"
                            aria-invalid="<?= !empty($errores['id_categoria']) ? 'true' : 'false'; ?>"
                            required>
                            <option value="">Selecciona una categoría</option>
                            <?php foreach ($categorias as $categoria): ?>
                                <option
                                    value="<?= (int) $categoria['id_categoria']; ?>"
                                    <?= (string) ($producto['id_categoria'] ?? '') === (string) $categoria['id_categoria'] ? 'selected' : ''; ?>>
                                    <?= htmlspecialchars((string) $categoria['tipo_categoria'], ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (!empty($errores['id_categoria'])): ?>
                            <div class="invalid-feedback d-block"><?= htmlspecialchars($errores['id_categoria'], ENT_QUOTES, 'UTF-8'); ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-4">
                        <p class="form-label">Imagen actual</p>
                        <div class="catalog-image seller-product-current-image">
                            <?php if ($imagenActualUrl !== null): ?>
                                <img
                                    src="<?= htmlspecialchars($imagenActualUrl, ENT_QUOTES, 'UTF-8'); ?>"
                                    alt="<?= htmlspecialchars((string) ($producto['nombre'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                    data-product-image>
                                <span class="catalog-image-placeholder" aria-hidden="true" hidden><i class="bi bi-image"></i></span>
                            <?php else: ?>
                                <span class="catalog-image-placeholder" aria-hidden="true"><i class="bi bi-image"></i></span>
                            <?php endif; ?>
                        </div>
                        <label for="imagen" class="form-label mt-3">Cambiar imagen</label>
                        <input
                            id="imagen"
                            name="imagen"
                            type="file"
                            accept=".jpg,.jpeg,.png,image/jpeg,image/png"
                            class="form-control clay-input <?= !empty($errores['imagen']) ? 'is-invalid' : ''; ?>"
                            aria-invalid="<?= !empty($errores['imagen']) ? 'true' : 'false'; ?>">
                        <small class="form-text text-muted">Formatos permitidos: jpg o png. Si no seleccionas otra imagen, se conserva la actual.</small>
                        <?php if (!empty($errores['imagen'])): ?>
                            <div class="invalid-feedback d-block"><?= htmlspecialchars($errores['imagen'], ENT_QUOTES, 'UTF-8'); ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="seller-product-form-actions">
                        <button type="submit" class="btn btn-primary clay-button">Guardar cambios</button>
                        <a class="btn btn-outline-primary clay-button" href="index.php?accion=mis_productos">Cancelar</a>
                    </div>
                </form>
            </section>
        <?php endif; ?>
    </main>

    <footer class="site-footer">
        <p class="footer-text">ITVOmarket es informativo: publicaciones y ubicación entre estudiantes. Sin compras ni pagos.</p>
    </footer>
    <script src="assets/js/catalogo.js" defer></script>
</body>
</html>
