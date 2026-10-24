<?php
if (!isset($fondos)) {
    header("Location: ../controlador/FondoController.php");
    exit();
}
// Asegurar que sean arrays para evitar cualquier warning o error
$fondos = is_array($fondos) ?$fondos : [];
$transacciones = is_array($transacciones) ? $transacciones : [];$idCuentaFiltro = isset($idCuentaFiltro) ?$idCuentaFiltro : 'todas';
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
                </section>
            </header>

            <?php if (!empty($mensaje)): ?>
                <section class="alert-container"><?php echo $mensaje; ?></section>
            <?php endif; ?>

            <!-- Tarjetas de Fondos Dinámicas con foreach seguro -->
            <?php 
                $saldoGeneral = 0;
                if (!empty($fondos) && is_array($fondos)) {
                    foreach ($fondos as$f) {
                        if (is_array($f) && isset($f['saldo'])) {
                            $saldoGeneral += floatval($f['saldo']);
                        }
                    }
                }
                $isAllActive = ($idCuentaFiltro === 'todas');
            ?>
            <section class="cards-grid-cuentas" id="cuentasCardsContainer">
                <div class="account-card <?php echo $isAllActive ? 'active' : ''; ?>" onclick="filtrarPorCuenta('todas', this)">
                    <span class="account-card-sub">Todos los Fondos</span>
                    <strong class="account-card-title">Saldo General</strong>
                    <span class="account-card-value">
                        $<?php echo number_format($saldoGeneral, 2); ?>
                    </span>
                </div>
                <?php foreach ($fondos as$f): ?>
                    <?php $isActiveCard = ($idCuentaFiltro ==$f['id_cuenta']); ?>
                    <div class="account-card <?php echo $isActiveCard ? 'active' : ''; ?>" onclick="filtrarPorCuenta(<?php echo $f['id_cuenta']; ?>, this)">
                        <span class="account-card-sub"><?php echo htmlspecialchars($f['banco_nombre'] ?? 'Banco'); ?></span>
                        <strong class="account-card-title"><?php echo htmlspecialchars($f['num_cuenta'] ?? ''); ?></strong>
                        <span class="account-card-value">
                            <?php echo htmlspecialchars($f['tipo_moneda'] ?? 'USD') . ' ' . number_format($f['saldo'] ?? 0, 2); ?>
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
                                                <strong><?php echo ($esIngreso ? '+ $' : '- $') . number_format($t['monto'] ?? 0, 2); ?></strong>
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
                    const sign = esIngreso ? '+ $' : '-$';
                    const banco = (t.banco_nombre || 'General') + ' - ' + (t.num_cuenta || 'Caja/Efectivo');

                    return `<tr>
                        <td>${t.fecha || ''}</td>
                        <td>${t.concepto || ''}</td>
                        <td><span class="${badgeClass}">${esIngreso ? '+ Ingreso' : '- Egreso'}</span></td>
                        <td>${banco}</td>
                        <td>${t.nombre_metodo || 'Efectivo'}</td>
                        <td class="${textClass}"><strong>${sign}${Number(t.monto || 0).toFixed(2)}</strong></td>
                    </tr>`;
                }).join('');
            })
            .catch(error => console.error('Error al filtrar transacciones:', error));
    }
</script>
</body>
</html>