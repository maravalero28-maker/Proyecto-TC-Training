<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../vista/Login.php");
    exit();
}

require_once "../configuracion/conecxion.php";
require_once "../modelo/ModelFondo.php";

$database = new Conexion();
$db = $database->getConexion();
$objFondo = new ModelFondo($db);

$mensaje = '';
$idCuentaFiltro = isset($_GET['id_cuenta']) ? $_GET['id_cuenta'] : 'todas';

$fondos = $objFondo->obtenerTodos();
$transacciones = $objFondo->obtenerTransacciones($idCuentaFiltro);

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