<?php
class Reporte {
    private $db;

    public function __construct() {
        require_once(__DIR__ . '/../configuracion/conecxion.php');
        $this->db = (new Conexion())->getConexion();
    }

    public function obtenerPagos($origen, $fechaInicio = '', $fechaFin = '', $metodoPago = '', $banco = '') {
        $sql = '';
        $params = [];

        if ($origen === 'clientes') {
            $sql = "SELECT p.id_pago, c.id_cliente AS cedula, c.Nombre AS nombre, p.Monto AS monto,
                            mp.nombre AS metodo_pago, b.nombre AS banco_receptor, p.Fecha AS fecha_pago
                    FROM pagos p 
                    JOIN cliente c ON p.id_cliente = c.id_cliente
                    JOIN metodo_pago mp ON p.id_metodo = mp.id_metodo
                    JOIN cuenta cu ON p.id_cuenta = cu.id_cuenta
                    JOIN banco b ON cu.id_banco = b.id_banco WHERE 1=1";
        } elseif ($origen === 'nomina') {
            $sql = "SELECT n.id_nomina, e.cedula, e.nombre, n.monto,
                            mp.nombre AS metodo_pago, b.nombre AS banco_receptor, n.fecha_pago
                    FROM pago_nomina n
                    JOIN entrenador e ON n.id_entrenador = e.cedula
                    JOIN metodo_pago mp ON n.id_metodo = mp.id_metodo
                    JOIN cuenta cu ON n.id_cuenta = cu.id_cuenta
                    JOIN banco b ON cu.id_banco = b.id_banco WHERE 1=1";
        } elseif ($origen === 'servicios') {
            $sql = "SELECT s.id_pago_servicio AS id_servicio, s.concepto, s.monto,
                            mp.nombre AS metodo_pago, b.nombre AS banco_receptor, s.fecha AS fecha_pago
                    FROM pago_servicio s
                    JOIN metodo_pago mp ON s.id_metodo = mp.id_metodo
                    JOIN cuenta cu ON s.id_cuenta = cu.id_cuenta
                    JOIN banco b ON cu.id_banco = b.id_banco WHERE 1=1";
        } else {
            return [];
        }

        if (!empty($fechaInicio)) {
            $sql .= " AND DATE(" . ($origen === 'clientes' ? 'p.Fecha' : ($origen === 'nomina' ? 'n.fecha_pago' : 's.fecha')) . ") >= :fecha_inicio";
            $params[':fecha_inicio'] = $fechaInicio;
        }
        if (!empty($fechaFin)) {
            $sql .= " AND DATE(" . ($origen === 'clientes' ? 'p.Fecha' : ($origen === 'nomina' ? 'n.fecha_pago' : 's.fecha')) . ") <= :fecha_fin";
            $params[':fecha_fin'] = $fechaFin;
        }
        if (!empty($metodoPago)) {
            $sql .= " AND mp.nombre = :metodo_pago";
            $params[':metodo_pago'] = $metodoPago;
        }
        if (!empty($banco)) {
            $sql .= " AND b.nombre = :banco";
            $params[':banco'] = $banco;
        }

        $sql .= " ORDER BY " . ($origen === 'clientes' ? 'p.Fecha' : ($origen === 'nomina' ? 'n.fecha_pago' : 's.fecha')) . " DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerClientesPorEstatus($estatusFinanciero = '') {
        $sql = "SELECT c.id_cliente, c.id_cliente AS cedula, c.Nombre AS nombre, c.apellido,
                        c.telefono, c.Solvencia AS estatus_solvencia, p.nombre AS nombre_plan
                FROM cliente c
                LEFT JOIN planes p ON c.Plan = p.id_plan WHERE 1=1";
        $params = [];

        if ($estatusFinanciero === 'solvente') {
            $sql .= " AND c.Solvencia = 'Solvente'";
        } elseif ($estatusFinanciero === 'insolvente') {
            $sql .= " AND c.Solvencia = 'Insolvente'";
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>