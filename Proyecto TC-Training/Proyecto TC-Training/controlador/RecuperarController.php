<?php
session_start();

include "../configuracion/conecxion.php";
include "../modelo/Administrador.php";

$conexion = new Conexion();
$db = $conexion->getConexion();
$adminModel = new Administrador($db);

$mensaje = '';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $step = $_POST['step'] ?? '1';

    if ($step === '1') {
        if (empty($username)) {
            $mensaje = 'Ingresa tu usuario.';
            include "../vista/recuperar.php";
            exit();
        }
        $user = $adminModel->obtenerPorUsuario($username);
        if (!$user) {
            $mensaje = 'Usuario no encontrado.';
            include "../vista/recuperar.php";
            exit();
        }
        
        $pregunta = $user['PreguntaSecreta'] ?? '';
        include "../vista/recuperar.php";
        exit();
    }

    if ($step === '2') {
        $username = trim($_POST['username'] ?? '');
        $respuesta = trim($_POST['respuesta'] ?? '');
        $newpass = trim($_POST['newpass'] ?? '');

        $user = $adminModel->obtenerPorUsuario($username);
        if (!$user) {
            $mensaje = 'Usuario no encontrado.';
            include "../vista/recuperar.php";
            exit();
        }

        if (empty($respuesta) || empty($newpass)) {
            $mensaje = 'Completa la respuesta y la nueva contraseña.';
            $pregunta = $user['PreguntaSecreta'] ?? '';
            include "../vista/recuperar.php";
            exit();
        }

        
        if (!password_verify($respuesta, $user['RespuestaSecreta'])) {
            $mensaje = 'Respuesta secreta incorrecta.';
            $pregunta = $user['PreguntaSecreta'] ?? '';
            include "../vista/recuperar.php";
            exit();
        }

        
        if ($adminModel->actualizarContrasennaPorId($user['id_admin'], $newpass)) {
            $mensaje = 'Contraseña actualizada. Ahora puedes iniciar sesión.';
            header('Location: ../vista/Login.php?msg=recuperado');
            exit();
        } else {
            $mensaje = 'No se pudo actualizar la contraseña.';
            include "../vista/recuperar.php";
            exit();
        }
    }

}


include "../vista/recuperar.php";

?>
