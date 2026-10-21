<?php
if (!isset($mensaje) && !isset($montoValor)) {
    header("Location: ../controlador/PagoServicioController.php?action=nuevo");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Registrar Pago de Servicio | TC-Control</title>
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
                <h1 class="font-lexend">Pago de Servicios</h1>
                <p>Registrar gasto operativo o servicio</p>
            </header>
            <?php if (!empty($mensaje)): ?>
                <section class="alert-container"><?php echo $mensaje; ?></section>
            <?php endif; ?>
            <form action="../controlador/PagoServicioController.php" method="POST" novalidate>
                <fieldset class="form-group">
                    <label for="concepto" class="form-label">Concepto / Descripción</label>
                    <input type="text" id="concepto" name="concepto" class="form-control" placeholder="Ej. Electricidad, Internet, Mantenimiento, contador" value="<?php echo htmlspecialchars($conceptoValor ?? ''); ?>" required>
                </fieldset>

                <fieldset class="form-group">
                    <label for="monto" class="form-label">Monto</label>
                    <section class="input-inline">
                        <span class="currency-symbol">$</span>
                        <input type="number" step="0.01" min="0.01" id="monto" name="monto" class="form-control flex-1" value="<?php echo htmlspecialchars($montoValor ?? ''); ?>" required>
                    </section>
                </fieldset>

                <fieldset class="form-group">
                    <label for="id_metodo" class="form-label">Método de pago</label>
                    <select id="id_metodo" name="id_metodo" class="form-control">
                        <option value="">Seleccione método</option>
                        <?php foreach($metodosPagoList as $m): ?>
                            <option value="<?php echo $m['id_metodo']; ?>" <?php echo (isset($idMetodoValor) && $idMetodoValor == $m['id_metodo']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($m['nombre']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </fieldset>

                <fieldset class="form-group">
                    <label for="id_cuenta" class="form-label">Cuenta (Opcional)</label>
                    <select id="id_cuenta" name="id_cuenta" class="form-control">
                        <option value="">Ninguna / Efectivo</option>
                        <?php foreach($cuentasList as $c): ?>
                            <option value="<?php echo $c['id_cuenta']; ?>" <?php echo (isset($idCuentaValor) && $idCuentaValor == $c['id_cuenta']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($c['banco_nombre'] . ' - ' . $c['num_cuenta'] . ' (' . $c['tipo_moneda'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </fieldset>

                <fieldset class="form-group">
                    <label for="fecha" class="form-label">Fecha</label>
                    <input type="date" id="fecha" name="fecha" class="form-control" value="<?php echo htmlspecialchars($fechaValor ?? date('Y-m-d')); ?>" required>
                </fieldset>

                <button type="submit" name="btnservicio" class="btn btn-primary login-btn">Registrar Servicio</button>
            </form>
            <footer class="login-footer">
                <a href="../controlador/PagoServicioController.php" class="link-back">
                    <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M19 12H5M12 19l-7-7 7-7"></path></svg>
                    Volver a Servicios
                </a>
            </footer>
        </section>
    </main>
</body>
</html>