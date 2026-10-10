<?php
session_start();

if (isset($_SESSION['admin_id'])) {
    header("Location: ../controlador/InicioController.php");
    exit();
}

require_once __DIR__ . '/../configuracion/conecxion.php';
require_once __DIR__ . '/../modelo/Administrador.php';
$dbCount = 0;
try {
    $conn = new Conexion();
    $pdo = $conn->getConexion();
    $adminModelForView = new Administrador($pdo);
    $allAdmins = $adminModelForView->obtenerTodos();
    $dbCount = count($allAdmins);
} catch (Exception $e) {
    $dbCount = 0;
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>TC Training</title>
    <link rel="stylesheet" href="../estilos.css"> 
    <link rel="stylesheet" href="../responsive.css">
</head>

<body>

    <main class="login-wrapper">
        <canvas id="particles-canvas"></canvas>

        <section class="login-card">
                <header class="login-header">
                <img src="../Imagenes/472842978_1608453203376849_549310859470506009_n.jpg" alt="Logo" class="logo-large">
                <h1 class="font-lexend">TC-Control</h1>
                <p>Ingresa tus credenciales para acceder al sistema.</p>
            </header>

            
            <?php if (isset($_GET['error'])): ?>
                <section class="alert alert-error">
                    <?php 
                    switch($_GET['error']) {
                        case 'campos_vacios':
                            echo "Por favor, completa todos los campos.";
                            break;
                        case 'usuario_no_existe':
                            
                            echo "El usuario <strong>" . htmlspecialchars($_GET['user'] ?? '') . "</strong> no existe.";
                            break;
                        case 'password_incorrecta':
                            echo "Contraseña incorrecta. Inténtalo nuevamente.";
                            break;
                        default:
                            echo "Error al iniciar sesión. Inténtalo nuevamente.";
                    }
                    ?>
                </section>
            <?php endif; ?>

            
            <?php if (isset($_GET['info'])): ?>
                <?php if ($_GET['info'] === 'registrado'): ?>
                    <section class="alert alert-success">
                        Administrador registrado con éxito. Ahora puedes iniciar sesión.
                    </section>
                <?php else: ?>
                    <section class="alert alert-info">
                        <?php echo htmlspecialchars($_GET['info']); ?>
                    </section>
                <?php endif; ?>
            <?php endif; ?>

            <form id="login-form" action="../controlador/Login.php" method="post" novalidate>
                <fieldset class="form-group">
                    <label for="username" class="form-label">Usuario</label>
                    <input type="text" id="username" name="username" class="form-control"
                        placeholder="Nombre de usuario">
                    <p class="error-msg">Este campo es obligatorio.</p>
                </fieldset>

                <fieldset class="form-group">
                    <label for="password" class="form-label">Contraseña</label>
                    <input type="password" id="password" name="password" class="form-control" placeholder="••••••••">
                    <p class="error-msg">Este campo es obligatorio.</p>
                </fieldset>

                <button type="submit" class="btn btn-primary login-btn">Iniciar Sesión</button>
            </form>
            <footer class="login-footer">
                <?php if ($dbCount === 0): ?>
                    <p>¿No tienes cuenta? <a href="../controlador/RegistroAdminControlador.php" class="link-modern">Regístrate aquí</a></p>
                <?php else: ?>
                    <p><a href="../controlador/RecuperarController.php" class="link-modern">¿Olvidó su contraseña?</a></p>
                <?php endif; ?>
            </footer>
        </section>

    </main>
</body>

</html>
