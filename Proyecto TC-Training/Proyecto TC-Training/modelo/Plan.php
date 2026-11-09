<?php
class Plan {
    private $db;
    private $lastError = '';

    public function __construct($db_conn) {
        $this->db = $db_conn;
    }

    public function getLastError() {
        return $this->lastError;
    }

    private function createTableIfMissing() {
        $sql = "CREATE TABLE IF NOT EXISTS planes (
            id_plan INT(11) NOT NULL AUTO_INCREMENT,
            nombre VARCHAR(120) NOT NULL,
            precio DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            dias INT(11) NOT NULL DEFAULT 30,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id_plan)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

        try {
            $this->db->exec($sql);
            return true;
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    private function tryRecoverMissingTable(PDOException $e) {
        if (strpos($e->getMessage(), '1146') !== false || stripos($e->getMessage(), "doesn't exist") !== false) {
            return $this->createTableIfMissing();
        }
        return false;
    }

    public function obtenerTodos() {
        $sql = "SELECT id_plan, nombre, precio, dias FROM planes ORDER BY dias ASC";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            if ($this->tryRecoverMissingTable($e)) {
                try {
                    $stmt = $this->db->prepare($sql);
                    $stmt->execute();
                    return $stmt->fetchAll(PDO::FETCH_ASSOC);
                } catch (PDOException $inner) {
                    $this->lastError = $inner->getMessage();
                }
            }
            return [];
        }
    }

    public function obtenerPorId($id) {
        $sql = "SELECT id_plan, nombre, precio, dias FROM planes WHERE id_plan = ? LIMIT 1";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            if ($this->tryRecoverMissingTable($e)) {
                try {
                    $stmt = $this->db->prepare($sql);
                    $stmt->execute([$id]);
                    return $stmt->fetch(PDO::FETCH_ASSOC);
                } catch (PDOException $inner) {
                    $this->lastError = $inner->getMessage();
                }
            }
            return false;
        }
    }

    public function registrar($nombre, $precio, $dias) {
        $sql = "INSERT INTO planes (nombre, precio, dias) VALUES (?, ?, ?)";
        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$nombre, $precio, $dias]);
        } catch (PDOException $e) {
            if ($this->tryRecoverMissingTable($e)) {
                try {
                    $stmt = $this->db->prepare($sql);
                    return $stmt->execute([$nombre, $precio, $dias]);
                } catch (PDOException $inner) {
                    $this->lastError = $inner->getMessage();
                }
            } else {
                $this->lastError = $e->getMessage();
            }
            return false;
        }
    }

    public function actualizar($id, $nombre, $precio, $dias) {
        $sql = "UPDATE planes SET nombre = ?, precio = ?, dias = ? WHERE id_plan = ?";
        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$nombre, $precio, $dias, $id]);
        } catch (PDOException $e) {
            if ($this->tryRecoverMissingTable($e)) {
                try {
                    $stmt = $this->db->prepare($sql);
                    return $stmt->execute([$nombre, $precio, $dias, $id]);
                } catch (PDOException $inner) {
                    $this->lastError = $inner->getMessage();
                }
            } else {
                $this->lastError = $e->getMessage();
            }
            return false;
        }
    }

    public function eliminar($id) {
        try {
            $stmt = $this->db->query("SHOW COLUMNS FROM pagos LIKE 'id_plan'");
            if ($stmt->rowCount() === 0) {
                $this->db->exec("ALTER TABLE pagos ADD COLUMN id_plan INT(11) DEFAULT NULL");
            }
            $this->db->exec("UPDATE pagos p
                             INNER JOIN cliente c ON c.id_cliente = p.id_cliente
                             SET p.id_plan = (
                                 SELECT pl.id_plan
                                 FROM planes pl
                                 WHERE pl.id_plan = CAST(c.Plan AS UNSIGNED) OR pl.nombre = c.Plan
                                 LIMIT 1
                             )
                             WHERE p.id_plan IS NULL");

            $stmt = $this->db->prepare("SELECT COUNT(*) FROM cliente WHERE Plan = ?");
            $stmt->execute([$id]);
            $usadoPorClientes = (int) $stmt->fetchColumn() > 0;
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM pagos WHERE id_plan = ?");
            $stmt->execute([$id]);
            $usadoPorPagos = (int) $stmt->fetchColumn() > 0;
            if ($usadoPorClientes || $usadoPorPagos) {
                $this->lastError = 'No se puede eliminar el plan porque está asociado a clientes o pagos del historial.';
                return false;
            }

            $stmt = $this->db->prepare("DELETE FROM planes WHERE id_plan = ?");
            $stmt->execute([$id]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }
}
?>