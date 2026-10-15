<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../vista/Login.php");
    exit();
}

require_once "../configuracion/conecxion.php";
require_once "../modelo/ModelPagoServicio.php";

$database = new Conexion();
$db = $database->getConexion();
$objServicio = new ModelPagoServicio($db);

$mensaje = '';
$showMessageCard = true;
$startDate = $_GET['start_date'] ?? '';
$endDate = $_GET['end_date'] ?? '';
$metodoPago = $_GET['metodo_pago'] ?? 'todos';
$search = $_GET['q'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btnservicio'])) {
    $id_admin = $_SESSION['admin_id'];
    $rawMonto = trim($_POST['monto'] ?? '');
    $monto = str_replace([' ', ','], ['', '.'], $rawMonto);
    $concepto = trim($_POST['concepto'] ?? '');
    $metodo_pago = $_POST['metodo_pago'] ?? 'Efectivo';
    $fecha = $_POST['fecha'] ?? date('Y-m-d');

    if (!is_numeric($monto) || floatval($monto) <= 0 || empty($concepto)) {
        $mensaje = '<article class="alert alert-warning">Completa el concepto y verifica que el monto sea mayor a 0.</article>';
        include "../vista/registro_servicio.php";
        exit();
    }

    $resultado = $objServicio->registrar(floatval($monto), $concepto, $metodo_pago, $fecha, $id_admin);
    if ($resultado === 'success') {
        header("Location: ../controlador/PagoServicioController.php?msj=creado");
        exit();
    }
    $mensaje = '<article class="alert alert-danger">Error al registrar el pago de servicio.</article>';
    include "../vista/registro_servicio.php";
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btnactualizarservicio'])) {
    $id = $_POST['id_pago_servicio'] ?? 0;
    $rawMonto = trim($_POST['monto'] ?? '');
    $monto = str_replace([' ', ','], ['', '.'], $rawMonto);
    $concepto = trim($_POST['concepto'] ?? '');
    $metodo_pago = $_POST['metodo_pago'] ?? 'Efectivo';
    $fecha = $_POST['fecha'] ?? date('Y-m-d');

    if (!is_numeric($monto) || floatval($monto) <= 0) {
        header("Location: ../controlador/PagoServicioController.php?msj=error");
        exit();
    }
    $actualizado = $objServicio->actualizar($id, floatval($monto), $concepto, $metodo_pago, $fecha);
    if ($actualizado) {
        header("Location: ../controlador/PagoServicioController.php?msj=actualizado");
        exit();
    } else {
        header("Location: ../controlador/PagoServicioController.php?msj=error");
        exit();
    }
}

if (isset($_GET['action']) && $_GET['action'] === 'eliminar' && isset($_GET['id'])) {
    if ($objServicio->eliminar(intval($_GET['id']))) {
        header("Location: ../controlador/PagoServicioController.php?msj=eliminado");
        exit();
    }
    header("Location: ../controlador/PagoServicioController.php?msj=error");
    exit();
}

if (isset($_GET['action']) && $_GET['action'] === 'nuevo') {
    $montoValor = '';
    $conceptoValor = '';
    $metodoValor = 'Efectivo';
    $fechaValor = date('Y-m-d');
    include "../vista/registro_servicio.php";
    exit();
}

if (isset($_GET['action']) && $_GET['action'] === 'editar' && isset($_GET['id'])) {
    $servicio = $objServicio->obtenerPorId($_GET['id']);
    if (!$servicio) {
        header("Location: ../controlador/PagoServicioController.php?msj=error");
        exit();
    }
    include "../vista/editar_servicio.php";
    exit();
}

if (isset($_GET['msj'])) {
    if ($_GET['msj'] === 'creado') {
        $mensaje = '<article class="alert alert-success">Pago de servicio registrado correctamente.</article>';
        $showMessageCard = false;
    } elseif ($_GET['msj'] === 'actualizado') {
        $mensaje = '<article class="alert alert-success">Pago de servicio actualizado correctamente.</article>';
    } elseif ($_GET['msj'] === 'eliminado') {
        $mensaje = '<article class="alert alert-success">Pago de servicio eliminado correctamente.</article>';
    } elseif ($_GET['msj'] === 'error') {
        $mensaje = '<article class="alert alert-danger">Ocurrió un error al procesar la solicitud.</article>';
    }
}

$year = isset($_GET['year']) ? intval($_GET['year']) : (int)date('Y');
$filters = ['start_date' => $startDate, 'end_date' => $endDate, 'metodo_pago' => $metodoPago];
$pagosServicios = $objServicio->obtenerTodos($search, $year, $filters);

// Matriz de servicios por mes del año actual
$serviciosMatrix = [];
try {
    $sql = "SELECT MONTH(fecha) AS mes, SUM(monto) AS total, COUNT(id_pago_servicio) AS count_payments FROM pago_servicio";
    $params = [];
    if (!empty($year)) {
        $sql .= " WHERE YEAR(fecha) = ?";
        $params[] = $year;
    }
    $sql .= " GROUP BY MONTH(fecha)";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $r) {
        $serviciosMatrix[intval($r['mes'])] = [
            'count' => intval($r['count_payments']),
            'total' => floatval($r['total'])
        ];
    }
} catch (PDOException $e) {
    $serviciosMatrix = [];
}

if (isset($_GET['ajax']) && $_GET['ajax']) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'pagos' => $pagosServicios,
        'serviciosMatrix' => $serviciosMatrix,
        'year' => $year,
        'filters' => $filters
    ]);
    exit();
}

include "../vista/servicios.php";
?>