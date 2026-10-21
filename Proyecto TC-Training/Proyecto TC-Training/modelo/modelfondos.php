<?php
class ModelFondos {
    private $db;
    private $lastError = null;

    public function __construct($db_conn) {
        $this->db = $db_conn;
    }

    public function getLastError() {
        return $this->lastError;
    }

    // Obtener todas las cuentas bancarias/fondos con el nombre de su banco
    public function obtenerTodas() {
        $sql = "SELECT c.id_cuenta, c.num_cuenta, c.tipo_moneda, c.saldo, b.nombre AS banco_nombre
                FROM cuenta c
                INNER JOIN banco b ON c.id_banco = b.id_banco
                ORDER BY c.id_cuenta ASC";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return [];
        }
    }

    // Registrar una nueva cuenta
    public function registrar($id_banco, $num_cuenta, $tipo_moneda, $saldo_inicial = 0) {
        $sql = "INSERT INTO cuenta (id_banco, num_cuenta, tipo_moneda, saldo) VALUES (?, ?, ?, ?)";
        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$id_banco, $num_cuenta, $tipo_moneda, $saldo_inicial]);
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    // Obtener todas las transacciones (Ingresos de clientes [+], Egresos de nómina [-] y Egresos de servicios [-])
    public function obtenerTransacciones($id_cuenta = null, $year = null) {
        $params = [];
        $whereCuentaCliente = "";
        $whereCuentaNomina = "";
        $whereCuentaServicio = "";

        if (!empty($id_cuenta) && $id_cuenta !== 'todas') {
            $whereCuentaCliente = " AND p.id_cuenta = " . intval($id_cuenta);
            $whereCuentaNomina = " AND n.id_cuenta = " . intval($id_cuenta);
            $whereCuentaServicio = " AND ps.id_cuenta = " . intval($id_cuenta);
        }

        $sql = "
            SELECT 
                'ingreso' AS tipo_movimiento,
                p.id_pago AS id_referencia,
                p.Monto AS monto,
                p.Fecha AS fecha,
                CONCAT('Pago Cliente: ', IFNULL(CONCAT(cli.Nombre, ' ', cli.apellido), 'Cliente General')) AS concepto,
                p.id_cuenta,
                m.nombre AS nombre_metodo,
                c.num_cuenta,
                b.nombre AS banco_nombre,
                c.tipo_moneda
            FROM pagos p
            LEFT JOIN cliente cli ON p.id_cliente = cli.id_cliente
            LEFT JOIN metodo_pago m ON p.id_metodo = m.id_metodo
            LEFT JOIN cuenta c ON p.id_cuenta = c.id_cuenta
            LEFT JOIN banco b ON c.id_banco = b.id_banco
            WHERE p.id_cuenta IS NOT NULL {$whereCuentaCliente}

            UNION ALL

            SELECT 
                'egreso_nomina' AS tipo_movimiento,
                n.id_nomina AS id_referencia,
                n.monto AS monto,
                n.fecha_pago AS fecha,
                CONCAT('Nómina: ', IFNULL(CONCAT(e.nombre, ' ', e.apellido), n.concepto)) AS concepto,
                n.id_cuenta,
                m.nombre AS nombre_metodo,
                c.num_cuenta,
                b.nombre AS banco_nombre,
                c.tipo_moneda
            FROM pago_nomina n
            LEFT JOIN entrenador e ON n.id_entrenador = e.cedula
            LEFT JOIN metodo_pago m ON n.id_metodo = m.id_metodo
            LEFT JOIN cuenta c ON n.id_cuenta = c.id_cuenta
            LEFT JOIN banco b ON c.id_banco = b.id_banco
            WHERE n.id_cuenta IS NOT NULL {$whereCuentaNomina}

            UNION ALL

            SELECT 
                'egreso_servicio' AS tipo_movimiento,
                ps.id_pago_servicio AS id_referencia,
                ps.monto AS monto,
                ps.fecha AS fecha,
                CONCAT('Servicio/Gasto: ', ps.concepto) AS concepto,
                ps.id_cuenta,
                m.nombre AS nombre_metodo,
                c.num_cuenta,
                b.nombre AS banco_nombre,
                c.tipo_moneda
            FROM pago_servicio ps
            LEFT JOIN metodo_pago m ON ps.id_metodo = m.id_metodo
            LEFT JOIN cuenta c ON ps.id_cuenta = c.id_cuenta
            LEFT JOIN banco b ON c.id_banco = b.id_banco
            WHERE ps.id_cuenta IS NOT NULL {$whereCuentaServicio}

            ORDER BY fecha DESC
        ";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return [];
        }
    }
}
?>