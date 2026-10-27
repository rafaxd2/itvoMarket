<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ITVOmarket | Código no válido</title>
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
        <section class="auth-panel clay-card row w-100" aria-labelledby="titulo-enlace-invalido">
            <div class="col-md-6 auth-main">
                <h1 id="titulo-enlace-invalido" class="fw-bold mb-2">Código incorrecto o vencido</h1>
                <p class="recovery-subtitle">Solicita un código nuevo para continuar con la recuperación de tu cuenta.</p>
                <?php if (!empty($errorValidacion)): ?>
                    <div class="alert alert-danger" role="alert"><?= htmlspecialchars($errorValidacion, ENT_QUOTES, 'UTF-8'); ?></div>
                <?php endif; ?>
                <div class="recovery-invalid">
                    <strong>Código incorrecto o vencido</strong>
                    <p class="mb-0">Por seguridad, el código dejó de ser válido. Solicita uno nuevo para continuar.</p>
                </div>
                <a class="btn btn-primary clay-button w-100 mt-3" href="index.php?accion=olvide">Solicitar un código nuevo</a>
                <div class="recovery-back-link">
                    <a href="index.php?accion=login">Volver a inicio de sesión</a>
                </div>
            </div>
            <aside class="col-md-6 auth-side clay-panel-color d-flex flex-column justify-content-center">
                <h2 class="fw-bold">Recupera tu acceso</h2>
                <p class="mt-3">Los códigos de recuperación son temporales y de un solo uso. Si no se puede validar, comienza una nueva solicitud.</p>
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
