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

    // Reportes extras: título, columnas disponibles (clave => etiqueta) y columnas del modo "resumido"
    private const EXTRAS = [
        'entrenadores' => [
            'titulo' => 'Entrenadores',
            'columnas' => [
                'cedula' => 'Cédula', 'nombre' => 'Nombre', 'apellido' => 'Apellido', 'telefono' => 'Teléfono',
                'estatus' => 'Estatus', 'fecha_registro' => 'Fecha de registro', 'pagos_realizados' => 'Pagos realizados',
                'total_pagado_usd' => 'Total pagado (USD)', 'ultimo_pago' => 'Último pago',
            ],
            'resumido' => ['cedula', 'nombre', 'apellido', 'estatus'],
        ],
        'fondos_saldos' => [
            'titulo' => 'Fondos - Saldos por cuenta',
            'columnas' => [
                'id_cuenta' => 'N.º cuenta', 'banco' => 'Banco', 'num_cuenta' => 'N.º de cuenta / referencia',
                'tipo_moneda' => 'Moneda', 'saldo_inicial' => 'Saldo inicial', 'ingresos' => 'Ingresos',
                'egresos' => 'Egresos', 'saldo_actual' => 'Saldo actual', 'ingresos_usd' => 'Ingresos (USD)',
                'egresos_usd' => 'Egresos (USD)', 'saldo_usd' => 'Saldo (USD)',
            ],
            'resumido' => ['banco', 'num_cuenta', 'tipo_moneda', 'saldo_actual', 'saldo_usd'],
        ],
        'fondos_movimientos' => [
            'titulo' => 'Fondos - Movimientos',
            'columnas' => [
                'fecha' => 'Fecha', 'tipo' => 'Tipo de movimiento', 'concepto' => 'Concepto', 'banco' => 'Banco',
                'num_cuenta' => 'N.º de cuenta / referencia', 'metodo_pago' => 'Método de pago', 'tipo_moneda' => 'Moneda',
                'monto' => 'Monto', 'tasa' => 'Tasa de cambio', 'monto_usd' => 'Monto (USD)',
            ],
            'resumido' => ['fecha', 'tipo', 'concepto', 'monto_usd'],
        ],
        'usuarios' => [
            'titulo' => 'Ajustes - Usuarios y roles',
            'columnas' => [
                'id_admin' => 'N.º usuario', 'usuario' => 'Usuario', 'rol' => 'Rol', 'descripcion_rol' => 'Descripción del rol',
                'modulos_acceso' => 'Módulos con acceso', 'fecha_creacion' => 'Fecha de creación',
                'pregunta_seguridad' => 'Pregunta de seguridad configurada',
            ],
            'resumido' => ['usuario', 'rol', 'fecha_creacion'],
        ],
    ];

    public function __construct() {
        $this->modelo = new Reporte();
    }

    public function index() {
        // Datos (sin contraseña) para mostrar el formulario de configuración del correo
        $configCorreo = (new ConfigCorreo())->obtenerPublico();
        // Roles para el filtro del reporte de usuarios (solo administrador)
        $rolesLista = esAdmin() ? $this->modelo->obtenerRoles() : [];
        require_once(__DIR__ . '/../vista/reportes.php');
    }

    private function redirigirConMensaje($tipo, $texto) {
        $_SESSION['reporte_msg'] = ['tipo' => $tipo, 'texto' => $texto];
        header('Location: Reportecontrolador.php?action=index');
        exit();
    }

    private function fmt($n) {
        return number_format((float) $n, 2, ',', '.');
    }

    // Comprueba que las librerías de /vendor existan (PDF, Excel y correo las necesitan)
    private function exigirLibrerias() {
        if (!file_exists(__DIR__ . '/../vendor/autoload.php')) {
            $this->redirigirConMensaje('error', 'Faltan las librerías de PDF/Excel. Ejecuta "composer install" en la raíz del proyecto.');
        }
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

    // KPIs de los reportes extras (para el PDF/Excel y para el correo)
    private function kpisExtra($modulo, array $datos) {
        $n = count($datos);

        if ($modulo === 'entrenadores') {
            $activos = 0;
            $total = 0;
            foreach ($datos as $f) {
                if (strtolower((string) ($f['estatus'] ?? '')) === 'activo') {
                    $activos++;
                }
                $total += (float) ($f['total_pagado_usd'] ?? 0);
            }
            return [
                'Entrenadores'        => (string) $n,
                'Activos'             => (string) $activos,
                'Inactivos'           => (string) ($n - $activos),
                'Total pagado (USD)'  => $this->fmt($total),
            ];
        }

        if ($modulo === 'fondos_saldos') {
            $cuentas = 0;
            $ing = $egr = $sal = 0;
            foreach ($datos as $f) {
                if (strpos((string) ($f['banco'] ?? ''), 'TOTAL') === 0) {
                    continue;
                }
                $cuentas++;
                $ing += (float) ($f['ingresos_usd'] ?? 0);
                $egr += (float) ($f['egresos_usd'] ?? 0);
                $sal += (float) ($f['saldo_usd'] ?? 0);
            }
            return [
                'Cuentas / fondos'      => (string) $cuentas,
                'Ingresos (USD)'        => $this->fmt($ing),
                'Egresos (USD)'         => $this->fmt($egr),
                'Saldo total (USD)'     => $this->fmt($sal),
            ];
        }

        if ($modulo === 'fondos_movimientos') {
            $ing = $egr = 0;
            foreach ($datos as $f) {
                $usd = (float) ($f['monto_usd'] ?? 0);
                if (strpos((string) ($f['tipo'] ?? ''), 'Ingreso') === 0) {
                    $ing += $usd;
                } else {
                    $egr += $usd;
                }
            }
            return [
                'Movimientos'      => (string) $n,
                'Ingresos (USD)'   => $this->fmt($ing),
                'Egresos (USD)'    => $this->fmt($egr),
                'Balance (USD)'    => $this->fmt($ing - $egr),
            ];
        }

        // usuarios
        $roles = [];
        $conPregunta = 0;
        foreach ($datos as $f) {
            $roles[$f['rol'] ?? 'Sin rol'] = true;
            if (($f['pregunta_seguridad'] ?? '') === 'Sí') {
                $conPregunta++;
            }
        }
        return [
            'Usuarios'                        => (string) $n,
            'Roles en uso'                    => (string) count($roles),
            'Con pregunta de seguridad'       => (string) $conPregunta,
        ];
    }

    public function generar() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $modulosBase = ['pagos_clientes', 'pagos_nomina', 'pagos_servicios', 'clientes'];
            $modulosPermitidos = array_merge($modulosBase, array_keys(self::EXTRAS));
            $nivelesPermitidos = ['general', 'resumido', 'personalizado'];
            $columnasPermitidas = ['cedula', 'nombre', 'monto', 'metodo_pago', 'banco_receptor', 'fecha_pago', 'estatus_solvencia'];
            $modulo = in_array($_POST['modulo'] ?? '', $modulosPermitidos, true) ? $_POST['modulo'] : 'pagos_clientes';
            $esExtra = isset(self::EXTRAS[$modulo]);

            // El reporte de usuarios es parte de Gestión de Usuarios: solo el administrador
            if ($modulo === 'usuarios' && !esAdmin()) {
                $this->redirigirConMensaje('error', 'Solo el administrador puede generar el reporte de usuarios.');
            }

            $nivelDetalle = in_array($_POST['nivel_detalle'] ?? '', $nivelesPermitidos, true) ? $_POST['nivel_detalle'] : 'general';
            $columnasRecibidas = is_array($_POST['columnas'] ?? null) ? $_POST['columnas'] : [];
            $columnasRecibidas = array_values(array_filter($columnasRecibidas, 'is_string'));
            $columnasSeleccionadas = array_values(array_intersect($columnasRecibidas, $columnasPermitidas));
            $formatoRecibido = $_POST['formato'] ?? '';
            $formato = in_array($formatoRecibido, ['excel', 'correo', 'pdf', 'xlsx'], true) ? $formatoRecibido : 'html';

            $datos = [];
            $titulos = [
                'pagos_clientes' => 'Pagos de clientes',
                'pagos_nomina' => 'Pagos de nómina',
                'pagos_servicios' => 'Pagos de servicios',
                'clientes' => 'Clientes por solvencia'
            ];
            foreach (self::EXTRAS as $clave => $def) {
                $titulos[$clave] = $def['titulo'];
            }
            $titulo = 'Reporte: ' . $titulos[$modulo];

            $fechaInicio = trim((string) ($_POST['fecha_inicio'] ?? ''));
            $fechaFin = trim((string) ($_POST['fecha_fin'] ?? ''));
            $metodoPago = trim((string) ($_POST['metodo_pago'] ?? ''));

            if (strpos($modulo, 'pagos') !== false) {
                $origen = str_replace('pagos_', '', $modulo);
                $banco = trim((string) ($_POST['banco'] ?? ''));
                $datos = $this->modelo->obtenerPagos($origen, $fechaInicio, $fechaFin, $metodoPago, $banco);
            } elseif ($modulo === 'clientes') {
                $estatusFinanciero = in_array($_POST['estatus_financiero'] ?? '', ['', 'solvente', 'insolvente'], true)
                    ? $_POST['estatus_financiero']
                    : '';
                $datos = $this->modelo->obtenerClientesPorEstatus($estatusFinanciero);
            } elseif ($modulo === 'entrenadores') {
                $estatusEntrenador = in_array($_POST['estatus_entrenador'] ?? '', ['', 'activo', 'inactivo'], true)
                    ? $_POST['estatus_entrenador']
                    : '';
                $datos = $this->modelo->obtenerEntrenadores($estatusEntrenador);
            } elseif ($modulo === 'fondos_saldos') {
                $monedaFondo = in_array($_POST['moneda_fondo'] ?? '', ['', 'USD', 'BS'], true) ? $_POST['moneda_fondo'] : '';
                $datos = $this->modelo->obtenerFondosSaldos($monedaFondo);
            } elseif ($modulo === 'fondos_movimientos') {
                $datos = $this->modelo->obtenerFondosMovimientos($fechaInicio, $fechaFin, $metodoPago);
            } elseif ($modulo === 'usuarios') {
                $datos = $this->modelo->obtenerUsuarios((int) ($_POST['rol_filtro'] ?? 0));
            }

            // Copia de los datos completos: los KPIs se calculan con ella,
            // porque "resumido"/"personalizado" pueden quitar columnas como el monto.
            $datosCompletos = $datos;

            if (!empty($datos)) {
                if ($esExtra) {
                    // Columnas elegidas para los reportes extras (cada módulo tiene su propio grupo de casillas)
                    $columnasModulo = array_keys(self::EXTRAS[$modulo]['columnas']);
                    $columnasFinales = $columnasModulo;
                    if ($nivelDetalle === 'resumido') {
                        $columnasFinales = self::EXTRAS[$modulo]['resumido'];
                    } elseif ($nivelDetalle === 'personalizado') {
                        $recibidas = is_array($_POST['columnas_' . $modulo] ?? null) ? $_POST['columnas_' . $modulo] : [];
                        $recibidas = array_values(array_filter($recibidas, 'is_string'));
                        $elegidas = array_values(array_intersect($columnasModulo, $recibidas));
                        if (!empty($elegidas)) {
                            $columnasFinales = $elegidas;
                        }
                    }
                    $datos = array_map(function ($fila) use ($columnasFinales) {
                        $nueva = [];
                        foreach ($columnasFinales as $col) {
                            if (array_key_exists($col, $fila)) {
                                $nueva[$col] = $fila[$col];
                            }
                        }
                        return $nueva;
                    }, $datos);
                } elseif ($nivelDetalle === 'resumido') {
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

            // KPIs del reporte (se usan en el correo y en el resumen del PDF/Excel)
            $calcularKpis = function () use ($modulo, $esExtra, $datosCompletos) {
                if ($esExtra) {
                    return $this->kpisExtra($modulo, $datosCompletos);
                }
                require_once(__DIR__ . '/../modelo/ReporteMailer.php');
                return ReporteMailer::calcularKpis($modulo, $datosCompletos);
            };

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
                $this->exigirLibrerias();

                try {
                    require_once(__DIR__ . '/../modelo/ReporteMailer.php');
                    $kpis = $calcularKpis();
                    (new ReporteMailer())->enviar($destino, $titulo, $kpis, $datos, $adjuntos);
                } catch (\Throwable $e) {
                    error_log('Error enviando reporte por correo: ' . $e->getMessage());
                    $this->redirigirConMensaje('error', 'No se pudo enviar el correo: ' . $e->getMessage());
                }

                $this->redirigirConMensaje('ok', 'Reporte enviado correctamente a ' . $destino . '.');
            }

            // Descarga real en PDF o Excel (.xlsx)
            if ($formato === 'pdf' || $formato === 'xlsx') {
                if (empty($datos) || empty($datos[0])) {
                    $this->redirigirConMensaje('error', 'No hay registros para exportar con los filtros seleccionados.');
                }
                $this->exigirLibrerias();

                try {
                    require_once(__DIR__ . '/../modelo/ReporteExportador.php');
                    $resumen = $calcularKpis();
                    ReporteExportador::descargar(
                        $formato,
                        'reporte_' . $modulo . '_' . date('Ymd_His'),
                        $titulo,
                        [['titulo' => $titulos[$modulo], 'datos' => $datos]],
                        $resumen
                    );
                } catch (\Throwable $e) {
                    error_log('Error generando reporte ' . $formato . ': ' . $e->getMessage());
                    $this->redirigirConMensaje('error', 'No se pudo generar el archivo: ' . $e->getMessage());
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

    // Reporte completo de la actividad en el sistema: reúne todos los reportes en un solo PDF o Excel
    public function completo() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirigirConMensaje('error', 'Solicitud no válida.');
        }
        $formato = (($_POST['formato'] ?? '') === 'xlsx') ? 'xlsx' : 'pdf';
        $this->exigirLibrerias();

        @set_time_limit(180);
        @ini_set('memory_limit', '512M');

        try {
            require_once(__DIR__ . '/../modelo/ReporteExportador.php');

            $pagosClientes   = $this->modelo->obtenerPagos('clientes');
            $pagosNomina     = $this->modelo->obtenerPagos('nomina');
            $pagosServicios  = $this->modelo->obtenerPagos('servicios');
            $clientes        = $this->modelo->obtenerClientesPorEstatus('');
            $clientes        = array_map(function ($f) { unset($f['id_cliente']); return $f; }, $clientes); // la cédula ya es el id
            $entrenadores    = $this->modelo->obtenerEntrenadores('');
            $fondosSaldos    = $this->modelo->obtenerFondosSaldos('');
            $fondosMov       = $this->modelo->obtenerFondosMovimientos();
            $usuarios        = esAdmin() ? $this->modelo->obtenerUsuarios(0) : null;

            $suma = function (array $filas, $campo) {
                $t = 0;
                foreach ($filas as $f) { $t += (float) ($f[$campo] ?? 0); }
                return $t;
            };
            $solventes = 0;
            foreach ($clientes as $c) {
                if (($c['estatus_solvencia'] ?? '') === 'Solvente') { $solventes++; }
            }
            $entrenadoresActivos = 0;
            foreach ($entrenadores as $e) {
                if (strtolower((string) ($e['estatus'] ?? '')) === 'activo') { $entrenadoresActivos++; }
            }
            $saldoFondos = 0;
            foreach ($fondosSaldos as $f) {
                if (strpos((string) ($f['banco'] ?? ''), 'TOTAL') !== 0) { $saldoFondos += (float) ($f['saldo_usd'] ?? 0); }
            }
            $ingresos = $suma($pagosClientes, 'monto');
            $egresos = $suma($pagosNomina, 'monto') + $suma($pagosServicios, 'monto');

            $resumen = [
                'Fecha de generación'              => date('d/m/Y H:i'),
                'Generado por'                     => $_SESSION['admin_user'] ?? 'N/D',
                'Clientes registrados'             => (string) count($clientes),
                'Clientes solventes'               => (string) $solventes,
                'Clientes insolventes'             => (string) (count($clientes) - $solventes),
                'Entrenadores (activos / total)'   => $entrenadoresActivos . ' / ' . count($entrenadores),
                'Pagos de clientes (cantidad)'     => (string) count($pagosClientes),
                'Ingresos por pagos de clientes (USD)' => $this->fmt($ingresos),
                'Pagos de nómina (cantidad)'       => (string) count($pagosNomina),
                'Pagos de servicios (cantidad)'    => (string) count($pagosServicios),
                'Egresos nómina + servicios (USD)' => $this->fmt($egresos),
                'Balance ingresos - egresos (USD)' => $this->fmt($ingresos - $egresos),
                'Saldo total en fondos (USD)'      => $this->fmt($saldoFondos),
            ];
            if ($usuarios !== null) {
                $resumen['Usuarios del sistema'] = (string) count($usuarios);
            }

            $secciones = [
                ['titulo' => 'Pagos de clientes',   'datos' => $pagosClientes],
                ['titulo' => 'Pagos de nómina',     'datos' => $pagosNomina],
                ['titulo' => 'Pagos de servicios',  'datos' => $pagosServicios],
                ['titulo' => 'Clientes',            'datos' => $clientes],
                ['titulo' => 'Entrenadores',        'datos' => $entrenadores],
                ['titulo' => 'Fondos - Saldos',     'datos' => $fondosSaldos],
                ['titulo' => 'Fondos - Movimientos', 'datos' => $fondosMov],
            ];
            if ($usuarios !== null) {
                $secciones[] = ['titulo' => 'Usuarios y roles', 'datos' => $usuarios];
            }

            ReporteExportador::descargar(
                $formato,
                'reporte_completo_actividad_' . date('Ymd_His'),
                'Reporte completo de la actividad en el sistema',
                $secciones,
                $resumen
            );
        } catch (\Throwable $e) {
            error_log('Error generando reporte completo: ' . $e->getMessage());
            $this->redirigirConMensaje('error', 'No se pudo generar el reporte completo: ' . $e->getMessage());
        }
    }
}

if (basename($_SERVER['SCRIPT_FILENAME'] ?? '') === basename(__FILE__)) {
    $controlador = new Reportecontrolador();
    $accion = $_GET['action'] ?? 'index';

    if (in_array($accion, ['index', 'generar', 'guardarConfig', 'completo'], true)) {
        $controlador->$accion();
    } else {
        http_response_code(404);
        echo 'Acción no encontrada.';
    }
}
?>
