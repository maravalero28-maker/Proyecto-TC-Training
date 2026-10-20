<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../vista/Login.php');
    exit();
}

require_once(__DIR__ . '/../modelo/Reporte.php');

class Reportecontrolador {
    private $modelo;

    public function __construct() {
        $this->modelo = new Reporte();
    }

    public function index() {
        require_once(__DIR__ . '/../vista/reportes.php');
    }

    public function generar() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $modulosPermitidos = ['pagos_clientes', 'pagos_nomina', 'pagos_servicios', 'clientes'];
            $nivelesPermitidos = ['general', 'resumido', 'personalizado'];
            $columnasPermitidas = ['cedula', 'nombre', 'monto', 'metodo_pago', 'banco_receptor', 'fecha_pago', 'estatus_solvencia'];
            $modulo = in_array($_POST['modulo'] ?? '', $modulosPermitidos, true) ? $_POST['modulo'] : 'pagos_clientes';
            $nivelDetalle = in_array($_POST['nivel_detalle'] ?? '', $nivelesPermitidos, true) ? $_POST['nivel_detalle'] : 'general';
            $columnasRecibidas = is_array($_POST['columnas'] ?? null) ? $_POST['columnas'] : [];
            $columnasRecibidas = array_values(array_filter($columnasRecibidas, 'is_string'));
            $columnasSeleccionadas = array_values(array_intersect($columnasRecibidas, $columnasPermitidas));
            $formato = ($_POST['formato'] ?? '') === 'excel' ? 'excel' : 'html';

            $datos = [];
            $titulos = [
                'pagos_clientes' => 'Pagos de clientes',
                'pagos_nomina' => 'Pagos de nómina',
                'pagos_servicios' => 'Pagos de servicios',
                'clientes' => 'Clientes por solvencia'
            ];
            $titulo = 'Reporte: ' . $titulos[$modulo];

            if (strpos($modulo, 'pagos') !== false) {
                $origen = str_replace('pagos_', '', $modulo);
                $fechaInicio = trim((string) ($_POST['fecha_inicio'] ?? ''));
                $fechaFin = trim((string) ($_POST['fecha_fin'] ?? ''));
                $metodoPago = trim((string) ($_POST['metodo_pago'] ?? ''));
                $banco = trim((string) ($_POST['banco'] ?? ''));
                $datos = $this->modelo->obtenerPagos($origen, $fechaInicio, $fechaFin, $metodoPago, $banco);
            } elseif ($modulo === 'clientes') {
                $estatusFinanciero = in_array($_POST['estatus_financiero'] ?? '', ['', 'solvente', 'insolvente'], true)
                    ? $_POST['estatus_financiero']
                    : '';
                $datos = $this->modelo->obtenerClientesPorEstatus($estatusFinanciero);
            }

            if (!empty($datos)) {
                if ($nivelDetalle === 'resumido') {
                    $datos = array_map(function($fila) use ($modulo) {
                        if (strpos($modulo, 'pagos') !== false) {
                            return [
                                'Referencia' => $fila['id_pago'] ?? $fila['id_servicio'] ?? $fila['id_nomina'],
                                'Monto' => $fila['monto'],
                                'Método' => $fila['metodo_pago'],
                                'Fecha' => $fila['fecha_pago']
                            ];
                        } else {
                            return [
                                'Cédula' => $fila['cedula'],
                                'Nombre' => $fila['nombre'] . ' ' . ($fila['apellido'] ?? ''),
                                'Solvencia' => $fila['estatus_solvencia']
                            ];
                        }
                    }, $datos);
                } elseif ($nivelDetalle === 'personalizado' && !empty($columnasSeleccionadas)) {
                    $datos = array_map(function($fila) use ($columnasSeleccionadas) {
                        $nuevaFila = [];
                        foreach ($columnasSeleccionadas as $col) {
                            if (array_key_exists($col, $fila)) {
                                $nuevaFila[$col] = $fila[$col];
                            }
                        }
                        return $nuevaFila;
                    }, $datos);
                }
            }

            if ($formato === 'excel') {
                header('Content-Type: text/csv; charset=utf-8');
                header('Content-Disposition: attachment; filename=reporte_' . $modulo . '_' . date('Ymd') . '.csv');
                $output = fopen('php://output', 'w');
                if (!empty($datos)) {
                    fputcsv($output, array_keys($datos[0]));
                    foreach ($datos as $row) {
                        fputcsv($output, $row);
                    }
                }
                fclose($output);
                exit();
            }

            require_once(__DIR__ . '/../vista/resultadoReportes.php');
        }
    }
}

if (basename($_SERVER['SCRIPT_FILENAME'] ?? '') === basename(__FILE__)) {
    $controlador = new Reportecontrolador();
    $accion = $_GET['action'] ?? 'index';

    if (in_array($accion, ['index', 'generar'], true)) {
        $controlador->$accion();
    } else {
        http_response_code(404);
        echo 'Acción no encontrada.';
    }
}
?>