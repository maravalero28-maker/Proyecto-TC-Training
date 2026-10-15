<?php
require_once(__DIR__ . '/../modelo/Reporte.php');

class ReporteControlador {
    private $modelo;

    public function __construct() {
        $this->modelo = new Reporte();
    }


    public function index() {
        // Carga la vista principal de reportes
        require_once(__DIR__ . '/../vista/reportes.php');
    }

    public function generar() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $tipoReporte = $_POST['tipo_reporte'] ?? 'movimientos';
            $formato = $_POST['formato'] ?? 'html';

            if ($tipoReporte === 'movimientos') {
                $fechaInicio = $_POST['fecha_inicio'] ?? date('Y-m-d');
                $fechaFin = $_POST['fecha_fin'] ?? date('Y-m-d');
                
                $datos = $this->modelo->obtenerMovimientosPorFecha($fechaInicio, $fechaFin);
                $titulo = "Reporte de Trazabilidad de Accesos ($fechaInicio al $fechaFin)";
                
                require_once(__DIR__ . '/../vista/resultadoReportes.php');
            } else {
                $rol = $_POST['rol'] ?? '';
                $activo = $_POST['activo'] ?? '';

                $datos = $this->modelo->obtenerUsuariosGeneral($rol, $activo);
                $titulo = "Reporte de Padrón y Estado de Usuarios";
                
                require_once(__DIR__ . '/../vista/resultadoReportes.php');
            }
        }
    }
}
}

// Permitir ejecución directa estilo procedural como el resto de controladores del proyecto
if (php_sapi_name() !== 'cli') {
    session_start();
    if (!isset($_SESSION['admin_id'])) {
        header("Location: ../vista/Login.php");
        exit();
    }

    $ctrl = new ReporteControlador();
    $action = $_REQUEST['action'] ?? 'index';
    if ($action === 'generar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $ctrl->generar();
        exit();
    }
    $ctrl->index();
    exit();
}

?>
