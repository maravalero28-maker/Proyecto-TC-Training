<?php
if (!isset($mensaje)) {
    header("Location: ../controlador/EntrenadorController.php?action=nuevo");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Registro de Entrenador - TC</title>
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
                <h1 class="font-lexend">Registro de Entrenador</h1>
                <p>Completa los datos para registrar un nuevo instructor.</p>
            </header>
            <form id="registro-entrenador-form" action="../controlador/EntrenadorController.php" method="POST" novalidate>
                <article class="form-grid">
                    <fieldset class="form-group">
                        <label for="cedula" class="form-label">Cédula</label>
                        <input type="text" id="cedula" name="cedula" class="form-control" placeholder="Cédula de identidad" oninput="this.value = this.value.replace(/[^0-9]/g, '')" required>
                        <p class="error-msg">La cédula es obligatoria.</p>
                    </fieldset>
                    <fieldset class="form-group">
                        <label for="nombre" class="form-label">Nombre</label>
                        <input type="text" id="nombre" name="nombre" class="form-control" placeholder="Nombre" required>
                        <p class="error-msg">El nombre es obligatorio.</p>
                    </fieldset>
                    <fieldset class="form-group">
                        <label for="apellido" class="form-label">Apellido</label>
                        <input type="text" id="apellido" name="apellido" class="form-control" placeholder="Apellido" required>
                        <p class="error-msg">El apellido es obligatorio.</p>
                    </fieldset>
                    <fieldset class="form-group">
                        <label for="telefono" class="form-label">Teléfono</label>
                        <input type="text" id="telefono" name="telefono" class="form-control" placeholder="Teléfono" oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                    </fieldset>
                    <fieldset class="form-group">
                        <label for="estatus" class="form-label">Estatus</label>
                        <select id="estatus" name="estatus" class="form-control">
                            <option value="activo">Activo</option>
                            <option value="inactivo">Inactivo</option>
                        </select>
                    </fieldset>
                </article>
                <button type="submit" name="btnregistrar" class="btn btn-primary login-btn">Registrar Entrenador</button>
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