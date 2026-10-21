<?php
if (!isset($cuentas)) {
    header("Location: ../controlador/CuentaController.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Gestión de Cuentas y Saldos | TC-Control</title>
    <link rel="stylesheet" href="../estilos.css">
    <link rel="stylesheet" href="../responsive.css">
    <script src="../jv/script.js" defer></script>


</head>
<body>
<?php include __DIR__ . '/partes/toast-notification.php'; ?>

<header class="topbar">
    <button id="hamburger-btn" class="menu-toggle hamburger-menu" aria-label="Abrir menú" style="background: none; border: none; cursor: pointer; padding: 0.5rem; align-items: center">
        <svg width="24" height="24" fill="none"><use href="#icon-menu"></use></svg>
    </button>
    <a href="../controlador/InicioController.php" class="brand">
        <img src="../Imagenes/472842978_1608453203376849_549310859470506009_n.jpg" alt="Logo">
        TC-Control
    </a>
    <section class="user-actions" style="margin-left: auto;">
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
        <?php $active = 'cuentas'; include __DIR__ . '/partes/sidebar.php'; ?>
        <article class="main-content">
            <header class="page-header">
                <section>
                    <h1 class="page-title">Cuentas Bancarias y Fondos</h1>
                    <p class="page-subtitle">Monitoreo de saldos y flujo de dinero por cuenta (Zelle, Binance, Banesco, etc.)</p>
                </section>
            </header>

            <?php if (!empty($mensaje)): ?>
                <section class="alert-container" style="margin-bottom: 20px;"><?php echo $mensaje; ?></section>
            <?php endif; ?>

            <!-- Tarjetas de Cuentas -->
            <section class="cards-grid-cuentas" id="cuentasCardsContainer">
                <div class="account-card account-card--all active" onclick="filtrarPorCuenta('todas', this)">
                    <span class="account-card-label">Vista general</span>
                    <strong class="account-card-name">Todas las cuentas</strong>
                    <span class="account-card-detail">Ver movimientos consolidados</span>
                </div>
                <?php foreach ($cuentas as $c): ?>
                    <?php
                    $bancoNombre = $c['banco_nombre'] ?? 'Banco';
                    $numeroCuenta = $c['num_cuenta'] ?? '';
                    $monedaCuenta = $c['tipo_moneda'] ?? '';
                    $saldoCuenta = (float) ($c['saldo'] ?? 0);
                    $claseSaldo = $saldoCuenta < 0 ? 'account-card--negative' : 'account-card--positive';
                    ?>
                    <div class="account-card <?php echo $claseSaldo; ?>" onclick="filtrarPorCuenta(<?php echo (int) $c['id_cuenta']; ?>, this)">
                        <span class="account-card-label"><?php echo htmlspecialchars($bancoNombre); ?></span>
                        <strong class="account-card-name"><?php echo htmlspecialchars($numeroCuenta); ?></strong>
                        <span class="account-card-balance">
                            <?php echo htmlspecialchars($monedaCuenta) . ' ' . number_format($saldoCuenta, 2); ?>
                        </span>
                    </div>
                <?php endforeach; ?>
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
                                    <th>Banco / Cuenta</th>
                                    <th>Método</th>
                                    <th>Monto</th>
                                </tr>
                            </thead>
                            <tbody id="transaccionesTableBody">
                                <?php if (empty($transacciones)): ?>
                                    <tr><td colspan="6" class="empty-table-msg">No hay transacciones registradas en las cuentas.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($transacciones as$t): ?>
                                        <?php $esIngreso = ($t['tipo_movimiento'] === 'ingreso'); ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($t['fecha']); ?></td>
                                            <td><?php echo htmlspecialchars($t['concepto']); ?></td>
                                            <td>
                                                <span class="<?php echo $esIngreso ? 'badge-ingreso' : 'badge-egreso'; ?>">
                                                    <?php echo $esIngreso ? '+ Ingreso' : '- Egreso'; ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars(($t['banco_nombre'] ?? 'N/A') . ' - ' . ($t['num_cuenta'] ?? 'N/A')); ?></td>
                                            <td><?php echo htmlspecialchars($t['nombre_metodo'] ?? 'Efectivo'); ?></td>
                                            <td style="font-weight: bold; color: <?php echo $esIngreso ? '#4ade80' : '#f87171'; ?>;">
                                                <?php echo ($esIngreso ? '+ $' : '- $') . number_format($t['monto'], 2); ?>
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

        fetch('../controlador/CuentaController.php?id_cuenta=' + idCuenta + '&ajax=1')
            .then(res => res.json())
            .then(data => {
                const tbody = document.getElementById('transaccionesTableBody');
                if (!data.transacciones || data.transacciones.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="6" class="empty-table-msg">No hay transacciones registradas en esta cuenta.</td></tr>';
                    return;
                }
                tbody.innerHTML = data.transacciones.map(t => {
                    const esIngreso = t.tipo_movimiento === 'ingreso';
                    const badgeClass = esIngreso ? 'badge-ingreso' : 'badge-egreso';
                    const sign = esIngreso ? '+ $' : '-$';
                    const color = esIngreso ? '#4ade80' : '#f87171';
                    const banco = (t.banco_nombre || 'N/A') + ' - ' + (t.num_cuenta || 'N/A');

                    return `<tr>
                        <td>${t.fecha || ''}</td>
                        <td>${t.concepto || ''}</td>
                        <td><span class="${badgeClass}">${esIngreso ? '+ Ingreso' : '- Egreso'}</span></td>
                        <td>${banco}</td>
                        <td>${t.nombre_metodo || 'Efectivo'}</td>
                        <td style="font-weight: bold; color: ${color};">${sign}${Number(t.monto).toFixed(2)}</td>
                    </tr>`;
                }).join('');
            });
    }
</script>
</body>
</html>