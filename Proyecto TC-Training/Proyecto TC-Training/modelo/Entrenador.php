<?php
class Entrenador {
    private $db;
    private $lastError = null;

    public function __construct($db_conn) {
        $this->db = $db_conn;
        $this->asegurarTabla();
    }

    private function asegurarTabla() {
        $sql = "CREATE TABLE IF NOT EXISTS entrenador (
            cedula VARCHAR(30) NOT NULL,
            nombre VARCHAR(150) NOT NULL,
            apellido VARCHAR(150) NOT NULL,
            telefono VARCHAR(25) NOT NULL,
            estatus VARCHAR(20) NOT NULL DEFAULT 'activo',
            fecha_registro TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (cedula)
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

    public function registrar($cedula, $nombre, $apellido, $telefono, $estatus) {
        if (empty($cedula) || empty($nombre) || empty($apellido)) {
            return "warning";
        }
        $sql = "INSERT INTO entrenador (cedula, nombre, apellido, telefono, estatus) VALUES (?, ?, ?, ?, ?)";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$cedula, $nombre, $apellido, $telefono, $estatus]);
            return "success";
        } catch (PDOException $e) {
            if ($e->getCode() == 1062) {
                return "existe";
            }
            $this->lastError = $e->getMessage();
            return "error";
        }
    }

    public function obtenerTodos($filtro = 'todos') {
        $sql = "SELECT cedula, nombre, apellido, telefono, estatus, fecha_registro FROM entrenador";
        if ($filtro === 'activo') {
            $sql .= " WHERE LOWER(estatus) = 'activo'";
        } elseif ($filtro === 'inactivo') {
            $sql .= " WHERE LOWER(estatus) = 'inactivo'";
        }
        $sql .= " ORDER BY fecha_registro DESC";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    public function obtenerPorCedula($cedula) {
        $sql = "SELECT cedula, nombre, apellido, telefono, estatus, fecha_registro FROM entrenador WHERE cedula = ?";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$cedula]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return false;
        }
    }

    public function actualizar($cedulaOriginal, $cedulaNueva, $nombre, $apellido, $telefono, $estatus) {
        try {
            if ($cedulaOriginal !== $cedulaNueva) {
                $check = $this->obtenerPorCedula($cedulaNueva);
                if ($check) return "existe";
            }
            $sql = "UPDATE entrenador SET cedula = ?, nombre = ?, apellido = ?, telefono = ?, estatus = ? WHERE cedula = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$cedulaNueva, $nombre, $apellido, $telefono, $estatus, $cedulaOriginal]) ? "success" : "error";
        } catch (PDOException $e) {
            return "error";
        }
    }

    public function eliminar($cedula) {
        $sql = "UPDATE entrenador SET estatus = 'inactivo' WHERE cedula = ?";
        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$cedula]);
        } catch (PDOException $e) {
            return false;
        }
    }
}
?>