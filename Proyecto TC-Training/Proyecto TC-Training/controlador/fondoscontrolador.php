<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../vista/Login.php");
    exit();
}

require_once "../configuracion/conecxion.php";
require_once "../modelo/modelfondos.php";

$database = new Conexion();
$db = $database->getConexion();
$objFondo = new modelfondos($db);

$mensaje = '';
$idCuentaFiltro = $_GET['id_cuenta'] ?? 'todas';

// Obtener bancos para el formulario de crear cuenta
$bancosList = [];
try {
    $stmtBancos = $db->query("SELECT id_banco, nombre FROM banco ORDER BY nombre ASC");
    $bancosList = $stmtBancos->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {}

// Procesar creación de nueva cuenta
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btncrearcuenta'])) {
    $id_banco = intval($_POST['id_banco'] ?? 0);
    $num_cuenta = trim($_POST['num_cuenta'] ?? '');
    $tipo_moneda = trim($_POST['tipo_moneda'] ?? 'USD');
    $saldo_inicial = floatval(str_replace([' ', ','], ['', '.'], $_POST['saldo'] ?? '0'));

    if ($id_banco > 0 && !empty($num_cuenta)) {
        if ($objFondo->registrar($id_banco, $num_cuenta, $tipo_moneda, $saldo_inicial)) {
            header("Location: ../controlador/fondoscontrolador.php?msj=creado");
            exit();
        } else {
            $mensaje = '<article class="alert alert-danger">Error al registrar la cuenta bancaria.</article>';
        }
    } else {
        $mensaje = '<article class="alert alert-warning">Completa los campos obligatorios.</article>';
    }
}

if (isset($_GET['msj']) && $_GET['msj'] === 'creado') {
    $mensaje = '<article class="alert alert-success">Cuenta bancaria registrada correctamente.</article>';
}

$cuentas = $objFondo->obtenerTodas();
$transacciones = $objFondo->obtenerTransacciones($idCuentaFiltro);

// Respuesta AJAX
if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'cuentas' => $cuentas,
        'transacciones' => $transacciones,
        'filtro_cuenta' => $idCuentaFiltro
    ]);
    exit();
}

include "../vista/fondos.php";
?>