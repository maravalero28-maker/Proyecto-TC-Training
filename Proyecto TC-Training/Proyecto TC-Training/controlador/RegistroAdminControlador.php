<?php
session_start();

include "../configuracion/conecxion.php";
include "../modelo/Administrador.php";

$conexion = new Conexion();
$conexion = $conexion->getConexion();
$mensaje = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $usuario = trim($_POST["usuario"] ?? "");
    $password = trim($_POST["password"] ?? "");
    $pregunta = trim($_POST["pregunta_secreta"] ?? "");
    $respuesta = trim($_POST["respuesta_secreta"] ?? "");

    if (empty($usuario) || empty($password) || empty($pregunta) || empty($respuesta)) {
        $mensaje = '<article class="alert alert-warning">⚠️ Por favor, rellena todos los campos</article>';
    } else {
        $admin = new Administrador($conexion);
        $resultado = $admin->registrar($usuario, $password, $pregunta, $respuesta);

        $alertas = [
            "success" => '<article class="alert alert-success">✅ Administrador registrado con éxito. <a href="Login.php">Iniciar sesión</a></article>',
            "error"   => '<article class="alert alert-danger">❌ Error al registrar en la base de datos</article>',
            "existe"  => '<article class="alert alert-warning">⚠️ El usuario ya existe. Elige otro nombre</article>',
            "warning" => '<article class="alert alert-warning">⚠️ Por favor, rellena todos los campos</article>'
        ];
        $mensaje = $alertas[$resultado] ?? '<article class="alert alert-danger">❌ Error desconocido</article>';

        if ($resultado === 'success') {
            header('Location: ../vista/Login.php?info=registrado');
            exit();
        }
    }
}

include "../vista/registro_admin.php";
?>
