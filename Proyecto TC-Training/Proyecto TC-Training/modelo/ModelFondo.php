<?php
class ModelFondo {
    private $db;
    private $lastError = null;

    public function __construct($db_conn) {
        $this->db = $db_conn;
    }

    public function getLastError() {
        return $this->lastError;
    }

    // Obtener todas las cuentas desde la tabla 'cuenta' con saldo dinámico calculado
    public function obtenerTodos() {
        $sql = "SELECT c.id_cuenta, c.num_cuenta, c.tipo_moneda,
                       (
                         IFNULL((SELECT SUM(Monto) FROM pagos WHERE id_cuenta = c.id_cuenta), 0)
                         - IFNULL((SELECT SUM(monto) FROM pago_nomina WHERE id_cuenta = c.id_cuenta), 0)
                         - IFNULL((SELECT SUM(monto) FROM pago_servicio WHERE id_cuenta = c.id_cuenta), 0)
                       ) AS saldo,
                       b.nombre AS banco_nombre
                FROM cuenta c
                INNER JOIN banco b ON c.id_banco = b.id_banco
                ORDER BY c.id_cuenta ASC";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $resultado = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return is_array($resultado) ? $resultado : [];
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return [];
        }
    }

    // Registrar nuevo fondo en la tabla cuenta
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

    // Obtener transacciones unificadas con filtrado directo por tabla
    public function obtenerTransacciones($id_cuenta = 'todas') {
        $params = [];
        $whereCliente = "";
        $whereNomina = "";
        $whereServicio = "";

        if (!empty($id_cuenta) && $id_cuenta !== 'todas' && $id_cuenta !== 'all') {
            $whereCliente = " WHERE p.id_cuenta = ? ";
            $whereNomina = " WHERE n.id_cuenta = ? ";
            $whereServicio = " WHERE ps.id_cuenta = ? ";
            $params = [intval($id_cuenta), intval($id_cuenta), intval($id_cuenta)];
        }

        $sql = "
            SELECT 
                'ingreso' AS tipo_movimiento,
                p.id_pago AS id_referencia,
                p.Monto AS monto,
                p.Fecha AS fecha,
                CONCAT('Pago Cliente: ', IFNULL(CONCAT(cli.Nombre, ' ', cli.apellido), 'Cliente General')) AS concepto,
                p.id_cuenta AS id_cuenta,
                IFNULL(m.nombre, p.MetodoPago) AS nombre_metodo,
                c.num_cuenta AS num_cuenta,
                b.nombre AS banco_nombre,
                c.tipo_moneda AS tipo_moneda
            FROM pagos p
            LEFT JOIN cliente cli ON p.id_cliente = cli.id_cliente
            LEFT JOIN metodo_pago m ON p.id_metodo = m.id_metodo
            LEFT JOIN cuenta c ON p.id_cuenta = c.id_cuenta
            LEFT JOIN banco b ON c.id_banco = b.id_banco
            {$whereCliente}

            UNION ALL

            SELECT 
                'egreso_nomina' AS tipo_movimiento,
                n.id_nomina AS id_referencia,
                n.monto AS monto,
                n.fecha_pago AS fecha,
                CONCAT('Nómina: ', IFNULL(CONCAT(e.nombre, ' ', e.apellido), n.concepto)) AS concepto,
                n.id_cuenta AS id_cuenta,
                m.nombre AS nombre_metodo,
                c.num_cuenta AS num_cuenta,
                b.nombre AS banco_nombre,
                c.tipo_moneda AS tipo_moneda
            FROM pago_nomina n
            LEFT JOIN entrenador e ON n.id_entrenador = e.cedula
            LEFT JOIN metodo_pago m ON n.id_metodo = m.id_metodo
            LEFT JOIN cuenta c ON n.id_cuenta = c.id_cuenta
            LEFT JOIN banco b ON c.id_banco = b.id_banco
            {$whereNomina}

            UNION ALL

            SELECT 
                'egreso_servicio' AS tipo_movimiento,
                ps.id_pago_servicio AS id_referencia,
                ps.monto AS monto,
                ps.fecha AS fecha,
                CONCAT('Servicio/Gasto: ', ps.concepto) AS concepto,
                ps.id_cuenta AS id_cuenta,
                m.nombre AS nombre_metodo,
                c.num_cuenta AS num_cuenta,
                b.nombre AS banco_nombre,
                c.tipo_moneda AS tipo_moneda
            FROM pago_servicio ps
            LEFT JOIN metodo_pago m ON ps.id_metodo = m.id_metodo
            LEFT JOIN cuenta c ON ps.id_cuenta = c.id_cuenta
            LEFT JOIN banco b ON c.id_banco = b.id_banco
            {$whereServicio}

            ORDER BY fecha DESC, id_referencia DESC
        ";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $resultado = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return is_array($resultado) ? $resultado : [];
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return [];
        }
    }
}
?>