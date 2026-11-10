<?php
class Reporte {
    private $db;

    public function __construct() {
        require_once(__DIR__ . '/../configuracion/conecxion.php');
        $this->db = (new Conexion())->getConexion();
        require_once(__DIR__ . '/ModelTasa.php');
        ModelTasa::asegurarEsquema($this->db);
    }

    public function obtenerPagos($origen, $fechaInicio = '', $fechaFin = '', $metodoPago = '', $banco = '') {
        $sql = '';
        $params = [];

        if ($origen === 'clientes') {
            $sql = "SELECT p.id_pago, c.id_cliente AS cedula, c.Nombre AS nombre, COALESCE(p.monto_usd, p.Monto) AS monto,
                            mp.nombre AS metodo_pago, b.nombre AS banco_receptor, p.Fecha AS fecha_pago
                    FROM pagos p 
                    JOIN cliente c ON p.id_cliente = c.id_cliente
                    JOIN metodo_pago mp ON p.id_metodo = mp.id_metodo
                    JOIN cuenta cu ON p.id_cuenta = cu.id_cuenta
                    JOIN banco b ON cu.id_banco = b.id_banco WHERE p.anulado = 0";
        } elseif ($origen === 'nomina') {
            $sql = "SELECT n.id_nomina, e.cedula, e.nombre, COALESCE(n.monto_usd, n.monto) AS monto,
                            mp.nombre AS metodo_pago, b.nombre AS banco_receptor, n.fecha_pago
                    FROM pago_nomina n
                    JOIN entrenador e ON n.id_entrenador = e.cedula
                    JOIN metodo_pago mp ON n.id_metodo = mp.id_metodo
                    JOIN cuenta cu ON n.id_cuenta = cu.id_cuenta
                    JOIN banco b ON cu.id_banco = b.id_banco WHERE n.anulado = 0";
        } elseif ($origen === 'servicios') {
            $sql = "SELECT s.id_pago_servicio AS id_servicio, s.concepto, COALESCE(s.monto_usd, s.monto) AS monto,
                            mp.nombre AS metodo_pago, b.nombre AS banco_receptor, s.fecha AS fecha_pago
                    FROM pago_servicio s
                    JOIN metodo_pago mp ON s.id_metodo = mp.id_metodo
                    JOIN cuenta cu ON s.id_cuenta = cu.id_cuenta
                    JOIN banco b ON cu.id_banco = b.id_banco WHERE s.anulado = 0";
        } else {
            return [];
        }

        if (!empty($fechaInicio)) {
            $sql .= " AND DATE(" . ($origen === 'clientes' ? 'p.Fecha' : ($origen === 'nomina' ? 'n.fecha_pago' : 's.fecha')) . ") >= :fecha_inicio";
            $params[':fecha_inicio'] = $fechaInicio;
        }
        if (!empty($fechaFin)) {
            $sql .= " AND DATE(" . ($origen === 'clientes' ? 'p.Fecha' : ($origen === 'nomina' ? 'n.fecha_pago' : 's.fecha')) . ") <= :fecha_fin";
            $params[':fecha_fin'] = $fechaFin;
        }
        if (!empty($metodoPago)) {
            $sql .= " AND mp.nombre = :metodo_pago";
            $params[':metodo_pago'] = $metodoPago;
        }
        if (!empty($banco)) {
            $sql .= " AND b.nombre = :banco";
            $params[':banco'] = $banco;
        }

        $sql .= " ORDER BY " . ($origen === 'clientes' ? 'p.Fecha' : ($origen === 'nomina' ? 'n.fecha_pago' : 's.fecha')) . " DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerClientesPorEstatus($estatusFinanciero = '') {
        $sql = "SELECT c.id_cliente, c.id_cliente AS cedula, c.Nombre AS nombre, c.apellido,
                        c.telefono, c.Solvencia AS estatus_solvencia, p.nombre AS nombre_plan
                FROM cliente c
                LEFT JOIN planes p ON c.Plan = p.id_plan WHERE 1=1";
        $params = [];

        if ($estatusFinanciero === 'solvente') {
            $sql .= " AND c.Solvencia = 'Solvente'";
        } elseif ($estatusFinanciero === 'insolvente') {
            $sql .= " AND c.Solvencia = 'Insolvente'";
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ==================================================================
    //  REPORTES EXTRAS: entrenadores, fondos y usuarios
    // ==================================================================

    // Entrenadores con el resumen de lo que se les ha pagado por nómina (pagos no anulados)
    public function obtenerEntrenadores($estatus = '') {
        $sql = "SELECT e.cedula, e.nombre, e.apellido, e.telefono, e.estatus, e.fecha_registro,
                       COUNT(n.id_nomina) AS pagos_realizados,
                       COALESCE(SUM(COALESCE(n.monto_usd, n.monto)), 0) AS total_pagado_usd,
                       MAX(n.fecha_pago) AS ultimo_pago
                FROM entrenador e
                LEFT JOIN pago_nomina n ON n.id_entrenador = e.cedula AND n.anulado = 0
                WHERE 1=1";

        if ($estatus === 'activo') {
            $sql .= " AND LOWER(e.estatus) = 'activo'";
        } elseif ($estatus === 'inactivo') {
            $sql .= " AND LOWER(e.estatus) = 'inactivo'";
        }

        $sql .= " GROUP BY e.cedula, e.nombre, e.apellido, e.telefono, e.estatus, e.fecha_registro
                  ORDER BY e.nombre ASC, e.apellido ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Saldos por cuenta (en su moneda y en dólares). $moneda: '', 'USD' o 'BS'.
    // Al final agrega una fila "TOTAL (USD)" con la suma de las columnas en dólares.
    public function obtenerFondosSaldos($moneda = '') {
        require_once(__DIR__ . '/ModelFondo.php');
        $fondo = new ModelFondo($this->db);
        $cuentas = $fondo->obtenerTodos();
        $usd = $fondo->obtenerTotalesUSD();
        $moneda = strtoupper((string) $moneda);

        $filas = [];
        foreach ($cuentas as $c) {
            if ($moneda !== '' && strtoupper((string) $c['tipo_moneda']) !== $moneda) {
                continue;
            }
            $id = (int) $c['id_cuenta'];
            $u = $usd['cuentas'][$id] ?? ['ingresos' => 0, 'egresos' => 0, 'saldo' => 0];
            $filas[] = [
                'id_cuenta'     => $id,
                'banco'         => $c['banco_nombre'],
                'num_cuenta'    => $c['num_cuenta'],
                'tipo_moneda'   => $c['tipo_moneda'],
                'saldo_inicial' => round((float) $c['saldo_inicial'], 2),
                'ingresos'      => round((float) $c['ingresos'], 2),
                'egresos'       => round((float) $c['egresos'], 2),
                'saldo_actual'  => round((float) $c['saldo'], 2),
                'ingresos_usd'  => round((float) $u['ingresos'], 2),
                'egresos_usd'   => round((float) $u['egresos'], 2),
                'saldo_usd'     => round((float) $u['saldo'], 2),
            ];
        }

        // Movimientos antiguos registrados sin cuenta (efectivo / caja), solo si existen
        if ($moneda === '') {
            $caja = $usd['sin_cuenta'];
            if (abs($caja['ingresos']) > 0 || abs($caja['egresos']) > 0) {
                $resumenCaja = $fondo->obtenerResumenSinCuenta();
                $filas[] = [
                    'id_cuenta'     => '',
                    'banco'         => 'Efectivo / Caja (sin cuenta)',
                    'num_cuenta'    => '-',
                    'tipo_moneda'   => 'N/A',
                    'saldo_inicial' => 0,
                    'ingresos'      => round((float) $resumenCaja['ingresos'], 2),
                    'egresos'       => round((float) $resumenCaja['egresos'], 2),
                    'saldo_actual'  => round((float) $resumenCaja['saldo'], 2),
                    'ingresos_usd'  => round((float) $caja['ingresos'], 2),
                    'egresos_usd'   => round((float) $caja['egresos'], 2),
                    'saldo_usd'     => round((float) $caja['saldo'], 2),
                ];
            }
        }

        if (!empty($filas)) {
            $ing = $egr = $sal = 0;
            foreach ($filas as $f) {
                $ing += $f['ingresos_usd'];
                $egr += $f['egresos_usd'];
                $sal += $f['saldo_usd'];
            }
            $filas[] = [
                'id_cuenta'     => '',
                'banco'         => 'TOTAL (USD)',
                'num_cuenta'    => '',
                'tipo_moneda'   => '',
                'saldo_inicial' => '',
                'ingresos'      => '',
                'egresos'       => '',
                'saldo_actual'  => '',
                'ingresos_usd'  => round($ing, 2),
                'egresos_usd'   => round($egr, 2),
                'saldo_usd'     => round($sal, 2),
            ];
        }
        return $filas;
    }

    // Movimientos de fondos (ingresos y egresos de todas las cuentas) con filtro opcional de fechas y método
    public function obtenerFondosMovimientos($fechaInicio = '', $fechaFin = '', $metodoPago = '') {
        require_once(__DIR__ . '/ModelFondo.php');
        $fondo = new ModelFondo($this->db);
        $movimientos = $fondo->obtenerTransacciones('todas');

        $normalizar = function ($t) {
            return str_replace(' ', '', mb_strtolower(strtr((string) $t, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'Á' => 'a', 'É' => 'e', 'Í' => 'i', 'Ó' => 'o', 'Ú' => 'u'])));
        };
        $tipos = [
            'ingreso'         => 'Ingreso (pago de cliente)',
            'egreso_nomina'   => 'Egreso (nómina)',
            'egreso_servicio' => 'Egreso (servicio)',
        ];

        $filas = [];
        foreach ($movimientos as $m) {
            $fecha = (string) $m['fecha'];
            if ($fechaInicio !== '' && $fecha < $fechaInicio) {
                continue;
            }
            if ($fechaFin !== '' && $fecha > $fechaFin) {
                continue;
            }
            if ($metodoPago !== '' && $normalizar($m['nombre_metodo']) !== $normalizar($metodoPago)) {
                continue;
            }

            $montoUsd = $m['monto_usd'];
            if ($montoUsd === null && strtoupper((string) $m['tipo_moneda']) === 'USD') {
                $montoUsd = $m['monto'];
            }

            $filas[] = [
                'fecha'       => $fecha,
                'tipo'        => $tipos[$m['tipo_movimiento']] ?? $m['tipo_movimiento'],
                'concepto'    => $m['concepto'],
                'banco'       => $m['banco_nombre'] ?? 'Efectivo / Caja',
                'num_cuenta'  => $m['num_cuenta'] ?? '-',
                'metodo_pago' => $m['nombre_metodo'] ?? '-',
                'tipo_moneda' => $m['tipo_moneda'] ?? '-',
                'monto'       => round((float) $m['monto'], 2),
                'tasa'        => $m['tasa'] !== null ? (float) $m['tasa'] : '',
                'monto_usd'   => $montoUsd !== null ? round((float) $montoUsd, 2) : '',
            ];
        }
        return $filas;
    }

    // Roles disponibles (para el filtro del reporte de usuarios)
    public function obtenerRoles() {
        try {
            $stmt = $this->db->query("SELECT id_rol, nombre FROM rol_usuario ORDER BY id_rol ASC");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    // Usuarios del sistema con su rol y módulos. NO incluye contraseñas ni respuestas secretas.
    public function obtenerUsuarios($idRol = 0) {
        require_once(__DIR__ . '/Rol.php');
        require_once(__DIR__ . '/Administrador.php');
        new Administrador($this->db); // asegura las columnas de pregunta secreta e id_rol
        new Rol($this->db);           // asegura la columna "permisos" de los roles

        $sql = "SELECT a.id_admin, a.Usuario AS usuario, a.id_rol, r.nombre AS rol, r.descripcion AS descripcion_rol,
                       r.permisos AS permisos_rol, a.fecha_creacion,
                       (a.PreguntaSecreta IS NOT NULL AND a.PreguntaSecreta <> '') AS tiene_pregunta
                FROM administrador a
                LEFT JOIN rol_usuario r ON r.id_rol = a.id_rol";
        $params = [];
        if ((int) $idRol > 0) {
            $sql .= " WHERE a.id_rol = :id_rol";
            $params[':id_rol'] = (int) $idRol;
        }
        $sql .= " ORDER BY a.id_admin ASC";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }

        $filas = [];
        foreach ($usuarios as $u) {
            if ((int) $u['id_rol'] === 1) {
                $modulos = 'Acceso total (incluye registrar y gestionar usuarios)';
            } else {
                $claves = array_filter(explode(',', (string) $u['permisos_rol']));
                $nombres = array_map(function ($k) {
                    return Rol::MODULOS[$k] ?? $k;
                }, $claves);
                $modulos = $nombres ? implode(', ', $nombres) : 'Sin módulos asignados';
            }
            $filas[] = [
                'id_admin'           => (int) $u['id_admin'],
                'usuario'            => $u['usuario'],
                'rol'                => $u['rol'] ?? 'Sin rol',
                'descripcion_rol'    => $u['descripcion_rol'] ?? '-',
                'modulos_acceso'     => $modulos,
                'fecha_creacion'     => $u['fecha_creacion'],
                'pregunta_seguridad' => $u['tiene_pregunta'] ? 'Sí' : 'No',
            ];
        }
        return $filas;
    }
}
?>
