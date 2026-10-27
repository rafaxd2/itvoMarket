<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ITVOmarket | Iniciar sesión</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/itvomarket.css">
    <link rel="stylesheet" href="assets/css/clay.css">
    <link rel="stylesheet" href="assets/css/auth-flotantes.css">
    <link rel="stylesheet" href="assets/css/recuperacion-contrasena.css">
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
                <h2 class="fw-bold mb-4">Acceso de estudiantes</h2>

                <?php if (!empty($errores['general'])): ?>
                    <div class="alert alert-danger" role="alert"><?= htmlspecialchars($errores['general'], ENT_QUOTES, 'UTF-8'); ?></div>
                <?php endif; ?>

                <?php if (isset($_GET['mensaje']) && $_GET['mensaje'] === 'registro_exitoso'): ?>
                    <div class="alert alert-success" role="alert">Cuenta creada correctamente. Ahora puedes iniciar sesión.</div>
                <?php endif; ?>

                <form method="post" action="index.php?accion=procesarLogin" novalidate>
                    <div class="mb-3">
                        <label for="correo" class="form-label">Correo electrónico</label>
                        <input
                            id="correo"
                            name="correo"
                            type="email"
                            class="form-control clay-input <?= !empty($errores['general']) ? 'is-invalid' : '' ?>"
                            value="<?= htmlspecialchars($correo, ENT_QUOTES, 'UTF-8'); ?>"
                            aria-invalid="<?= !empty($errores['general']) ? 'true' : 'false'; ?>"
                            required>
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

                    <button type="submit" class="btn btn-primary clay-button w-100">Iniciar sesión</button>
                </form>

                <div class="login-forgot-link">
                    <a href="index.php?accion=olvide">¿Olvidaste tu contraseña?</a>
                </div>
                <div class="mt-4 text-center">
                    <span>¿Aún no tienes cuenta?</span>
                    <a href="index.php?accion=registro" class="ms-1 text-decoration-none fw-semibold" style="color: var(--itvo-primary);">Crear una cuenta</a>
                </div>
            </div>

            <div class="col-md-6 auth-side clay-panel-color d-flex flex-column justify-content-center">
                <h3 class="fw-bold">Tu cuenta, dos roles</h3>
                <p class="mt-3 mb-0">Compra productos de estudiantes o publica tus propios productos según tu rol.</p>
            </div>
        </div>
    </div>

    <footer class="site-footer">
        <p class="footer-text">ITVOmarket es informativo: publicaciones y ubicación entre estudiantes. Sin compras ni pagos.</p>
    </footer>
    <script src="assets/js/auth-entrada.js" defer></script>
</body>
</html>
