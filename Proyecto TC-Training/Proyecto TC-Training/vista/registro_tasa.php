<?php
if (!isset($tasasRecientes)) {
    header("Location: ../controlador/FondoController.php?action=nueva_tasa");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Tasa del Día | TC-Control</title>
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
                <h1 class="font-lexend">Tasa del día</h1>
                <p>Bolívares por cada dólar (Bs por $)</p>
            </header>
            <?php if (!empty($mensaje)): ?>
                <section class="alert-container"><?php echo $mensaje; ?></section>
            <?php endif; ?>
            <form action="../controlador/FondoController.php" method="POST" novalidate>
                <fieldset class="form-group">
                    <label for="fecha" class="form-label">Fecha</label>
                    <input type="date" id="fecha" name="fecha" class="form-control" value="<?php echo $tasaFechaValor ?? date('Y-m-d'); ?>" required>
                </fieldset>

                <fieldset class="form-group">
                    <label for="tasa" class="form-label">Tasa (Bs por $)</label>
                    <input type="number" step="0.0001" min="0.0001" id="tasa" name="tasa" class="form-control" placeholder="Ej. 40.25" value="<?php echo $tasaValor ?? ''; ?>" required>
                    <small class="text-muted">Si ya hay una tasa para esa fecha, se reemplaza. Los pagos ya registrados conservan la tasa con la que se guardaron.</small>
                </fieldset>

                <button type="submit" name="btnguardartasa" class="btn btn-primary login-btn">Guardar tasa</button>
            </form>

            <?php if (!empty($tasasRecientes)): ?>
                <section class="table-responsive">
                    <table class="payments-table">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Bs por $</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tasasRecientes as $t): ?>
                                <tr>
                                    <td><?php echo date('d/m/Y', strtotime($t['fecha'])); ?></td>
                                    <td><?php echo number_format($t['tasa'], 2, ',', '.'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </section>
            <?php endif; ?>

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