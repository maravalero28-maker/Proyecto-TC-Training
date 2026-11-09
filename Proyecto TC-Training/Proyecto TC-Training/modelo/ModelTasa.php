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
            'pagos'         => array_merge($movimiento, [
                'id_plan' => "INT(11) DEFAULT NULL"
            ]),
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

        try {
            if ($db->query("SHOW TABLES LIKE 'pagos'")->rowCount() > 0
                && $db->query("SHOW TABLES LIKE 'cliente'")->rowCount() > 0
                && $db->query("SHOW TABLES LIKE 'planes'")->rowCount() > 0) {
                $db->exec("UPDATE pagos p
                           INNER JOIN cliente c ON c.id_cliente = p.id_cliente
                           SET p.id_plan = (
                               SELECT pl.id_plan
                               FROM planes pl
                               WHERE pl.id_plan = CAST(c.Plan AS UNSIGNED) OR pl.nombre = c.Plan
                               LIMIT 1
                           )
                           WHERE p.id_plan IS NULL");
            }

        } catch (PDOException $e) {
        }

        self::migrarSaldoFisico($db);
        self::asegurarRelaciones($db);
        self::$esquemaListo = true;
    }

    private static function migrarSaldoFisico($db) {
        $tablas = ['cuenta', 'pagos', 'pago_nomina', 'pago_servicio'];
        foreach ($tablas as $tabla) {
            if ($db->query("SHOW TABLES LIKE '{$tabla}'")->rowCount() === 0) {
                return;
            }
        }

        try {
            $db->exec("CREATE TABLE IF NOT EXISTS migracion_saldo_cuenta (
                id TINYINT(1) NOT NULL PRIMARY KEY,
                completada TINYINT(1) NOT NULL DEFAULT 0
            ) ENGINE=InnoDB");
            $db->exec("INSERT IGNORE INTO migracion_saldo_cuenta (id, completada) VALUES (1, 0)");

            $db->beginTransaction();
            $stmtMigracion = $db->query("SELECT completada FROM migracion_saldo_cuenta WHERE id = 1 FOR UPDATE");
            if ((int) $stmtMigracion->fetchColumn() === 0) {
                $db->exec("UPDATE cuenta SET saldo_inicial = saldo WHERE saldo_inicial = 0 AND saldo <> 0");
                $db->exec("UPDATE cuenta
                           SET saldo_inicial_usd = CASE
                               WHEN UPPER(tipo_moneda) = 'BS' THEN COALESCE(ROUND(
                                   saldo_inicial / NULLIF((SELECT tasa FROM tasa_cambio ORDER BY fecha DESC LIMIT 1), 0), 2
                               ), 0)
                               ELSE saldo_inicial
                           END
                           WHERE saldo_inicial_usd = 0 AND saldo_inicial <> 0");
                $db->exec("UPDATE cuenta c
                           SET c.saldo = COALESCE(c.saldo_inicial, 0)
                               + (SELECT COALESCE(SUM(p.Monto), 0) FROM pagos p WHERE p.id_cuenta = c.id_cuenta AND p.anulado = 0)
                               - (SELECT COALESCE(SUM(n.monto), 0) FROM pago_nomina n WHERE n.id_cuenta = c.id_cuenta AND n.anulado = 0)
                               - (SELECT COALESCE(SUM(ps.monto), 0) FROM pago_servicio ps WHERE ps.id_cuenta = c.id_cuenta AND ps.anulado = 0)");
                $db->exec("UPDATE migracion_saldo_cuenta SET completada = 1 WHERE id = 1");
            }
            $db->commit();
        } catch (PDOException $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw new RuntimeException('No fue posible sincronizar el saldo físico de las cuentas.', 0, $e);
        }
    }

    private static function asegurarRelaciones($db) {
        if ($db->query("SHOW TABLES LIKE 'cliente'")->rowCount() > 0
            && $db->query("SHOW TABLES LIKE 'planes'")->rowCount() > 0) {
            $db->exec("UPDATE cliente c
                       INNER JOIN planes pl ON pl.nombre = c.Plan
                       SET c.Plan = pl.id_plan
                       WHERE c.Plan NOT REGEXP '^[0-9]+$'");

            $stmtInvalidos = $db->query("SELECT COUNT(*)
                                         FROM cliente c
                                         LEFT JOIN planes pl ON pl.id_plan = CAST(c.Plan AS UNSIGNED)
                                         WHERE pl.id_plan IS NULL");
            if ((int) $stmtInvalidos->fetchColumn() === 0) {
                $stmtPlan = $db->query("SHOW COLUMNS FROM cliente LIKE 'Plan'");
                $columnaPlan = $stmtPlan->fetch(PDO::FETCH_ASSOC);
                if ($columnaPlan && strtolower($columnaPlan['Type']) !== 'int(11)') {
                    $db->exec("ALTER TABLE cliente MODIFY Plan INT(11) NOT NULL DEFAULT 2");
                }
            }
        }

        $relaciones = [
            ['cliente', 'Plan', 'planes', 'id_plan', 'fk_cliente_plan'],
            ['pagos', 'id_cliente', 'cliente', 'id_cliente', 'fk_pago_cliente'],
            ['pagos', 'id_cuenta', 'cuenta', 'id_cuenta', 'fk_pago_cuenta'],
            ['pagos', 'id_metodo', 'metodo_pago', 'id_metodo', 'fk_metodo_pago_pagos'],
            ['pagos', 'id_plan', 'planes', 'id_plan', 'fk_pago_plan'],
            ['pago_nomina', 'id_entrenador', 'entrenador', 'cedula', 'fk_nomina_entrenador'],
            ['pago_nomina', 'id_admin', 'administrador', 'id_admin', 'fk_nomina_admin'],
            ['pago_nomina', 'id_metodo', 'metodo_pago', 'id_metodo', 'fk_nomina_metodo'],
            ['pago_nomina', 'id_cuenta', 'cuenta', 'id_cuenta', 'fk_nomina_cuenta'],
            ['pago_servicio', 'id_admin', 'administrador', 'id_admin', 'fk_id_admin'],
            ['pago_servicio', 'id_metodo', 'metodo_pago', 'id_metodo', 'fk_metodo_pago_servicio'],
            ['pago_servicio', 'id_cuenta', 'cuenta', 'id_cuenta', 'fk_servicio_cuenta']
        ];

        foreach ($relaciones as $relacion) {
            list($tabla, $columna, $tablaReferida, $columnaReferida, $nombre) = $relacion;
            if ($db->query("SHOW TABLES LIKE '{$tabla}'")->rowCount() === 0
                || $db->query("SHOW TABLES LIKE '{$tablaReferida}'")->rowCount() === 0) {
                continue;
            }

            $stmt = $db->prepare(
                "SELECT k.CONSTRAINT_NAME, r.DELETE_RULE
                 FROM information_schema.KEY_COLUMN_USAGE k
                 INNER JOIN information_schema.REFERENTIAL_CONSTRAINTS r
                    ON r.CONSTRAINT_SCHEMA = k.CONSTRAINT_SCHEMA
                   AND r.CONSTRAINT_NAME = k.CONSTRAINT_NAME
                 WHERE k.CONSTRAINT_SCHEMA = DATABASE()
                   AND k.TABLE_NAME = ?
                   AND k.COLUMN_NAME = ?
                   AND k.REFERENCED_TABLE_NAME = ?
                   AND k.REFERENCED_COLUMN_NAME = ?
                 LIMIT 1"
            );
            $stmt->execute([$tabla, $columna, $tablaReferida, $columnaReferida]);
            $existente = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($existente && strtoupper($existente['DELETE_RULE']) === 'CASCADE') {
                $db->exec("ALTER TABLE `{$tabla}` DROP FOREIGN KEY `{$existente['CONSTRAINT_NAME']}`");
                $existente = false;
            }
            if ($existente) {
                continue;
            }

            $stmtTipos = $db->prepare(
                "SELECT c.COLUMN_TYPE AS tipo_actual, r.COLUMN_TYPE AS tipo_referido
                 FROM information_schema.COLUMNS c
                 INNER JOIN information_schema.COLUMNS r
                    ON r.TABLE_SCHEMA = c.TABLE_SCHEMA
                   AND r.TABLE_NAME = ?
                   AND r.COLUMN_NAME = ?
                 WHERE c.TABLE_SCHEMA = DATABASE()
                   AND c.TABLE_NAME = ?
                   AND c.COLUMN_NAME = ?"
            );
            $stmtTipos->execute([$tablaReferida, $columnaReferida, $tabla, $columna]);
            $tipos = $stmtTipos->fetch(PDO::FETCH_ASSOC);
            if (!$tipos || strtolower($tipos['tipo_actual']) !== strtolower($tipos['tipo_referido'])) {
                continue;
            }

            $stmt = $db->query(
                "SELECT COUNT(*)
                 FROM `{$tabla}` t
                 LEFT JOIN `{$tablaReferida}` r ON t.`{$columna}` = r.`{$columnaReferida}`
                 WHERE t.`{$columna}` IS NOT NULL AND r.`{$columnaReferida}` IS NULL"
            );
            if ((int) $stmt->fetchColumn() > 0) {
                continue;
            }

            $db->exec(
                "ALTER TABLE `{$tabla}`
                 ADD CONSTRAINT `{$nombre}` FOREIGN KEY (`{$columna}`)
                 REFERENCES `{$tablaReferida}` (`{$columnaReferida}`) ON DELETE RESTRICT"
            );
        }
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