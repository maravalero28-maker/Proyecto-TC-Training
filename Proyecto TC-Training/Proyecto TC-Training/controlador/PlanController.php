<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../vista/Login.php");
    exit();
}

require_once "../configuracion/conecxion.php";
require_once "../modelo/Plan.php";

$db = (new Conexion())->getConexion();
$objPlan = new Plan($db);

$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btnGuardarPlan'])) {
    $id = intval($_POST['id_plan'] ?? 0);
    $nombre = trim($_POST['nombre'] ?? '');
    $precio = trim(str_replace(',', '.', $_POST['precio'] ?? '0'));
    $dias = intval($_POST['dias'] ?? 0);

    $errors = [];
    if ($nombre === '') {
        $errors[] = 'El nombre del plan es obligatorio.';
    }
    if ($precio === '' || !is_numeric($precio) || floatval($precio) <= 0) {
        $errors[] = 'El precio del plan debe ser un número mayor a 0.';
    }
    if ($dias <= 0) {
        $errors[] = 'Los días de vigencia deben ser un número mayor a 0.';
    }

    if (!empty($errors)) {
        $mensaje = ['text' => implode(' ', $errors), 'type' => 'error'];
        $plan = [
            'id_plan' => $id,
            'nombre' => $nombre,
            'precio' => $precio,
            'dias' => $dias,
        ];
        include "../vista/plan_form.php";
        exit();
    }

    if ($id) {
        $ok = $objPlan->actualizar($id, $nombre, $precio, $dias);
        $mensajeText = $ok ? 'Plan actualizado' : 'Error al actualizar el plan';
        if (!$ok && $objPlan->getLastError()) {
            $mensajeText .= ' (' . htmlspecialchars($objPlan->getLastError()) . ')';
        }
    } else {
        $ok = $objPlan->registrar($nombre, $precio, $dias);
        $mensajeText = $ok ? 'Plan creado' : 'Error al crear plan';
        if (!$ok && $objPlan->getLastError()) {
            $mensajeText .= ' (' . htmlspecialchars($objPlan->getLastError()) . ')';
        }
    }

    $tipo = $ok ? 'success' : 'error';
    header('Location: ../controlador/PlanController.php?msj=' . urlencode($mensajeText) . '&type=' . $tipo);
    exit();
}

if (isset($_GET['action']) && $_GET['action'] === 'nuevo') {
    include "../vista/plan_form.php";
    exit();
}

if (isset($_GET['action']) && $_GET['action'] === 'editar' && isset($_GET['id'])) {
    $plan = $objPlan->obtenerPorId($_GET['id']);
    if (!$plan) {
        header('Location: ../controlador/PlanController.php?msj=' . urlencode('Plan no encontrado') . '&type=error');
        exit();
    }
    include "../vista/plan_form.php";
    exit();
}

if (isset($_GET['action']) && $_GET['action'] === 'eliminar' && isset($_GET['id'])) {
    $objPlan->eliminar($_GET['id']);
    header('Location: ../controlador/PlanController.php?msj=' . urlencode('Plan eliminado') . '&type=success');
    exit();
}

if (isset($_GET['msj'])) {
    $mensaje = [
        'text' => htmlspecialchars($_GET['msj']),
        'type' => in_array($_GET['type'] ?? '', ['success', 'error', 'warning', 'info']) ? $_GET['type'] : 'success',
    ];
}

$planes = $objPlan->obtenerTodos();
include "../vista/planes.php";
?>