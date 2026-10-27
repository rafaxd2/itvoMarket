<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ITVOmarket | Crea tu nueva contraseña</title>
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
        <section class="auth-panel clay-card row w-100" aria-labelledby="titulo-nueva-contrasena">
            <div class="col-md-6 auth-main">
                <h1 id="titulo-nueva-contrasena" class="fw-bold mb-2">Crea tu nueva contraseña</h1>
                <p class="recovery-subtitle">El código fue verificado. Ahora elige una contraseña segura para tu cuenta.</p>

                <?php if (!empty($errores['general'])): ?>
                    <div class="alert alert-danger" role="alert"><?= htmlspecialchars($errores['general'], ENT_QUOTES, 'UTF-8'); ?></div>
                <?php endif; ?>

                <form method="post" action="index.php?accion=guardar_contrasena" novalidate>
                    <div class="mb-3">
                        <label for="nueva-contrasena" class="form-label">Nueva contraseña</label>
                        <input
                            id="nueva-contrasena"
                            name="contrasena"
                            type="password"
                            class="form-control clay-input <?= !empty($errores['contrasena']) ? 'is-invalid' : ''; ?>"
                            placeholder="Escribe una nueva contraseña"
                            autocomplete="new-password"
                            aria-invalid="<?= !empty($errores['contrasena']) ? 'true' : 'false'; ?>"
                            aria-describedby="<?= !empty($errores['contrasena']) ? 'error-nueva-contrasena' : 'requisitos-contrasena'; ?>"
                            required>
                        <?php if (!empty($errores['contrasena'])): ?>
                            <div id="error-nueva-contrasena" class="invalid-feedback"><?= htmlspecialchars($errores['contrasena'], ENT_QUOTES, 'UTF-8'); ?></div>
                        <?php endif; ?>
                    </div>
                    <div id="requisitos-contrasena" class="recovery-info">
                        <strong>Requisitos:</strong> Al menos 8 caracteres, con letras y números.
                    </div>
                    <div class="mb-3">
                        <label for="confirmar-contrasena" class="form-label">Confirmar contraseña</label>
                        <input
                            id="confirmar-contrasena"
                            name="confirmar_contrasena"
                            type="password"
                            class="form-control clay-input <?= !empty($errores['confirmar_contrasena']) ? 'is-invalid' : ''; ?>"
                            placeholder="Repite la nueva contraseña"
                            autocomplete="new-password"
                            aria-invalid="<?= !empty($errores['confirmar_contrasena']) ? 'true' : 'false'; ?>"
                            <?= !empty($errores['confirmar_contrasena']) ? 'aria-describedby="error-confirmar-contrasena"' : ''; ?>
                            required>
                        <?php if (!empty($errores['confirmar_contrasena'])): ?>
                            <div id="error-confirmar-contrasena" class="invalid-feedback"><?= htmlspecialchars($errores['confirmar_contrasena'], ENT_QUOTES, 'UTF-8'); ?></div>
                        <?php endif; ?>
                    </div>
                    <button type="submit" class="btn btn-primary clay-button w-100">Guardar nueva contraseña</button>
                </form>

                <div class="recovery-back-link">
                    <a href="index.php?accion=restablecer">Volver a ingresar el código</a><br>
                    <a href="index.php?accion=login">Volver a inicio de sesión</a>
                </div>
            </div>
            <aside class="col-md-6 auth-side clay-panel-color d-flex flex-column justify-content-center">
                <h2 class="fw-bold">Recupera tu acceso</h2>
                <p class="mt-3">Ya comprobamos que tienes acceso al correo de la cuenta. Crea una contraseña nueva para volver a entrar.</p>
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
