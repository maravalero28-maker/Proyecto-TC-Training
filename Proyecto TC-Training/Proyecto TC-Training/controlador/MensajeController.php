<?php
session_start();

$accion = $_GET['action'] ?? '';
$esAjax = ($_SERVER['REQUEST_METHOD'] === 'POST');

if (!isset($_SESSION['admin_id'])) {
    if ($esAjax) {
        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'mensaje' => 'Sesión expirada. Inicia sesión de nuevo.']);
    } else {
        header("Location: ../vista/Login.php");
    }
    exit();
}

require_once "../configuracion/conecxion.php";
require_once "../configuracion/mensajeria.php";
require_once "../modelo/Cliente.php";
require_once "../modelo/Plan.php";
require_once "../modelo/PlantillaMensaje.php";

$database = new Conexion();
$db = $database->getConexion();
$objPlantilla = new PlantillaMensaje($db);

function responderJson($datos) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($datos, JSON_UNESCAPED_UNICODE);
    exit();
}

if ($esAjax) {
    if ($accion === 'guardar') {
        $res = $objPlantilla->guardar($_POST['titulo'] ?? '', $_POST['tipo'] ?? 'otro', $_POST['contenido'] ?? '');
        if ($res['ok']) {
            $res['plantilla'] = $objPlantilla->obtenerPorId($res['id']);
        }
        responderJson($res);
    }

    if ($accion === 'eliminar') {
        responderJson($objPlantilla->eliminar((int)($_POST['id'] ?? 0)));
    }

    if ($accion === 'predeterminar') {
        responderJson($objPlantilla->marcarPredeterminada((int)($_POST['id'] ?? 0)));
    }

    responderJson(['ok' => false, 'mensaje' => 'Acción no válida.']);
}

if ($accion === 'masivo') {
    $objCliente = new Cliente($db);
    $objCliente->actualizarSolvenciaPorVencimiento();
    $clientes = $objCliente->obtenerTodosConDiasRestantes('todos', 'todos');
    $objPlan = new Plan($db);
    $planMap = [];
    foreach ($objPlan->obtenerTodos() as $plan) {
        $planMap[$plan['id_plan']] = $plan['nombre'];
    }
    $planEtiquetas = ['basico' => 'Semanal', 'premium' => 'Mensual', 'vip' => 'Anual'];
    $plantillas = $objPlantilla->obtenerTodas();
    $tiposMensaje = unserialize(TIPOS_MENSAJE);
    include "../vista/mensaje_masivo.php";
    exit();
}

header("Location: ../controlador/ClienteController.php");
exit();
