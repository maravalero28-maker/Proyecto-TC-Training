<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Registro Administrador - TC-Control</title>
    <link rel="stylesheet" href="../estilos.css">
    <link rel="stylesheet" href="../responsive.css">
    <script src="../jv/script.js" defer></script>
</head>
<body>
    <main class="login-wrapper">
        <?php include __DIR__ . '/partes/toast-notification.php'; ?>
        <section class="login-card">
            <header class="login-header">
                <h1 class="font-lexend">Nuevo Administrador</h1>
                <p>Crea una cuenta de administrador para acceder al sistema.</p>
            </header>

            <form action="../controlador/RegistroAdminControlador.php" method="POST">

                <fieldset class="form-group">
                    <label for="usuario" class="form-label">Usuario</label>
                    <input type="text" name="usuario" class="form-control" placeholder="Nombre de usuario" required>
                </fieldset>
                
                <fieldset class="form-group">
                    <label for="password" class="form-label">Contraseña</label>
                    <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                </fieldset>

                <fieldset class="form-group">
                    <label for="pregunta_secreta" class="form-label">Pregunta secreta</label>
                    <select name="pregunta_secreta" class="form-control" required>
                        <option value="">Seleccione una pregunta</option>
                        <option value="¿Cuál es el nombre de tu mascota?">¿Cuál es el nombre de tu mascota?</option>
                        <option value="¿Cuál es el nombre de tu madre?">¿Cuál es el nombre de tu madre?</option>
                        <option value="¿Cuál es tu ciudad de nacimiento?">¿Cuál es tu ciudad de nacimiento?</option>
                        <option value="¿Cuál es tu comida favorita?">¿Cuál es tu comida favorita?</option>
                    </select>
                </fieldset>

                <fieldset class="form-group">
                    <label for="respuesta_secreta" class="form-label">Respuesta secreta</label>
                    <input type="text" name="respuesta_secreta" class="form-control" placeholder="Respuesta" required>
                </fieldset>

                
                <button type="submit" name="btnregistrar" value="Registrar" class="btn btn-primary login-btn">Registrar Usuario</button>
            </form>
            
            <footer class="login-footer">
                <a href="Login.php" class="link-back link-back-center">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="18" height="18" aria-hidden="true">
                        <path d="M15 18l-6-6 6-6" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    Volver al Inicio
                </a>
            </footer>
        </section>
    </main>
</body>
</html>
