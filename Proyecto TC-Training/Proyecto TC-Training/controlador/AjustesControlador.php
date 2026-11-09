<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../vista/Login.php");
    exit();
}
require_once __DIR__ . '/permisos.php';
requerirModulo('ajustes');

include "../configuracion/conecxion.php";
include "../modelo/Configuracion.php";
include "../modelo/Administrador.php";
include "../modelo/Rol.php";

$conexion = new Conexion();
$db = $conexion->getConexion();
$configModel = new Configuracion($db);
$adminModel = new Administrador($db);
$rolModel = new Rol($db);

$mensaje = null;

if (isset($_SESSION['flash'])) {
    $mensaje = $_SESSION['flash'];
    unset($_SESSION['flash']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

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

$esAdmin = esAdmin();
$admins = $esAdmin ? $adminModel->obtenerTodos() : [];
$roles = $esAdmin ? $rolModel->obtenerTodos() : [];

include "../vista/ajustes.php";
?>
