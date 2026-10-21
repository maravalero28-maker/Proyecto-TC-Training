<?php
if (!isset($nomina)) {
    header("Location: ../controlador/NominaController.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Editar Pago de Nómina | TC-Control</title>
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
                <h1 class="font-lexend">Editar Pago de Nómina</h1>
                <p>Modifica los datos del pago.</p>
            </header>
            <form action="../controlador/NominaController.php" method="POST" novalidate>
                <input type="hidden" name="id_nomina" value="<?php echo htmlspecialchars($nomina['id_nomina']); ?>">

                <fieldset class="form-group">
                    <label for="id_entrenador" class="form-label">Entrenador</label>
                    <select id="id_entrenador" name="id_entrenador" class="form-control" required>
                        <?php foreach ($entrenadores as $e): ?>
                            <option value="<?php echo $e['cedula']; ?>" <?php echo ($e['cedula'] == $nomina['id_entrenador']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($e['nombre'] . ' ' . $e['apellido'] . ' (Cédula: ' . $e['cedula'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </fieldset>

                <fieldset class="form-group">
                    <label for="concepto" class="form-label">Concepto</label>
                    <input type="text" id="concepto" name="concepto" class="form-control" value="<?php echo htmlspecialchars($nomina['concepto']); ?>" required>
                </fieldset>

                <fieldset class="form-group">
                    <label for="monto" class="form-label">Monto</label>
                    <input type="number" step="0.01" min="0.01" id="monto" name="monto" class="form-control" value="<?php echo htmlspecialchars($nomina['monto']); ?>" required>
                </fieldset>

                <fieldset class="form-group">
                    <label for="fecha_pago" class="form-label">Fecha de Pago</label>
                    <input type="date" id="fecha_pago" name="fecha_pago" class="form-control" value="<?php echo htmlspecialchars($nomina['fecha_pago']); ?>" required>
                </fieldset>

                <fieldset class="form-group">
                    <label for="id_metodo" class="form-label">Método de pago</label>
                    <select id="id_metodo" name="id_metodo" class="form-control" required>
                        <option value="">Seleccione método</option>
                        <?php foreach($metodosPagoList as $metodo): ?>
                            <option value="<?php echo $metodo['id_metodo']; ?>" <?php echo (isset($idMetodoValor) && $idMetodoValor == $metodo['id_metodo']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($metodo['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </fieldset>

                <fieldset class="form-group">
                    <label for="id_cuenta" class="form-label">Cuenta / Banco Receptora</label>
                    <select id="id_cuenta" name="id_cuenta" class="form-control">
                        <option value="">Seleccione cuenta (Opcional)</option>
                        <?php foreach($cuentasList as $cuenta): ?>
                            <option value="<?php echo $cuenta['id_cuenta']; ?>" <?php echo (isset($idCuentaValor) && $idCuentaValor == $cuenta['id_cuenta']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cuenta['banco_nombre'] . ' - ' . $cuenta['num_cuenta'] . ' (' . $cuenta['tipo_moneda'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </fieldset>

                <button type="submit" name="btnactualizarnomina" class="btn btn-primary login-btn">Actualizar Pago Nómina</button>
            </form>
            <footer class="login-footer">
                <a href="../controlador/NominaController.php" class="link-back">
                    <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M19 12H5M12 19l-7-7 7-7"></path></svg>
                    Volver a Nómina
                </a>
            </footer>
        </section>
    </main>
</body>
</html>