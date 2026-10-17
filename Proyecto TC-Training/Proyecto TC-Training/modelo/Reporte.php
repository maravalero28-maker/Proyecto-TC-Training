<?php
class Reporte {
    private $db;

    public function __construct() {
        // Ajuste: usar el archivo de conexión existente (conecxion.php) y la clase Conexion
        require_once(__DIR__ . '/../configuracion/conecxion.php');
        $this->db = (new Conexion())->getConexion();
    }

    // Obtener trazabilidad de accesos (movimientos) filtrada por fechas
    public function obtenerMovimientosPorFecha($fechaInicio, $fechaFin) {
        $sql = "SELECT m.id_movimiento, u_sujeto.cedula AS cedula_sujeto, u_sujeto.rol AS rol_sujeto, 
                       m.tipo_movimiento, m.timestamp_registro, m.sincronizado_jpy,
                       u_operador.cedula AS cedula_operador
                FROM movimientos m
                JOIN usuarios u_sujeto ON m.id_usuario = u_sujeto.id_usuario
                JOIN usuarios u_operador ON m.id_operador = u_operador.id_usuario
                WHERE DATE(m.timestamp_registro) BETWEEN :fecha_inicio AND :fecha_fin
                ORDER BY m.timestamp_registro DESC";
                
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':fecha_inicio' => $fechaInicio,
            ':fecha_fin' => $fechaFin
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Obtener padrón y estado de los usuarios
    public function obtenerUsuariosGeneral($rol = '', $activo = '') {
        $sql = "SELECT id_usuario, cedula, rol, empresa_externa, activo FROM usuarios WHERE 1=1";
        $params = [];

        if (!empty($rol)) {
            $sql .= " AND rol = :rol";
            $params[':rol'] = $rol;
        }
        if ($activo !== '') {
            $sql .= " AND activo = :activo";
            $params[':activo'] = $activo;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>