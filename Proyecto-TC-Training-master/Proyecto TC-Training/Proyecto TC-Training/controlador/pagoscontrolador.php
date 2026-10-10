<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../vista/Login.php");
    exit();
}

require_once "../configuracion/conecxion.php";
require_once "../modelo/ModelPago.php";
require_once "../modelo/Cliente.php";
require_once "../modelo/Plan.php";

$database = new Conexion();
$db = $database->getConexion();
$objPago = new ModelPago($db);
$objCliente = new Cliente($db);
$objPlan = new Plan($db);
$planes = $objPlan->obtenerTodos();

$mensaje = '';
$showMessageCard = true;
$filtro = $_GET['filter'] ?? 'todos';
$search = $_GET['q'] ?? ($_GET['filter'] ?? 'todos');
$startDate = $_GET['start_date'] ?? '';
$endDate = $_GET['end_date'] ?? '';
$metodoPago = $_GET['metodo_pago'] ?? 'todos';
$solvenciaFilter = $_GET['solvencia'] ?? 'todos';
$planFilter = $_GET['plan'] ?? 'todos';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btnpago'])) {
    $id_cliente = $_POST['id_cliente'] ?? 0;
    $rawMonto = trim($_POST['monto'] ?? '');
    $monto = str_replace(' ', '', $rawMonto);
    if (strpos($monto, ',') !== false) {
        if (strpos($monto, '.') !== false) {
            $monto = str_replace('.', '', $monto);
        }
        $monto = str_replace(',', '.', $monto);
    }
    $fecha = $_POST['fecha'] ?? date('Y-m-d');
    $metodo_pago = $_POST['metodo_pago'] ?? null;
    $plan_id = $_POST['plan_id'] ?? '';

    
    $allowedMethods = ['Efectivo','Transferencia','Pago Móvil','Divisas'];
    if (!is_numeric($monto) || floatval($monto) <= 0) {
        $mensaje = '<article class="alert alert-warning">El monto debe ser un número mayor que 0.</article>';
        $clientes = $objPago->obtenerClientes();
        $id_clienteValor = $id_cliente;
        $plan_idValor = $plan_id;
        $metodoValor = $metodo_pago;
        $montoValor = htmlspecialchars($rawMonto);
        $fechaValor = htmlspecialchars($fecha);
        include "../vista/registropago.php";
        exit();
    }
    if (!in_array($metodo_pago, $allowedMethods)) {
        $metodo_pago = null; 
    }

    $resultado = $objPago->registrar($id_cliente, floatval($monto), $fecha, $metodo_pago);

    if ($resultado === 'success') {
        if (!empty($plan_id) && is_numeric($plan_id)) {
            $objCliente->actualizarPlan($id_cliente, intval($plan_id));
        }
        $objCliente->actualizarSolvencia($id_cliente, 'Solvente');
        header("Location: ../controlador/pagoscontrolador.php?msj=creado");
        exit();
    }

    $alertas = [
        "success" => '<article class="alert alert-success">Pago registrado correctamente.</article>',
        "error"   => '<article class="alert alert-danger">Error al guardar: ' . htmlspecialchars($objPago->getLastError() ?? 'Verifica la conexión a la base de datos.') . '</article>',
        "warning" => '<article class="alert alert-warning">Por favor completa los campos principales.</article>'
    ];
    $mensaje = $alertas[$resultado] ?? '<article class="alert alert-danger">Error desconocido.</article>';
    $clientes = $objPago->obtenerClientes();
    $id_clienteValor = $id_cliente;
    $plan_idValor = $plan_id;
    $metodoValor = $metodo_pago;
    $montoValor = htmlspecialchars($rawMonto);
    $fechaValor = htmlspecialchars($fecha);
    include "../vista/registropago.php";
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btnactualizarpago'])) {
    $id_pago = $_POST['id_pago'] ?? 0;
    $id_cliente = $_POST['id_cliente'] ?? 0;
    $rawMonto = trim($_POST['monto'] ?? '');
    $monto = str_replace(' ', '', $rawMonto);
    if (strpos($monto, ',') !== false) {
        if (strpos($monto, '.') !== false) {
            $monto = str_replace('.', '', $monto);
        }
        $monto = str_replace(',', '.', $monto);
    }
    $fecha = $_POST['fecha'] ?? date('Y-m-d');
    $metodo_pago = $_POST['metodo_pago'] ?? null;
    $plan_id = $_POST['plan_id'] ?? '';

    
    $allowedMethods = ['Efectivo','Transferencia','Pago Móvil','Divisas'];
    if (!is_numeric($monto) || floatval($monto) <= 0) {
        header("Location: ../controlador/pagoscontrolador.php?msj=error");
        exit();
    }
    if (!in_array($metodo_pago, $allowedMethods)) {
        $metodo_pago = null;
    }
    $actualizado = $objPago->actualizar($id_pago, $id_cliente, floatval($monto), $fecha, $metodo_pago);
    if ($actualizado) {
        if (!empty($plan_id) && is_numeric($plan_id)) {
            $objCliente->actualizarPlan($id_cliente, intval($plan_id));
        }
        $objCliente->actualizarSolvencia($id_cliente, 'Solvente');
        header("Location: ../controlador/pagoscontrolador.php?msj=actualizado");
        exit();
    } else {
        header("Location: ../controlador/pagoscontrolador.php?msj=error");
        exit();
    }
}


if (isset($_GET['action']) && $_GET['action'] === 'eliminar' && isset($_GET['id'])) {
    $id_pago = intval($_GET['id']);
    $deleted = $objPago->eliminar($id_pago);
    if ($deleted) {
        header("Location: ../controlador/pagoscontrolador.php?msj=eliminado");
        exit();
    } else {
        header("Location: ../controlador/pagoscontrolador.php?msj=error");
        exit();
    }
}

if (isset($_GET['action']) && $_GET['action'] === 'nuevo') {
    $clientes = $objPago->obtenerClientes();
    $id_clienteValor = isset($_GET['cliente']) ? intval($_GET['cliente']) : '';
    $plan_idValor = '';
    $montoValor = '';
    $fechaValor = date('Y-m-d');
    $metodoValor = '';
    include "../vista/registropago.php";
    exit();
}

if (isset($_GET['action']) && $_GET['action'] === 'editar' && isset($_GET['id'])) {
    $id_pago = $_GET['id'];
    $pago = $objPago->obtenerPorId($id_pago);
    if (!$pago) {
        header("Location: ../controlador/pagoscontrolador.php?msj=error");
        exit();
    }
    $clientes = $objPago->obtenerClientes(false);
    $id_pagoValor = $pago['id_pago'];
    $id_clienteValor = $pago['id_cliente'];
    $montoValor = $pago['Monto'];
    $fechaValor = $pago['Fecha'];
    $metodoValor = $pago['MetodoPago'] ?? '';
    $plan_idValor = '';
    include "../vista/editar_pago.php";
    exit();
}

if (isset($_GET['msj'])) {
    if ($_GET['msj'] === 'creado') {
        $mensaje = '<article class="alert alert-success">Pago registrado correctamente.</article>';
        $showMessageCard = false;
    } elseif ($_GET['msj'] === 'actualizado') {
        $mensaje = '<article class="alert alert-success">Pago actualizado correctamente.</article>';
    } elseif ($_GET['msj'] === 'error') {
        $mensaje = '<article class="alert alert-danger">Ocurrió un error al procesar la solicitud.</article>';
    } elseif ($_GET['msj'] === 'eliminado') {
        $mensaje = '<article class="alert alert-success">Pago eliminado correctamente.</article>';
    }
}

$year = isset($_GET['year']) ? intval($_GET['year']) : (int)date('Y');
$filters = [
    'start_date' => $startDate,
    'end_date' => $endDate,
    'metodo_pago' => $metodoPago,
    'solvencia' => $solvenciaFilter,
    'plan' => $planFilter
];
$pagos = $objPago->obtenerTodos($search, $year, $filters);
$clientsList = $objPago->obtenerClientes(true, $search);
$paymentsMatrix = [];
try {
    
    $sql = "SELECT p.id_cliente, MONTH(p.Fecha) AS mes, SUM(p.Monto) AS total, COUNT(p.id_pago) AS count_payments FROM pagos p INNER JOIN cliente c ON p.id_cliente = c.id_cliente";
    $params = [];
    $conds = [];

    if (!empty($filters['start_date'])) {
        $conds[] = "p.Fecha >= ?";
        $params[] = $filters['start_date'];
    }
    if (!empty($filters['end_date'])) {
        $conds[] = "p.Fecha <= ?";
        $params[] = $filters['end_date'];
    }
    if (!empty($filters['metodo_pago']) && $filters['metodo_pago'] !== 'todos') {
        $conds[] = "p.MetodoPago = ?";
        $params[] = $filters['metodo_pago'];
    }
    if (!empty($filters['plan']) && $filters['plan'] !== 'todos') {
        $conds[] = "c.Plan = ?";
        $params[] = $filters['plan'];
    }
    if (!empty($filters['solvencia']) && $filters['solvencia'] !== 'todos') {
        $conds[] = "c.Solvencia = ?";
        $params[] = $filters['solvencia'];
    }
    if (empty($filters['start_date']) && empty($filters['end_date']) && !empty($year)) {
        $conds[] = "YEAR(p.Fecha) = ?";
        $params[] = $year;
    }
    if (!empty($search) && $search !== 'todos') {
        $conds[] = "(c.Nombre LIKE ? OR c.apellido LIKE ? OR CONCAT(c.Nombre,' ',c.apellido) LIKE ?)";
        $params[] = "%{$search}%";
        $params[] = "%{$search}%";
        $params[] = "%{$search}%";
    }

    if (!empty($conds)) {
        $sql .= ' WHERE ' . implode(' AND ', $conds);
    }
    $sql .= ' GROUP BY p.id_cliente, MONTH(p.Fecha)';
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $r) {
        $paymentsMatrix[intval($r['id_cliente'])][intval($r['mes'])] = [
            'count' => intval($r['count_payments']),
            'total' => floatval($r['total'])
        ];
    }
} catch (PDOException $e) {
    $paymentsMatrix = [];
}

$solvencyDistribution = [
    'Solvente' => 0,
    'Insolvente' => 0,
    'En Mora' => 0,
    'Inactivo' => 0
];
try {
    $stmt = $db->query("SELECT Solvencia, COUNT(*) AS total FROM cliente GROUP BY Solvencia");
    $stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($stats as $row) {
        $key = trim($row['Solvencia']);
        if ($key === '') {
            $key = 'Inactivo';
        }
        $solvencyDistribution[$key] = intval($row['total']);
    }
} catch (PDOException $e) {
    
}


if (isset($_GET['ajax']) && $_GET['ajax']) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'pagos' => $pagos,
        'clientsList' => $clientsList,
        'paymentsMatrix' => $paymentsMatrix,
        'year' => $year,
        'filters' => $filters,
        'solvencyDistribution' => $solvencyDistribution
    ]);
    exit();
}

include "../vista/pagos.php";
?>
