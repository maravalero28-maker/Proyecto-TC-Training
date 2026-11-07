<?php
if (!isset($nombreMetodoValor)) {
    header("Location: ../controlador/FondoController.php?action=nuevo_metodo");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Registrar Método de Pago | TC-Control</title>
    <link rel="stylesheet" href="../estilos.css">
    <link rel="stylesheet" href="../responsive.css">
    <script src="../jv/login.js" defer></script>
    <script src="../jv/script.js" defer></script>
</head>
<body>
    <?php include __DIR__ . '/partes/toast-notification.php'; ?>
    <main class="login-wrapper login-wrapper-scroll">
        <canvas id="particles-canvas"></canvas>
        <section class="login-card login-card-wide">
            <header class="login-header">
                <h1 class="font-lexend">Nuevo método de pago</h1>
                <p>Registrar un método de pago disponible en el sistema</p>
            </header>
            <?php if (!empty($mensaje)): ?>
                <section class="alert-container"><?php echo $mensaje; ?></section>
            <?php endif; ?>
            <form action="../controlador/FondoController.php" method="POST" novalidate>
                <fieldset class="form-group">
                    <label for="nombre" class="form-label">Nombre del método</label>
                    <input type="text" id="nombre" name="nombre" class="form-control" maxlength="255" placeholder="Ej. PayPal" value="<?php echo $nombreMetodoValor ?? ''; ?>" required>
                </fieldset>

                <button type="submit" name="btncrearmetodo" class="btn btn-primary login-btn">Registrar método</button>
            </form>
            <footer class="login-footer">
                <a href="../controlador/FondoController.php" class="link-back">
                    <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M19 12H5M12 19l-7-7 7-7"></path></svg>
                    Volver a Fondos
                </a>
            </footer>
        </section>
    </main>
</body>
</html>