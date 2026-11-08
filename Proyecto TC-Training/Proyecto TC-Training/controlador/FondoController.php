<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../vista/Login.php");
    exit();
}

require_once "../configuracion/conecxion.php";
require_once "../modelo/ModelFondo.php";
require_once "../modelo/ModelTasa.php";

$database = new Conexion();
$db = $database->getConexion();
$objFondo = new ModelFondo($db);
$objTasa = new ModelTasa($db);

$mensaje = '';
$idCuentaFiltro = isset($_GET['id_cuenta']) ? $_GET['id_cuenta'] : 'todas';

// Procesar registro de nueva cuenta (el banco se elige de la lista o se escribe uno nuevo)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btncrearcuenta'])) {
    $id_banco = intval($_POST['id_banco'] ?? 0);
    $bancoNuevo = trim($_POST['banco_nuevo'] ?? '');
    $num_cuenta = trim($_POST['num_cuenta'] ?? '');
    $tipo_moneda = trim($_POST['tipo_moneda'] ?? '');
    $rawSaldo = trim($_POST['saldo_inicial'] ?? '');

    $bancosList = $objFondo->obtenerBancos();
    $idBancoValor = $id_banco ?: '';
    $bancoNuevoValor = htmlspecialchars($bancoNuevo);
    $numCuentaValor = htmlspecialchars($num_cuenta);
    $tipoMonedaValor = $tipo_moneda;
    $saldoInicialValor = htmlspecialchars($rawSaldo);

    if (($id_banco <= 0 && $bancoNuevo === '') || $num_cuenta === '' || !in_array($tipo_moneda, ['BS', 'USD'], true)) {
        $mensaje = '<article class="alert alert-warning">Selecciona o escribe un banco, e indica el número de cuenta y la moneda.</article>';
        include "../vista/registro_cuenta.php";
        exit();
    }

    if (mb_strlen($bancoNuevo) > 255 || mb_strlen($num_cuenta) > 100) {
        $mensaje = '<article class="alert alert-warning">El nombre del banco o el número de cuenta es demasiado largo.</article>';
        include "../vista/registro_cuenta.php";
        exit();
    }

    // Saldo inicial (opcional): en la moneda de la cuenta; si es en BS se guarda también su equivalente en dólares con la tasa de hoy
    $saldoInicial = 0;
    $saldoInicialUsd = 0;
    if ($rawSaldo !== '') {
        $saldoNumero = str_replace(' ', '', $rawSaldo);
        if (strpos($saldoNumero, ',') !== false) {
            if (strpos($saldoNumero, '.') !== false) {
                $saldoNumero = str_replace('.', '', $saldoNumero);
            }
            $saldoNumero = str_replace(',', '.', $saldoNumero);
        }
        if (!is_numeric($saldoNumero) || floatval($saldoNumero) < 0) {
            $mensaje = '<article class="alert alert-warning">El saldo inicial debe ser un número igual o mayor que 0.</article>';
            include "../vista/registro_cuenta.php";
            exit();
        }
        $saldoInicial = round(floatval($saldoNumero), 2);
        $saldoInicialUsd = $saldoInicial;
        if ($tipo_moneda === 'BS' && $saldoInicial > 0) {
            $tasaHoy = $objTasa->obtenerExacta(date('Y-m-d'));
            if (!$tasaHoy) {
                $mensaje = '<article class="alert alert-warning">Para registrar un saldo inicial en bolívares primero registra la tasa de hoy en Tasa del Día.</article>';
                include "../vista/registro_cuenta.php";
                exit();
            }
            $saldoInicialUsd = round($saldoInicial / $tasaHoy, 2);
        }
    }

    if ($bancoNuevo !== '') {
        $id_banco = $objFondo->obtenerOCrearBanco($bancoNuevo);
    }

    if (!$id_banco) {
        $mensaje = '<article class="alert alert-danger">No se pudo registrar el banco.</article>';
        include "../vista/registro_cuenta.php";
        exit();
    }

    if ($objFondo->existeCuenta($id_banco, $num_cuenta)) {
        $mensaje = '<article class="alert alert-warning">Ya existe una cuenta con ese número en ese banco.</article>';
        include "../vista/registro_cuenta.php";
        exit();
    }

    if ($objFondo->registrarCuenta($id_banco, $num_cuenta, $tipo_moneda, $saldoInicial, $saldoInicialUsd)) {
        header("Location: ../controlador/FondoController.php?msj=cuenta_creada");
        exit();
    }

    $mensaje = '<article class="alert alert-danger">Error al registrar la cuenta.</article>';
    include "../vista/registro_cuenta.php";
    exit();
}

// Procesar registro de nuevo método de pago
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btncrearmetodo'])) {
    $nombreMetodo = trim($_POST['nombre'] ?? '');
    $nombreMetodoValor = htmlspecialchars($nombreMetodo);

    if ($nombreMetodo === '' || mb_strlen($nombreMetodo) > 255) {
        $mensaje = '<article class="alert alert-warning">Escribe el nombre del método de pago (máximo 255 caracteres).</article>';
        include "../vista/registro_metodo.php";
        exit();
    }

    if ($objFondo->existeMetodo($nombreMetodo)) {
        $mensaje = '<article class="alert alert-warning">Ese método de pago ya existe.</article>';
        include "../vista/registro_metodo.php";
        exit();
    }

    if ($objFondo->registrarMetodo($nombreMetodo)) {
        header("Location: ../controlador/FondoController.php?msj=metodo_creado");
        exit();
    }

    $mensaje = '<article class="alert alert-danger">Error al registrar el método de pago.</article>';
    include "../vista/registro_metodo.php";
    exit();
}

if (isset($_GET['action']) && $_GET['action'] === 'nueva_cuenta') {
    $bancosList = $objFondo->obtenerBancos();
    $idBancoValor = '';
    $bancoNuevoValor = '';
    $numCuentaValor = '';
    $tipoMonedaValor = '';
    $saldoInicialValor = '';
    include "../vista/registro_cuenta.php";
    exit();
}

if (isset($_GET['action']) && $_GET['action'] === 'nuevo_metodo') {
    $nombreMetodoValor = '';
    include "../vista/registro_metodo.php";
    exit();
}

// Procesar registro de la tasa del día
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btnguardartasa'])) {
    $fechaTasa = trim($_POST['fecha'] ?? '');
    $rawTasa = trim($_POST['tasa'] ?? '');
    $tasaNumero = str_replace(' ', '', $rawTasa);
    if (strpos($tasaNumero, ',') !== false) {
        if (strpos($tasaNumero, '.') !== false) {
            $tasaNumero = str_replace('.', '', $tasaNumero);
        }
        $tasaNumero = str_replace(',', '.', $tasaNumero);
    }

    $tasasRecientes = $objTasa->obtenerRecientes();
    $tasaFechaValor = htmlspecialchars($fechaTasa);
    $tasaValor = htmlspecialchars($rawTasa);

    $fechaValida = DateTime::createFromFormat('Y-m-d', $fechaTasa);
    if (!$fechaValida || $fechaValida->format('Y-m-d') !== $fechaTasa) {
        $mensaje = '<article class="alert alert-warning">Selecciona una fecha válida.</article>';
        include "../vista/registro_tasa.php";
        exit();
    }

    if (!is_numeric($tasaNumero) || floatval($tasaNumero) <= 0 || floatval($tasaNumero) > 100000000) {
        $mensaje = '<article class="alert alert-warning">La tasa debe ser un número mayor que 0.</article>';
        include "../vista/registro_tasa.php";
        exit();
    }

    if ($objTasa->guardar($fechaTasa, floatval($tasaNumero), $_SESSION['admin_id'] ?? null)) {
        header("Location: ../controlador/FondoController.php?msj=tasa_guardada");
        exit();
    }

    $mensaje = '<article class="alert alert-danger">Error al guardar la tasa.</article>';
    include "../vista/registro_tasa.php";
    exit();
}

if (isset($_GET['action']) && $_GET['action'] === 'nueva_tasa') {
    $tasasRecientes = $objTasa->obtenerRecientes();
    $tasaFechaValor = date('Y-m-d');
    $tasaValor = '';
    include "../vista/registro_tasa.php";
    exit();
}

if (isset($_GET['msj'])) {
    if ($_GET['msj'] === 'cuenta_creada') {
        $mensaje = '<article class="alert alert-success">Cuenta registrada correctamente.</article>';
    } elseif ($_GET['msj'] === 'metodo_creado') {
        $mensaje = '<article class="alert alert-success">Método de pago registrado correctamente.</article>';
    } elseif ($_GET['msj'] === 'tasa_guardada') {
        $mensaje = '<article class="alert alert-success">Tasa del día guardada correctamente.</article>';
    } elseif ($_GET['msj'] === 'error') {
        $mensaje = '<article class="alert alert-danger">Ocurrió un error al procesar la solicitud.</article>';
    }
}

$fondos = $objFondo->obtenerTodos();
$totalesUSD = $objFondo->obtenerTotalesUSD();
$fondoEfectivo = $totalesUSD['sin_cuenta'];
foreach ($fondos as $i => $fondo) {
    $fondos[$i]['saldo_usd'] = $totalesUSD['cuentas'][(int) $fondo['id_cuenta']]['saldo'] ?? 0;
}
$tasaVigente = $objTasa->obtenerVigente(date('Y-m-d'));
$tasaHoyRegistrada = ($tasaVigente && $tasaVigente['fecha'] === date('Y-m-d'));
$transacciones = $objFondo->obtenerTransacciones($idCuentaFiltro);

// Si alguna consulta falló, se muestra el motivo en lugar de dejar saldos en 0 sin explicación
$errorFondos = $objFondo->getLastError();
if ($errorFondos && empty($mensaje)) {
    $mensaje = '<article class="alert alert-warning">No se pudieron leer algunos datos de Fondos: ' . htmlspecialchars($errorFondos) . '</article>';
}

// Respuesta en formato JSON con directivas estrictas anti-caché
if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Cache-Control: post-check=0, pre-check=0', false);
    header('Pragma: no-cache');
    
    echo json_encode([
        'fondos' => $fondos,
        'transacciones' => $transacciones,
        'filtro_activo' => $idCuentaFiltro
    ]);
    exit();
}

include "../vista/fondos.php";
?>