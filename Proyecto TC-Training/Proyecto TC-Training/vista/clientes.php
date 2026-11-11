<?php
if (!isset($clientes) || !isset($status) || !isset($solvencia)) {
    header("Location: ../controlador/ClienteController.php");
    exit();
}

$planEtiquetas = [
    'basico' => 'Semanal',
    'premium' => 'Mensual',
    'vip' => 'Anual',
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Clientes | TC-Control</title>
    <link rel="stylesheet" href="../estilos.css">
    <link rel="stylesheet" href="../responsive.css">
    <link rel="stylesheet" href="../mensajes.css">
    <meta name="codigo-pais" content="<?php echo htmlspecialchars(CODIGO_PAIS); ?>">
    <script src="../jv/script.js" defer></script>
    <script src="../jv/mensajes.js" defer></script>
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
        
        <?php $active = 'clientes'; include __DIR__ . '/partes/sidebar.php'; ?>

        <article class="main-content">
            <header class="page-header">
                <section>
                    <h1 class="page-title font-lexend">Directorio de Clientes</h1>
                    <p class="page-subtitle">Administra los datos personales de los miembros</p>
                </section>
                <section class="header-actions">
                    <fieldset class="search-bar">
                        <img src="../Imagenes/Lupa.png" alt="" class="search-icon" aria-hidden="true">
                        <input type="text" id="table-search" class="form-control search-input" placeholder="Buscar por nombre de cliente...">
                    </fieldset>
                    <a href="../controlador/MensajeController.php?action=masivo" class="btn btn-outline">Mensaje masivo</a>
                    <a href="../controlador/ClienteController.php?action=nuevo" class="btn btn-primary">+ Nuevo Cliente</a>
                </section>
            </header>

            <section class="card">
                <header class="card-header">
                    <h2 class="card-title font-lexend">Listado Completo</h2>
                    <fieldset class="flex-gap">
                        <a href="../controlador/ClienteController.php?status=todos&solvencia=<?php echo $solvencia; ?>" class="btn btn-outline btn-sm <?php echo $status == 'todos' ? 'active-filter' : ''; ?>">Todos</a>
                        <a href="../controlador/ClienteController.php?status=activo&solvencia=<?php echo $solvencia; ?>" class="btn btn-outline btn-sm <?php echo $status == 'activo' ? 'active-filter' : ''; ?>">Activos</a>
                        <a href="../controlador/ClienteController.php?status=inactivo&solvencia=<?php echo $solvencia; ?>" class="btn btn-outline btn-sm <?php echo $status == 'inactivo' ? 'active-filter' : ''; ?>">Inactivos</a>
                        <section class="spacer"></section>
                        <a href="../controlador/ClienteController.php?status=<?php echo $status; ?>&solvencia=todos" class="btn btn-outline btn-sm <?php echo $solvencia == 'todos' ? 'active-filter' : ''; ?>">Todos</a>
                        <a href="../controlador/ClienteController.php?status=<?php echo $status; ?>&solvencia=solvente" class="btn btn-outline btn-sm <?php echo $solvencia == 'solvente' ? 'active-filter' : ''; ?>">Solventes</a>
                        <a href="../controlador/ClienteController.php?status=<?php echo $status; ?>&solvencia=insolvente" class="btn btn-outline btn-sm <?php echo $solvencia == 'insolvente' ? 'active-filter' : ''; ?>">Insolventes</a>
                    </fieldset>
                </header>
                <article class="table-responsive">
                    <table class="payments-table">
                        <thead>
                            <tr>
                                <th>Nombre Completo</th>
                                <th>Teléfono</th>
                                <th>Fecha Ingreso</th>
                                <th>Estatus</th>
                                <th>Plan</th>
                                <th>Días Restantes</th>
                                <th>Solvencia</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($clientes as $cliente): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($cliente['Nombre'] . ' ' . $cliente['apellido']); ?></td>
                                <td><?php echo htmlspecialchars($cliente['telefono']); ?></td>
                                <td><?php echo htmlspecialchars(date('d M Y', strtotime($cliente['fecha_inscripcion']))); ?></td>
                                <td><?php echo htmlspecialchars(ucfirst($cliente['estatus'] ?? 'No definido')); ?></td>
                                <td><?php echo htmlspecialchars($planMap[$cliente['Plan']] ?? $planEtiquetas[strtolower($cliente['Plan'] ?? '')] ?? ucfirst($cliente['Plan'] ?? 'No definido')); ?></td>
                                <td>
                                    <?php if (strtolower($cliente['estatus']) !== 'activo'): ?>
                                        <span class="badge badge-inactive">Retirado</span>
                                    <?php else: ?>
                                        <?php echo $cliente['dias_restantes'] > 0 ? htmlspecialchars($cliente['dias_restantes'] . ' días') : 'Expirado'; ?>
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge <?php echo ucfirst($cliente['Solvencia']) == 'Solvente' ? 'badge-active' : 'badge-inactive'; ?>"><?php echo htmlspecialchars(ucfirst($cliente['Solvencia'])); ?></span></td>
                                <td>
                                    <section class="transactions-actions">
                                        <button type="button" class="btn btn-outline btn-sm btn-enviar-mensaje"
                                                data-id="<?php echo (int)$cliente['id_cliente']; ?>"
                                                data-nombre="<?php echo htmlspecialchars($cliente['Nombre']); ?>"
                                                data-apellido="<?php echo htmlspecialchars($cliente['apellido']); ?>"
                                                data-telefono="<?php echo htmlspecialchars($cliente['telefono']); ?>"
                                                data-plan="<?php echo htmlspecialchars($planMap[$cliente['Plan']] ?? $planEtiquetas[strtolower($cliente['Plan'] ?? '')] ?? ucfirst($cliente['Plan'] ?? '')); ?>"
                                                data-dias="<?php echo strtolower($cliente['estatus']) === 'activo' ? (int)$cliente['dias_restantes'] : ''; ?>">
                                            Enviar mensaje
                                        </button>
                                        <a href="../controlador/ClienteController.php?action=editar&id=<?php echo $cliente['id_cliente']; ?>" class="text-primary" title="Editar">
                                            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                            </svg>
                                        </a>
                                        <a href="../controlador/ClienteController.php?action=eliminar&id=<?php echo $cliente['id_cliente']; ?>" 
                                           class="text-danger" 
                                           title="Eliminar"
                                           onclick="return confirm('¿Estás seguro de que deseas eliminar a este cliente?');">
                                            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                                        </a>
                                    </section>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </article>
                <section class="pagination-container">
                    <span class="pagination-info" id="pagination-info">Mostrando clientes</span>
                    <fieldset class="flex-gap">
                        <button id="btn-prev-page" class="btn btn-outline btn-sm" disabled>&lt; Anterior</button>
                        <button id="btn-next-page" class="btn btn-outline btn-sm">Siguiente &gt;</button>
                    </fieldset>
                </section>
            </section>
        </article>
    </section>
</main>

<dialog id="dlg-mensaje" class="msg-dialog" aria-labelledby="dlg-titulo">
    <header class="msg-dialog-header">
        <section>
            <h2 id="dlg-titulo" class="card-title font-lexend">Enviar mensaje</h2>
            <p id="dlg-destinatario" class="text-muted"></p>
        </section>
        <button type="button" id="dlg-cerrar" class="btn btn-outline btn-sm" aria-label="Cerrar">&times;</button>
    </header>

    <?php include __DIR__ . '/partes/editor_mensaje.php'; ?>

    <footer class="msg-dialog-footer">
        <button type="button" id="dlg-cancelar" class="btn btn-outline">Cancelar</button>
        <button type="button" id="btn-enviar" class="btn btn-primary">Enviar mensaje</button>
    </footer>
</dialog>

<script type="application/json" id="plantillas-json"><?php echo json_encode($plantillas, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?></script>

</body>
</html>