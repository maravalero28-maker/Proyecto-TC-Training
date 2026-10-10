<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Recuperar contraseña</title>
    <link rel="stylesheet" href="../estilos.css">
    <link rel="stylesheet" href="../responsive.css">
</head>
<body>
    <main class="login-wrapper">
        <section class="login-card">
            <header class="login-header">
                <h1>Recuperar contraseña</h1>
                <p>Ingresa tu usuario para ver la pregunta secreta y recuperar la contraseña.</p>
            </header>

            <?php if (!empty($mensaje)): ?>
                <section class="alert alert-warning"><?php echo htmlspecialchars($mensaje); ?></section>
            <?php endif; ?>

            <?php if (isset($pregunta) && !empty($pregunta) && isset($_POST['step']) && $_POST['step'] === '1'): ?>
                <form action="../controlador/RecuperarController.php" method="post">
                    <input type="hidden" name="step" value="2">
                    <input type="hidden" name="username" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                    <fieldset class="form-group">
                        <label class="form-label">Pregunta:</label>
                        <section class="form-control muted-bg"><?php echo htmlspecialchars($pregunta); ?></section>
                    </fieldset>
                    <fieldset class="form-group">
                        <label for="respuesta">Respuesta</label>
                        <input type="text" name="respuesta" class="form-control" required>
                    </fieldset>
                    <fieldset class="form-group">
                        <label for="newpass">Nueva contraseña</label>
                        <input type="password" name="newpass" class="form-control" required>
                    </fieldset>
                    <button type="submit" class="btn btn-primary">Restablecer contraseña</button>
                </form>
            <?php else: ?>
                <form action="../controlador/RecuperarController.php" method="post">
                    <input type="hidden" name="step" value="1">
                    <fieldset class="form-group">
                        <label for="username">Usuario</label>
                        <input type="text" name="username" class="form-control" required>
                    </fieldset>
                    <button type="submit" class="btn btn-primary">Continuar</button>
                </form>
            <?php endif; ?>

            <footer class="login-footer">
                <a href="Login.php">Volver al login</a>
            </footer>
        </section>
    </main>
</body>
</html>
