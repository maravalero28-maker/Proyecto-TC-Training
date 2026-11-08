<?php
// Tasas de cambio (Bs por dólar) registradas manualmente día a día
class ModelTasa {
    private $db;
    private $lastError = null;
    private static $esquemaListo = false;

    public function __construct($db_conn) {
        $this->db = $db_conn;
        self::asegurarEsquema($db_conn);
    }

    // Crea la tabla de tasas y agrega las columnas de conversión, de estado (anulado) y de saldo inicial si faltan
    public static function asegurarEsquema($db) {
        if (self::$esquemaListo) {
            return;
        }
        try {
            $db->exec("CREATE TABLE IF NOT EXISTS tasa_cambio (
                id_tasa INT(11) NOT NULL AUTO_INCREMENT,
                fecha DATE NOT NULL,
                tasa DECIMAL(18,4) NOT NULL,
                id_admin INT(11) DEFAULT NULL,
                PRIMARY KEY (id_tasa),
                UNIQUE KEY uq_tasa_fecha (fecha)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
        } catch (PDOException $e) {
        }

        $movimiento = [
            'moneda'    => "VARCHAR(3) DEFAULT NULL",
            'tasa'      => "DECIMAL(18,4) DEFAULT NULL",
            'monto_usd' => "DECIMAL(12,2) DEFAULT NULL",
            'anulado'   => "TINYINT(1) NOT NULL DEFAULT 0"
        ];
        $columnasPorTabla = [
            'pagos'         => $movimiento,
            'pago_nomina'   => $movimiento,
            'pago_servicio' => $movimiento,
            'cuenta'        => [
                'saldo_inicial'     => "DECIMAL(12,2) NOT NULL DEFAULT 0",
                'saldo_inicial_usd' => "DECIMAL(12,2) NOT NULL DEFAULT 0"
            ]
        ];
        foreach ($columnasPorTabla as $tabla => $columnas) {
            try {
                if ($db->query("SHOW TABLES LIKE '{$tabla}'")->rowCount() === 0) {
                    continue;
                }
                foreach ($columnas as $nombre => $definicion) {
                    $stmt = $db->query("SHOW COLUMNS FROM {$tabla} LIKE '{$nombre}'");
                    if ($stmt->rowCount() === 0) {
                        $db->exec("ALTER TABLE {$tabla} ADD COLUMN {$nombre} {$definicion}");
                    }
                }
            } catch (PDOException $e) {
            }
        }
        self::$esquemaListo = true;
    }

    public function getLastError() {
        return $this->lastError;
    }

    // Convierte un monto (escrito en la moneda de la cuenta) a dólares. Sin cuenta se toma como USD.
    // Para cuentas en BS exige la tasa registrada exactamente para esa fecha.
    // Devuelve ['moneda' => ..., 'tasa' => ..., 'monto_usd' => ..., 'error' => texto o vacío]
    public function convertirMonto($cuentasList, $id_cuenta, $monto, $fecha) {
        $moneda = 'USD';
        if (!empty($id_cuenta)) {
            foreach ($cuentasList as $c) {
                if ($c['id_cuenta'] == $id_cuenta) {
                    $moneda = strtoupper(trim($c['tipo_moneda']));
                    break;
                }
            }
        }

        $conv = ['moneda' => ($moneda === 'BS') ? 'BS' : 'USD', 'tasa' => null, 'monto_usd' => round($monto, 2), 'error' => ''];
        if ($moneda !== 'BS') {
            return $conv;
        }

        $tasa = $this->obtenerExacta($fecha);
        if (!$tasa) {
            $conv['error'] = 'No hay una tasa registrada para el ' . date('d/m/Y', strtotime($fecha)) . '. Regístrala en Fondos > Tasa del Día para poder registrar pagos en bolívares.';
            return $conv;
        }

        $conv['tasa'] = $tasa;
        $conv['monto_usd'] = round($monto / $tasa, 2);
        return $conv;
    }

    // Tasa registrada exactamente para esa fecha (o null)
    public function obtenerExacta($fecha) {
        try {
            $stmt = $this->db->prepare("SELECT tasa FROM tasa_cambio WHERE fecha = ? LIMIT 1");
            $stmt->execute([$fecha]);
            $valor = $stmt->fetchColumn();
            return $valor !== false ? floatval($valor) : null;
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return null;
        }
    }

    // Última tasa registrada hasta esa fecha (['fecha' => ..., 'tasa' => ...] o null)
    public function obtenerVigente($fecha) {
        try {
            $stmt = $this->db->prepare("SELECT fecha, tasa FROM tasa_cambio WHERE fecha <= ? ORDER BY fecha DESC LIMIT 1");
            $stmt->execute([$fecha]);
            $fila = $stmt->fetch(PDO::FETCH_ASSOC);
            return $fila ?: null;
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return null;
        }
    }

    // Últimas tasas registradas, de la más reciente a la más antigua
    public function obtenerRecientes($limite = 10) {
        $limite = max(1, intval($limite));
        try {
            $stmt = $this->db->query("SELECT fecha, tasa FROM tasa_cambio ORDER BY fecha DESC LIMIT {$limite}");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return [];
        }
    }

    // Guardar la tasa de una fecha; si ya existía, se corrige
    public function guardar($fecha, $tasa, $id_admin = null) {
        try {
            $stmt = $this->db->prepare("INSERT INTO tasa_cambio (fecha, tasa, id_admin) VALUES (?, ?, ?)
                                        ON DUPLICATE KEY UPDATE tasa = VALUES(tasa), id_admin = VALUES(id_admin)");
            return $stmt->execute([$fecha, $tasa, $id_admin]);
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }
}
?>