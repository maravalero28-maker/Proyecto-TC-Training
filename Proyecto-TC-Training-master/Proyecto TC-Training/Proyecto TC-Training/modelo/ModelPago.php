<?php
class ModelPago {
    private $db;
    private $lastError = null;

    public function __construct($db_conn) {
        $this->db = $db_conn;
        $this->asegurarTablaPagos();
    }

    private function asegurarTablaPagos() {
        $sql = "CREATE TABLE IF NOT EXISTS pagos (
            id_pago INT(11) NOT NULL AUTO_INCREMENT,
            id_cliente INT(11) NOT NULL,
            Monto DECIMAL(10,2) NOT NULL,
            Fecha DATE NOT NULL,
            MetodoPago VARCHAR(50) DEFAULT NULL,
            fecha_registro TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id_pago),
            KEY idx_id_cliente (id_cliente),
            CONSTRAINT fk_pagos_cliente FOREIGN KEY (id_cliente) REFERENCES cliente(id_cliente) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

        try {
            $this->db->exec($sql);
            $stmt = $this->db->prepare("SHOW COLUMNS FROM pagos LIKE 'MetodoPago'");
            $stmt->execute();
            if ($stmt->rowCount() === 0) {
                $this->db->exec("ALTER TABLE pagos ADD COLUMN MetodoPago VARCHAR(50) DEFAULT NULL");
            }
        } catch (PDOException $e) {
            
        }
    }

    public function registrar($id_cliente, $monto, $fecha, $metodo_pago = null) {
        if (empty($id_cliente) || empty($monto) || empty($fecha)) {
            return "warning";
        }

        $sql = "INSERT INTO pagos (id_cliente, Monto, Fecha, MetodoPago) VALUES (?, ?, ?, ?)";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id_cliente, $monto, $fecha, $metodo_pago]);
            return "success";
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return "error";
        }
    }

    public function getLastError() {
        return $this->lastError;
    }

    public function obtenerTodos($filtro = 'todos', $year = null, $options = []) {
        $sql = "SELECT p.id_pago, p.id_cliente, p.Monto, p.Fecha, p.MetodoPago,
                       CONCAT(c.Nombre, ' ', c.apellido) AS NombreCliente,
                       c.Plan, c.Solvencia, c.estatus
                FROM pagos p
                INNER JOIN cliente c ON p.id_cliente = c.id_cliente";
        $params = [];
        $conds = [];

        if ($filtro !== 'todos' && !empty($filtro)) {
            $conds[] = "(c.Nombre LIKE ? OR c.apellido LIKE ? OR CONCAT(c.Nombre,' ',c.apellido) LIKE ? )";
            $params[] = "%{$filtro}%";
            $params[] = "%{$filtro}%";
            $params[] = "%{$filtro}%";
        }

        if (!empty($options['start_date'])) {
            $conds[] = "p.Fecha >= ?";
            $params[] = $options['start_date'];
        }
        if (!empty($options['end_date'])) {
            $conds[] = "p.Fecha <= ?";
            $params[] = $options['end_date'];
        }

        if (!empty($options['metodo_pago']) && $options['metodo_pago'] !== 'todos') {
            $conds[] = "p.MetodoPago = ?";
            $params[] = $options['metodo_pago'];
        }

        if (!empty($options['plan']) && $options['plan'] !== 'todos') {
            $conds[] = "c.Plan = ?";
            $params[] = $options['plan'];
        }

        if (!empty($options['solvencia']) && $options['solvencia'] !== 'todos') {
            $conds[] = "c.Solvencia = ?";
            $params[] = $options['solvencia'];
        }

        if (!empty($year) && is_numeric($year) && empty($options['start_date']) && empty($options['end_date'])) {
            $conds[] = "YEAR(p.Fecha) = ?";
            $params[] = intval($year);
        }

        if (!empty($conds)) {
            $sql .= ' WHERE ' . implode(' AND ', $conds);
        }

        $sql .= " ORDER BY p.Fecha DESC";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    public function obtenerPorId($id) {
        $sql = "SELECT p.id_pago, p.id_cliente, p.Monto, p.Fecha, p.MetodoPago,
                       CONCAT(c.Nombre, ' ', c.apellido) AS NombreCliente
                FROM pagos p
                INNER JOIN cliente c ON p.id_cliente = c.id_cliente
                WHERE p.id_pago = ?";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return false;
        }
    }

    public function actualizar($id_pago, $id_cliente, $monto, $fecha, $metodo_pago = null) {
        $sql = "UPDATE pagos SET id_cliente = ?, Monto = ?, Fecha = ?, MetodoPago = ? WHERE id_pago = ?";
        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$id_cliente, $monto, $fecha, $metodo_pago ?? null, $id_pago]);
        } catch (PDOException $e) {
            return false;
        }
    }

    public function eliminar($id_pago) {
        $sql = "DELETE FROM pagos WHERE id_pago = ?";
        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$id_pago]);
        } catch (PDOException $e) {
            return false;
        }
    }

    public function obtenerClientes($soloActivos = true, $search = null) {
        $sql = "SELECT id_cliente, CONCAT(Nombre, ' ', apellido) AS nombre_completo, Nombre, apellido, estatus, Plan 
            FROM cliente";
        $conds = [];
        $params = [];
        if ($soloActivos) {
            $conds[] = "estatus = 'activo'";
        }
        if (!empty($search) && $search !== 'todos') {
            $conds[] = "(Nombre LIKE ? OR apellido LIKE ? OR CONCAT(Nombre,' ',apellido) LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }
        if (!empty($conds)) {
            $sql .= ' WHERE ' . implode(' AND ', $conds);
        }
        $sql .= " ORDER BY Nombre ASC";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    public function obtenerUltimoPagoPorCliente($id_cliente) {
        $sql = "SELECT Fecha FROM pagos WHERE id_cliente = ? ORDER BY Fecha DESC LIMIT 1";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id_cliente]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ? $row['Fecha'] : null;
        } catch (PDOException $e) {
            return null;
        }
    }
}
?>
