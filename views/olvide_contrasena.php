<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ITVOmarket | Olvidé mi contraseña</title>
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
    <main class="auth-layout container min-vh-100 d-flex align-items-center justify-content-center py-5">
        <img class="auth-logo" src="assets/img/logo-itvomarket.png" alt="ITVOmarket">
        <section class="auth-panel clay-card row w-100" aria-labelledby="titulo-recuperacion">
            <div class="col-md-6 auth-main">
                <h1 id="titulo-recuperacion" class="fw-bold mb-2">Olvidé mi contraseña</h1>
                <p class="recovery-subtitle">Solicita un código temporal para recuperar el acceso a tu cuenta.</p>

                <h2 class="recovery-form-title">Recuperación de contraseña</h2>
                <form method="post" action="index.php?accion=olvide">
                    <div class="mb-3">
                        <label for="correo-recuperacion" class="form-label">Correo registrado</label>
                        <input
                            id="correo-recuperacion"
                            name="correo"
                            type="email"
                            class="form-control clay-input <?= !empty($errores['correo']) ? 'is-invalid' : ''; ?>"
                            value="<?= htmlspecialchars($correo ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                            placeholder="nombre@gmail.com"
                            autocomplete="email"
                            maxlength="254"
                            pattern="[A-Za-z0-9_%+-]+(\.[A-Za-z0-9_%+-]+)*@[Gg][Mm][Aa][Ii][Ll]\.[Cc][Oo][Mm]"
                            aria-invalid="<?= !empty($errores['correo']) ? 'true' : 'false'; ?>"
                            aria-describedby="ayuda-correo-recuperacion<?= !empty($errores['correo']) ? ' error-correo-recuperacion' : ''; ?>"
                            required>
                        <?php if (!empty($errores['correo'])): ?>
                            <div id="error-correo-recuperacion" class="invalid-feedback"><?= htmlspecialchars($errores['correo'], ENT_QUOTES, 'UTF-8'); ?></div>
                        <?php endif; ?>
                        <div id="ayuda-correo-recuperacion" class="form-text recovery-help">
                            Si el correo está asociado a una cuenta, recibirás un código para restablecer tu contraseña.
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary clay-button w-100">Solicitar código de recuperación</button>
                </form>

                <div class="recovery-back-link">
                    <a href="index.php?accion=login">Volver a inicio de sesión</a>
                </div>
            </div>
            <aside class="col-md-6 auth-side clay-panel-color d-flex flex-column justify-content-center">
                <h2 class="fw-bold">Recupera tu acceso</h2>
                <p class="mt-3">Usa el correo con el que te registraste. El código te permitirá elegir una nueva contraseña.</p>
                <p class="mb-0">Una misma cuenta para consultar como Comprador o publicar como Vendedor.</p>
            </aside>
        </section>
    </main>
    <footer class="site-footer">
        <p class="footer-text">ITVOmarket es informativo: publicaciones y ubicación entre estudiantes. Sin compras ni pagos.</p>
    </footer>
    <script src="assets/js/auth-entrada.js" defer></script>
</body>
</html>
