<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../vista/Login.php");
    exit();
}

require_once "../configuracion/conecxion.php";
require_once "../modelo/Entrenador.php";

$database = new Conexion();
$db = $database->getConexion();
$objEntrenador = new Entrenador($db);

$mensaje = '';
$status = $_GET['status'] ?? 'todos';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btnregistrar'])) {
    $resultado = $objEntrenador->registrar(
        trim($_POST['cedula'] ?? ''),
        trim($_POST['nombre'] ?? ''),
        trim($_POST['apellido'] ?? ''),
        trim($_POST['telefono'] ?? ''),
        $_POST['estatus'] ?? 'activo'
    );

    if ($resultado === 'success') {
        header("Location: ../controlador/EntrenadorController.php?msj=creado");
        exit();
    }

    $alertas = [
        "existe"  => '<article class="alert alert-warning">Ya existe un entrenador con esta cédula.</article>',
        "error"   => '<article class="alert alert-danger">Error al registrar en la base de datos.</article>',
        "warning" => '<article class="alert alert-warning">Por favor completa los campos obligatorios (Cédula, Nombre, Apellido).</article>'
    ];
    $mensaje = $alertas[$resultado] ?? '<article class="alert alert-danger">Error desconocido.</article>';
    include "../vista/registro_entrenador.php";
    exit();
}

if (isset($_POST['btnactualizar'])) {
    $cedulaOriginal = $_POST['cedula_original'] ?? '';
    $cedulaNueva = trim($_POST['cedula'] ?? '');
    $resultado = $objEntrenador->actualizar(
        $cedulaOriginal,
        $cedulaNueva,
        trim($_POST['nombre'] ?? ''),
        trim($_POST['apellido'] ?? ''),
        trim($_POST['telefono'] ?? ''),
        $_POST['estatus'] ?? 'activo'
    );

    if ($resultado === 'success') {
        header("Location: ../controlador/EntrenadorController.php?msj=actualizado");
        exit();
    } elseif ($resultado === 'existe') {
        header("Location: ../controlador/EntrenadorController.php?action=editar&cedula=" . urlencode($cedulaOriginal) . "&msj=existe");
        exit();
    } else {
        header("Location: ../controlador/EntrenadorController.php?msj=error");
        exit();
    }
}

if (isset($_GET['action']) && $_GET['action'] === 'nuevo') {
    include "../vista/registro_entrenador.php";
    exit();
}

if (isset($_GET['action']) && $_GET['action'] === 'editar' && isset($_GET['cedula'])) {
    $entrenador = $objEntrenador->obtenerPorCedula($_GET['cedula']);
    if (!$entrenador) {
        header("Location: ../controlador/EntrenadorController.php?msj=error");
        exit();
    }
    include "../vista/editar_entrenador.php";
    exit();
}

if (isset($_GET['action']) && $_GET['action'] === 'eliminar' && isset($_GET['cedula'])) {
    $objEntrenador->eliminar($_GET['cedula']);
    header("Location: ../controlador/EntrenadorController.php?msj=eliminado");
    exit();
}

if (isset($_GET['msj'])) {
    if ($_GET['msj'] === 'creado') {
        $mensaje = '<article class="alert alert-success">Entrenador registrado exitosamente.</article>';
    } elseif ($_GET['msj'] === 'eliminado') {
        $mensaje = '<article class="alert alert-success">Entrenador eliminado correctamente.</article>';
    } elseif ($_GET['msj'] === 'actualizado') {
        $mensaje = '<article class="alert alert-success">Entrenador actualizado correctamente.</article>';
    } elseif ($_GET['msj'] === 'existe') {
        $mensaje = '<article class="alert alert-warning">La cédula ingresada ya pertenece a otro entrenador.</article>';
    } elseif ($_GET['msj'] === 'error') {
        $mensaje = '<article class="alert alert-danger">Ocurrió un error al procesar la solicitud.</article>';
    }
}

$entrenadores = $objEntrenador->obtenerTodos($status);
include "../vista/entrenadores.php";
?>