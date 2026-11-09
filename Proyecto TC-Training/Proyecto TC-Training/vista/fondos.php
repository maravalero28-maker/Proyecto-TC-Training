<?php
if (!isset($fondos)) {
    header("Location: ../controlador/FondoController.php");
    exit();
}
// Asegurar que sean arrays para evitar cualquier warning o error
$fondos = is_array($fondos) ?$fondos : [];
$transacciones = is_array($transacciones) ? $transacciones : [];$idCuentaFiltro = isset($idCuentaFiltro) ?$idCuentaFiltro : 'todas';
$tasaVigente = $tasaVigente ?? null;
$tasaHoyRegistrada = $tasaHoyRegistrada ?? false;
$totalesUSD = (isset($totalesUSD) && is_array($totalesUSD)) ? $totalesUSD : [];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Gestión de Fondos y Saldos | TC-Control</title>
    <link rel="stylesheet" href="../estilos.css">
    <link rel="stylesheet" href="../responsive.css">
    <script src="../jv/script.js" defer></script>
</head>
<body>
<?php include __DIR__ . '/partes/toast-notification.php'; ?>

<header class="topbar">
    <button id="hamburger-btn" class="menu-toggle hamburger-menu" aria-label="Abrir menú">
        <svg width="24" height="24" fill="none"><use href="#icon-menu"></use></svg>
    </button>
    <a href="../controlador/InicioController.php" class="brand">
        <img src="../Imagenes/472842978_1608453203376849_549310859470506009_n.jpg" alt="Logo">
        TC-Control
    </a>
    <section class="user-actions">
        <span class="text-muted">Administrador</span>
        <a href="../controlador/logout.php" class="btn btn-primary btn-sm">Salir</a>
    </section>
</header>
    
<main class="dashboard-layout">
    <svg class="svg-hidden" style="display: none;">
        <symbol id="icon-menu" viewBox="0 0 24 24">
            <line x1="3" y1="12" x2="21" y2="12" stroke="currentColor" stroke-width="2" stroke-linecap="round"></line>
            <line x1="3" y1="6" x2="21" y2="6" stroke="currentColor" stroke-width="2" stroke-linecap="round"></line>
            <line x1="3" y1="18" x2="21" y2="18" stroke="currentColor" stroke-width="2" stroke-linecap="round"></line>
        </symbol>
    </svg>

    <section class="layout-body">
        <?php $active = 'fondos'; include __DIR__ . '/partes/sidebar.php'; ?>
        <article class="main-content">
            <header class="page-header">
                <section>
                    <h1 class="page-title">Cuentas Bancarias y Fondos</h1>
                    <p class="page-subtitle">Monitoreo de saldos y flujo de dinero por fondo (Zelle, Binance, Banesco, etc.)</p>
                    <p class="page-subtitle">
                        <?php if ($tasaVigente): ?>
                            Tasa del día: <strong>Bs <?php echo number_format($tasaVigente['tasa'], 2, ',', '.'); ?></strong> por $ (<?php echo date('d/m/Y', strtotime($tasaVigente['fecha'])); ?>)<?php echo $tasaHoyRegistrada ? '' : ' - falta registrar la tasa de hoy'; ?>
                        <?php else: ?>
                            Aún no hay una tasa registrada.
                        <?php endif; ?>
                    </p>
                </section>
                <!-- Acciones de fondos: modifica aquí los botones de tasa y método de pago. -->
                <section class="header-actions">
                    <a href="../controlador/FondoController.php?action=nueva_tasa" class="btn btn-primary">Tasa del Día</a>
                    <a href="../controlador/FondoController.php?action=nuevo_metodo" class="btn btn-primary">+ Nuevo Método de Pago</a>
                    <a href="../controlador/FondoController.php?action=nueva_cuenta" class="btn btn-primary">+ Nueva Cuenta</a>
                </section>
            </header>

            <?php if (!empty($mensaje)): ?>
                <section class="alert-container"><?php echo $mensaje; ?></section>
            <?php endif; ?>

            <!-- Tarjetas de Fondos Dinámicas con foreach seguro -->
            <?php 
                // Saldo general siempre en dólares (cada movimiento convertido con su tasa)
                $totalesGenerales = $totalesUSD['general'] ?? ['ingresos' => 0, 'egresos' => 0, 'saldo' => 0];
                $saldoGeneral = $totalesGenerales['saldo'];
                $inicialGeneral = $totalesGenerales['inicial'] ?? 0;
                $ingresosGeneral = $totalesGenerales['ingresos'];
                $egresosGeneral = $totalesGenerales['egresos'];
                // Movimientos registrados sin cuenta (efectivo, en dólares)
                $fondoEfectivo = (isset($fondoEfectivo) && is_array($fondoEfectivo)) ? $fondoEfectivo : ['ingresos' => 0, 'egresos' => 0, 'saldo' => 0];
                $hayEfectivo = (round(floatval($fondoEfectivo['ingresos']), 2) != 0 || round(floatval($fondoEfectivo['egresos']), 2) != 0);
                $isAllActive = ($idCuentaFiltro === 'todas');
            ?>
            <section class="cards-grid-cuentas" id="cuentasCardsContainer">
                <div class="account-card <?php echo $isAllActive ? 'active' : ''; ?>" onclick="filtrarPorCuenta('todas', this)">
                    <span class="account-card-sub">Todos los Fondos (en USD)</span>
                    <strong class="account-card-title">Saldo General</strong>
                    <span class="account-card-value">
                        $<?php echo number_format($saldoGeneral, 2); ?>
                    </span>
                    <?php if (round($inicialGeneral, 2) != 0): ?>
                        <span class="account-card-sub">Saldo inicial: $<?php echo number_format($inicialGeneral, 2); ?></span>
                    <?php endif; ?>
                    <span class="account-card-sub">Ingresos: +$<?php echo number_format($ingresosGeneral, 2); ?> | Egresos: -$<?php echo number_format($egresosGeneral, 2); ?></span>
                </div>
                <?php foreach ($fondos as$f): ?>
                    <?php $isActiveCard = ($idCuentaFiltro ==$f['id_cuenta']); ?>
                    <div class="account-card <?php echo $isActiveCard ? 'active' : ''; ?>" onclick="filtrarPorCuenta(<?php echo $f['id_cuenta']; ?>, this)">
                        <span class="account-card-sub"><?php echo htmlspecialchars($f['banco_nombre'] ?? 'Banco'); ?></span>
                        <strong class="account-card-title"><?php echo htmlspecialchars($f['num_cuenta'] ?? ''); ?></strong>
                        <span class="account-card-value">
                            <?php echo htmlspecialchars($f['tipo_moneda'] ?? 'USD') . ' ' . number_format($f['saldo'] ?? 0, 2); ?>
                        </span>
                        <?php if (floatval($f['saldo_inicial'] ?? 0) != 0): ?>
                            <span class="account-card-sub">Saldo inicial: <?php echo number_format($f['saldo_inicial'], 2); ?></span>
                        <?php endif; ?>
                        <?php if (strtoupper($f['tipo_moneda'] ?? '') === 'BS'): ?>
                            <span class="account-card-sub">&asymp; $<?php echo number_format($f['saldo_usd'] ?? 0, 2); ?> USD</span>
                        <?php endif; ?>
                        <span class="account-card-sub">Ingresos: +<?php echo number_format($f['ingresos'] ?? 0, 2); ?> | Egresos: -<?php echo number_format($f['egresos'] ?? 0, 2); ?></span>
                    </div>
                <?php endforeach; ?>
                <?php if ($hayEfectivo): ?>
                    <div class="account-card <?php echo ($idCuentaFiltro === 'sin_cuenta') ? 'active' : ''; ?>" onclick="filtrarPorCuenta('sin_cuenta', this)">
                        <span class="account-card-sub">Sin cuenta asignada</span>
                        <strong class="account-card-title">Efectivo / Caja</strong>
                        <span class="account-card-value">
                            $<?php echo number_format($fondoEfectivo['saldo'], 2); ?>
                        </span>
                        <span class="account-card-sub">Ingresos: +$<?php echo number_format($fondoEfectivo['ingresos'], 2); ?> | Egresos: -$<?php echo number_format($fondoEfectivo['egresos'], 2); ?></span>
                    </div>
                <?php endif; ?>
            </section>

            <!-- Tabla de Transacciones -->
            <section class="card">
                <header class="card-header">
                    <h2 class="card-title">Movimientos y Transacciones</h2>
                </header>
                <article class="card-body">
                    <section class="table-responsive">
                        <table class="payments-table">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Concepto</th>
                                    <th>Tipo</th>
                                    <th>Banco / Fondo</th>
                                    <th>Método</th>
                                    <th>Monto</th>
                                </tr>
                            </thead>
                            <tbody id="transaccionesTableBody">
                                <?php if (empty($transacciones)): ?>
                                    <tr><td colspan="6" class="empty-table-msg">No hay transacciones registradas en los fondos.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($transacciones as$t): ?>
                                        <?php $esIngreso = ($t['tipo_movimiento'] === 'ingreso'); ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($t['fecha'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($t['concepto'] ?? ''); ?></td>
                                            <td>
                                                <span class="<?php echo $esIngreso ? 'badge-ingreso' : 'badge-egreso'; ?>">
                                                    <?php echo $esIngreso ? '+ Ingreso' : '- Egreso'; ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars(($t['banco_nombre'] ?? 'General') . ' - ' . ($t['num_cuenta'] ?? 'Caja/Efectivo')); ?></td>
                                            <td><?php echo htmlspecialchars($t['nombre_metodo'] ?? 'Efectivo'); ?></td>
                                            <td class="<?php echo $esIngreso ? 'text-success' : 'text-danger'; ?>">
                                                <?php $esBs = (strtoupper($t['tipo_moneda'] ?? 'USD') === 'BS'); ?>
                                                <strong><?php echo ($esIngreso ? '+ ' : '- ') . ($esBs ? 'Bs ' : '$') . number_format($t['monto'] ?? 0, 2); ?></strong>
                                                <?php if ($esBs && isset($t['monto_usd']) && $t['monto_usd'] !== ''): ?>
                                                    <br><small>&asymp; $<?php echo number_format($t['monto_usd'], 2); ?></small>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </section>
                </article>
            </section>
        </article>
    </section>
</main>

<script>
    function filtrarPorCuenta(idCuenta, elemento) {
        document.querySelectorAll('.account-card').forEach(c => c.classList.remove('active'));
        if (elemento) elemento.classList.add('active');

        // Añadimos &_t=Date.now() para evitar que el navegador guarde en caché la respuesta anterior
        fetch('../controlador/FondoController.php?id_cuenta=' + idCuenta + '&ajax=1&_t=' + Date.now())
            .then(res => res.json())
            .then(data => {
                const tbody = document.getElementById('transaccionesTableBody');
                if (!data.transacciones || data.transacciones.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="6" class="empty-table-msg">No hay transacciones registradas en este fondo.</td></tr>';
                    return;
                }
                
                tbody.innerHTML = data.transacciones.map(t => {
                    const esIngreso = t.tipo_movimiento === 'ingreso';
                    const badgeClass = esIngreso ? 'badge-ingreso' : 'badge-egreso';
                    const textClass = esIngreso ? 'text-success' : 'text-danger';
                    const esBs = (t.tipo_moneda || 'USD').toUpperCase() === 'BS';
                    const sign = (esIngreso ? '+ ' : '- ') + (esBs ? 'Bs ' : '$');
                    const equivalente = (esBs && t.monto_usd !== null && t.monto_usd !== undefined && t.monto_usd !== '') ? `<br><small>&asymp; $${Number(t.monto_usd).toFixed(2)}</small>` : '';
                    const banco = (t.banco_nombre || 'General') + ' - ' + (t.num_cuenta || 'Caja/Efectivo');

                    return `<tr>
                        <td>${t.fecha || ''}</td>
                        <td>${t.concepto || ''}</td>
                        <td><span class="${badgeClass}">${esIngreso ? '+ Ingreso' : '- Egreso'}</span></td>
                        <td>${banco}</td>
                        <td>${t.nombre_metodo || 'Efectivo'}</td>
                        <td class="${textClass}"><strong>${sign}${Number(t.monto || 0).toFixed(2)}</strong>${equivalente}</td>
                    </tr>`;
                }).join('');
            })
            .catch(error => console.error('Error al filtrar transacciones:', error));
    }
</script>
</body>
</html>