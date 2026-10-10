<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../vista/Login.php");
    exit();
}

require_once "../configuracion/conecxion.php";
require_once "../modelo/Cliente.php";
require_once "../modelo/ModelPago.php";

$conexion = new Conexion();
$db = $conexion->getConexion();

$config = $db->query("SELECT * FROM configuracion WHERE id = 1")->fetch(PDO::FETCH_ASSOC);
if (!$config) {
    $config = [
        'nombre_empresa' => 'TC-Control',
        'moneda' => 'USD',
        'tarifa_base' => 0,
    ];
}

$clienteModel = new Cliente($db);
$pagoModel = new ModelPago($db);
$clienteModel->actualizarSolvenciaPorVencimiento();

$totalActivos = $db->query("SELECT COUNT(*) FROM cliente WHERE estatus = 'Activo'")->fetchColumn();
$totalInsolventes = $db->query("SELECT COUNT(*) FROM cliente WHERE estatus = 'Activo' AND Solvencia != 'Solvente'")->fetchColumn();

if (!isset($config['ingresos_periodo']) || !in_array($config['ingresos_periodo'], ['semanal', 'mensual', 'anual'], true)) {
    $config['ingresos_periodo'] = 'semanal';
}
$ingresosPeriodo = $config['ingresos_periodo'];
$periodNames = [
    'semanal' => 'Semanal',
    'mensual' => 'Mensual',
    'anual' => 'Anual'
];
$periodDisplay = $periodNames[$ingresosPeriodo];

$hoy = new DateTime();
$inicioPeriodo = clone $hoy;
$finPeriodo = clone $hoy;

switch ($ingresosPeriodo) {
    case 'mensual':
        $inicioPeriodo->modify('first day of this month');
        $finPeriodo->modify('last day of this month');
        break;
    case 'anual':
        $inicioPeriodo->modify('first day of January this year');
        $finPeriodo->modify('last day of December this year');
        break;
    case 'semanal':
    default:
        $inicioPeriodo->modify('monday this week');
        $finPeriodo->modify('sunday this week');
        break;
}

$stmtIngresos = $db->prepare("SELECT COALESCE(SUM(Monto), 0) AS total FROM pagos WHERE Fecha BETWEEN ? AND ?");
$stmtIngresos->execute([$inicioPeriodo->format('Y-m-d'), $finPeriodo->format('Y-m-d')]);
$ingresosEstimados = (float) $stmtIngresos->fetchColumn();


$stmtPagos = $db->prepare("SELECT p.id_pago, p.id_cliente, p.Monto, p.Fecha, p.MetodoPago, CONCAT(c.Nombre, ' ', c.apellido) AS NombreCliente, c.Plan, c.Solvencia FROM pagos p INNER JOIN cliente c ON p.id_cliente = c.id_cliente WHERE p.Fecha BETWEEN ? AND ? ORDER BY p.Fecha ASC");
$stmtPagos->execute([$inicioPeriodo->format('Y-m-d'), $finPeriodo->format('Y-m-d')]);
$pagosPeriodo = $stmtPagos->fetchAll(PDO::FETCH_ASSOC);

$metodoTotales = [];
$ingresosDiarios = [];
foreach ($pagosPeriodo as $pago) {
    $metodo = trim($pago['MetodoPago'] ?: 'Otros');
    $monto = (float) $pago['Monto'];
    $metodoTotales[$metodo] = ($metodoTotales[$metodo] ?? 0) + $monto;
    if (!empty($pago['Fecha'])) {
        $ingresosDiarios[$pago['Fecha']] = ($ingresosDiarios[$pago['Fecha']] ?? 0) + $monto;
    }
}

$solvencyDistribution = [
    'Solvente' => 0,
    'Insolvente' => 0,
    'En Mora' => 0,
    'Inactivo' => 0
];
try {
    $stmtSolvencia = $db->query("SELECT Solvencia, COUNT(*) AS total FROM cliente GROUP BY Solvencia");
    $solvData = $stmtSolvencia->fetchAll(PDO::FETCH_ASSOC);
    foreach ($solvData as $row) {
        $key = trim($row['Solvencia']);
        if ($key === '') {
            $key = 'Inactivo';
        }
        if (!isset($solvencyDistribution[$key])) {
            $solvencyDistribution[$key] = 0;
        }
        $solvencyDistribution[$key] += (int) $row['total'];
    }
} catch (PDOException $e) {
    
}

$reportData = [
    'period' => $periodDisplay,
    'pagos' => $pagosPeriodo,
    'methodTotals' => $metodoTotales,
    'dailyTotals' => $ingresosDiarios,
    'solvencyDistribution' => $solvencyDistribution
];


$configRow = $db->query("SELECT vencimiento_dias FROM configuracion WHERE id = 1")->fetch(PDO::FETCH_ASSOC);
$vencDias = $configRow['vencimiento_dias'] ?? 7;

$clientesAll = $clienteModel->obtenerClientes();
$proxVencimientos = [];
$hoy = new DateTime();

foreach ($clientesAll as $c) {
    if (strtolower($c['estatus']) !== 'activo') {
        continue;
    }
    $ultimoPago = $pagoModel->obtenerUltimoPagoPorCliente($c['id_cliente']);
    $baseDate = $ultimoPago ? new DateTime($ultimoPago) : new DateTime($c['fecha_inscripcion']);

    try {
        $stmt = $db->prepare("SELECT dias FROM planes WHERE id_plan = ? LIMIT 1");
        $stmt->execute([$c['Plan']]);
        $planRow = $stmt->fetch(PDO::FETCH_ASSOC);
        $planDias = $planRow ? intval($planRow['dias']) : 30;
    } catch (PDOException $e) {
        $planDias = 30;
    }

    $nextDue = clone $baseDate;
    $nextDue->modify("+{$planDias} days");

    $secondsDiff = $nextDue->getTimestamp() - $hoy->getTimestamp();
    $daysDiff = (int) floor($secondsDiff / 86400);
    $hoursDiff = $secondsDiff > 0 ? (int) ceil($secondsDiff / 3600) : 0;

    if ($secondsDiff >= 0 && $daysDiff <= (int)$vencDias) {
        $proxVencimientos[] = [
            'id_cliente' => $c['id_cliente'],
            'nombre' => $c['Nombre'] . ' ' . $c['apellido'],
            'next_due' => $nextDue->format('Y-m-d'),
            'days' => $daysDiff,
            'hours' => $hoursDiff,
            'seconds' => $secondsDiff
        ];
    }
}

include "../vista/inicio.php";
