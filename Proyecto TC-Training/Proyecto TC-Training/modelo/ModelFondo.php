<?php
require_once __DIR__ . '/ModelTasa.php';

class ModelFondo {
    private $db;
    private $lastError = null;

    public function __construct($db_conn) {
        $this->db = $db_conn;
        ModelTasa::asegurarEsquema($db_conn);
    }

    public function getLastError() {
        return $this->lastError;
    }

    // Suma de montos por cuenta en una tabla de movimientos.
    // Devuelve [id_cuenta => total]; los movimientos sin cuenta (NULL o 0) van bajo la clave 'sin_cuenta'.
    // Cada tabla se consulta por separado: si una falla, las demás siguen sumando y el error queda en getLastError().
    private function sumarPorCuenta($tabla, $columnaMonto) {
        $totales = [];
        try {
            $stmt = $this->db->query("SELECT id_cuenta, SUM({$columnaMonto}) AS total FROM {$tabla} GROUP BY id_cuenta");
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
                $clave = empty($fila['id_cuenta']) ? 'sin_cuenta' : (int) $fila['id_cuenta'];
                $totales[$clave] = ($totales[$clave] ?? 0) + floatval($fila['total']);
            }
        } catch (PDOException $e) {
            $this->lastError = "Tabla {$tabla}: " . $e->getMessage();
        }
        return $totales;
    }

    // Obtener todas las cuentas con sus ingresos, egresos y saldo calculado (ingresos - egresos)
    public function obtenerTodos() {
        $sql = "SELECT c.id_cuenta, c.num_cuenta, c.tipo_moneda, b.nombre AS banco_nombre
                FROM cuenta c
                INNER JOIN banco b ON c.id_banco = b.id_banco
                ORDER BY c.id_cuenta ASC";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $cuentas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return [];
        }

        $pagos = $this->sumarPorCuenta('pagos', 'Monto');
        $nomina = $this->sumarPorCuenta('pago_nomina', 'monto');
        $servicios = $this->sumarPorCuenta('pago_servicio', 'monto');

        foreach ($cuentas as $i => $cuenta) {
            $id = (int) $cuenta['id_cuenta'];
            $ingresos = $pagos[$id] ?? 0;
            $egresos = ($nomina[$id] ?? 0) + ($servicios[$id] ?? 0);
            $cuentas[$i]['ingresos'] = $ingresos;
            $cuentas[$i]['egresos'] = $egresos;
            $cuentas[$i]['saldo'] = $ingresos - $egresos;
        }
        return $cuentas;
    }

    // Tasas registradas, de la más reciente a la más antigua
    private function cargarTasas() {
        try {
            $stmt = $this->db->query("SELECT fecha, tasa FROM tasa_cambio ORDER BY fecha DESC");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return [];
        }
    }

    // Convierte un monto a dólares. En USD queda igual; en BS se divide entre la última tasa registrada hasta esa fecha.
    // Si no hay ninguna tasa para esa fecha, el monto se deja sin convertir.
    private function aDolares($monto, $moneda, $fecha, $tasas) {
        $monto = floatval($monto);
        if (strtoupper((string) $moneda) !== 'BS') {
            return $monto;
        }
        foreach ($tasas as $t) {
            if ($t['fecha'] <= $fecha && floatval($t['tasa']) > 0) {
                return $monto / floatval($t['tasa']);
            }
        }
        return $monto;
    }

    // Totales en dólares: general, sin cuenta (efectivo) y por cuenta.
    // Los pagos de clientes usan el equivalente guardado al registrarlos; nómina y servicios se convierten con la tasa de su fecha.
    public function obtenerTotalesUSD() {
        $vacio = ['ingresos' => 0, 'egresos' => 0, 'saldo' => 0];
        $res = ['general' => $vacio, 'sin_cuenta' => $vacio, 'cuentas' => []];
        $tasas = $this->cargarTasas();

        $monedas = [];
        try {
            $stmt = $this->db->query("SELECT id_cuenta, tipo_moneda FROM cuenta");
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $c) {
                $monedas[(int) $c['id_cuenta']] = $c['tipo_moneda'];
            }
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
        }

        $origenes = [
            ['ingresos', "SELECT id_cuenta, Monto AS monto, Fecha AS fecha, monto_usd FROM pagos"],
            ['egresos',  "SELECT id_cuenta, monto, fecha_pago AS fecha, NULL AS monto_usd FROM pago_nomina"],
            ['egresos',  "SELECT id_cuenta, monto, fecha, NULL AS monto_usd FROM pago_servicio"]
        ];

        foreach ($origenes as $origen) {
            $tipo = $origen[0];
            try {
                $stmt = $this->db->query($origen[1]);
                foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
                    $id = (int) $fila['id_cuenta'];
                    if ($fila['monto_usd'] !== null) {
                        $usd = floatval($fila['monto_usd']);
                    } else {
                        $usd = $this->aDolares($fila['monto'], $monedas[$id] ?? 'USD', $fila['fecha'], $tasas);
                    }

                    $res['general'][$tipo] += $usd;
                    if ($id > 0) {
                        if (!isset($res['cuentas'][$id])) {
                            $res['cuentas'][$id] = $vacio;
                        }
                        $res['cuentas'][$id][$tipo] += $usd;
                    } else {
                        $res['sin_cuenta'][$tipo] += $usd;
                    }
                }
            } catch (PDOException $e) {
                $this->lastError = $e->getMessage();
            }
        }

        $res['general']['saldo'] = $res['general']['ingresos'] - $res['general']['egresos'];
        $res['sin_cuenta']['saldo'] = $res['sin_cuenta']['ingresos'] - $res['sin_cuenta']['egresos'];
        foreach ($res['cuentas'] as $id => $c) {
            $res['cuentas'][$id]['saldo'] = $c['ingresos'] - $c['egresos'];
        }
        return $res;
    }

    // Resumen de los movimientos registrados sin cuenta (efectivo / caja)
    public function obtenerResumenSinCuenta() {
        $ingresos = $this->sumarPorCuenta('pagos', 'Monto')['sin_cuenta'] ?? 0;
        $egresos = ($this->sumarPorCuenta('pago_nomina', 'monto')['sin_cuenta'] ?? 0)
                 + ($this->sumarPorCuenta('pago_servicio', 'monto')['sin_cuenta'] ?? 0);
        return ['ingresos' => $ingresos, 'egresos' => $egresos, 'saldo' => $ingresos - $egresos];
    }

    // Registrar nuevo fondo en la tabla cuenta
    public function registrar($id_banco, $num_cuenta, $tipo_moneda, $saldo_inicial = 0) {
        $sql = "INSERT INTO cuenta (id_banco, num_cuenta, tipo_moneda, saldo) VALUES (?, ?, ?, ?)";
        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$id_banco, $num_cuenta, $tipo_moneda, $saldo_inicial]);
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    // Obtener transacciones unificadas con filtrado directo por tabla
    public function obtenerTransacciones($id_cuenta = 'todas') {
        $params = [];
        $whereCliente = "";
        $whereNomina = "";
        $whereServicio = "";

        if ($id_cuenta === 'sin_cuenta') {
            $whereCliente = " WHERE (p.id_cuenta IS NULL OR p.id_cuenta = 0) ";
            $whereNomina = " WHERE (n.id_cuenta IS NULL OR n.id_cuenta = 0) ";
            $whereServicio = " WHERE (ps.id_cuenta IS NULL OR ps.id_cuenta = 0) ";
        } elseif (!empty($id_cuenta) && $id_cuenta !== 'todas' && $id_cuenta !== 'all') {
            $whereCliente = " WHERE p.id_cuenta = ? ";
            $whereNomina = " WHERE n.id_cuenta = ? ";
            $whereServicio = " WHERE ps.id_cuenta = ? ";
            $params = [intval($id_cuenta), intval($id_cuenta), intval($id_cuenta)];
        }

        $sql = "
            SELECT 
                'ingreso' AS tipo_movimiento,
                p.id_pago AS id_referencia,
                p.Monto AS monto,
                p.tasa AS tasa,
                p.monto_usd AS monto_usd,
                p.Fecha AS fecha,
                CONCAT('Pago Cliente: ', IFNULL(CONCAT(cli.Nombre, ' ', cli.apellido), 'Cliente General')) AS concepto,
                p.id_cuenta AS id_cuenta,
                m.nombre AS nombre_metodo,
                c.num_cuenta AS num_cuenta,
                b.nombre AS banco_nombre,
                c.tipo_moneda AS tipo_moneda
            FROM pagos p
            LEFT JOIN cliente cli ON p.id_cliente = cli.id_cliente
            LEFT JOIN metodo_pago m ON p.id_metodo = m.id_metodo
            LEFT JOIN cuenta c ON p.id_cuenta = c.id_cuenta
            LEFT JOIN banco b ON c.id_banco = b.id_banco
            {$whereCliente}

            UNION ALL

            SELECT 
                'egreso_nomina' AS tipo_movimiento,
                n.id_nomina AS id_referencia,
                n.monto AS monto,
                NULL AS tasa,
                NULL AS monto_usd,
                n.fecha_pago AS fecha,
                CONCAT('Nómina: ', IFNULL(CONCAT(e.nombre, ' ', e.apellido), n.concepto)) AS concepto,
                n.id_cuenta AS id_cuenta,
                m.nombre AS nombre_metodo,
                c.num_cuenta AS num_cuenta,
                b.nombre AS banco_nombre,
                c.tipo_moneda AS tipo_moneda
            FROM pago_nomina n
            LEFT JOIN entrenador e ON n.id_entrenador = e.cedula
            LEFT JOIN metodo_pago m ON n.id_metodo = m.id_metodo
            LEFT JOIN cuenta c ON n.id_cuenta = c.id_cuenta
            LEFT JOIN banco b ON c.id_banco = b.id_banco
            {$whereNomina}

            UNION ALL

            SELECT 
                'egreso_servicio' AS tipo_movimiento,
                ps.id_pago_servicio AS id_referencia,
                ps.monto AS monto,
                NULL AS tasa,
                NULL AS monto_usd,
                ps.fecha AS fecha,
                CONCAT('Servicio/Gasto: ', ps.concepto) AS concepto,
                ps.id_cuenta AS id_cuenta,
                m.nombre AS nombre_metodo,
                c.num_cuenta AS num_cuenta,
                b.nombre AS banco_nombre,
                c.tipo_moneda AS tipo_moneda
            FROM pago_servicio ps
            LEFT JOIN metodo_pago m ON ps.id_metodo = m.id_metodo
            LEFT JOIN cuenta c ON ps.id_cuenta = c.id_cuenta
            LEFT JOIN banco b ON c.id_banco = b.id_banco
            {$whereServicio}

            ORDER BY fecha DESC, id_referencia DESC
        ";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $resultado = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return is_array($resultado) ? $resultado : [];
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return [];
        }
    }

    // Obtener la lista de bancos registrados
    public function obtenerBancos() {
        try {
            $stmt = $this->db->query("SELECT id_banco, nombre FROM banco ORDER BY nombre ASC");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return [];
        }
    }

    // Buscar un banco por nombre; si no existe, lo crea. Devuelve su id o false si falla
    public function obtenerOCrearBanco($nombre) {
        try {
            $stmt = $this->db->prepare("SELECT id_banco FROM banco WHERE nombre = ? LIMIT 1");
            $stmt->execute([$nombre]);
            $id = $stmt->fetchColumn();
            if ($id) {
                return (int) $id;
            }
            $stmt = $this->db->prepare("INSERT INTO banco (nombre) VALUES (?)");
            $stmt->execute([$nombre]);
            return (int) $this->db->lastInsertId();
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    // Verificar si ya existe una cuenta con ese número en ese banco
    public function existeCuenta($id_banco, $num_cuenta) {
        try {
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM cuenta WHERE id_banco = ? AND num_cuenta = ?");
            $stmt->execute([$id_banco, $num_cuenta]);
            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    // Verificar si ya existe un método de pago con ese nombre
    public function existeMetodo($nombre) {
        try {
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM metodo_pago WHERE nombre = ?");
            $stmt->execute([$nombre]);
            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    // Registrar un nuevo método de pago
    public function registrarMetodo($nombre) {
        try {
            $stmt = $this->db->prepare("INSERT INTO metodo_pago (nombre) VALUES (?)");
            return $stmt->execute([$nombre]);
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }
}
?>