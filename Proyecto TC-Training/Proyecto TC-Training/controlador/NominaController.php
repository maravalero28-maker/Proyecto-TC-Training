<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../vista/Login.php");
    exit();
}

require_once "../configuracion/conecxion.php";
require_once "../modelo/ModelNomina.php";
require_once "../modelo/Entrenador.php";

$database = new Conexion();
$db = $database->getConexion();
$objNomina = new ModelNomina($db);
$objEntrenador = new Entrenador($db);

$mensaje = '';
$showMessageCard = true;
$startDate = $_GET['start_date'] ?? '';
$endDate = $_GET['end_date'] ?? '';
$metodoPago = $_GET['metodo_pago'] ?? 'todos';
$search = $_GET['q'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btnnomina'])) {
    $id_entrenador = $_POST['id_entrenador'] ?? '';
    $id_admin = $_SESSION['admin_id'];
    $rawMonto = trim($_POST['monto'] ?? '');
    $monto = str_replace([' ', ','], ['', '.'], $rawMonto);
    $fecha = $_POST['fecha_pago'] ?? date('Y-m-d');
    $concepto = trim($_POST['concepto'] ?? 'Pago de Nómina / Honorarios');
    $metodo_pago = $_POST['metodo_pago'] ?? 'Efectivo';

    $allowedMethods = ['Efectivo','Transferencia','Pago Móvil','Divisas'];
    if (!is_numeric($monto) || floatval($monto) <= 0 || empty($id_entrenador)) {
        $mensaje = '<article class="alert alert-warning">Por favor completa los campos obligatorios y verifica que el monto sea mayor a 0.</article>';
        $entrenadores = $objEntrenador->obtenerTodos('activo');
        include "../vista/registro_nomina.php";
        exit();
    }
    if (!in_array($metodo_pago, $allowedMethods)) {
        $metodo_pago = 'Efectivo';
    }

    $resultado = $objNomina->registrar($id_entrenador, $id_admin, floatval($monto), $fecha, $concepto, $metodo_pago);
    if ($resultado === 'success') {
        header("Location: ../controlador/NominaController.php?msj=creado");
        exit();
    }
    $mensaje = '<article class="alert alert-danger">Error al registrar el pago de nómina.</article>';
    $entrenadores = $objEntrenador->obtenerTodos('activo');
    include "../vista/registro_nomina.php";
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btnactualizarnomina'])) {
    $id_nomina = $_POST['id_nomina'] ?? 0;
    $id_entrenador = $_POST['id_entrenador'] ?? '';
    $rawMonto = trim($_POST['monto'] ?? '');
    $monto = str_replace([' ', ','], ['', '.'], $rawMonto);
    $fecha = $_POST['fecha_pago'] ?? date('Y-m-d');
    $concepto = trim($_POST['concepto'] ?? '');
    $metodo_pago = $_POST['metodo_pago'] ?? 'Efectivo';

    if (!is_numeric($monto) || floatval($monto) <= 0) {
        header("Location: ../controlador/NominaController.php?msj=error");
        exit();
    }
    $actualizado = $objNomina->actualizar($id_nomina, $id_entrenador, floatval($monto), $fecha, $concepto, $metodo_pago);
    if ($actualizado) {
        header("Location: ../controlador/NominaController.php?msj=actualizado");
        exit();
    } else {
        header("Location: ../controlador/NominaController.php?msj=error");
        exit();
    }
}

if (isset($_GET['action']) && $_GET['action'] === 'eliminar' && isset($_GET['id'])) {
    if ($objNomina->eliminar(intval($_GET['id']))) {
        header("Location: ../controlador/NominaController.php?msj=eliminado");
        exit();
    }
    header("Location: ../controlador/NominaController.php?msj=error");
    exit();
}

if (isset($_GET['action']) && $_GET['action'] === 'nuevo') {
    $entrenadores = $objEntrenador->obtenerTodos('activo');
    $montoValor = '';
    $fechaValor = date('Y-m-d');
    $conceptoValor = 'Pago de Nómina / Honorarios';
    $metodoValor = '';
    $id_entrenadorValor = '';
    include "../vista/registro_nomina.php";
    exit();
}

if (isset($_GET['action']) && $_GET['action'] === 'editar' && isset($_GET['id'])) {
    $nomina = $objNomina->obtenerPorId($_GET['id']);
    if (!$nomina) {
        header("Location: ../controlador/NominaController.php?msj=error");
        exit();
    }
    $entrenadores = $objEntrenador->obtenerTodos('todos');
    include "../vista/editar_nomina.php";
    exit();
}

if (isset($_GET['msj'])) {
    if ($_GET['msj'] === 'creado') {
        $mensaje = '<article class="alert alert-success">Pago de nómina registrado correctamente.</article>';
        $showMessageCard = false;
    } elseif ($_GET['msj'] === 'actualizado') {
        $mensaje = '<article class="alert alert-success">Pago de nómina actualizado correctamente.</article>';
    } elseif ($_GET['msj'] === 'eliminado') {
        $mensaje = '<article class="alert alert-success">Pago de nómina eliminado correctamente.</article>';
    } elseif ($_GET['msj'] === 'error') {
        $mensaje = '<article class="alert alert-danger">Ocurrió un error al procesar la solicitud.</article>';
    }
}

$year = isset($_GET['year']) ? intval($_GET['year']) : (int)date('Y');
$filters = ['start_date' => $startDate, 'end_date' => $endDate, 'metodo_pago' => $metodoPago];
$pagosNomina = $objNomina->obtenerTodos($search, $year, $filters);
$entrenadoresList = $objEntrenador->obtenerTodos('todos');

// Generar matriz de pagos de nómina por mes para cada entrenador
$nominaMatrix = [];
try {
    $sql = "SELECT id_entrenador, MONTH(fecha_pago) AS mes, SUM(monto) AS total, COUNT(id_nomina) AS count_payments FROM pago_nomina";
    $params = [];
    $conds = [];
    if (!empty($year)) {
        $conds[] = "YEAR(fecha_pago) = ?";
        $params[] = $year;
    }
    if (!empty($conds)) {
        $sql .= ' WHERE ' . implode(' AND ', $conds);
    }
    $sql .= ' GROUP BY id_entrenador, MONTH(fecha_pago)';
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $r) {
        $nominaMatrix[$r['id_entrenador']][intval($r['mes'])] = [
            'count' => intval($r['count_payments']),
            'total' => floatval($r['total'])
        ];
    }
} catch (PDOException $e) {
    $nominaMatrix = [];
}

if (isset($_GET['ajax']) && $_GET['ajax']) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'pagos' => $pagosNomina,
        'entrenadoresList' => $entrenadoresList,
        'nominaMatrix' => $nominaMatrix,
        'year' => $year,
        'filters' => $filters
    ]);
    exit();
}

include "../vista/nomina.php";
?>