<?php
require_once __DIR__ . '/ModelTasa.php';

class Cliente {
    private $db;

    public function __construct($db_conn) {
        $this->db = $db_conn;
        ModelTasa::asegurarEsquema($db_conn);
    }

    public function registrar($nombre, $apellido, $telefono, $fecha, $estatus, $plan) {
        if (empty($nombre) || empty($apellido) || empty($plan)) {
            return "warning";
        }

        $sql = "INSERT INTO cliente (Nombre, apellido, telefono, fecha_inscripcion, estatus, Solvencia, Plan) 
                VALUES (?, ?, ?, ?, ?, 'Insolvente', ?)";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$nombre, $apellido, $telefono, $fecha, $estatus, $plan]);
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
            $referencia = $this->obtenerReferenciaVencimiento($cliente);
            $planDias = $referencia['dias'];
            $cliente['dias_restantes'] = 0;

            if (strtolower($cliente['estatus']) !== 'activo' || !$referencia['pagado']) {
                continue;
            }

            $baseDate = new DateTime($referencia['fecha']);

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
        $sql = "UPDATE cliente SET estatus = 'inactivo' WHERE id_cliente = ?";
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
            $referencia = $this->obtenerReferenciaVencimiento($cliente);
            $baseDate = new DateTime($referencia['fecha']);
            $planDias = $referencia['dias'];

            $nextDue = clone $baseDate;
            $nextDue->modify("+{$planDias} days");
            $nuevaSolvencia = $referencia['pagado'] && $hoy <= $nextDue ? 'Solvente' : 'Insolvente';

            if ($nuevaSolvencia !== $cliente['actual_solvencia']) {
                $ok = $this->actualizarSolvencia($cliente['id_cliente'], $nuevaSolvencia);
                if (!$ok) {
                    $updated = false;
                }
            }
        }

        return $updated;
    }

    private function obtenerReferenciaVencimiento($cliente) {
        $fechaBase = $cliente['fecha_inscripcion'];
        $dias = intval($cliente['plan_dias'] ?? 30);

        try {
            $stmt = $this->db->prepare(
                "SELECT p.Fecha, pl.dias
                 FROM pagos p
                 INNER JOIN planes pl ON pl.id_plan = p.id_plan
                 WHERE p.id_cliente = ?
                   AND p.anulado = 0
                   AND ABS(
                       COALESCE(
                           p.monto_usd,
                           CASE WHEN p.moneda = 'BS' AND p.tasa > 0 THEN p.Monto / p.tasa ELSE p.Monto END
                       ) - pl.precio
                   ) <= 0.01
                 ORDER BY p.Fecha DESC, p.id_pago DESC
                 LIMIT 1"
            );
            $stmt->execute([$cliente['id_cliente']]);
            $ultimoPagoValido = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($ultimoPagoValido) {
                $fechaBase = $ultimoPagoValido['Fecha'];
                $dias = intval($ultimoPagoValido['dias']);
            }
        } catch (PDOException $e) {
        }

        return ['fecha' => $fechaBase, 'dias' => max(1, $dias), 'pagado' => isset($ultimoPagoValido) && (bool) $ultimoPagoValido];
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

    public function actualizar($id, $nombre, $apellido, $telefono, $fecha, $estatus, $plan) {
        $sql = "UPDATE cliente SET 
                    Nombre = ?, 
                    apellido = ?, 
                    telefono = ?, 
                    fecha_inscripcion = ?, 
                    estatus = ?, 
                    Plan = ? 
                WHERE id_cliente = ?";
        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$nombre, $apellido, $telefono, $fecha, $estatus, $plan, $id]);
        } catch (PDOException $e) {
            return false;
        }
    }
    
}
?>
