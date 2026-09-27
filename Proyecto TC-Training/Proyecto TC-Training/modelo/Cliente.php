<?php
class Cliente {
    private $db;

    public function __construct($db_conn) {
        $this->db = $db_conn;
    }

    public function registrar($nombre, $apellido, $telefono, $fecha, $estatus, $solvencia, $plan) {
        if (empty($nombre) || empty($apellido) || empty($plan)) {
            return "warning";
        }

        $sql = "INSERT INTO cliente (Nombre, apellido, telefono, fecha_inscripcion, estatus, Solvencia, Plan) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$nombre, $apellido, $telefono, $fecha, $estatus, $solvencia, $plan]);
            return "success";
        } catch (PDOException $e) {
            return "error";
        }
    }

    public function obtenerTodos($filtro = 'todos') {
        $sql = "SELECT id_cliente, Nombre, apellido, telefono, fecha_inscripcion, estatus, Solvencia, Plan FROM cliente";
        
        if ($filtro == 'solvente') {
            $sql .= " WHERE Solvencia = 'Solvente'";
        } elseif ($filtro == 'insolvente') {
            $sql .= " WHERE Solvencia != 'Solvente'";
        }
        
        $sql .= " ORDER BY fecha_inscripcion DESC";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    public function obtenerTodosConDiasRestantes($status = 'todos', $solvencia = 'todos') {
        $sql = "SELECT c.id_cliente, c.Nombre, c.apellido, c.telefono, c.fecha_inscripcion, c.estatus, c.Solvencia, c.Plan, p.dias AS plan_dias
                FROM cliente c
                LEFT JOIN planes p ON c.Plan = p.id_plan";

        $conds = [];
        $params = [];
        if ($status === 'activo') {
            $conds[] = "LOWER(c.estatus) = 'activo'";
        } elseif ($status === 'inactivo') {
            $conds[] = "LOWER(c.estatus) = 'inactivo'";
        }

        if ($solvencia === 'solvente') {
            $conds[] = "c.Solvencia = 'Solvente'";
        } elseif ($solvencia === 'insolvente') {
            $conds[] = "c.Solvencia != 'Solvente'";
        }

        if (!empty($conds)) {
            $sql .= ' WHERE ' . implode(' AND ', $conds);
        }

        $sql .= " ORDER BY c.fecha_inscripcion DESC";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }

        $hoy = new DateTime();
        foreach ($clientes as &$cliente) {
            $planDias = intval($cliente['plan_dias'] ?? 30);
            $cliente['dias_restantes'] = 0;

            if (strtolower($cliente['estatus']) !== 'activo') {
                continue;
            }

            $baseDate = new DateTime($cliente['fecha_inscripcion']);
            try {
                $stmtPago = $this->db->prepare("SELECT Fecha FROM pagos WHERE id_cliente = ? ORDER BY Fecha DESC LIMIT 1");
                $stmtPago->execute([$cliente['id_cliente']]);
                $ultimoPago = $stmtPago->fetch(PDO::FETCH_ASSOC);
                if ($ultimoPago && !empty($ultimoPago['Fecha'])) {
                    $baseDate = new DateTime($ultimoPago['Fecha']);
                }
            } catch (PDOException $e) {
                
            }

            $nextDue = clone $baseDate;
            $nextDue->modify("+{$planDias} days");
            $interval = $hoy->diff($nextDue);
            $dias = (int)$interval->format('%r%a');
            $cliente['dias_restantes'] = max(0, $dias);
        }
        unset($cliente);

        return $clientes;
    }

    public function eliminar($id) {
        $sql = "DELETE FROM cliente WHERE id_cliente = ?";
        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            return false;
        }
    }

    public function actualizarEstatus($id, $nuevoEstatus) {
        $sql = "UPDATE cliente SET estatus = ? WHERE id_cliente = ?";
        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$nuevoEstatus, $id]);
        } catch (PDOException $e) {
            return false;
        }
    }

    public function actualizarSolvencia($id, $solvencia) {
        $sql = "UPDATE cliente SET Solvencia = ? WHERE id_cliente = ?";
        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$solvencia, $id]);
        } catch (PDOException $e) {
            return false;
        }
    }

    public function actualizarPlan($id, $plan) {
        $sql = "UPDATE cliente SET Plan = ? WHERE id_cliente = ?";
        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$plan, $id]);
        } catch (PDOException $e) {
            return false;
        }
    }

    public function actualizarSolvenciaPorVencimiento() {
        $sql = "SELECT c.id_cliente, c.Solvencia AS actual_solvencia, c.fecha_inscripcion, c.Plan, p.dias AS plan_dias
                FROM cliente c
                LEFT JOIN planes p ON c.Plan = p.id_plan";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return false;
        }

        $hoy = new DateTime();
        $updated = true;

        foreach ($clientes as $cliente) {
            $baseDate = new DateTime($cliente['fecha_inscripcion']);
            $planDias = intval($cliente['plan_dias'] ?? 30);

            try {
                $stmtPago = $this->db->prepare("SELECT Fecha FROM pagos WHERE id_cliente = ? ORDER BY Fecha DESC LIMIT 1");
                $stmtPago->execute([$cliente['id_cliente']]);
                $ultimoPago = $stmtPago->fetch(PDO::FETCH_ASSOC);
                if ($ultimoPago && !empty($ultimoPago['Fecha'])) {
                    $baseDate = new DateTime($ultimoPago['Fecha']);
                }
            } catch (PDOException $e) {
                
            }

            $nextDue = clone $baseDate;
            $nextDue->modify("+{$planDias} days");
            $nuevaSolvencia = $hoy > $nextDue ? 'Insolvente' : 'Solvente';

            if ($nuevaSolvencia !== $cliente['actual_solvencia']) {
                $ok = $this->actualizarSolvencia($cliente['id_cliente'], $nuevaSolvencia);
                if (!$ok) {
                    $updated = false;
                }
            }
        }

        return $updated;
    }

    public function obtenerClientes($filtro = 'todos') {
        return $this->obtenerTodos($filtro);
    }

    public function obtenerPorId($id) {
        $sql = "SELECT id_cliente, Nombre, apellido, telefono, fecha_inscripcion, estatus, Solvencia, Plan 
                FROM cliente WHERE id_cliente = ?";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return false;
        }
    }

    public function actualizar($id, $nombre, $apellido, $telefono, $fecha, $estatus, $solvencia, $plan) {
        $sql = "UPDATE cliente SET 
                    Nombre = ?, 
                    apellido = ?, 
                    telefono = ?, 
                    fecha_inscripcion = ?, 
                    estatus = ?, 
                    Solvencia = ?, 
                    Plan = ? 
                WHERE id_cliente = ?";
        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$nombre, $apellido, $telefono, $fecha, $estatus, $solvencia, $plan, $id]);
        } catch (PDOException $e) {
            return false;
        }
    }
}
?>
