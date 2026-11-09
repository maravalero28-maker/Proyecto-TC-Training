<?php
require_once __DIR__ . '/ModelTasa.php';
require_once __DIR__ . '/MovimientoCuenta.php';

class ModelPagoServicio {
    private $db;
    private $lastError = null;

    public function __construct($db_conn) {
        $this->db = $db_conn;
        $this->asegurarTabla();
        ModelTasa::asegurarEsquema($this->db);
    }

    private function asegurarTabla() {
        try {
            $stmtMetodo = $this->db->prepare("SHOW COLUMNS FROM pago_servicio LIKE 'id_metodo'");
            $stmtMetodo->execute();
            if ($stmtMetodo->rowCount() === 0) {
                $this->db->exec("ALTER TABLE pago_servicio ADD COLUMN id_metodo INT(11) DEFAULT NULL");
            }

            $stmtCuenta = $this->db->prepare("SHOW COLUMNS FROM pago_servicio LIKE 'id_cuenta'");
            $stmtCuenta->execute();
            if ($stmtCuenta->rowCount() === 0) {
                $this->db->exec("ALTER TABLE pago_servicio ADD COLUMN id_cuenta INT(11) DEFAULT NULL");
            }

            $stmtEstado = $this->db->prepare("SHOW COLUMNS FROM pago_servicio LIKE 'anulado'");
            $stmtEstado->execute();
            if ($stmtEstado->rowCount() === 0) {
                $this->db->exec("ALTER TABLE pago_servicio ADD COLUMN anulado TINYINT(1) NOT NULL DEFAULT 0");
            }
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
        }
    }

    public function getLastError() {
        return $this->lastError;
    }

    public function registrar($monto, $concepto, $id_metodo, $id_cuenta, $fecha, $id_admin, $moneda = null, $tasa = null, $monto_usd = null) {
        if (empty($monto) || empty($concepto) || empty($fecha)) {
            return "warning";
        }
        $id_metodo = !empty($id_metodo) ? intval($id_metodo) : null;
        $id_cuenta = !empty($id_cuenta) ? intval($id_cuenta) : null;

        $sql = "INSERT INTO pago_servicio (monto, concepto, id_metodo, id_cuenta, fecha, id_admin, moneda, tasa, monto_usd) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        try {
            $this->db->beginTransaction();
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$monto, $concepto, $id_metodo, $id_cuenta, $fecha, $id_admin, $moneda, $tasa, $monto_usd]);
            if (!MovimientoCuenta::ajustarSaldo($this->db, $id_cuenta, -$monto)) {
                throw new RuntimeException('No se pudo descontar el monto de la cuenta seleccionada.');
            }
            $this->db->commit();
            return "success";
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            $this->lastError = $e->getMessage();
            return "error";
        }
    }

    public function obtenerTodos($search = '', $year = null, $options = []) {
        $sql = "SELECT ps.*,
                       m.nombre AS nombre_metodo, 
                       c.num_cuenta, 
                       c.tipo_moneda AS moneda_cuenta,
                       b.nombre AS banco_nombre,
                       a.Usuario AS NombreAdmin 
                FROM pago_servicio ps 
                LEFT JOIN administrador a ON ps.id_admin = a.id_admin
                LEFT JOIN metodo_pago m ON ps.id_metodo = m.id_metodo
                LEFT JOIN cuenta c ON ps.id_cuenta = c.id_cuenta
                LEFT JOIN banco b ON c.id_banco = b.id_banco";
        $params = [];
        $conds = [];

        if (!empty($search) && $search !== 'todos') {
            $conds[] = "(ps.concepto LIKE ? OR m.nombre LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }
        if (!empty($options['start_date'])) {
            $conds[] = "ps.fecha >= ?";
            $params[] = $options['start_date'];
        }
        if (!empty($options['end_date'])) {
            $conds[] = "ps.fecha <= ?";
            $params[] = $options['end_date'];
        }
        if (!empty($options['metodo_pago']) && $options['metodo_pago'] !== 'todos') {
            $conds[] = "ps.id_metodo = ?";
            $params[] = $options['metodo_pago'];
        }
        if (!empty($year) && is_numeric($year) && empty($options['start_date']) && empty($options['end_date'])) {
            $conds[] = "YEAR(ps.fecha) = ?";
            $params[] = intval($year);
        }

        if (!empty($conds)) {
            $sql .= ' WHERE ' . implode(' AND ', $conds);
        }
        $sql .= " ORDER BY ps.fecha DESC";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    public function obtenerPorId($id) {
        $sql = "SELECT * FROM pago_servicio WHERE id_pago_servicio = ?";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return false;
        }
    }

    public function actualizar($id, $monto, $concepto, $id_metodo, $id_cuenta, $fecha, $moneda = null, $tasa = null, $monto_usd = null) {
        $id_metodo = !empty($id_metodo) ? intval($id_metodo) : null;
        $id_cuenta = !empty($id_cuenta) ? intval($id_cuenta) : null;

        $sql = "UPDATE pago_servicio SET monto = ?, concepto = ?, id_metodo = ?, id_cuenta = ?, fecha = ?, moneda = ?, tasa = ?, monto_usd = ? WHERE id_pago_servicio = ?";
        try {
            $this->db->beginTransaction();
            $stmtActual = $this->db->prepare("SELECT monto, id_cuenta, anulado FROM pago_servicio WHERE id_pago_servicio = ? FOR UPDATE");
            $stmtActual->execute([$id]);
            $actual = $stmtActual->fetch(PDO::FETCH_ASSOC);
            if (!$actual) {
                throw new RuntimeException('No se encontró el pago de servicio que se desea actualizar.');
            }

            if ((int) $actual['anulado'] === 0) {
                $mismoFondo = (int) $actual['id_cuenta'] === (int) $id_cuenta;
                $saldoActualizado = $mismoFondo
                    ? MovimientoCuenta::ajustarSaldo($this->db, $id_cuenta, $actual['monto'] - $monto)
                    : MovimientoCuenta::ajustarSaldo($this->db, $actual['id_cuenta'], $actual['monto'])
                        && MovimientoCuenta::ajustarSaldo($this->db, $id_cuenta, -$monto);
                if (!$saldoActualizado) {
                    throw new RuntimeException('No se pudo actualizar el saldo de las cuentas del servicio.');
                }
            }

            $stmt = $this->db->prepare($sql);
            $ok = $stmt->execute([$monto, $concepto, $id_metodo, $id_cuenta, $fecha, $moneda, $tasa, $monto_usd, $id]);
            $this->db->commit();
            return $ok;
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    public function cambiarEstado($id, $anulado) {
        if ($anulado !== 0 && $anulado !== 1 && $anulado !== '0' && $anulado !== '1') {
            return false;
        }

        $sql = "UPDATE pago_servicio SET anulado = ? WHERE id_pago_servicio = ?";
        try {
            $this->db->beginTransaction();
            $stmtActual = $this->db->prepare("SELECT monto, id_cuenta, anulado FROM pago_servicio WHERE id_pago_servicio = ? FOR UPDATE");
            $stmtActual->execute([$id]);
            $actual = $stmtActual->fetch(PDO::FETCH_ASSOC);
            if (!$actual) {
                throw new RuntimeException('No se encontró el pago de servicio que se desea cambiar.');
            }

            if ((int) $actual['anulado'] !== (int) $anulado) {
                $delta = (int) $anulado === 1 ? $actual['monto'] : -$actual['monto'];
                if (!MovimientoCuenta::ajustarSaldo($this->db, $actual['id_cuenta'], $delta)) {
                    throw new RuntimeException('No se pudo actualizar el saldo de la cuenta del servicio.');
                }
            }

            $stmt = $this->db->prepare($sql);
            $ok = $stmt->execute([(int) $anulado, $id]);
            $this->db->commit();
            return $ok;
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            $this->lastError = $e->getMessage();
            return false;
        }
    }
}
?>