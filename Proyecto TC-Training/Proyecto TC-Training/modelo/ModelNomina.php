<?php
class ModelNomina {
    private $db;
    private $lastError = null;

    public function __construct($db_conn) {
        $this->db = $db_conn;
        $this->asegurarTabla();
    }

    private function asegurarTabla() {
        try {
            $stmtMetodo = $this->db->prepare("SHOW COLUMNS FROM pago_nomina LIKE 'id_metodo'");
            $stmtMetodo->execute();
            if ($stmtMetodo->rowCount() === 0) {
                $this->db->exec("ALTER TABLE pago_nomina ADD COLUMN id_metodo INT(11) DEFAULT NULL");
            }

            $stmtCuenta = $this->db->prepare("SHOW COLUMNS FROM pago_nomina LIKE 'id_cuenta'");
            $stmtCuenta->execute();
            if ($stmtCuenta->rowCount() === 0) {
                $this->db->exec("ALTER TABLE pago_nomina ADD COLUMN id_cuenta INT(11) DEFAULT NULL");
            }
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
        }
    }

    public function getLastError() {
        return $this->lastError;
    }

    public function registrar($id_entrenador, $id_admin, $monto, $fecha_pago, $concepto, $id_metodo = null, $id_cuenta = null) {
        if (empty($id_entrenador) || empty($monto) || empty($fecha_pago)) {
            return "warning";
        }
        $id_metodo = !empty($id_metodo) ? intval($id_metodo) : null;
        $id_cuenta = !empty($id_cuenta) ? intval($id_cuenta) : null;

        $sql = "INSERT INTO pago_nomina (id_entrenador, id_admin, monto, fecha_pago, concepto, id_metodo, id_cuenta) VALUES (?, ?, ?, ?, ?, ?, ?)";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id_entrenador, $id_admin, $monto, $fecha_pago, $concepto, $id_metodo, $id_cuenta]);
            return "success";
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return "error";
        }
    }

   public function obtenerTodos($search = '', $year = null, $options = []) {
        $sql = "SELECT n.id_nomina, n.id_entrenador, n.monto, n.fecha_pago, n.concepto, n.id_metodo, n.id_cuenta,
                       m.nombre AS nombre_metodo,
                       CONCAT(e.nombre, ' ', e.apellido) AS NombreEntrenador, a.Usuario AS NombreAdmin
                FROM pago_nomina n
                LEFT JOIN entrenador e ON n.id_entrenador = e.cedula
                LEFT JOIN administrador a ON n.id_admin = a.id_admin
                LEFT JOIN metodo_pago m ON n.id_metodo = m.id_metodo";
        $params = [];
        $conds = [];

        if (!empty($search) && $search !== 'todos') {
            $conds[] = "(e.nombre LIKE ? OR e.apellido LIKE ? OR CONCAT(e.nombre,' ',e.apellido) LIKE ? OR n.id_entrenador LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }
        if (!empty($options['start_date'])) {
            $conds[] = "n.fecha_pago >= ?";
            $params[] = $options['start_date'];
        }
        if (!empty($options['end_date'])) {
            $conds[] = "n.fecha_pago <= ?";
            $params[] = $options['end_date'];
        }
        if (!empty($options['metodo_pago']) && $options['metodo_pago'] !== 'todos') {
            $conds[] = "n.id_metodo = ?";
            $params[] = $options['metodo_pago'];
        }
        if (!empty($year) && is_numeric($year) && empty($options['start_date']) && empty($options['end_date'])) {
            $conds[] = "YEAR(n.fecha_pago) = ?";
            $params[] = intval($year);
        }

        if (!empty($conds)) {
            $sql .= ' WHERE ' . implode(' AND ', $conds);
        }
        $sql .= " ORDER BY n.fecha_pago DESC, n.id_nomina DESC";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    public function obtenerPorId($id) {
        $sql = "SELECT n.*, CONCAT(e.nombre, ' ', e.apellido) AS NombreEntrenador FROM pago_nomina n INNER JOIN entrenador e ON n.id_entrenador = e.cedula WHERE n.id_nomina = ?";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return false;
        }
    }

    public function actualizar($id_nomina, $id_entrenador, $monto, $fecha_pago, $concepto, $id_metodo = null, $id_cuenta = null) {
        $id_metodo = !empty($id_metodo) ? intval($id_metodo) : null;
        $id_cuenta = !empty($id_cuenta) ? intval($id_cuenta) : null;

        $sql = "UPDATE pago_nomina SET id_entrenador = ?, monto = ?, fecha_pago = ?, concepto = ?, id_metodo = ?, id_cuenta = ? WHERE id_nomina = ?";
        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$id_entrenador, $monto, $fecha_pago, $concepto, $id_metodo, $id_cuenta, $id_nomina]);
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    public function eliminar($id_nomina) {
        $sql = "DELETE FROM pago_nomina WHERE id_nomina = ?";
        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$id_nomina]);
        } catch (PDOException $e) {
            return false;
        }
    }
}
?>