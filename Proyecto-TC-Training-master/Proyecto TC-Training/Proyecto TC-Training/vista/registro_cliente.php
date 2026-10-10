<?php
if (!isset($mensaje)) {
    header("Location: ../controlador/ClienteController.php?action=nuevo");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Registro de Clientes - TC</title>
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
                <h1 class="font-lexend">Inscripción de Cliente</h1>
                <p>Completa los datos para registrar un nuevo cliente.</p>
            </header>

            <form id="registro-cliente-form" action="../controlador/ClienteController.php" method="POST" novalidate>
                <article class="form-grid">
                    <fieldset class="form-group">
                        <label for="nombre" class="form-label">Nombre</label>
                        <input type="text" id="nombre" name="nombre" class="form-control" placeholder="Nombre">
                        <p class="error-msg">Este campo es obligatorio.</p>
                    </fieldset>

                    <fieldset class="form-group">
                        <label for="apellido" class="form-label">Apellido</label>
                        <input type="text" id="apellido" name="apellido" class="form-control" placeholder="Apellido">
                        <p class="error-msg">Este campo es obligatorio.</p>
                    </fieldset>

                    <fieldset class="form-group">
                        <label for="telefono" class="form-label">Teléfono</label>
                        <input type="text" id="telefono" name="telefono" class="form-control" placeholder="Solo números"
                               oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                        <p class="error-msg">Ingresa un teléfono válido.</p>
                    </fieldset>

                    <fieldset class="form-group">
                        <label for="fecha" class="form-label">Fecha de Ingreso</label>
                        <input type="date" id="fecha" name="fecha" class="form-control">
                        <p class="error-msg">Selecciona una fecha.</p>
                    </fieldset>

                    <fieldset class="form-group">
                        <label for="estatus" class="form-label">Estatus</label>
                        <select id="estatus" name="estatus" class="form-control">
                            <option value="">Seleccionar</option>
                            <option value="activo">Activo</option>
                            <option value="inactivo">Inactivo</option>
                        </select>
                        <p class="error-msg">Selecciona un estatus.</p>
                    </fieldset>

                    <fieldset class="form-group">
                        <label for="solvencia" class="form-label">Solvencia</label>
                        <select id="solvencia" name="solvencia" class="form-control">
                            <option value="">Seleccionar</option>
                            <option value="solvente">Solvente</option>
                            <option value="insolvente">Insolvente</option>
                        </select>
                        <p class="error-msg">Selecciona solvencia.</p>
                    </fieldset>

                    <fieldset class="form-group">
                        <label for="plan" class="form-label">Plan</label>
                        <select id="plan" name="plan" class="form-control">
                            <option value="">Ninguno (sin plan)</option>
                            <?php if (!empty($planes)): ?>
                                <?php foreach($planes as $pl): ?>
                                    <option value="<?php echo $pl['id_plan']; ?>"><?php echo htmlspecialchars($pl['nombre']); ?> - $<?php echo number_format($pl['precio'],2,',','.'); ?> (<?php echo intval($pl['dias']); ?> días)</option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                        <p class="error-msg">Selecciona un plan si aplica.</p>
                    </fieldset>
                </article>

                <button type="submit" name="btnregistrar" class="btn btn-primary login-btn">Registrar Cliente</button>
            </form>

            <footer class="login-footer">
                <a href="../controlador/ClienteController.php" class="link-back">
                    <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M19 12H5M12 19l-7-7 7-7"></path></svg>
                    Volver al Directorio
                </a>
            </footer>
        </section>
    </main>

    <script>
    document.getElementById('registro-cliente-form').addEventListener('submit', function(e) {
        
        let inputs = this.querySelectorAll('.form-control:not(#plan)');
        let valid = true;

        inputs.forEach(input => {
            let errorMsg = input.nextElementSibling;
            if (input.value.trim() === "") {
                input.classList.add('error');
                if (errorMsg && errorMsg.classList.contains('error-msg')) {
                    errorMsg.classList.add('show');
                }
                valid = false;
            } else {
                input.classList.remove('error');
                if (errorMsg && errorMsg.classList.contains('error-msg')) {
                    errorMsg.classList.remove('show');
                }
            }
        });

        if (!valid) {
            e.preventDefault();
        }
    });
    </script>
</body>
</html>
