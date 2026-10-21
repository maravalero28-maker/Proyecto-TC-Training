<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Registrar Pago | TC-Control</title>
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
                <h1 class="font-lexend">Registro de pagos</h1>
                <p>Registrar nuevos pagos</p>
            </header>
            <?php if (!empty($mensaje)): ?>
                <section class="alert-container">
                    <?php echo $mensaje; ?>
                </section>
            <?php endif; ?>
            <form id="registro-pagos-form" action="../controlador/pagoscontrolador.php" method="POST" novalidate>
                <fieldset class="form-group">
                    <label for="id_cliente" class="form-label">Cliente</label>
                    <select id="id_cliente" name="id_cliente" class="form-control" required>
                        <option value="">Seleccione un cliente</option>
                        <?php foreach ($clientes as $cliente): ?>
                            <option value="<?php echo $cliente['id_cliente']; ?>" 
                                <?php echo (isset($id_clienteValor) && $cliente['id_cliente'] == $id_clienteValor) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cliente['nombre_completo']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="error-msg">Selecciona un cliente.</p>
                </fieldset>

                <fieldset class="form-group">
                    <label for="plan_id" class="form-label">Plan</label>
                    <select id="plan_id" name="plan_id" class="form-control">
                        <option value="" id="keepPlanOption">Mantener plan actual</option>
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
                    <section class="input-inline">
                        <span class="currency-symbol">$</span>
                        <input type="number" step="0.01" min="0.01" id="monto" name="monto" class="form-control flex-1" placeholder="Monto" 
                               value="<?php echo $montoValor ?? ''; ?>" required>
                    </section>
                    <p class="error-msg">El monto es obligatorio.</p>
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

                <fieldset class="form-group">
                    <label for="fecha" class="form-label">Fecha de Pago</label>
                    <input type="date" id="fecha" name="fecha" class="form-control" 
                           value="<?php echo $fechaValor ?? date('Y-m-d'); ?>" required>
                    <p class="error-msg">Selecciona una fecha.</p>
                </fieldset>

                <button type="submit" name="btnpago" class="btn btn-primary login-btn">Registrar pago</button>
            </form>

            <footer class="login-footer">
                <a href="../controlador/pagoscontrolador.php" class="link-back">
                    <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M19 12H5M12 19l-7-7 7-7"></path></svg>
                    Volver al Directorio
                </a>
            </footer>
        </section>
    </main>

    <script>
        (function(){
            const plans = <?php echo json_encode($planes, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>;
            const clients = <?php echo json_encode($clientes, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>;
            const plansMap = {};
            plans.forEach(p => { plansMap[p.id_plan] = p.nombre; });
            const clientsMap = {};
            clients.forEach(c => { clientsMap[c.id_cliente] = c.Plan || null; });

            const clienteSelect = document.getElementById('id_cliente');
            const keepOpt = document.getElementById('keepPlanOption');

            function updateKeepText() {
                const val = clienteSelect.value;
                if (!val) {
                    keepOpt.textContent = 'Mantener plan actual';
                    return;
                }
                const planId = clientsMap[val] || null;
                if (planId && plansMap[planId]) {
                    keepOpt.textContent = 'Mantener plan actual (' + plansMap[planId] + ')';
                } else {
                    keepOpt.textContent = 'Mantener plan actual (Sin plan)';
                }
            }

            if (clienteSelect) {
                clienteSelect.addEventListener('change', updateKeepText);
                updateKeepText();
            }
        })();
    </script>
</body>
</html>