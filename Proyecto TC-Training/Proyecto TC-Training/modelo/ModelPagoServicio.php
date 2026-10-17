<?php
class ModelPagoServicio {
    private $db;
    private $lastError = null;

    public function __construct($db_conn) {
        $this->db = $db_conn;
        $this->asegurarTabla();
    }

    private function asegurarTabla() {
        $sql = "CREATE TABLE IF NOT EXISTS pago_servicio (
            id_pago_servicio INT(11) NOT NULL AUTO_INCREMENT,
            monto DECIMAL(10,2) NOT NULL,
            concepto VARCHAR(80) NOT NULL,
            metodo_pago VARCHAR(80) NOT NULL,
            fecha DATE NOT NULL DEFAULT CURRENT_DATE,
            id_admin INT(11) DEFAULT NULL,
            PRIMARY KEY (id_pago_servicio),
            KEY fk_id_admin (id_admin),
            CONSTRAINT fk_servicio_admin FOREIGN KEY (id_admin) REFERENCES administrador(id_admin)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
        try {
            $this->db->exec($sql);
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
        }
    }

    public function getLastError() {
        return $this->lastError;
    }

    public function registrar($monto, $concepto, $metodo_pago, $fecha, $id_admin) {
        if (empty($monto) || empty($concepto) || empty($fecha)) {
            return "warning";
        }
        $sql = "INSERT INTO pago_servicio (monto, concepto, metodo_pago, fecha, id_admin) VALUES (?, ?, ?, ?, ?)";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$monto, $concepto, $metodo_pago, $fecha, $id_admin]);
            return "success";
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return "error";
        }
    }

    public function obtenerTodos($search = '', $year = null, $options = []) {
        $sql = "SELECT ps.*, a.Usuario AS NombreAdmin FROM pago_servicio ps LEFT JOIN administrador a ON ps.id_admin = a.id_admin";
        $params = [];
        $conds = [];

        if (!empty($search) && $search !== 'todos') {
            $conds[] = "(ps.concepto LIKE ? OR ps.metodo_pago LIKE ?)";
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
            $conds[] = "ps.metodo_pago = ?";
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

    public function actualizar($id, $monto, $concepto, $metodo_pago, $fecha) {
        $sql = "UPDATE pago_servicio SET monto = ?, concepto = ?, metodo_pago = ?, fecha = ? WHERE id_pago_servicio = ?";
        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$monto, $concepto, $metodo_pago, $fecha, $id]);
        } catch (PDOException $e) {
            return false;
        }
    }

    public function eliminar($id) {
        $sql = "DELETE FROM pago_servicio WHERE id_pago_servicio = ?";
        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            return false;
        }
    }
}
?>