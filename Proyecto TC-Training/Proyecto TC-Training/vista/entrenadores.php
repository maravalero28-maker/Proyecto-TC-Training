<?php
if (!isset($entrenadores) || !isset($status)) {
    header("Location: ../controlador/EntrenadorController.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Entrenadores | TC-Control</title>
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
        TC-Control
    </a>
    <section class="user-actions">
        <span class="text-muted">Administrador</span>
        <a href="Login.php" class="btn btn-primary btn-sm">Salir</a>
    </section>
</header>

<main class="dashboard-layout">
    <section class="layout-body">
        <?php $active = 'entrenadores'; include __DIR__ . '/partes/sidebar.php'; ?>
        <article class="main-content">
            <header class="page-header">
                <section>
                    <h1 class="page-title font-lexend">Directorio de Entrenadores</h1>
                    <p class="page-subtitle">Gestiona los instructores del gimnasio</p>
                </section>
                <section class="header-actions">
                    <fieldset class="search-bar">
                        <img src="../Imagenes/Lupa.png" alt="" class="search-icon" aria-hidden="true">
                        <input type="text" id="table-search" class="form-control search-input" placeholder="Buscar por nombre...">
                    </fieldset>
                    <a href="../controlador/EntrenadorController.php?action=nuevo" class="btn btn-primary">+ Nuevo Entrenador</a>
                </section>
            </header>

            <section class="card">
                <header class="card-header">
                    <h2 class="card-title font-lexend">Listado Completo</h2>
                    <fieldset class="flex-gap">
                        <a href="../controlador/EntrenadorController.php?status=todos" class="btn btn-outline btn-sm <?php echo $status == 'todos' ? 'active-filter' : ''; ?>">Todos</a>
                        <a href="../controlador/EntrenadorController.php?status=activo" class="btn btn-outline btn-sm <?php echo $status == 'activo' ? 'active-filter' : ''; ?>">Activos</a>
                        <a href="../controlador/EntrenadorController.php?status=inactivo" class="btn btn-outline btn-sm <?php echo $status == 'inactivo' ? 'active-filter' : ''; ?>">Inactivos</a>
                    </fieldset>
                </header>
                <article class="table-responsive">
                    <table class="payments-table">
                        <thead>
                            <tr>
                                <th>Cédula</th>
                                <th>Nombre Completo</th>
                                <th>Teléfono</th>
                                <th>Estatus</th>
                                <th>Fecha Registro</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($entrenadores)): ?>
                                <tr><td colspan="6">No hay entrenadores registrados.</td></tr>
                            <?php else: ?>
                                <?php foreach ($entrenadores as $e): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($e['cedula']); ?></td>
                                    <td><?php echo htmlspecialchars($e['nombre'] . ' ' . $e['apellido']); ?></td>
                                    <td><?php echo htmlspecialchars($e['telefono']); ?></td>
                                    <td><span class="badge <?php echo strtolower($e['estatus']) == 'activo' ? 'badge-active' : 'badge-inactive'; ?>"><?php echo htmlspecialchars(ucfirst($e['estatus'])); ?></span></td>
                                    <td><?php echo htmlspecialchars(date('d M Y', strtotime($e['fecha_registro']))); ?></td>
                                    <td>
                                        <section class="transactions-actions">
                                            <a href="../controlador/EntrenadorController.php?action=editar&cedula=<?php echo urlencode($e['cedula']); ?>" class="text-primary" title="Editar">
                                                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                                            </a>
                                            <a href="../controlador/EntrenadorController.php?action=eliminar&cedula=<?php echo urlencode($e['cedula']); ?>" class="text-danger" title="Desactivar" onclick="return confirm('¿Estás seguro de desactivar este entrenador?');">
                                                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                                            </a>
                                        </section>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </article>
            </section>
        </article>
    </section>
</main>
</body>
</html>