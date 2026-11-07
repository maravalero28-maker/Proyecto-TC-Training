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
require_once "../modelo/ModelTasa.php";

$database = new Conexion();
$db = $database->getConexion();
$objPago = new ModelPago($db);
$objTasa = new ModelTasa($db);
$objCliente = new Cliente($db);
$objPlan = new Plan($db);
$planes = $objPlan->obtenerTodos();

// Plan que corresponde al pago: el seleccionado en el formulario o, si no se elige ninguno, el actual del cliente
function obtenerPlanDelPago($objPago, $planes, $id_cliente, $plan_id) {
    $buscar = $plan_id;
    if ($buscar === '' || $buscar === null) {
        $buscar = '';
        foreach ($objPago->obtenerClientes(false) as $cli) {
            if ($cli['id_cliente'] == $id_cliente) {
                $buscar = $cli['Plan'] ?? '';
                break;
            }
        }
    }
    foreach ($planes as $p) {
        if ((string) $p['id_plan'] === (string) $buscar || strcasecmp($p['nombre'], (string) $buscar) === 0) {
            return $p;
        }
    }
    return null;
}

// Convierte el monto a dólares según la moneda de la cuenta receptora (sin cuenta = USD).
// Para cuentas en BS exige la tasa registrada exactamente para la fecha del pago.
function convertirMontoPago($objTasa, $cuentasList, $id_cuenta, $monto, $fecha) {
    $moneda = 'USD';
    if (!empty($id_cuenta)) {
        foreach ($cuentasList as $c) {
            if ($c['id_cuenta'] == $id_cuenta) {
                $moneda = strtoupper(trim($c['tipo_moneda']));
                break;
            }
        }
    }

    $conv = [
        'moneda' => ($moneda === 'BS') ? 'BS' : 'USD',
        'tasa' => null,
        'fecha' => $fecha,
        'monto_usd' => round($monto, 2),
        'usd_exacto' => $monto,
        'error' => ''
    ];
    if ($moneda !== 'BS') {
        return $conv;
    }

    $tasa = $objTasa->obtenerExacta($fecha);
    if (!$tasa) {
        $conv['error'] = 'No hay una tasa registrada para el ' . date('d/m/Y', strtotime($fecha)) . '. Regístrala en Fondos > Tasa del Día para poder registrar pagos en bolívares.';
        return $conv;
    }

    $conv['tasa'] = $tasa;
    $conv['usd_exacto'] = $monto / $tasa;
    $conv['monto_usd'] = round($monto / $tasa, 2);
    return $conv;
}

// Verifica que el pago equivalga al precio del plan.
// Devuelve ['error' => texto o vacío, 'monto_usd' => equivalente en dólares a guardar]
function validarMontoContraPlan($objPago, $planes, $id_cliente, $plan_id, $conv) {
    $plan = obtenerPlanDelPago($objPago, $planes, $id_cliente, $plan_id);
    if (!$plan) {
        return ['error' => 'El cliente no tiene un plan válido asignado. Selecciona un plan para registrar el pago.', 'monto_usd' => $conv['monto_usd']];
    }

    $precio = floatval($plan['precio']);
    // En bolívares se tolera un centavo de dólar por el redondeo de la tasa
    $tolerancia = ($conv['moneda'] === 'BS') ? 0.01 : 0.004;

    if (abs($conv['usd_exacto'] - $precio) > $tolerancia) {
        if ($conv['moneda'] === 'BS') {
            $texto = 'El monto debe equivaler al precio del plan "' . $plan['nombre'] . '": $' . number_format($precio, 2, ',', '.')
                   . '. Con la tasa del ' . date('d/m/Y', strtotime($conv['fecha'])) . ' (Bs ' . number_format($conv['tasa'], 2, ',', '.') . ' por $) son Bs '
                   . number_format($precio * $conv['tasa'], 2, ',', '.') . '.';
        } else {
            $texto = 'El monto debe ser igual al precio del plan "' . $plan['nombre'] . '": $' . number_format($precio, 2, ',', '.') . '.';
        }
        return ['error' => $texto, 'monto_usd' => $conv['monto_usd']];
    }

    return ['error' => '', 'monto_usd' => round($precio, 2)];
}

// Obtener métodos de pago y cuentas desde la BD
$metodosPagoList = [];
try {
    $stmtMetodos = $db->query("SELECT id_metodo, nombre FROM metodo_pago");
    $metodosPagoList = $stmtMetodos->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {}

$cuentasList = [];
try {
    $stmtCuentas = $db->query("SELECT c.id_cuenta, c.num_cuenta, c.tipo_moneda, b.nombre AS banco_nombre FROM cuenta c INNER JOIN banco b ON c.id_banco = b.id_banco");
    $cuentasList = $stmtCuentas->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {}

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
    $id_metodo = !empty($_POST['id_metodo']) ? intval($_POST['id_metodo']) : null;
    $id_cuenta = !empty($_POST['id_cuenta']) ? intval($_POST['id_cuenta']) : null;
    $plan_id = $_POST['plan_id'] ?? '';

    if (!is_numeric($monto) || floatval($monto) <= 0) {
        $mensaje = '<article class="alert alert-warning">El monto debe ser un número mayor que 0.</article>';
        $clientes = $objPago->obtenerClientes();
        $id_clienteValor = $id_cliente;
        $plan_idValor = $plan_id;
        $idMetodoValor = $id_metodo;
        $idCuentaValor = $id_cuenta;
        $montoValor = htmlspecialchars($rawMonto);
        $fechaValor = htmlspecialchars($fecha);
        include "../vista/registropago.php";
        exit();
    }

    $conv = convertirMontoPago($objTasa, $cuentasList, $id_cuenta, floatval($monto), $fecha);
    $errorPlan = $conv['error'];
    $montoUsd = $conv['monto_usd'];
    if ($errorPlan === '') {
        $validacion = validarMontoContraPlan($objPago, $planes, $id_cliente, $plan_id, $conv);
        $errorPlan = $validacion['error'];
        $montoUsd = $validacion['monto_usd'];
    }
    if ($errorPlan !== '') {
        $mensaje = '<article class="alert alert-warning">' . htmlspecialchars($errorPlan) . '</article>';
        $clientes = $objPago->obtenerClientes();
        $id_clienteValor = $id_cliente;
        $plan_idValor = $plan_id;
        $idMetodoValor = $id_metodo;
        $idCuentaValor = $id_cuenta;
        $montoValor = htmlspecialchars($rawMonto);
        $fechaValor = htmlspecialchars($fecha);
        include "../vista/registropago.php";
        exit();
    }

    $resultado = $objPago->registrar($id_cliente, floatval($monto), $fecha, $id_metodo, $id_cuenta, $conv['moneda'], $conv['tasa'], $montoUsd);

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
    $idMetodoValor = $id_metodo;
    $idCuentaValor = $id_cuenta;
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
    $id_metodo = !empty($_POST['id_metodo']) ? intval($_POST['id_metodo']) : null;
    $id_cuenta = !empty($_POST['id_cuenta']) ? intval($_POST['id_cuenta']) : null;
    $plan_id = $_POST['plan_id'] ?? '';

    if (!is_numeric($monto) || floatval($monto) <= 0) {
        header("Location: ../controlador/pagoscontrolador.php?msj=error");
        exit();
    }

    $pagoActual = $objPago->obtenerPorId($id_pago);
    $moneda = $pagoActual['moneda'] ?? null;
    $tasa = $pagoActual['tasa'] ?? null;
    $montoUsd = $pagoActual['monto_usd'] ?? null;

    // Solo se revalida y reconvierte si cambia algo que afecta el valor del pago
    $cambioFinanciero = !$pagoActual
        || round(floatval($pagoActual['Monto']), 2) != round(floatval($monto), 2)
        || $pagoActual['Fecha'] !== $fecha
        || (int) ($pagoActual['id_cuenta'] ?? 0) !== (int) ($id_cuenta ?? 0);
    $debeValidar = $cambioFinanciero
        || (int) $pagoActual['id_cliente'] !== (int) $id_cliente
        || $plan_id !== '';

    if ($debeValidar) {
        $conv = convertirMontoPago($objTasa, $cuentasList, $id_cuenta, floatval($monto), $fecha);
        $errorPlan = $conv['error'];
        $montoUsd = $conv['monto_usd'];
        if ($errorPlan === '') {
            $validacion = validarMontoContraPlan($objPago, $planes, $id_cliente, $plan_id, $conv);
            $errorPlan = $validacion['error'];
            $montoUsd = $validacion['monto_usd'];
        }
        if ($errorPlan !== '') {
            $mensaje = '<article class="alert alert-warning">' . htmlspecialchars($errorPlan) . '</article>';
            $clientes = $objPago->obtenerClientes(false);
            $id_pagoValor = $id_pago;
            $id_clienteValor = $id_cliente;
            $plan_idValor = $plan_id;
            $montoValor = $rawMonto;
            $fechaValor = $fecha;
            $idMetodoValor = $id_metodo;
            $idCuentaValor = $id_cuenta;
            include "../vista/editar_pago.php";
            exit();
        }
        $moneda = $conv['moneda'];
        $tasa = $conv['tasa'];
    }

    $actualizado = $objPago->actualizar($id_pago, $id_cliente, floatval($monto), $fecha, $id_metodo, $id_cuenta, $moneda, $tasa, $montoUsd);
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
    $idMetodoValor = '';
    $idCuentaValor = '';
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
    $montoValor = $pago['Monto'] ?? $pago['monto'];
    $fechaValor = $pago['Fecha'] ?? $pago['fecha'];
    $idMetodoValor = $pago['id_metodo'] ?? '';
    $idCuentaValor = $pago['id_cuenta'] ?? '';
    $plan_idValor = '';
    include "../vista/editar_pago.php";
    exit();
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

if (isset($_GET['ajax']) && $_GET['ajax']) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'pagos' => $pagos,
        'clientsList' => $clientsList,
        'year' => $year,
        'filters' => $filters
    ]);
    exit();
}

include "../vista/pagos.php";
?>
