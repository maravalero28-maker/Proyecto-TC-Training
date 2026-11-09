<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../vista/Login.php");
    exit();
}

include "../configuracion/conecxion.php";
include "../modelo/Administrador.php";
include "../modelo/Rol.php";
require_once "permisos.php";

requerirAdmin();

$conexion = new Conexion();
$db = $conexion->getConexion();
$adminModel = new Administrador($db);
$rolModel = new Rol($db);

function volver($texto, $tipo) {
    $_SESSION['flash'] = ['text' => $texto, 'type' => $tipo];
    header("Location: AjustesControlador.php?abrir=usuarios");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    volver('Acción no válida.', 'error');
}

$accion = $_POST['accion'] ?? '';
$id = intval($_POST['id_admin'] ?? 0);

if ($accion === 'cambiar_rol') {
    $idRol = intval($_POST['id_rol'] ?? 0);
    if (!$rolModel->obtenerPorId($idRol)) {
        volver('El rol seleccionado no existe.', 'error');
    }
    if ($id === (int) $_SESSION['admin_id'] && $idRol !== 1) {
        volver('No puedes quitarte tu propio rol de administrador.', 'error');
    }
    if ($adminModel->cambiarRol($id, $idRol)) {
        volver('Rol actualizado correctamente.', 'success');
    }
    volver('No se pudo cambiar el rol (debe existir al menos un administrador).', 'error');
}

if ($accion === 'eliminar') {
    if ($id === (int) $_SESSION['admin_id']) {
        volver('No puedes eliminar tu propio usuario.', 'error');
    }
    if ($adminModel->eliminar($id)) {
        volver('Usuario eliminado correctamente.', 'success');
    }
    volver('No se pudo eliminar (debe existir al menos un administrador).', 'error');
}

if ($accion === 'nuevo_rol') {
    $nombreRol = trim($_POST['nombre_rol'] ?? '');
    $modulos = $_POST['modulos'] ?? [];
    if (!is_array($modulos)) {
        $modulos = [];
    }
    $nuevoId = $rolModel->crear($nombreRol, $modulos);
    if (!$nuevoId) {
        volver('Indica un nombre de rol que no exista y marca al menos un módulo.', 'warning');
    }
    if ($id > 0) {
        $adminModel->cambiarRol($id, $nuevoId);
    }
    volver('Rol creado y asignado correctamente.', 'success');
}

if ($accion === 'modificar') {
    $usuario = trim($_POST['usuario'] ?? '');
    $password = trim($_POST['password'] ?? '');
    if ($usuario === '') {
        volver('El nombre de usuario no puede estar vacío.', 'warning');
    }
    $resultado = $adminModel->actualizar($id, $usuario, $password);
    if ($resultado === 'existe') {
        volver('Ese nombre de usuario ya está en uso.', 'warning');
    }
    if ($resultado === 'success') {
        volver('Usuario modificado correctamente.', 'success');
    }
    volver('No se pudo modificar el usuario.', 'error');
}

volver('Acción no válida.', 'error');
?>
