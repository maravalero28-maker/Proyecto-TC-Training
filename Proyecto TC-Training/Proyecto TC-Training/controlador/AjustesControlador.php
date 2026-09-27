<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../vista/Login.php");
    exit();
}

include "../configuracion/conecxion.php";
include "../modelo/Configuracion.php";
include "../modelo/Administrador.php";

$conexion = new Conexion();
$db = $conexion->getConexion();
$configModel = new Configuracion($db);
$adminModel = new Administrador($db);

$mensaje = null;


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    if (isset($_POST['delete_admin_id']) && !empty($_POST['delete_admin_id'])) {
        $idEliminar = intval($_POST['delete_admin_id']);
        if ($adminModel->eliminar($idEliminar)) {
            $mensaje = "Usuario eliminado correctamente.";
        } else {
            $mensaje = "No se pudo eliminar el usuario.";
        }
    }

    $nuevoNombre = $_POST['gym-name'];
    $vencDias = isset($_POST['vencimiento-dias']) ? intval($_POST['vencimiento-dias']) : 7;
    $ingresosPeriodo = $_POST['ingresos-periodo'] ?? 'semanal';
    $ingresosPeriodo = in_array($ingresosPeriodo, ['semanal', 'mensual', 'anual'], true) ? $ingresosPeriodo : 'semanal';

    
    $actualConfig = $configModel->obtenerConfiguracion();
    $tarifaActual = $actualConfig['tarifa_base'] ?? 30.00;
    $monedaActual = $actualConfig['moneda'] ?? 'USD';

    if ($configModel->actualizarConfiguracion($nuevoNombre, $tarifaActual, $monedaActual, $vencDias, $ingresosPeriodo)) {
        $mensaje = ['text' => 'Ajustes guardados correctamente.', 'type' => 'success'];
    } else {
        $mensaje = ['text' => 'Error al guardar los ajustes.', 'type' => 'error'];
    }
}


$config = $configModel->obtenerConfiguracion();


$admins = $adminModel->obtenerTodos();


include "../vista/ajustes.php";
?>