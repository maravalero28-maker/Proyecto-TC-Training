<?php
class ModelPago {
    private $db;
    private $lastError = null;

    public function __construct($db_conn) {
        $this->db = $db_conn;
        $this->asegurarTablaPagos();
    }

    private function asegurarTablaPagos() {
        try {
            // Verificar y asegurar columnas id_metodo e id_cuenta en la tabla pagos
            $stmtMetodo = $this->db->prepare("SHOW COLUMNS FROM pagos LIKE 'id_metodo'");
            $stmtMetodo->execute();
            if ($stmtMetodo->rowCount() === 0) {
                $this->db->exec("ALTER TABLE pagos ADD COLUMN id_metodo INT(11) DEFAULT NULL");
            }

            $stmtCuenta = $this->db->prepare("SHOW COLUMNS FROM pagos LIKE 'id_cuenta'");
            $stmtCuenta->execute();
            if ($stmtCuenta->rowCount() === 0) {
                $this->db->exec("ALTER TABLE pagos ADD COLUMN id_cuenta INT(11) DEFAULT NULL");
            }
        } catch (PDOException $e) {
            
        }
    }

    public function registrar($id_cliente, $monto, $fecha, $id_metodo = null, $id_cuenta = null) {
        if (empty($id_cliente) || empty($monto) || empty($fecha)) {
            return "warning";
        }

        // Manejar valores vacíos como NULL para evitar errores de llave foránea
        $id_metodo = !empty($id_metodo) ? intval($id_metodo) : null;
        $id_cuenta = !empty($id_cuenta) ? intval($id_cuenta) : null;

        $sql = "INSERT INTO pagos (id_cliente, Monto, Fecha, id_metodo, id_cuenta) VALUES (?, ?, ?, ?, ?)";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id_cliente, $monto, $fecha, $id_metodo, $id_cuenta]);
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
        $sql = "SELECT p.id_pago, p.id_cliente, p.Monto, p.Fecha, p.id_metodo, p.id_cuenta,
                       m.nombre AS nombre_metodo,
                       CONCAT(c.Nombre, ' ', c.apellido) AS NombreCliente,
                       c.Plan, c.Solvencia, c.estatus
                FROM pagos p
                INNER JOIN cliente c ON p.id_cliente = c.id_cliente
                LEFT JOIN metodo_pago m ON p.id_metodo = m.id_metodo";
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
            $conds[] = "p.id_metodo = ?";
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

        // ORDENADO Por fecha más reciente primero y por ID descendente como respaldo secundario
        $sql .= " ORDER BY p.Fecha DESC, p.id_pago DESC";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    public function obtenerPorId($id) {
        $sql = "SELECT p.id_pago, p.id_cliente, p.Monto, p.Fecha, p.id_metodo, p.id_cuenta,
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

    public function actualizar($id_pago, $id_cliente, $monto, $fecha, $id_metodo = null, $id_cuenta = null) {
        $id_metodo = !empty($id_metodo) ? intval($id_metodo) : null;
        $id_cuenta = !empty($id_cuenta) ? intval($id_cuenta) : null;

        $sql = "UPDATE pagos SET id_cliente = ?, Monto = ?, Fecha = ?, id_metodo = ?, id_cuenta = ? WHERE id_pago = ?";
        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$id_cliente, $monto, $fecha, $id_metodo, $id_cuenta, $id_pago]);
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
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