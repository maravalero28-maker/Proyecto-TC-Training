<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../vista/Login.php");
    exit();
}

require_once "../configuracion/conecxion.php";
require_once "../modelo/ModelPagoServicio.php";
require_once "../modelo/ModelTasa.php";
require_once "../modelo/ModelFondo.php";

$database = new Conexion();
$db = $database->getConexion();
$objServicio = new ModelPagoServicio($db);
$objFondo = new ModelFondo($db);

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
$startDate = $_GET['start_date'] ?? '';
$endDate = $_GET['end_date'] ?? '';
$metodoPago = $_GET['metodo_pago'] ?? 'todos';
$search = $_GET['q'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btnservicio'])) {
    $id_admin = $_SESSION['admin_id'];
    $rawMonto = trim($_POST['monto'] ?? '');
    $monto = str_replace([' ', ','], ['', '.'], $rawMonto);
    $concepto = trim($_POST['concepto'] ?? '');
    $fecha = $_POST['fecha'] ?? date('Y-m-d');
    $id_metodo = !empty($_POST['id_metodo']) ? intval($_POST['id_metodo']) : null;
    $id_cuenta = !empty($_POST['id_cuenta']) ? intval($_POST['id_cuenta']) : null;

    if (!is_numeric($monto) || floatval($monto) <= 0 || empty($concepto)) {
        $mensaje = '<article class="alert alert-warning">Completa el concepto y verifica que el monto sea mayor a 0.</article>';
        $montoValor = htmlspecialchars($rawMonto);
        $conceptoValor = htmlspecialchars($concepto);
        $fechaValor = htmlspecialchars($fecha);
        $idMetodoValor = $id_metodo;
        $idCuentaValor = $id_cuenta;
        include "../vista/registro_servicio.php";
        exit();
    }

    // La cuenta es obligatoria, se convierte a dólares y debe tener saldo suficiente
    $validacion = $objFondo->validarEgreso($cuentasList, $id_cuenta, floatval($monto), $fecha);
    if ($validacion['error'] !== '') {
        $mensaje = '<article class="alert alert-warning">' . htmlspecialchars($validacion['error']) . '</article>';
        $montoValor = $rawMonto;
        $conceptoValor = $concepto;
        $fechaValor = $fecha;
        $idMetodoValor = $id_metodo;
        $idCuentaValor = $id_cuenta;
        include "../vista/registro_servicio.php";
        exit();
    }

    $resultado = $objServicio->registrar(floatval($monto), $concepto, $id_metodo, $id_cuenta, $fecha, $id_admin, $validacion['moneda'], $validacion['tasa'], $validacion['monto_usd']);
    if ($resultado === 'success') {
        header("Location: ../controlador/PagoServicioController.php?msj=creado");
        exit();
    }
    $mensaje = '<article class="alert alert-danger">Error al registrar el pago de servicio.</article>';
    include "../vista/registro_servicio.php";
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btncambiarestadoservicio'])) {
    $id = intval($_POST['id_pago_servicio'] ?? 0);
    $nuevoEstado = $_POST['nuevo_estado'] ?? '';
    $servicioActual = $objServicio->obtenerPorId($id);

    if (!$servicioActual || !in_array($nuevoEstado, ['0', '1'], true)) {
        header("Location: ../controlador/PagoServicioController.php?msj=error");
        exit();
    }

    // Al habilitar un pago de servicio vuelve a descontarse de su cuenta, así que debe alcanzar el saldo
    if ($nuevoEstado === '0' && !empty($servicioActual['id_cuenta'])) {
        $errorSaldo = $objFondo->verificarSaldoEgreso($cuentasList, $servicioActual['id_cuenta'], floatval($servicioActual['monto']), 'pago_servicio', $id);
        if ($errorSaldo !== '') {
            header("Location: ../controlador/PagoServicioController.php?msj=sin_saldo");
            exit();
        }
    }

    if ($objServicio->cambiarEstado($id, $nuevoEstado)) {
        header("Location: ../controlador/PagoServicioController.php?msj=" . ($nuevoEstado === '1' ? 'inhabilitado' : 'habilitado'));
        exit();
    }

    header("Location: ../controlador/PagoServicioController.php?msj=error");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btnactualizarservicio'])) {
    $id = $_POST['id_pago_servicio'] ?? 0;
    $rawMonto = trim($_POST['monto'] ?? '');
    $monto = str_replace([' ', ','], ['', '.'], $rawMonto);
    $concepto = trim($_POST['concepto'] ?? '');
    $fecha = $_POST['fecha'] ?? date('Y-m-d');
    $id_metodo = !empty($_POST['id_metodo']) ? intval($_POST['id_metodo']) : null;
    $id_cuenta = !empty($_POST['id_cuenta']) ? intval($_POST['id_cuenta']) : null;

    if (!is_numeric($monto) || floatval($monto) <= 0) {
        header("Location: ../controlador/PagoServicioController.php?msj=error");
        exit();
    }

    $servicioActual = $objServicio->obtenerPorId($id);
    $moneda = $servicioActual['moneda'] ?? null;
    $tasa = $servicioActual['tasa'] ?? null;
    $montoUsd = $servicioActual['monto_usd'] ?? null;

    // Solo se reconvierte y se revisa el saldo si cambia algo que afecta el valor del pago
    $cambioFinanciero = !$servicioActual
        || round(floatval($servicioActual['monto']), 2) != round(floatval($monto), 2)
        || $servicioActual['fecha'] !== $fecha
        || (int) ($servicioActual['id_cuenta'] ?? 0) !== (int) ($id_cuenta ?? 0);

    if ($cambioFinanciero || empty($id_cuenta)) {
        $validacion = $objFondo->validarEgreso($cuentasList, $id_cuenta, floatval($monto), $fecha, 'pago_servicio', $id);
        if ($validacion['error'] !== '') {
            $mensaje = '<article class="alert alert-warning">' . htmlspecialchars($validacion['error']) . '</article>';
            $servicio = $servicioActual ?: [];
            $servicio['id_pago_servicio'] = $id;
            $servicio['monto'] = $rawMonto;
            $servicio['concepto'] = $concepto;
            $servicio['fecha'] = $fecha;
            $idMetodoValor = $id_metodo;
            $idCuentaValor = $id_cuenta;
            include "../vista/editar_servicio.php";
            exit();
        }
        $moneda = $validacion['moneda'];
        $tasa = $validacion['tasa'];
        $montoUsd = $validacion['monto_usd'];
    }

    $actualizado = $objServicio->actualizar($id, floatval($monto), $concepto, $id_metodo, $id_cuenta, $fecha, $moneda, $tasa, $montoUsd);
    if ($actualizado) {
        header("Location: ../controlador/PagoServicioController.php?msj=actualizado");
        exit();
    } else {
        header("Location: ../controlador/PagoServicioController.php?msj=error");
        exit();
    }
}

if (isset($_GET['action']) && $_GET['action'] === 'nuevo') {
    $montoValor = '';
    $conceptoValor = '';
    $fechaValor = date('Y-m-d');
    $idMetodoValor = '';
    $idCuentaValor = '';
    include "../vista/registro_servicio.php";
    exit();
}

if (isset($_GET['action']) && $_GET['action'] === 'editar' && isset($_GET['id'])) {
    $servicio = $objServicio->obtenerPorId($_GET['id']);
    if (!$servicio) {
        header("Location: ../controlador/PagoServicioController.php?msj=error");
        exit();
    }
    $idMetodoValor = $servicio['id_metodo'] ?? '';
    $idCuentaValor = $servicio['id_cuenta'] ?? '';
    include "../vista/editar_servicio.php";
    exit();
}

if (isset($_GET['msj'])) {
    if ($_GET['msj'] === 'creado') {
        $mensaje = '<article class="alert alert-success">Pago de servicio registrado correctamente.</article>';
        $showMessageCard = false;
    } elseif ($_GET['msj'] === 'actualizado') {
        $mensaje = '<article class="alert alert-success">Pago de servicio actualizado correctamente.</article>';
    } elseif ($_GET['msj'] === 'habilitado') {
        $mensaje = '<article class="alert alert-success">Pago de servicio habilitado correctamente.</article>';
    } elseif ($_GET['msj'] === 'inhabilitado') {
        $mensaje = '<article class="alert alert-success">Pago de servicio inhabilitado correctamente. El registro se conservó.</article>';
    } elseif ($_GET['msj'] === 'sin_saldo') {
        $mensaje = '<article class="alert alert-warning">No se puede habilitar este pago: la cuenta no tiene saldo suficiente.</article>';
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
    $sql = "SELECT MONTH(fecha) AS mes, SUM(COALESCE(monto_usd, monto)) AS total, COUNT(id_pago_servicio) AS count_payments FROM pago_servicio WHERE anulado = 0";
    $params = [];
    if (!empty($year)) {
        $sql .= " AND YEAR(fecha) = ?";
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