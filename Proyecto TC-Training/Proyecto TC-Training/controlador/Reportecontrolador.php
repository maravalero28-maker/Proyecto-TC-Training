<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../vista/Login.php');
    exit();
}
require_once __DIR__ . '/permisos.php';
requerirModulo('reportes');

require_once(__DIR__ . '/../modelo/Reporte.php');
require_once(__DIR__ . '/../modelo/ConfigCorreo.php');

class Reportecontrolador {
    private $modelo;

    public function __construct() {
        $this->modelo = new Reporte();
    }

    public function index() {
        // Datos (sin contraseña) para mostrar el formulario de configuración del correo
        $configCorreo = (new ConfigCorreo())->obtenerPublico();
        require_once(__DIR__ . '/../vista/reportes.php');
    }

    private function redirigirConMensaje($tipo, $texto) {
        $_SESSION['reporte_msg'] = ['tipo' => $tipo, 'texto' => $texto];
        header('Location: Reportecontrolador.php?action=index');
        exit();
    }

    // Guarda la configuración del correo emisor (SMTP) enviada desde la web
    public function guardarConfig() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirigirConMensaje('error', 'Solicitud no válida.');
        }

        $campo = function ($nombre) {
            return (isset($_POST[$nombre]) && is_string($_POST[$nombre])) ? trim($_POST[$nombre]) : '';
        };

        $usuario   = $campo('cfg_usuario');
        $nombre    = $campo('cfg_nombre');
        $host      = $campo('cfg_host');
        $puerto    = (int) $campo('cfg_puerto');
        $seguridad = in_array($campo('cfg_seguridad'), ['tls', 'ssl'], true) ? $campo('cfg_seguridad') : 'tls';
        // Google muestra la contraseña de aplicación en bloques con espacios: se quitan
        $clave     = preg_replace('/\s+/', '', $campo('cfg_clave'));

        if (!filter_var($usuario, FILTER_VALIDATE_EMAIL)) {
            $this->redirigirConMensaje('error', 'El correo emisor no es válido.');
        }
        if (!preg_match('/^[A-Za-z0-9.-]{3,150}$/', $host)) {
            $this->redirigirConMensaje('error', 'El servidor SMTP no es válido.');
        }
        if ($puerto < 1 || $puerto > 65535) {
            $this->redirigirConMensaje('error', 'El puerto no es válido.');
        }
        if ($nombre === '') {
            $nombre = 'TC-Control';
        }
        $nombre = mb_substr($nombre, 0, 100);

        try {
            (new ConfigCorreo())->guardar($host, $puerto, $seguridad, $usuario, $nombre, $clave);
        } catch (\Throwable $e) {
            error_log('Error guardando configuración de correo: ' . $e->getMessage());
            $this->redirigirConMensaje('error', 'No se pudo guardar la configuración: ' . $e->getMessage());
        }

        $this->redirigirConMensaje('ok', 'Configuración del correo guardada correctamente.');
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
            $formatoRecibido = $_POST['formato'] ?? '';
            $formato = in_array($formatoRecibido, ['excel', 'correo'], true) ? $formatoRecibido : 'html';

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

            // Copia de los datos completos: los KPIs del correo se calculan con ella,
            // porque "resumido"/"personalizado" pueden quitar columnas como el monto.
            $datosCompletos = $datos;

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

            if ($formato === 'correo') {
                $destino = trim((string) ($_POST['correo_destino'] ?? ''));
                $adjuntosRecibidos = is_array($_POST['adjuntos'] ?? null) ? $_POST['adjuntos'] : [];
                $adjuntosRecibidos = array_values(array_filter($adjuntosRecibidos, 'is_string'));
                $adjuntos = array_values(array_intersect($adjuntosRecibidos, ['pdf', 'xlsx']));

                if (!filter_var($destino, FILTER_VALIDATE_EMAIL)) {
                    $this->redirigirConMensaje('error', 'El correo destinatario no es válido.');
                }
                if (empty($datos) || empty($datos[0])) {
                    $this->redirigirConMensaje('error', 'No hay registros para enviar con los filtros seleccionados.');
                }
                if (!file_exists(__DIR__ . '/../vendor/autoload.php')) {
                    $this->redirigirConMensaje('error', 'Faltan las librerías de correo. Ejecuta "composer install" en la raíz del proyecto.');
                }

                try {
                    require_once(__DIR__ . '/../modelo/ReporteMailer.php');
                    $kpis = ReporteMailer::calcularKpis($modulo, $datosCompletos);
                    (new ReporteMailer())->enviar($destino, $titulo, $kpis, $datos, $adjuntos);
                } catch (\Throwable $e) {
                    error_log('Error enviando reporte por correo: ' . $e->getMessage());
                    $this->redirigirConMensaje('error', 'No se pudo enviar el correo: ' . $e->getMessage());
                }

                $this->redirigirConMensaje('ok', 'Reporte enviado correctamente a ' . $destino . '.');
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

    if (in_array($accion, ['index', 'generar', 'guardarConfig'], true)) {
        $controlador->$accion();
    } else {
        http_response_code(404);
        echo 'Acción no encontrada.';
    }
}
?>
