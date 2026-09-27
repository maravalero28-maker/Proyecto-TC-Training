<?php
session_start();
include '../configuracion/conecxion.php';
include '../modelo/Administrador.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $usuarioInput = trim($_POST['username']);
    $passInput    = trim($_POST['password']);

    if (empty($usuarioInput) || empty($passInput)) {
        header("Location: ../vista/Login.php?error=campos_vacios");
        exit();
    }

    $db = new Conexion();
    $con = $db->getConexion();
    $adminModel = new Administrador($con);
    
    $resultado = $adminModel->verificarCredenciales($usuarioInput, $passInput);
    
    switch($resultado) {
        case "correcto":
            
            $stmt = $con->prepare("SELECT id_admin, Usuario FROM administrador WHERE Usuario = ?");
            $stmt->execute([$usuarioInput]);
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $_SESSION['admin_id'] = $admin['id_admin'];
            $_SESSION['admin_user'] = $admin['Usuario'];
            
            header("Location: ../controlador/InicioController.php");
            exit();
            
        case "no_existe":
            header("Location: ../vista/Login.php?error=usuario_no_existe");
            exit();
            
        case "password_incorrecta":
            header("Location: ../vista/Login.php?error=password_incorrecta");
            exit();
            
        default:
            header("Location: ../vista/Login.php?error=db");
            exit();
    }
}

header("Location: ../vista/Login.php");
exit();
?>
