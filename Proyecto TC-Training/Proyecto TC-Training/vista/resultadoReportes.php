<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?php echo htmlspecialchars($titulo ?? 'Resultado de Reporte'); ?> | TC-Control</title>
    <link rel="stylesheet" href="../estilos.css">
    <link rel="stylesheet" href="../responsive.css">
    <script src="../jv/script.js" defer></script>
</head>
<body>

<section id="toast-container" aria-live="polite" aria-atomic="true"></section>

<!-- 1. ENCABEZADO SUPERIOR IGUAL AL RESTO DEL SISTEMA -->
<header class="topbar">
    <button id="hamburger-btn" class="hamburger-menu" aria-label="Abrir menú">☰</button>
    <a href="../controlador/InicioController.php" class="brand">
        <img src="../Imagenes/472842978_1608453203376849_549310859470506009_n.jpg" alt="Logo">
        TC-Control
    </a>
    <section class="user-actions">
        <span class="text-muted">Administrador</span>
        <a href="../controlador/logout.php" class="btn btn-primary btn-sm">Salir</a>
    </section>
</header>

<!-- 2. ESTRUCTURA DE LAYOUT GENERAL -->
<main class="dashboard-layout">
    <section class="layout-body">
        <?php $active = 'reportes'; include __DIR__ . '/partes/sidebar.php'; ?>

        <!-- 3. CONTENIDO PRINCIPAL DEL RESULTADO -->
        <article class="main-content reportes-page">
            <section class="card reportes-card resultado-card">
                <h2><?php echo htmlspecialchars($titulo ?? 'Resultado del Reporte'); ?></h2>
                <hr class="reportes-separador">
                
                <?php if (empty($datos)): ?>
                    <aside class="alerta-advertencia reportes-alerta" role="status">
                        <p>No se encontraron registros que coincidan con los filtros seleccionados.</p>
                    </aside>
                <?php else: ?>
                    <div class="acciones-reporte" style="margin-bottom: 1.5rem;">
                        <button type="button" onclick="window.print()" class="btn btn-primary">Imprimir o guardar en PDF</button>
                    </div>

                    <div class="table-responsive">
                        <table class="payments-table tabla-datos">
                        <thead>
                            <tr>
                                <?php foreach (array_keys($datos[0]) as $columna): ?>
                                    <th><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $columna))); ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($datos as $fila): ?>
                                <tr>
                                    <?php foreach ($fila as $valor): ?>
                                        <td><?php echo htmlspecialchars($valor ?? 'N/A'); ?></td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        </table>
                    </div>
                <?php endif; ?>
                
                <div class="navegacion-retorno" style="margin-top: 1.5rem;">
                    <a href="../controlador/Reportecontrolador.php?action=index" class="btn btn-outline">Volver a los filtros de reportes</a>
                </div>
            </section>
        </article>
    </section>
</main>

</body>
</html>