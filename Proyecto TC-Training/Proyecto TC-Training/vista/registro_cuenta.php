<?php
if (!isset($bancosList)) {
    header("Location: ../controlador/FondoController.php?action=nueva_cuenta");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Registrar Cuenta | TC-Control</title>
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
                <h1 class="font-lexend">Nueva cuenta</h1>
                <p>Registrar una cuenta bancaria o fondo</p>
            </header>
            <?php if (!empty($mensaje)): ?>
                <section class="alert-container"><?php echo $mensaje; ?></section>
            <?php endif; ?>
            <form action="../controlador/FondoController.php" method="POST" novalidate>
                <fieldset class="form-group">
                    <label for="id_banco" class="form-label">Banco</label>
                    <select id="id_banco" name="id_banco" class="form-control">
                        <option value="">Seleccione banco</option>
                        <?php foreach ($bancosList as $b): ?>
                            <option value="<?php echo $b['id_banco']; ?>" <?php echo (isset($idBancoValor) && $idBancoValor == $b['id_banco']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($b['nombre']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </fieldset>

                <fieldset class="form-group">
                    <label for="banco_nuevo" class="form-label">¿No está en la lista? Escribe el banco nuevo</label>
                    <input type="text" id="banco_nuevo" name="banco_nuevo" class="form-control" maxlength="255" placeholder="Ej. Banesco (si lo escribes, se usa en lugar del seleccionado)" value="<?php echo $bancoNuevoValor ?? ''; ?>">
                </fieldset>

                <fieldset class="form-group">
                    <label for="num_cuenta" class="form-label">Número de cuenta</label>
                    <input type="text" id="num_cuenta" name="num_cuenta" class="form-control" maxlength="100" value="<?php echo $numCuentaValor ?? ''; ?>" required>
                </fieldset>

                <fieldset class="form-group">
                    <label for="tipo_moneda" class="form-label">Moneda</label>
                    <select id="tipo_moneda" name="tipo_moneda" class="form-control" required>
                        <option value="">Seleccione moneda</option>
                        <option value="BS" <?php echo (isset($tipoMonedaValor) && $tipoMonedaValor === 'BS') ? 'selected' : ''; ?>>BS</option>
                        <option value="USD" <?php echo (isset($tipoMonedaValor) && $tipoMonedaValor === 'USD') ? 'selected' : ''; ?>>USD</option>
                    </select>
                </fieldset>

                <button type="submit" name="btncrearcuenta" class="btn btn-primary login-btn">Registrar cuenta</button>
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