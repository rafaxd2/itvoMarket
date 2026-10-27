<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ITVOmarket | Catálogo</title>
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
        <header class="catalog-heading">
            <h1><?= $tieneCriterios ? 'Resultados de búsqueda' : 'Catálogo de publicaciones'; ?></h1>
            <p>
                <?php if (!$tieneCriterios): ?>
                    Encuentra lo que ofrecen otros estudiantes. Los precios son informativos.
                <?php elseif ($publicaciones !== []): ?>
                    Se muestran publicaciones que coinciden con el texto y la categoría elegida.
                <?php else: ?>
                    Puedes cambiar el texto o elegir otra categoría para seguir buscando.
                <?php endif; ?>
            </p>
        </header>

        <?php if ($error !== ''): ?>
            <div class="alert alert-danger" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <form class="catalog-filters clay-card" method="get" action="index.php">
            <input type="hidden" name="accion" value="catalogo">
            <div class="catalog-filter-field catalog-search-field">
                <label for="catalogo-texto">Nombre o palabra clave</label>
                <input
                    id="catalogo-texto"
                    class="form-control clay-input"
                    type="search"
                    name="q"
                    maxlength="100"
                    value="<?= htmlspecialchars($texto, ENT_QUOTES, 'UTF-8'); ?>"
                    placeholder="¿Qué estás buscando?">
            </div>
            <div class="catalog-filter-field catalog-category-field">
                <label for="catalogo-categoria">Categoría</label>
                <select id="catalogo-categoria" class="form-select clay-input" name="categoria">
                    <option value="">Todas las categorías</option>
                    <?php foreach ($categorias as $categoria): ?>
                        <option
                            value="<?= (int) $categoria['id_categoria']; ?>"
                            <?= $idCategoria === (int) $categoria['id_categoria'] ? 'selected' : ''; ?>>
                            <?= htmlspecialchars($categoria['nombre'], ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-primary clay-button catalog-search-button">Buscar</button>
        </form>

        <?php if ($tieneCriterios): ?>
            <div class="catalog-search-summary">
                <p>
                    <?php if ($texto !== ''): ?>
                        <span>Búsqueda: “<?= htmlspecialchars($texto, ENT_QUOTES, 'UTF-8'); ?>”</span>
                    <?php endif; ?>
                    <?php if ($texto !== '' && $idCategoria !== null): ?><span aria-hidden="true"> · </span><?php endif; ?>
                    <?php foreach ($categorias as $categoria): ?>
                        <?php if ($idCategoria === (int) $categoria['id_categoria']): ?>
                            <span>Categoría: <?= htmlspecialchars($categoria['nombre'], ENT_QUOTES, 'UTF-8'); ?></span>
                            <?php break; ?>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </p>
                <a href="index.php?accion=catalogo">Limpiar filtros</a>
            </div>
        <?php endif; ?>

        <?php if ($error === ''): ?>
            <?php
            $cantidad = count($publicaciones);
            $sustantivo = $cantidad === 1 ? 'publicación' : 'publicaciones';
            $estadoConteo = $tieneCriterios ? 'encontrada' : 'disponible';
            $terminacionConteo = $cantidad === 1 ? '' : 's';
            ?>
            <h2 class="catalog-count">
                <?= $cantidad; ?> <?= $sustantivo; ?> <?= $estadoConteo . $terminacionConteo; ?>
            </h2>

            <?php if (!$hayPublicaciones): ?>
                <section class="catalog-empty clay-card clay-empty" aria-live="polite">
                    <div class="catalog-empty-icon" aria-hidden="true"><i class="bi bi-basket2"></i></div>
                    <h2>Aún no hay publicaciones</h2>
                    <p>Cuando los vendedores publiquen sus productos, los verás aquí.</p>
                </section>
            <?php elseif ($publicaciones === []): ?>
                <section class="catalog-empty clay-card clay-empty" aria-live="polite">
                    <div class="catalog-empty-icon" aria-hidden="true"><i class="bi bi-search"></i></div>
                    <h2>No encontramos publicaciones</h2>
                    <p>
                        <?php if ($texto !== '' && $idCategoria !== null): ?>
                            No hay coincidencias para “<?= htmlspecialchars($texto, ENT_QUOTES, 'UTF-8'); ?>” en <?= htmlspecialchars($nombreCategoria, ENT_QUOTES, 'UTF-8'); ?>. Prueba con otra palabra o elimina los filtros.
                        <?php elseif ($texto !== ''): ?>
                            No hay coincidencias para “<?= htmlspecialchars($texto, ENT_QUOTES, 'UTF-8'); ?>”. Prueba con otra palabra o elimina los filtros.
                        <?php else: ?>
                            No hay publicaciones en <?= htmlspecialchars($nombreCategoria, ENT_QUOTES, 'UTF-8'); ?>. Prueba con otra categoría o elimina los filtros.
                        <?php endif; ?>
                    </p>
                    <a class="btn btn-primary clay-button" href="index.php?accion=catalogo">Limpiar filtros</a>
                </section>
            <?php else: ?>
                <section class="catalog-grid" aria-label="Publicaciones disponibles">
                    <?php foreach ($publicaciones as $publicacion): ?>
                        <?php $urlImagen = CatalogoController::obtenerUrlImagenProducto($publicacion['imagen_producto'] ?? null); ?>
                        <?php
                        $parametrosDetalle = [
                            'accion' => 'producto',
                            'id' => (int) $publicacion['id_producto'],
                        ];
                        if ($texto !== '') {
                            $parametrosDetalle['q'] = $texto;
                        }
                        if ($idCategoria !== null) {
                            $parametrosDetalle['categoria'] = $idCategoria;
                        }
                        $urlDetalle = 'index.php?' . http_build_query($parametrosDetalle);
                        ?>
                        <a class="catalog-product-link" href="<?= htmlspecialchars($urlDetalle, ENT_QUOTES, 'UTF-8'); ?>">
                        <article class="catalog-product clay-card">
                            <div class="catalog-image">
                                <?php if ($urlImagen !== null): ?>
                                    <img
                                        src="<?= htmlspecialchars($urlImagen, ENT_QUOTES, 'UTF-8'); ?>"
                                        alt="<?= htmlspecialchars($publicacion['nombre'], ENT_QUOTES, 'UTF-8'); ?>"
                                        loading="lazy"
                                        data-product-image>
                                    <span class="catalog-image-placeholder" aria-hidden="true" hidden><i class="bi bi-image"></i></span>
                                <?php else: ?>
                                    <span class="catalog-image-placeholder" aria-hidden="true"><i class="bi bi-image"></i></span>
                                <?php endif; ?>
                            </div>
                            <div class="catalog-product-content">
                                <h3 title="<?= htmlspecialchars($publicacion['nombre'], ENT_QUOTES, 'UTF-8'); ?>">
                                    <?= htmlspecialchars($publicacion['nombre'], ENT_QUOTES, 'UTF-8'); ?>
                                </h3>
                                <p class="catalog-price">
                                    $<?= number_format((float) $publicacion['precio'], 2, '.', ','); ?> MXN
                                </p>
                                <p class="catalog-product-detail"><?= htmlspecialchars($publicacion['categoria'], ENT_QUOTES, 'UTF-8'); ?></p>
                                <p class="catalog-product-detail">Vendedor: <?= htmlspecialchars($publicacion['nombre_vendedor'], ENT_QUOTES, 'UTF-8'); ?></p>
                            </div>
                        </article>
                        </a>
                    <?php endforeach; ?>
                </section>
            <?php endif; ?>
        <?php endif; ?>
    </main>

    <footer class="site-footer">
        <p class="footer-text">ITVOmarket es informativo: publicaciones y ubicación entre estudiantes. Sin compras ni pagos.</p>
    </footer>
    <script src="assets/js/catalogo.js" defer></script>
</body>
</html>
