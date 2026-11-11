<?php
if (!isset($clientes) || !isset($plantillas)) {
    header("Location: ../controlador/MensajeController.php?action=masivo");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Mensaje masivo | TC-Control</title>
    <link rel="stylesheet" href="../estilos.css">
    <link rel="stylesheet" href="../responsive.css">
    <link rel="stylesheet" href="../mensajes.css">
    <meta name="codigo-pais" content="<?php echo htmlspecialchars(CODIGO_PAIS); ?>">
    <script src="../jv/script.js" defer></script>
    <script src="../jv/mensajes.js" defer></script>
</head>
<body>
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
                    <h1 class="page-title font-lexend">Mensaje masivo</h1>
                    <p class="page-subtitle">Elige el tipo de mensaje y envíalo a los clientes registrados</p>
                </section>
                <section class="header-actions">
                    <a href="../controlador/ClienteController.php" class="btn btn-outline">Volver a Clientes</a>
                </section>
            </header>

            <section class="card">
                <header class="card-header">
                    <h2 class="card-title font-lexend">1. Redacta o elige el mensaje</h2>
                </header>
                <section class="card-body">
                    <?php include __DIR__ . '/partes/editor_mensaje.php'; ?>
                </section>
            </section>

            <section class="card">
                <header class="card-header">
                    <h2 class="card-title font-lexend">2. Elige los destinatarios</h2>
                    <fieldset class="flex-gap">
                        <input type="text" id="masivo-buscar" class="form-control masivo-buscar" placeholder="Buscar cliente...">
                        <select id="masivo-filtro" class="form-control masivo-filtro">
                            <option value="todos">Todos</option>
                            <option value="activo">Solo activos</option>
                            <option value="inactivo">Solo inactivos</option>
                        </select>
                    </fieldset>
                </header>

                <section class="masivo-barra">
                    <span id="masivo-info" class="text-muted"></span>
                    <button type="button" id="btn-siguiente" class="btn btn-primary btn-sm">Abrir chat del siguiente pendiente</button>
                </section>
                <p class="msg-ayuda masivo-nota">WhatsApp y los celulares solo permiten abrir un chat por clic, así que el mensaje queda listo en cada chat y tú pulsas enviar. Los clientes ya abiertos se marcan para que no se repitan.</p>

                <article class="table-responsive">
                    <table class="payments-table" id="tabla-masivo">
                        <thead>
                            <tr>
                                <th><input type="checkbox" id="chk-todos" aria-label="Seleccionar todos"></th>
                                <th>Nombre Completo</th>
                                <th>Teléfono</th>
                                <th>Estatus</th>
                                <th>Envío</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($clientes as $cliente):
                                $planNombre = $planMap[$cliente['Plan']] ?? $planEtiquetas[strtolower($cliente['Plan'] ?? '')] ?? ucfirst($cliente['Plan'] ?? '');
                                $activo = strtolower($cliente['estatus']) === 'activo';
                            ?>
                            <tr data-nombre="<?php echo htmlspecialchars($cliente['Nombre']); ?>"
                                data-apellido="<?php echo htmlspecialchars($cliente['apellido']); ?>"
                                data-telefono="<?php echo htmlspecialchars($cliente['telefono']); ?>"
                                data-plan="<?php echo htmlspecialchars($planNombre); ?>"
                                data-dias="<?php echo $activo ? (int)$cliente['dias_restantes'] : ''; ?>"
                                data-estatus="<?php echo $activo ? 'activo' : 'inactivo'; ?>">
                                <td><input type="checkbox" class="chk-cliente" aria-label="Seleccionar cliente"></td>
                                <td><?php echo htmlspecialchars($cliente['Nombre'] . ' ' . $cliente['apellido']); ?></td>
                                <td><?php echo htmlspecialchars($cliente['telefono']); ?></td>
                                <td><?php echo htmlspecialchars(ucfirst($cliente['estatus'] ?? 'No definido')); ?></td>
                                <td><span class="celda-estado badge badge-inactive">Pendiente</span></td>
                                <td><button type="button" class="btn btn-outline btn-sm btn-fila-enviar">Enviar mensaje</button></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </article>
            </section>
        </article>
    </section>
</main>

<script type="application/json" id="plantillas-json"><?php echo json_encode($plantillas, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?></script>
</body>
</html>
