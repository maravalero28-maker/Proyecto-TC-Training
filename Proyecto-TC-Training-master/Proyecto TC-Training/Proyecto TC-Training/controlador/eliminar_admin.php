<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit();
}

include "../configuracion/conecxion.php";
include "../modelo/Administrador.php";

$conexion = new Conexion();
$db = $conexion->getConexion();
$adminModel = new Administrador($db);

$input = file_get_contents('php://input');
$data = [];
if (!empty($input)) {
    $decoded = json_decode($input, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        $data = $decoded;
    }
}

if (empty($data) && !empty($_POST)) {
    $data = $_POST;
}

if (!isset($data['id']) || empty($data['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID de usuario faltante']);
    exit();
}

$id = intval($data['id']);

try {
    $ok = $adminModel->eliminar($id);
    if ($ok) {
        echo json_encode(['success' => true, 'message' => 'Administrador eliminado']);
        exit();
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'No se pudo eliminar administrador']);
        exit();
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error del servidor']);
    exit();
}
?>