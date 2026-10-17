<?php
class ModelNomina {
    private $db;
    private $lastError = null;

    public function __construct($db_conn) {
        $this->db = $db_conn;
        $this->asegurarTabla();
    }

    private function asegurarTabla() {
        $sql = "CREATE TABLE IF NOT EXISTS pago_nomina (
            id_nomina INT(11) NOT NULL AUTO_INCREMENT,
            id_entrenador VARCHAR(30) NOT NULL,
            id_admin INT(11) NOT NULL,
            monto DECIMAL(10,2) NOT NULL,
            fecha_pago DATE NOT NULL,
            concepto VARCHAR(255) DEFAULT 'Pago de Nómina / Honorarios',
            metodo_pago VARCHAR(50) DEFAULT 'Efectivo',
            fecha_registro TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id_nomina),
            KEY idx_nomina_entrenador (id_entrenador),
            KEY idx_nomina_admin (id_admin),
            CONSTRAINT fk_nomina_entrenador FOREIGN KEY (id_entrenador) REFERENCES entrenador(cedula) ON DELETE CASCADE,
            CONSTRAINT fk_nomina_admin FOREIGN KEY (id_admin) REFERENCES administrador(id_admin)
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

    public function registrar($id_entrenador, $id_admin, $monto, $fecha_pago, $concepto, $metodo_pago) {
        if (empty($id_entrenador) || empty($monto) || empty($fecha_pago)) {
            return "warning";
        }
        $sql = "INSERT INTO pago_nomina (id_entrenador, id_admin, monto, fecha_pago, concepto, metodo_pago) VALUES (?, ?, ?, ?, ?, ?)";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id_entrenador, $id_admin, $monto, $fecha_pago, $concepto, $metodo_pago]);
            return "success";
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return "error";
        }
    }

    public function obtenerTodos($search = '', $year = null, $options = []) {
        $sql = "SELECT n.id_nomina, n.id_entrenador, n.monto, n.fecha_pago, n.concepto, n.metodo_pago,
                       CONCAT(e.nombre, ' ', e.apellido) AS NombreEntrenador, a.Usuario AS NombreAdmin
                FROM pago_nomina n
                INNER JOIN entrenador e ON n.id_entrenador = e.cedula
                INNER JOIN administrador a ON n.id_admin = a.id_admin";
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
            $conds[] = "n.metodo_pago = ?";
            $params[] = $options['metodo_pago'];
        }
        if (!empty($year) && is_numeric($year) && empty($options['start_date']) && empty($options['end_date'])) {
            $conds[] = "YEAR(n.fecha_pago) = ?";
            $params[] = intval($year);
        }

        if (!empty($conds)) {
            $sql .= ' WHERE ' . implode(' AND ', $conds);
        }
        $sql .= " ORDER BY n.fecha_pago DESC";

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

    public function actualizar($id_nomina, $id_entrenador, $monto, $fecha_pago, $concepto, $metodo_pago) {
        $sql = "UPDATE pago_nomina SET id_entrenador = ?, monto = ?, fecha_pago = ?, concepto = ?, metodo_pago = ? WHERE id_nomina = ?";
        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$id_entrenador, $monto, $fecha_pago, $concepto, $metodo_pago, $id_nomina]);
        } catch (PDOException $e) {
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