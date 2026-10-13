<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ITVOmarket | Crear cuenta</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/itvomarket.css">
    <link rel="stylesheet" href="assets/css/clay.css">
    <link rel="stylesheet" href="assets/css/auth-flotantes.css">
</head>
<body class="auth-page">
    <div class="auth-floating-decor" aria-hidden="true">
        <span class="auth-floating-anchor auth-floating-bubble--green auth-floating-bubble--one"><span class="auth-floating-bubble"><i class="bi bi-cup-straw"></i></span></span>
        <span class="auth-floating-anchor auth-floating-bubble--orange auth-floating-bubble--two"><span class="auth-floating-bubble"><i class="bi bi-basket2-fill"></i></span></span>
        <span class="auth-floating-anchor auth-floating-bubble--green auth-floating-bubble--three"><span class="auth-floating-bubble"><i class="bi bi-gift-fill"></i></span></span>
        <span class="auth-floating-anchor auth-floating-bubble--orange auth-floating-bubble--four"><span class="auth-floating-bubble"><i class="bi bi-bag-heart-fill"></i></span></span>
        <span class="auth-floating-anchor auth-floating-bubble--green auth-floating-bubble--five"><span class="auth-floating-bubble"><i class="bi bi-flower1"></i></span></span>
        <span class="auth-floating-anchor auth-floating-bubble--orange auth-floating-bubble--six"><span class="auth-floating-bubble"><i class="bi bi-phone-fill"></i></span></span>
    </div>
    <div class="auth-layout container min-vh-100 d-flex align-items-center justify-content-center py-5">
        <img class="auth-logo" src="assets/img/logo-itvomarket.png" alt="ITVOmarket">
        <div class="auth-panel clay-card row w-100">
            <div class="col-md-6 auth-main">
                <h2 class="fw-bold mb-4">Crea tu cuenta de estudiante</h2>

                <?php if (!empty($errores['general'])): ?>
                    <div class="alert alert-danger" role="alert"><?= htmlspecialchars($errores['general'], ENT_QUOTES, 'UTF-8'); ?></div>
                <?php endif; ?>

                <form method="post" action="index.php?accion=procesarRegistro" novalidate>
                    <div class="mb-3">
                        <label for="correo" class="form-label">Correo electrónico</label>
                        <input
                            id="correo"
                            name="correo"
                            type="email"
                            class="form-control clay-input <?= !empty($errores['correo']) ? 'is-invalid' : '' ?> <?= !empty($errores['general']) ? 'is-invalid' : '' ?>"
                            value="<?= htmlspecialchars($correo, ENT_QUOTES, 'UTF-8'); ?>"
                            aria-invalid="<?= !empty($errores['correo']) || !empty($errores['general']) ? 'true' : 'false'; ?>"
                            required>
                        <?php if (!empty($errores['correo'])): ?>
                            <div class="invalid-feedback d-block"><?= htmlspecialchars($errores['correo'], ENT_QUOTES, 'UTF-8'); ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label for="contrasena" class="form-label">Contraseña</label>
                        <input
                            id="contrasena"
                            name="contrasena"
                            type="password"
                            class="form-control clay-input <?= !empty($errores['general']) ? 'is-invalid' : '' ?>"
                            aria-invalid="<?= !empty($errores['general']) ? 'true' : 'false'; ?>"
                            required>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">¿Cómo quieres empezar?</label>
                        <div class="role-options d-flex gap-3 flex-wrap">
                            <label class="role-option" for="rolComprador">
                                <input class="form-check-input <?= !empty($errores['general']) ? 'is-invalid' : '' ?>" type="radio" name="rol" id="rolComprador" value="comprador" <?= $rol === 'comprador' ? 'checked' : ''; ?>>
                                <span>Comprador</span>
                            </label>
                            <label class="role-option" for="rolVendedor">
                                <input class="form-check-input <?= !empty($errores['general']) ? 'is-invalid' : '' ?>" type="radio" name="rol" id="rolVendedor" value="vendedor" <?= $rol === 'vendedor' ? 'checked' : ''; ?>>
                                <span>Vendedor</span>
                            </label>
                        </div>
                        <small class="text-muted">Puedes cambiar de rol después desde tu cuenta</small>
                    </div>

                    <button type="submit" class="btn btn-primary clay-button w-100">Crear cuenta</button>
                </form>

                <div class="mt-4 text-center">
                    <span>¿Ya tienes cuenta?</span>
                    <a href="index.php?accion=login" class="ms-1 text-decoration-none fw-semibold" style="color: var(--itvo-primary);">Iniciar sesión</a>
                </div>
            </div>

            <div class="col-md-6 auth-side clay-panel-color d-flex flex-column justify-content-center">
                <h3 class="fw-bold">Un espacio para tu comunidad</h3>
                <p class="mt-3 mb-0">Únete a vender y comprar productos dentro del campus con un solo registro.</p>
            </div>
        </div>
    </div>

    <footer class="site-footer">
        <p class="footer-text">ITVOmarket es informativo: publicaciones y ubicación entre estudiantes. Sin compras ni pagos.</p>
    </footer>
    <script src="assets/js/auth-entrada.js" defer></script>
</body>
</html>
