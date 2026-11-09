<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../vista/Login.php");
    exit();
}
require_once __DIR__ . '/permisos.php';
requerirModulo('nomina');

require_once "../configuracion/conecxion.php";
require_once "../modelo/ModelNomina.php";
require_once "../modelo/Entrenador.php";
require_once "../modelo/ModelTasa.php";
require_once "../modelo/ModelFondo.php";

$database = new Conexion();
$db = $database->getConexion();
$objNomina = new ModelNomina($db);
$objEntrenador = new Entrenador($db);
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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btnnomina'])) {
    $id_entrenador = $_POST['id_entrenador'] ?? '';
    $id_admin = $_SESSION['admin_id'];
    $rawMonto = trim($_POST['monto'] ?? '');
    $monto = str_replace([' ', ','], ['', '.'], $rawMonto);
    $fecha = $_POST['fecha_pago'] ?? date('Y-m-d');
    $concepto = trim($_POST['concepto'] ?? 'Pago de Nómina / Honorarios');
    $id_metodo = !empty($_POST['id_metodo']) ? intval($_POST['id_metodo']) : null;
    $id_cuenta = !empty($_POST['id_cuenta']) ? intval($_POST['id_cuenta']) : null;

    if (!is_numeric($monto) || floatval($monto) <= 0 || empty($id_entrenador)) {
        $mensaje = '<article class="alert alert-warning">Por favor completa los campos obligatorios y verifica que el monto sea mayor a 0.</article>';
        $entrenadores = $objEntrenador->obtenerTodos('activo');
        $id_entrenadorValor = $id_entrenador;
        $idMetodoValor = $id_metodo;
        $idCuentaValor = $id_cuenta;
        $montoValor = htmlspecialchars($rawMonto);
        $fechaValor = htmlspecialchars($fecha);
        include "../vista/registro_nomina.php";
        exit();
    }

    // La cuenta es obligatoria, se convierte a dólares y debe tener saldo suficiente
    $validacion = $objFondo->validarEgreso($cuentasList, $id_cuenta, floatval($monto), $fecha);
    if ($validacion['error'] !== '') {
        $mensaje = '<article class="alert alert-warning">' . htmlspecialchars($validacion['error']) . '</article>';
        $entrenadores = $objEntrenador->obtenerTodos('activo');
        $id_entrenadorValor = $id_entrenador;
        $idMetodoValor = $id_metodo;
        $idCuentaValor = $id_cuenta;
        $montoValor = $rawMonto;
        $fechaValor = $fecha;
        $conceptoValor = $concepto;
        include "../vista/registro_nomina.php";
        exit();
    }

    $resultado = $objNomina->registrar($id_entrenador, $id_admin, floatval($monto), $fecha, $concepto, $id_metodo, $id_cuenta, $validacion['moneda'], $validacion['tasa'], $validacion['monto_usd']);
    if ($resultado === 'success') {
        header("Location: ../controlador/NominaController.php?msj=creado");
        exit();
    }
    $mensaje = '<article class="alert alert-danger">Error al registrar el pago de nómina.</article>';
    $entrenadores = $objEntrenador->obtenerTodos('activo');
    include "../vista/registro_nomina.php";
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btncambiarestadonomina'])) {
    $id_nomina = intval($_POST['id_nomina'] ?? 0);
    $nuevoEstado = $_POST['nuevo_estado'] ?? '';
    $nominaActual = $objNomina->obtenerPorId($id_nomina);

    if (!$nominaActual || !in_array($nuevoEstado, ['0', '1'], true)) {
        header("Location: ../controlador/NominaController.php?msj=error");
        exit();
    }

    // Al habilitar un pago de nómina vuelve a descontarse de su cuenta, así que debe alcanzar el saldo
    if ($nuevoEstado === '0' && !empty($nominaActual['id_cuenta'])) {
        $errorSaldo = $objFondo->verificarSaldoEgreso($cuentasList, $nominaActual['id_cuenta'], floatval($nominaActual['monto']), 'pago_nomina', $id_nomina);
        if ($errorSaldo !== '') {
            header("Location: ../controlador/NominaController.php?msj=sin_saldo");
            exit();
        }
    }

    if ($objNomina->cambiarEstado($id_nomina, $nuevoEstado)) {
        header("Location: ../controlador/NominaController.php?msj=" . ($nuevoEstado === '1' ? 'inhabilitado' : 'habilitado'));
        exit();
    }

    header("Location: ../controlador/NominaController.php?msj=error");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btnactualizarnomina'])) {
    $id_nomina = $_POST['id_nomina'] ?? 0;
    $id_entrenador = $_POST['id_entrenador'] ?? '';
    $rawMonto = trim($_POST['monto'] ?? '');
    $monto = str_replace([' ', ','], ['', '.'], $rawMonto);
    $fecha = $_POST['fecha_pago'] ?? date('Y-m-d');
    $concepto = trim($_POST['concepto'] ?? '');
    $id_metodo = !empty($_POST['id_metodo']) ? intval($_POST['id_metodo']) : null;
    $id_cuenta = !empty($_POST['id_cuenta']) ? intval($_POST['id_cuenta']) : null;

    if (!is_numeric($monto) || floatval($monto) <= 0) {
        header("Location: ../controlador/NominaController.php?msj=error");
        exit();
    }

    $nominaActual = $objNomina->obtenerPorId($id_nomina);
    $moneda = $nominaActual['moneda'] ?? null;
    $tasa = $nominaActual['tasa'] ?? null;
    $montoUsd = $nominaActual['monto_usd'] ?? null;

    // Solo se reconvierte y se revisa el saldo si cambia algo que afecta el valor del pago
    $cambioFinanciero = !$nominaActual
        || round(floatval($nominaActual['monto']), 2) != round(floatval($monto), 2)
        || $nominaActual['fecha_pago'] !== $fecha
        || (int) ($nominaActual['id_cuenta'] ?? 0) !== (int) ($id_cuenta ?? 0);

    if ($cambioFinanciero || empty($id_cuenta)) {
        $validacion = $objFondo->validarEgreso($cuentasList, $id_cuenta, floatval($monto), $fecha, 'pago_nomina', $id_nomina);
        if ($validacion['error'] !== '') {
            $mensaje = '<article class="alert alert-warning">' . htmlspecialchars($validacion['error']) . '</article>';
            $nomina = $nominaActual ?: [];
            $nomina['id_nomina'] = $id_nomina;
            $nomina['id_entrenador'] = $id_entrenador;
            $nomina['monto'] = $rawMonto;
            $nomina['fecha_pago'] = $fecha;
            $nomina['concepto'] = $concepto;
            $entrenadores = $objEntrenador->obtenerTodos('todos');
            $idMetodoValor = $id_metodo;
            $idCuentaValor = $id_cuenta;
            include "../vista/editar_nomina.php";
            exit();
        }
        $moneda = $validacion['moneda'];
        $tasa = $validacion['tasa'];
        $montoUsd = $validacion['monto_usd'];
    }

    $actualizado = $objNomina->actualizar($id_nomina, $id_entrenador, floatval($monto), $fecha, $concepto, $id_metodo, $id_cuenta, $moneda, $tasa, $montoUsd);
    if ($actualizado) {
        header("Location: ../controlador/NominaController.php?msj=actualizado");
        exit();
    } else {
        header("Location: ../controlador/NominaController.php?msj=error");
        exit();
    }
}

if (isset($_GET['action']) && $_GET['action'] === 'nuevo') {
    $entrenadores = $objEntrenador->obtenerTodos('activo');
    $montoValor = '';
    $fechaValor = date('Y-m-d');
    $conceptoValor = 'Pago de Nómina / Honorarios';
    $idMetodoValor = '';
    $idCuentaValor = '';
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
    $idMetodoValor = $nomina['id_metodo'] ?? '';
    $idCuentaValor = $nomina['id_cuenta'] ?? '';
    include "../vista/editar_nomina.php";
    exit();
}

if (isset($_GET['msj'])) {
    if ($_GET['msj'] === 'creado') {
        $mensaje = '<article class="alert alert-success">Pago de nómina registrado correctamente.</article>';
        $showMessageCard = false;
    } elseif ($_GET['msj'] === 'actualizado') {
        $mensaje = '<article class="alert alert-success">Pago de nómina actualizado correctamente.</article>';
    } elseif ($_GET['msj'] === 'habilitado') {
        $mensaje = '<article class="alert alert-success">Pago de nómina habilitado correctamente.</article>';
    } elseif ($_GET['msj'] === 'inhabilitado') {
        $mensaje = '<article class="alert alert-success">Pago de nómina inhabilitado correctamente. El registro se conservó.</article>';
    } elseif ($_GET['msj'] === 'sin_saldo') {
        $mensaje = '<article class="alert alert-warning">No se puede habilitar este pago: la cuenta no tiene saldo suficiente.</article>';
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
    $sql = "SELECT id_entrenador, MONTH(fecha_pago) AS mes, SUM(COALESCE(monto_usd, monto)) AS total, COUNT(id_nomina) AS count_payments FROM pago_nomina";
    $params = [];
    $conds = ["anulado = 0"];
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

///////////////////////////////////////////////////
// BLOQUE AJAX (Al final, cuando ya existen las variables calculadas)
////////////////////////////////////////////////
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
////////////////////////////////////////

include "../vista/nomina.php";
?>