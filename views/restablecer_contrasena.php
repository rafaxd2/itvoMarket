<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ITVOmarket | Revisa tu correo</title>
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
        <section class="auth-panel clay-card row w-100" aria-labelledby="titulo-codigo">
            <div class="col-md-6 auth-main">
                <h1 id="titulo-codigo" class="fw-bold mb-2">Revisa tu correo</h1>
                <p class="recovery-subtitle">Solicitud registrada. El siguiente paso es ingresar tu código de recuperación.</p>

                <?php if (!empty($errorCodigo)): ?>
                    <div class="alert alert-danger" role="alert"><?= htmlspecialchars($errorCodigo, ENT_QUOTES, 'UTF-8'); ?></div>
                <?php endif; ?>
                <?php if (!empty($mensajeReenvio)): ?>
                    <div class="alert alert-info" role="status"><?= htmlspecialchars($mensajeReenvio, ENT_QUOTES, 'UTF-8'); ?></div>
                <?php endif; ?>

                <div class="recovery-email-card mb-3">
                    <strong>Correo indicado</strong>
                    <span><?= htmlspecialchars($correo, ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
                <p class="recovery-help mb-3">Enviamos un código de 6 dígitos a este correo. Ingrésalo aquí para crear una nueva contraseña.</p>

                <form method="post" action="index.php?accion=verificar_codigo" id="formulario-codigo" novalidate>
                    <label class="form-label d-block">Código de recuperación</label>
                    <div class="recovery-code-inputs" data-code-inputs aria-label="Código de recuperación">
                        <?php for ($indice = 0; $indice < 6; $indice++): ?>
                            <input
                                type="text"
                                class="form-control clay-input recovery-code-digit <?= !empty($errorCodigo) ? 'is-invalid' : ''; ?>"
                                data-code-digit
                                inputmode="numeric"
                                maxlength="1"
                                <?= $indice === 0 ? 'autocomplete="one-time-code"' : 'autocomplete="off"'; ?>
                                aria-label="Dígito <?= $indice + 1; ?>"
                                <?= !empty($errorCodigo) ? 'aria-invalid="true"' : ''; ?>
                                required>
                        <?php endfor; ?>
                    </div>
                    <input type="hidden" name="codigo" id="codigo-completo" disabled>
                    <noscript>
                        <input
                            type="text"
                            name="codigo"
                            class="form-control clay-input"
                            inputmode="numeric"
                            autocomplete="one-time-code"
                            pattern="[0-9]{6}"
                            maxlength="6"
                            aria-label="Código de recuperación de seis dígitos"
                            required>
                    </noscript>
                    <div class="form-text recovery-help mt-2">Ingresa los 6 dígitos que recibiste en tu correo.</div>
                    <div class="form-text recovery-help mb-3">¿No lo encuentras? Revisa la carpeta de correo no deseado antes de solicitar otro código.</div>
                    <button type="submit" class="btn btn-primary clay-button w-100">Verificar código</button>
                </form>

                <div class="recovery-actions">
                    <a href="index.php?accion=reenviar_codigo">Reenviar código</a>
                    <a href="index.php?accion=corregir_correo">Corregir correo</a>
                    <a href="index.php?accion=login">Volver a inicio de sesión</a>
                </div>
            </div>
            <aside class="col-md-6 auth-side clay-panel-color d-flex flex-column justify-content-center">
                <h2 class="fw-bold">Recupera tu acceso</h2>
                <p class="mt-3">Ingresa el código que enviamos a tu correo para comprobar que la cuenta es tuya. Después podrás elegir una nueva contraseña.</p>
                <p class="mb-0">Una misma cuenta para consultar como Comprador o publicar como Vendedor.</p>
            </aside>
        </section>
    </main>
    <footer class="site-footer">
        <p class="footer-text">ITVOmarket es informativo: publicaciones y ubicación entre estudiantes. Sin compras ni pagos.</p>
    </footer>
    <script src="assets/js/recuperacion-codigo.js" defer></script>
    <script src="assets/js/auth-entrada.js" defer></script>
</body>
</html>
