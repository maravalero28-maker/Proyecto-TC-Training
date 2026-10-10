<?php if (!isset($planes)) {
    header('Location: ../controlador/PlanController.php');
    exit();
} ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Planes - TC-Control</title>
    <link rel="stylesheet" href="../estilos.css">
    <link rel="stylesheet" href="../responsive.css">
    <script src="../jv/script.js" defer></script>
</head>
<body>
<?php include __DIR__ . '/partes/toast-notification.php'; ?>

<header class="topbar">
        <button id="hamburger-btn" class="hamburger-menu" aria-label="Abrir menú">☰</button>
        <a href="../controlador/InicioController.php" class="brand">
            <img src="../Imagenes/472842978_1608453203376849_549310859470506009_n.jpg" alt="Logo">
            <?php echo htmlspecialchars($config['nombre_empresa'] ?? 'TC-Control'); ?>
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

    <!--<header class="topbar">
        <button id="menu-toggle" class="menu-toggle" aria-label="Abrir menú" style="background: none; border: none; cursor: pointer; padding: 0.5rem; display: flex; align-items: center;">
            <svg width="24" height="24" fill="none">
                <use href="#icon-menu"></use>
            </svg>
        </button>

        <a href="../controlador/InicioController.php" class="brand">
            <img src="../Imagenes/472842978_1608453203376849_549310859470506009_n.jpg" alt="Logo">
            TC-Control
        </a>
        
        <section class="user-actions" style="margin-left: auto;">
            <span class="text-muted">Administrador</span>
            <a href="../controlador/logout.php" class="btn btn-primary btn-sm">Salir</a>
        </section>
    </header> -->

    <section class="layout-body">
        <?php $active = 'planes'; include __DIR__ . '/partes/sidebar.php'; ?>

        <article class="main-content">
            <header class="page-header">
                <section>
                    <h1 class="page-title">Planes</h1>
                    <p class="page-subtitle">Configura los planes disponibles para clientes</p>
                </section>
                <section class="header-actions">
                    <a href="../controlador/PlanController.php?action=nuevo" class="btn btn-primary">+ Nuevo Plan</a>
                </section>
            </header>

            <section class="card">
                <header class="card-header"><h2 class="card-title">Listado de Planes</h2></header>
                <article class="card-body">
                    <section class="table-responsive">
                        <table class="payments-table">
                            <thead><tr><th>Nombre</th><th>Precio</th><th>Días</th><th>Acciones</th></tr></thead>
                            <tbody>
                            <?php if (empty($planes)): ?>
                                <tr><td colspan="4">No hay planes definidos.</td></tr>
                            <?php else: ?>
                                <?php foreach($planes as $pl): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($pl['nombre']); ?></td>
                                        <td><?php echo number_format($pl['precio'],2,',','.'); ?></td>
                                        <td><?php echo intval($pl['dias']); ?></td>
                                        <td>
                                            <section class="transactions-actions">
                                                <a href="../controlador/PlanController.php?action=editar&id=<?php echo $pl['id_plan']; ?>" class="text-primary" title="Editar plan">
                                                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                                    </svg>
                                                </a>
                                                <a href="../controlador/PlanController.php?action=eliminar&id=<?php echo $pl['id_plan']; ?>" class="text-danger" title="Eliminar plan" onclick="return confirm('¿Eliminar plan? Esta acción no se puede deshacer.')">
                                                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                        <line x1="18" y1="6" x2="6" y2="18"></line>
                                                        <line x1="6" y1="6" x2="18" y2="18"></line>
                                                    </svg>
                                                </a>
                                            </section>
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
</body>
</html>
