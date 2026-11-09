<?php
class Rol {
    private $db;

    public const MODULOS = [
        'clientes'     => 'Clientes',
        'pagos'        => 'Pagos',
        'planes'       => 'Planes',
        'ajustes'      => 'Ajustes',
        'entrenadores' => 'Entrenadores',
        'nomina'       => 'Pago Nómina',
        'servicios'    => 'Otros Servicios',
        'reportes'     => 'Reportes',
        'fondos'       => 'Fondos'
    ];

    public function __construct($conexion) {
        $this->db = $conexion;
        try {
            $stmt = $this->db->prepare("SHOW COLUMNS FROM rol_usuario LIKE 'permisos'");
            $stmt->execute();
            if ($stmt->rowCount() === 0) {
                $this->db->exec("ALTER TABLE rol_usuario ADD COLUMN permisos VARCHAR(500) NOT NULL DEFAULT ''");
                $todos = implode(',', array_keys(self::MODULOS));
                $this->db->exec("UPDATE rol_usuario SET permisos = '$todos' WHERE id_rol IN (1,2)");
                $this->db->exec("UPDATE rol_usuario SET nombre = 'Recepcionista', descripcion = 'Acceso total menos registrar usuarios' WHERE id_rol = 2");
            }
            $col = $this->db->query("SHOW COLUMNS FROM rol_usuario LIKE 'id_rol'")->fetch(PDO::FETCH_ASSOC);
            if ($col && stripos($col['Extra'] ?? '', 'auto_increment') === false) {
                $this->db->exec("ALTER TABLE rol_usuario MODIFY id_rol INT(11) NOT NULL AUTO_INCREMENT");
            }
        } catch (PDOException $e) {
        }
    }

    public function obtenerTodos() {
        try {
            $stmt = $this->db->query("SELECT id_rol, nombre, descripcion, permisos FROM rol_usuario ORDER BY id_rol ASC");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    public function obtenerPorId($id) {
        try {
            $stmt = $this->db->prepare("SELECT id_rol, nombre, descripcion, permisos FROM rol_usuario WHERE id_rol = ? LIMIT 1");
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return false;
        }
    }

    public function crear($nombre, array $modulos) {
        $nombre = trim($nombre);
        $modulos = array_values(array_intersect($modulos, array_keys(self::MODULOS)));
        if ($nombre === '' || count($modulos) === 0) {
            return false;
        }
        try {
            $stmt = $this->db->prepare("SELECT id_rol FROM rol_usuario WHERE nombre = ?");
            $stmt->execute([$nombre]);
            if ($stmt->rowCount() > 0) {
                return false;
            }
            $stmt = $this->db->prepare("INSERT INTO rol_usuario (nombre, descripcion, permisos) VALUES (?, ?, ?)");
            $stmt->execute([$nombre, 'Rol personalizado', implode(',', $modulos)]);
            return (int) $this->db->lastInsertId();
        } catch (PDOException $e) {
            return false;
        }
    }
}
?>
