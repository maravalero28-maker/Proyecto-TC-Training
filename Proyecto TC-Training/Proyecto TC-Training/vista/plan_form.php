<?php

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?php echo isset($plan) ? 'Editar Plan' : 'Nuevo Plan'; ?> - TC-Control</title>
    <link rel="stylesheet" href="../estilos.css">
    <link rel="stylesheet" href="../responsive.css">
    <script src="../jv/script.js" defer></script>
</head>
<body>
<?php include __DIR__ . '/partes/toast-notification.php'; ?>
<main class="dashboard-layout">
    <header class="topbar">
        <a href="../controlador/InicioController.php" class="brand">
            <img src="../Imagenes/472842978_1608453203376849_549310859470506009_n.jpg" alt="Logo">
            TC-Control
        </a>
        <section class="user-actions">
            <span class="text-muted">Administrador</span>
            <a href="../controlador/logout.php" class="btn btn-primary btn-sm">Salir</a>
        </section>
    </header>
    <section class="layout-body">
        <?php $active = 'planes'; include __DIR__ . '/partes/sidebar.php'; ?>
        <article class="main-content">
            <header class="page-header">
                <h1 class="page-title"><?php echo isset($plan) ? 'Editar Plan' : 'Nuevo Plan'; ?></h1>
            </header>
            <section class="card center-card">
                <article class="card-body">
                    <form action="../controlador/PlanController.php" method="POST">
                        <?php if (isset($plan)): ?>
                            <input type="hidden" name="id_plan" value="<?php echo intval($plan['id_plan']); ?>">
                        <?php endif; ?>
                        <fieldset class="form-group">
                            <label class="form-label">Nombre</label>
                            <input type="text" name="nombre" class="form-control" value="<?php echo isset($plan) ? htmlspecialchars($plan['nombre']) : htmlspecialchars($nombre ?? ''); ?>" required>
                        </fieldset>
                        <fieldset class="form-group">
                            <label class="form-label">Precio</label>
                            <input type="number" step="0.01" name="precio" class="form-control" value="<?php echo isset($plan) ? htmlspecialchars($plan['precio']) : htmlspecialchars($precio ?? '0.00'); ?>" required>
                        </fieldset>
                        <fieldset class="form-group">
                            <label class="form-label">Días de vigencia</label>
                            <input type="number" name="dias" class="form-control" value="<?php echo isset($plan) ? intval($plan['dias']) : intval($dias ?? 30); ?>" required>
                        </fieldset>
                        <button type="submit" name="btnGuardarPlan" class="btn btn-primary">Guardar Plan</button>
                    </form>
                </article>
            </section>
        </article>
    </section>
</main>
</body>
</html>
