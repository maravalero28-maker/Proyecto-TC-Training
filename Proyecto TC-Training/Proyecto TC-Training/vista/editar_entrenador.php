<?php
if (!isset($entrenador)) {
    header("Location: ../controlador/EntrenadorController.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Editar Entrenador - TC</title>
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
                <h1 class="font-lexend">Editar Entrenador</h1>
                <p>Modifica los datos del instructor.</p>
            </header>
            <form action="../controlador/EntrenadorController.php" method="POST" novalidate>
                <input type="hidden" name="cedula_original" value="<?php echo htmlspecialchars($entrenador['cedula']); ?>">
                <article class="form-grid">
                    <fieldset class="form-group">
                        <label for="cedula" class="form-label">Cédula</label>
                        <input type="text" id="cedula" name="cedula" class="form-control" value="<?php echo htmlspecialchars($entrenador['cedula']); ?>" required>
                    </fieldset>
                    <fieldset class="form-group">
                        <label for="nombre" class="form-label">Nombre</label>
                        <input type="text" id="nombre" name="nombre" class="form-control" value="<?php echo htmlspecialchars($entrenador['nombre']); ?>" required>
                    </fieldset>
                    <fieldset class="form-group">
                        <label for="apellido" class="form-label">Apellido</label>
                        <input type="text" id="apellido" name="apellido" class="form-control" value="<?php echo htmlspecialchars($entrenador['apellido']); ?>" required>
                    </fieldset>
                    <fieldset class="form-group">
                        <label for="telefono" class="form-label">Teléfono</label>
                        <input type="text" id="telefono" name="telefono" class="form-control" value="<?php echo htmlspecialchars($entrenador['telefono']); ?>">
                    </fieldset>
                    <fieldset class="form-group">
                        <label for="estatus" class="form-label">Estatus</label>
                        <select id="estatus" name="estatus" class="form-control">
                            <option value="activo" <?php echo strtolower($entrenador['estatus']) === 'activo' ? 'selected' : ''; ?>>Activo</option>
                            <option value="inactivo" <?php echo strtolower($entrenador['estatus']) === 'inactivo' ? 'selected' : ''; ?>>Inactivo</option>
                        </select>
                    </fieldset>
                </article>
                <button type="submit" name="btnactualizar" class="btn btn-primary login-btn">Actualizar Entrenador</button>
            </form>
            <footer class="login-footer">
                <a href="../controlador/EntrenadorController.php" class="link-back">
                    <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M19 12H5M12 19l-7-7 7-7"></path></svg>
                    Volver al Directorio
                </a>
            </footer>
        </section>
    </main>
</body>
</html>