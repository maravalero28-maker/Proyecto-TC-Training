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
    private function sumarPorCuenta($tabla, $columnaMonto, $condicion = '') {
        $totales = [];
        try {
            $where = $condicion !== '' ? " WHERE {$condicion}" : '';
            $stmt = $this->db->query("SELECT id_cuenta, SUM({$columnaMonto}) AS total FROM {$tabla}{$where} GROUP BY id_cuenta");
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
        $sql = "SELECT c.id_cuenta, c.num_cuenta, c.tipo_moneda, c.saldo_inicial, b.nombre AS banco_nombre
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

        $pagos = $this->sumarPorCuenta('pagos', 'Monto', 'anulado = 0');
        $nomina = $this->sumarPorCuenta('pago_nomina', 'monto', 'anulado = 0');
        $servicios = $this->sumarPorCuenta('pago_servicio', 'monto', 'anulado = 0');

        foreach ($cuentas as $i => $cuenta) {
            $id = (int) $cuenta['id_cuenta'];
            $ingresos = $pagos[$id] ?? 0;
            $egresos = ($nomina[$id] ?? 0) + ($servicios[$id] ?? 0);
            $inicial = floatval($cuenta['saldo_inicial'] ?? 0);
            $cuentas[$i]['saldo_inicial'] = $inicial;
            $cuentas[$i]['ingresos'] = $ingresos;
            $cuentas[$i]['egresos'] = $egresos;
            $cuentas[$i]['saldo'] = $inicial + $ingresos - $egresos;
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
        $vacio = ['inicial' => 0, 'ingresos' => 0, 'egresos' => 0, 'saldo' => 0];
        $res = ['general' => $vacio, 'sin_cuenta' => $vacio, 'cuentas' => []];
        $tasas = $this->cargarTasas();

        $monedas = [];
        try {
            $stmt = $this->db->query("SELECT id_cuenta, tipo_moneda, saldo_inicial_usd FROM cuenta");
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $c) {
                $idCuenta = (int) $c['id_cuenta'];
                $monedas[$idCuenta] = $c['tipo_moneda'];
                $inicialUsd = floatval($c['saldo_inicial_usd'] ?? 0);
                $res['cuentas'][$idCuenta] = $vacio;
                $res['cuentas'][$idCuenta]['inicial'] = $inicialUsd;
                $res['general']['inicial'] += $inicialUsd;
            }
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
        }

        $origenes = [
            ['ingresos', "SELECT id_cuenta, Monto AS monto, Fecha AS fecha, monto_usd FROM pagos WHERE anulado = 0"],
            ['egresos',  "SELECT id_cuenta, monto, fecha_pago AS fecha, monto_usd FROM pago_nomina WHERE anulado = 0"],
            ['egresos',  "SELECT id_cuenta, monto, fecha, monto_usd FROM pago_servicio WHERE anulado = 0"]
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

        $res['general']['saldo'] = $res['general']['inicial'] + $res['general']['ingresos'] - $res['general']['egresos'];
        $res['sin_cuenta']['saldo'] = $res['sin_cuenta']['ingresos'] - $res['sin_cuenta']['egresos'];
        foreach ($res['cuentas'] as $id => $c) {
            $res['cuentas'][$id]['saldo'] = $c['inicial'] + $c['ingresos'] - $c['egresos'];
        }
        return $res;
    }

    // Resumen de los movimientos registrados sin cuenta (efectivo / caja)
    public function obtenerResumenSinCuenta() {
        $ingresos = $this->sumarPorCuenta('pagos', 'Monto', 'anulado = 0')['sin_cuenta'] ?? 0;
        $egresos = ($this->sumarPorCuenta('pago_nomina', 'monto', 'anulado = 0')['sin_cuenta'] ?? 0)
                 + ($this->sumarPorCuenta('pago_servicio', 'monto', 'anulado = 0')['sin_cuenta'] ?? 0);
        return ['ingresos' => $ingresos, 'egresos' => $egresos, 'saldo' => $ingresos - $egresos];
    }

    // Saldo disponible de una cuenta en su propia moneda: saldo inicial + ingresos - egresos (sin contar pagos inhabilitados).
    // Al editar un movimiento se puede excluir (tabla e id) para no contarlo dos veces. Devuelve null si falla.
    public function obtenerSaldoCuenta($id_cuenta, $excluirTabla = null, $excluirId = null) {
        try {
            $stmt = $this->db->prepare("SELECT IFNULL(saldo_inicial, 0) FROM cuenta WHERE id_cuenta = ?");
            $stmt->execute([$id_cuenta]);
            $saldo = floatval($stmt->fetchColumn());

            $stmt = $this->db->prepare("SELECT IFNULL(SUM(Monto), 0) FROM pagos WHERE id_cuenta = ? AND anulado = 0");
            $stmt->execute([$id_cuenta]);
            $saldo += floatval($stmt->fetchColumn());

            $egresos = [['pago_nomina', 'id_nomina'], ['pago_servicio', 'id_pago_servicio']];
            foreach ($egresos as $e) {
                $sql = "SELECT IFNULL(SUM(monto), 0) FROM {$e[0]} WHERE id_cuenta = ? AND anulado = 0";
                $params = [$id_cuenta];
                if ($excluirTabla === $e[0] && !empty($excluirId)) {
                    $sql .= " AND {$e[1]} <> ?";
                    $params[] = $excluirId;
                }
                $stmt = $this->db->prepare($sql);
                $stmt->execute($params);
                $saldo -= floatval($stmt->fetchColumn());
            }
            return $saldo;
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return null;
        }
    }

    // Revisa que la cuenta tenga saldo suficiente para un egreso (monto en la moneda de la cuenta).
    // Devuelve un texto de error, o vacío si alcanza.
    public function verificarSaldoEgreso($cuentasList, $id_cuenta, $monto, $excluirTabla = null, $excluirId = null) {
        $cuenta = null;
        foreach ($cuentasList as $c) {
            if ($c['id_cuenta'] == $id_cuenta) {
                $cuenta = $c;
                break;
            }
        }
        if (!$cuenta) {
            return 'La cuenta seleccionada no existe.';
        }

        $saldo = $this->obtenerSaldoCuenta($id_cuenta, $excluirTabla, $excluirId);
        if ($saldo === null) {
            return 'No se pudo verificar el saldo de la cuenta.';
        }

        if (round($monto, 2) > round($saldo, 2)) {
            $prefijo = (strtoupper(trim($cuenta['tipo_moneda'])) === 'BS') ? 'Bs ' : '$';
            return 'Saldo insuficiente en ' . $cuenta['banco_nombre'] . ' - ' . $cuenta['num_cuenta'] . ' (' . $cuenta['tipo_moneda'] . '): disponible '
                 . $prefijo . number_format($saldo, 2, ',', '.') . ' y el pago es de ' . $prefijo . number_format($monto, 2, ',', '.') . '.';
        }
        return '';
    }

    // Valida un pago que sale de una cuenta (nómina o servicios): cuenta obligatoria, conversión a dólares y saldo suficiente.
    // Devuelve ['error' => texto o vacío, 'moneda' => ..., 'tasa' => ..., 'monto_usd' => ...]
    public function validarEgreso($cuentasList, $id_cuenta, $monto, $fecha, $excluirTabla = null, $excluirId = null) {
        $res = ['error' => '', 'moneda' => null, 'tasa' => null, 'monto_usd' => null];

        if (empty($id_cuenta)) {
            $res['error'] = 'Selecciona la cuenta desde la que sale el pago.';
            return $res;
        }

        $modelTasa = new ModelTasa($this->db);
        $conv = $modelTasa->convertirMonto($cuentasList, $id_cuenta, $monto, $fecha);
        if ($conv['error'] !== '') {
            $res['error'] = $conv['error'];
            return $res;
        }

        $errorSaldo = $this->verificarSaldoEgreso($cuentasList, $id_cuenta, $monto, $excluirTabla, $excluirId);
        if ($errorSaldo !== '') {
            $res['error'] = $errorSaldo;
            return $res;
        }

        $res['moneda'] = $conv['moneda'];
        $res['tasa'] = $conv['tasa'];
        $res['monto_usd'] = $conv['monto_usd'];
        return $res;
    }

    // Registrar una cuenta nueva con su saldo inicial (en su moneda y su equivalente en dólares)
    public function registrarCuenta($id_banco, $num_cuenta, $tipo_moneda, $saldo_inicial = 0, $saldo_inicial_usd = 0) {
        $sql = "INSERT INTO cuenta (id_banco, num_cuenta, tipo_moneda, saldo, saldo_inicial, saldo_inicial_usd) VALUES (?, ?, ?, 0, ?, ?)";
        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$id_banco, $num_cuenta, $tipo_moneda, $saldo_inicial, $saldo_inicial_usd]);
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
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
        $whereCliente = " WHERE p.anulado = 0 ";
        $whereNomina = " WHERE n.anulado = 0 ";
        $whereServicio = " WHERE ps.anulado = 0 ";

        if ($id_cuenta === 'sin_cuenta') {
            $whereCliente = " WHERE p.anulado = 0 AND (p.id_cuenta IS NULL OR p.id_cuenta = 0) ";
            $whereNomina = " WHERE n.anulado = 0 AND (n.id_cuenta IS NULL OR n.id_cuenta = 0) ";
            $whereServicio = " WHERE ps.anulado = 0 AND (ps.id_cuenta IS NULL OR ps.id_cuenta = 0) ";
        } elseif (!empty($id_cuenta) && $id_cuenta !== 'todas' && $id_cuenta !== 'all') {
            $whereCliente = " WHERE p.anulado = 0 AND p.id_cuenta = ? ";
            $whereNomina = " WHERE n.anulado = 0 AND n.id_cuenta = ? ";
            $whereServicio = " WHERE ps.anulado = 0 AND ps.id_cuenta = ? ";
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
                n.tasa AS tasa,
                n.monto_usd AS monto_usd,
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
                ps.tasa AS tasa,
                ps.monto_usd AS monto_usd,
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