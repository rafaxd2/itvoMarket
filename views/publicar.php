<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ITVOmarket | Publicar producto</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/itvomarket.css">
    <link rel="stylesheet" href="assets/css/clay.css">
</head>
<body class="bg-light">
    <?php include __DIR__ . '/header.php'; ?>
    <div class="container py-5">
        <div class="row g-4 align-items-stretch">
            <div class="col-lg-8">
                <div class="card border-0 h-100 clay-card">
                    <div class="card-body p-4 p-lg-5">
                        <h2 class="fw-bold mb-4">Publicar producto</h2>

                        <?php if (!empty($mensajeExito)): ?>
                            <div class="product-published-toast" role="status" aria-live="polite">
                                <span><?= htmlspecialchars($mensajeExito, ENT_QUOTES, 'UTF-8'); ?></span>
                            </div>
                        <?php endif; ?>

                        <form method="post" action="index.php?accion=procesarPublicacion" enctype="multipart/form-data" novalidate>
                            <div class="mb-3">
                                <label for="nombre" class="form-label">Nombre del producto</label>
                                <input
                                    id="nombre"
                                    name="nombre"
                                    type="text"
                                    value="<?= htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8'); ?>"
                                    class="form-control clay-input <?= !empty($errores['nombre']) ? 'is-invalid' : '' ?>"
                                    aria-invalid="<?= !empty($errores['nombre']) ? 'true' : 'false'; ?>">
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
                                    step="0.01"
                                    value="<?= htmlspecialchars($precio, ENT_QUOTES, 'UTF-8'); ?>"
                                    class="form-control clay-input <?= !empty($errores['precio']) ? 'is-invalid' : '' ?>"
                                    aria-invalid="<?= !empty($errores['precio']) ? 'true' : 'false'; ?>">
                                <?php if (!empty($errores['precio'])): ?>
                                    <div class="invalid-feedback d-block"><?= htmlspecialchars($errores['precio'], ENT_QUOTES, 'UTF-8'); ?></div>
                                <?php endif; ?>
                            </div>

                            <div class="mb-3">
                                <label for="id_categoria" class="form-label">Categoría</label>
                                <select
                                    id="id_categoria"
                                    name="id_categoria"
                                    class="form-select clay-input <?= !empty($errores['id_categoria']) ? 'is-invalid' : '' ?>"
                                    aria-invalid="<?= !empty($errores['id_categoria']) ? 'true' : 'false'; ?>"
                                    required>
                                    <option value="">Selecciona una categoría</option>
                                    <?php foreach ($categorias as $categoria): ?>
                                        <option
                                            value="<?= (int) $categoria['id_categoria']; ?>"
                                            <?= (string) $idCategoria === (string) $categoria['id_categoria'] ? 'selected' : ''; ?>>
                                            <?= htmlspecialchars($categoria['tipo_categoria'], ENT_QUOTES, 'UTF-8'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (!empty($errores['id_categoria'])): ?>
                                    <div class="invalid-feedback d-block"><?= htmlspecialchars($errores['id_categoria'], ENT_QUOTES, 'UTF-8'); ?></div>
                                <?php endif; ?>
                            </div>

                            <div class="mb-3">
                                <label for="descripcion_producto" class="form-label">Descripción</label>
                                <textarea id="descripcion_producto" name="descripcion_producto" class="form-control clay-input" rows="4"><?= htmlspecialchars($descripcion, ENT_QUOTES, 'UTF-8'); ?></textarea>
                            </div>

                            <div class="mb-4">
                                <label for="imagen" class="form-label">Foto del producto</label>
                                <input id="imagen" name="imagen" type="file" accept=".jpg,.jpeg,.png,image/jpeg,image/png" class="form-control clay-input <?= !empty($errores['imagen']) ? 'is-invalid' : '' ?>" aria-invalid="<?= !empty($errores['imagen']) ? 'true' : 'false'; ?>">
                                <?php if (!empty($errores['imagen'])): ?>
                                    <div class="invalid-feedback d-block"><?= htmlspecialchars($errores['imagen'], ENT_QUOTES, 'UTF-8'); ?></div>
                                <?php endif; ?>
                                <small class="text-muted">Formatos permitidos: jpg o png.</small>
                            </div>

                            <button type="submit" class="btn btn-primary clay-button">Publicar producto</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card border-0 h-100 panel-card clay-card">
                    <div class="card-body p-4">
                        <h3 class="fw-bold mb-3">Antes de publicar</h3>
                        <ul class="list-unstyled mb-0">
                            <li class="mb-2">• Verifica que el nombre y el precio sean claros.</li>
                            <li class="mb-2">• Usa una foto con formato jpg o png.</li>
                            <li>• Añade una descripción útil para tus compradores.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <?php if (!empty($productos)): ?>
            <div class="mt-5">
                <h3 class="fw-bold mb-3">Mi catálogo</h3>
                <div class="row g-3">
                    <?php foreach ($productos as $producto): ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="product-card h-100 <?= isset($productoRecienteId) && (int) $productoRecienteId === (int) $producto['id_producto'] ? 'product-card--just-published' : ''; ?>">
                                <?php $urlImagenProducto = CatalogoController::obtenerUrlImagenProducto($producto['imagen_producto'] ?? null); ?>
                                <?php if ($urlImagenProducto !== null): ?>
                                    <img src="<?= htmlspecialchars($urlImagenProducto, ENT_QUOTES, 'UTF-8'); ?>" class="card-img-top" alt="<?= htmlspecialchars($producto['nombre'], ENT_QUOTES, 'UTF-8'); ?>" style="height: 220px; object-fit: cover;">
                                <?php else: ?>
                                    <div class="catalog-image-placeholder" aria-hidden="true"><i class="bi bi-image"></i></div>
                                <?php endif; ?>
                                <div class="card-body">
                                    <h5 class="card-title"><?= htmlspecialchars($producto['nombre'], ENT_QUOTES, 'UTF-8'); ?></h5>
                                    <p class="product-price fw-bold mb-2">$<?= htmlspecialchars((string) $producto['precio'], ENT_QUOTES, 'UTF-8'); ?></p>
                                    <p class="card-text text-muted"><?= htmlspecialchars($producto['descripcion_producto'], ENT_QUOTES, 'UTF-8'); ?></p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <footer class="site-footer">
        <p class="footer-text">ITVOmarket es informativo: publicaciones y ubicación entre estudiantes. Sin compras ni pagos.</p>
    </footer>
    <script src="assets/js/producto-animaciones.js" defer></script>
</body>
</html>
