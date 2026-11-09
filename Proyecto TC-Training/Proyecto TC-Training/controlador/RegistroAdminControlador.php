<?php
session_start();

include "../configuracion/conecxion.php";
include "../modelo/Administrador.php";
include "../modelo/Rol.php";
require_once __DIR__ . '/permisos.php';

$conexion = new Conexion();
$conexion = $conexion->getConexion();
$admin = new Administrador($conexion);
new Rol($conexion);
$mensaje = "";

// Si ya hay usuarios, solo un administrador con sesión puede registrar más.
// Si no hay ninguno, el primero que se registra queda como administrador.
$hayUsuarios = $admin->contar() > 0;
if ($hayUsuarios) {
    if (!isset($_SESSION['admin_id'])) {
        header("Location: ../vista/Login.php");
        exit();
    }
    requerirAdmin();
}
$idRol = $hayUsuarios ? 2 : 1;
$urlVolver = $hayUsuarios ? "../controlador/InicioController.php" : "../vista/Login.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $usuario = trim($_POST["usuario"] ?? "");
    $password = trim($_POST["password"] ?? "");
    $pregunta = trim($_POST["pregunta_secreta"] ?? "");
    $respuesta = trim($_POST["respuesta_secreta"] ?? "");

    if (empty($usuario) || empty($password) || empty($pregunta) || empty($respuesta)) {
        $mensaje = '<article class="alert alert-warning">⚠️ Por favor, rellena todos los campos</article>';
    } else {
        $resultado = $admin->registrar($usuario, $password, $pregunta, $respuesta, $idRol);

        $alertas = [
            "error"   => '<article class="alert alert-danger">❌ Error al registrar en la base de datos</article>',
            "existe"  => '<article class="alert alert-warning">⚠️ El usuario ya existe. Elige otro nombre</article>',
            "warning" => '<article class="alert alert-warning">⚠️ Por favor, rellena todos los campos</article>'
        ];

        if ($resultado === 'success') {
            if ($hayUsuarios) {
                $_SESSION['flash'] = ['text' => 'Usuario registrado como Recepcionista. Puedes cambiar su rol en Gestión de Usuarios.', 'type' => 'success'];
                header('Location: AjustesControlador.php?abrir=usuarios');
            } else {
                header('Location: ../vista/Login.php?info=registrado');
            }
            exit();
        }
        $mensaje = $alertas[$resultado] ?? '<article class="alert alert-danger">❌ Error desconocido</article>';
    }
}

include "../vista/registro_admin.php";
?>
