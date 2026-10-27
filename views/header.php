<?php
$rolActual = strtolower((string) ($_SESSION['usuario_rol'] ?? 'comprador'));
$accionActual = strtolower((string) ($_GET['accion'] ?? 'panel'));
$esVendedor = $rolActual === 'vendedor';
?>
<nav class="navbar navbar-expand-lg site-header">
    <div class="container-fluid px-4 site-header-content <?= ($_GET['rol_cambiado'] ?? '') === '1' ? 'role-change-in' : ''; ?>">
        <a class="navbar-brand" href="panel.php">
            <img class="site-logo" src="assets/img/logo-itvomarket.png" alt="">
            <span>ITVOmarket</span>
        </a>

        <?php if ($esVendedor): ?>
            <div class="d-flex align-items-center gap-2 nav-pills">
                <a class="nav-link <?= in_array($accionActual, ['publicar', 'procesarpublicacion'], true) ? 'active' : ''; ?>" href="index.php?accion=publicar">Publicar producto</a>
                <a class="nav-link <?= in_array($accionActual, ['ubicacion', 'guardarubicacion'], true) ? 'active' : ''; ?>" href="index.php?accion=ubicacion">Mi ubicación</a>
            </div>
        <?php else: ?>
            <div class="d-flex align-items-center gap-2 nav-pills">
                <a class="nav-link <?= $accionActual === 'catalogo' ? 'active' : ''; ?>" href="index.php?accion=catalogo">Catálogo</a>
            </div>
        <?php endif; ?>

        <details class="profile-menu ms-auto">
            <summary class="profile-menu-toggle" aria-label="Abrir menú de perfil" title="Perfil">
                <svg class="profile-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                    <path d="M12 12a4.25 4.25 0 1 0 0-8.5A4.25 4.25 0 0 0 12 12Zm0 2c-4.05 0-7.25 2.08-7.25 4.65V21h14.5v-2.35C19.25 16.08 16.05 14 12 14Z"/>
                </svg>
            </summary>
            <div class="profile-menu-panel">
                <div class="profile-menu-content">
                    <div class="profile-menu-heading">
                        <span>Sesión actual</span>
                        <strong><?= htmlspecialchars(ucfirst($rolActual), ENT_QUOTES, 'UTF-8'); ?></strong>
                    </div>
                    <div class="profile-menu-section">Cambiar de rol</div>
                    <?php foreach (['comprador' => 'Comprador', 'vendedor' => 'Vendedor'] as $rolOpcion => $nombreRol): ?>
                        <form method="post" action="index.php?accion=cambiarRol" data-role-change-form>
                            <input type="hidden" name="rol" value="<?= htmlspecialchars($rolOpcion, ENT_QUOTES, 'UTF-8'); ?>">
                            <button class="profile-menu-item" type="submit" <?= $rolActual === $rolOpcion ? 'disabled aria-current="true"' : ''; ?>>
                                <?= htmlspecialchars($nombreRol, ENT_QUOTES, 'UTF-8'); ?>
                                <?php if ($rolActual === $rolOpcion): ?><span class="profile-menu-current">Actual</span><?php endif; ?>
                            </button>
                        </form>
                    <?php endforeach; ?>
                    <div class="profile-menu-divider"></div>
                    <a class="profile-menu-item profile-menu-logout" href="index.php?accion=logout">Cerrar sesión</a>
                </div>
            </div>
        </details>
    </div>
</nav>
<script src="assets/js/interacciones-ui.js" defer></script>
