<?php
if (!isset($pago) && !isset($id_pagoValor)) {
    header("Location: ../controlador/pagoscontrolador.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Pago | TC-Control</title>
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
                <h1 class="font-lexend">Editar Pago</h1>
                <p>Modifica los datos del pago.</p>
            </header>

            <form action="../controlador/pagoscontrolador.php" method="POST" novalidate>
                <input type="hidden" name="id_pago" value="<?php echo htmlspecialchars($id_pagoValor); ?>">

                <fieldset class="form-group">
                    <label for="id_cliente" class="form-label">Cliente</label>
                    <select id="id_cliente" name="id_cliente" class="form-control" required>
                        <option value="">Seleccione un cliente</option>
                        <?php foreach ($clientes as $cliente): ?>
                            <option value="<?php echo $cliente['id_cliente']; ?>" 
                                <?php echo ($cliente['id_cliente'] == $id_clienteValor) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cliente['nombre_completo']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="error-msg">Selecciona un cliente.</p>
                </fieldset>

                <fieldset class="form-group">
                    <label for="plan_id" class="form-label">Plan</label>
                    <select id="plan_id" name="plan_id" class="form-control">
                        <option value="">Mantener plan actual</option>
                        <?php foreach ($planes as $plan): ?>
                            <option value="<?php echo $plan['id_plan']; ?>" <?php echo (isset($plan_idValor) && $plan_idValor == $plan['id_plan']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($plan['nombre'] . ' - $' . number_format($plan['precio'], 2, ',', '.') . ' (' . intval($plan['dias']) . ' días)'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="error-msg">Selecciona un plan si deseas cambiarlo.</p>
                </fieldset>

                <fieldset class="form-group">
                    <label for="monto" class="form-label">Monto</label>
                    <input type="number" step="0.01" min="0.01" id="monto" name="monto" class="form-control" 
                          value="<?php echo htmlspecialchars($montoValor); ?>" required>
                    <p class="error-msg">El monto es obligatorio.</p>
                </fieldset>

                <fieldset class="form-group">
                    <label for="fecha" class="form-label">Fecha de Pago</label>
                    <input type="date" id="fecha" name="fecha" class="form-control" 
                           value="<?php echo htmlspecialchars($fechaValor); ?>" required>
                    <p class="error-msg">Selecciona una fecha.</p>
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

                <button type="submit" name="btnactualizarpago" class="btn btn-primary login-btn">Actualizar Pago</button>
            </form>

            <footer class="login-footer">
                <a href="../controlador/pagoscontrolador.php" class="link-back">
                    <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M19 12H5M12 19l-7-7 7-7"></path></svg>
                    Volver a Pagos
                </a>
            </footer>
        </section>
    </main>
</body>
</html>