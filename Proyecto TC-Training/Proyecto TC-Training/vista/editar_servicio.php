<?php
if (!isset($servicio)) {
    header("Location: ../controlador/PagoServicioController.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Editar Pago de Servicio | TC-Control</title>
    <link rel="stylesheet" href="../estilos.css">
    <link rel="stylesheet" href="../responsive.css">
    <script src="../jv/login.js" defer></script>
    <script src="../jv/script.js" defer></script>
</head>
<body>
    <main class="login-wrapper login-wrapper-scroll">
        <canvas id="particles-canvas"></canvas>
        <section class="login-card login-card-wide">
            <header class="login-header">
                <h1 class="font-lexend">Editar Pago de Servicio</h1>
                <p>Modifica los datos del gasto.</p>
            </header>
            <form action="../controlador/PagoServicioController.php" method="POST" novalidate>
                <input type="hidden" name="id_pago_servicio" value="<?php echo htmlspecialchars($servicio['id_pago_servicio']); ?>">

                <fieldset class="form-group">
                    <label for="concepto" class="form-label">Concepto / Descripción</label>
                    <input type="text" id="concepto" name="concepto" class="form-control" value="<?php echo htmlspecialchars($servicio['concepto']); ?>" required>
                </fieldset>

                <fieldset class="form-group">
                    <label for="monto" class="form-label">Monto</label>
                    <input type="number" step="0.01" min="0.01" id="monto" name="monto" class="form-control" value="<?php echo htmlspecialchars($servicio['monto']); ?>" required>
                </fieldset>

                <fieldset class="form-group">
                    <label for="fecha" class="form-label">Fecha</label>
                    <input type="date" id="fecha" name="fecha" class="form-control" value="<?php echo htmlspecialchars($servicio['fecha']); ?>" required>
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

                <button type="submit" name="btnactualizarservicio" class="btn btn-primary login-btn">Actualizar Servicio</button>
            </form>

            <section class="form-group" style="margin-top: 1.5rem;">
                <label class="form-label">Estado del servicio</label>
                <p>
                    <span class="badge <?php echo !empty($servicio['anulado']) ? 'badge-inactive' : 'badge-active'; ?>">
                        <?php echo !empty($servicio['anulado']) ? 'Inhabilitado' : 'Habilitado'; ?>
                    </span>
                </p>
                <form action="../controlador/PagoServicioController.php" method="POST"
                      onsubmit="return confirm('<?php echo !empty($servicio['anulado']) ? '¿Habilitar este servicio? Volverá a contar en fondos y reportes.' : '¿Inhabilitar este servicio? Dejará de contar en fondos y reportes.'; ?>');">
                    <input type="hidden" name="id_pago_servicio" value="<?php echo htmlspecialchars($servicio['id_pago_servicio']); ?>">
                    <input type="hidden" name="nuevo_estado" value="<?php echo !empty($servicio['anulado']) ? '0' : '1'; ?>">
                    <button type="submit" name="btncambiarestadoservicio" class="btn <?php echo !empty($servicio['anulado']) ? 'btn-primary' : 'btn-outline'; ?>">
                        <?php echo !empty($servicio['anulado']) ? 'Habilitar servicio' : 'Inhabilitar servicio'; ?>
                    </button>
                </form>
            </section>

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