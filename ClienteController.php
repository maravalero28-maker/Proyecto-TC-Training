<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../vista/Login.php");
    exit();
}

require_once "../configuracion/conecxion.php";
require_once "../modelo/Cliente.php";
require_once "../modelo/Plan.php";

$database = new Conexion();
$db = $database->getConexion();
$objCliente = new Cliente($db);
$objPlan = new Plan($db);

$mensaje = '';
$status = $_GET['status'] ?? 'todos';
$solvencia = $_GET['solvencia'] ?? 'todos';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btnregistrar'])) {
    $resultado = $objCliente->registrar(
        $_POST['nombre'] ?? '',
        $_POST['apellido'] ?? '',
        $_POST['telefono'] ?? '',
        $_POST['fecha'] ?? date('Y-m-d'),
        $_POST['estatus'] ?? '',
        $_POST['solvencia'] ?? '',
        $_POST['plan'] ?? ''
    );

    if ($resultado === 'success') {
        header("Location: ../controlador/ClienteController.php?msj=creado");
        exit();
    }

    $alertas = [
        "success" => '<article class="alert alert-success">Cliente registrado exitosamente en Training Club.</article>',
        "error"   => '<article class="alert alert-danger">Error al guardar: Verifica la conexión a tc_control</article>',
        "warning" => '<article class="alert alert-warning">Por favor completa los campos principales</article>'
    ];
    $mensaje = $alertas[$resultado] ?? '<article class="alert alert-danger">Error desconocido.</article>';
    include "../vista/registro_cliente.php";
    exit();
}

if (isset($_POST['btnactualizar'])) {
    $id = $_POST['id_cliente'] ?? 0;
    $resultado = $objCliente->actualizar(
        $id,
        $_POST['nombre'] ?? '',
        $_POST['apellido'] ?? '',
        $_POST['telefono'] ?? '',
        $_POST['fecha'] ?? date('Y-m-d'),
        $_POST['estatus'] ?? '',
        $_POST['solvencia'] ?? '',
        $_POST['plan'] ?? ''
    );

    if ($resultado) {
        header("Location: ../controlador/ClienteController.php?msj=actualizado");
        exit();
    } else {
        header("Location: ../controlador/ClienteController.php?msj=error");
        exit();
    }
}

if (isset($_GET['action']) && $_GET['action'] === 'nuevo') {
    $planes = $objPlan->obtenerTodos();
    include "../vista/registro_cliente.php";
    exit();
}

if (isset($_GET['action']) && $_GET['action'] === 'editar' && isset($_GET['id'])) {
    $id = $_GET['id'];
    $cliente = $objCliente->obtenerPorId($id);
    if (!$cliente) {
        header("Location: ../controlador/ClienteController.php?msj=error");
        exit();
    }
    $planes = $objPlan->obtenerTodos();
    include "../vista/editar_cliente.php";
    exit();
}

if (isset($_GET['action']) && $_GET['action'] === 'eliminar' && isset($_GET['id'])) {
    $objCliente->eliminar($_GET['id']);
    header("Location: ../controlador/ClienteController.php?msj=eliminado");
    exit();
}

if (isset($_GET['msj'])) {
    if ($_GET['msj'] === 'creado') {
        $mensaje = '<article class="alert alert-success">Cliente registrado exitosamente. </article>';
    } elseif ($_GET['msj'] === 'eliminado') {
        $mensaje = '<article class="alert alert-success">Cliente eliminado correctamente.</article>';
    } elseif ($_GET['msj'] === 'error') {
        $mensaje = '<article class="alert alert-danger">Ocurrió un error al procesar la solicitud.</article>';
    } elseif ($_GET['msj'] === 'actualizado') {
        $mensaje = '<article class="alert alert-success">Cliente actualizado correctamente.</article>';
    }
}

$objCliente->actualizarSolvenciaPorVencimiento();
$clientes = $objCliente->obtenerTodosConDiasRestantes($status, $solvencia);
$planes = $objPlan->obtenerTodos();
$planMap = [];
foreach ($planes as $plan) {
    $planMap[$plan['id_plan']] = $plan['nombre'];
}

$planEtiquetas = [
    'basico' => 'Semanal',
    'premium' => 'Mensual',
    'vip' => 'Anual',
];

include "../vista/clientes.php";
