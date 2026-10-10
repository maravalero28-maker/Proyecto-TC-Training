<?php
class Administrador {
    private $db;

    public function __construct($conexion) {
        $this->db = $conexion;
        
        try {
            $stmt = $this->db->prepare("SHOW COLUMNS FROM administrador LIKE 'PreguntaSecreta'");
            $stmt->execute();
            if ($stmt->rowCount() === 0) {
                $this->db->exec("ALTER TABLE administrador ADD COLUMN PreguntaSecreta VARCHAR(255) DEFAULT NULL");
            }
            $stmt = $this->db->prepare("SHOW COLUMNS FROM administrador LIKE 'RespuestaSecreta'");
            $stmt->execute();
            if ($stmt->rowCount() === 0) {
                $this->db->exec("ALTER TABLE administrador ADD COLUMN RespuestaSecreta VARCHAR(255) DEFAULT NULL");
            }
        } catch (PDOException $e) {
            
        }
    }

    public function registrar($usuario, $password, $pregunta = null, $respuesta = null) {
        if (empty($usuario) || empty($password) || empty($pregunta) || empty($respuesta)) {
            return "warning";
        }

        try {
            
            $stmt = $this->db->prepare("SELECT id_admin FROM administrador WHERE Usuario = ?");
            $stmt->execute([$usuario]);

            if ($stmt->rowCount() > 0) {
                return "existe";
            }

            
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $hashedAnswer = password_hash($respuesta, PASSWORD_DEFAULT);

            
            $stmt = $this->db->prepare("INSERT INTO administrador (Usuario, Contrasenna, PreguntaSecreta, RespuestaSecreta) VALUES (?, ?, ?, ?)");

            if ($stmt->execute([$usuario, $hashedPassword, $pregunta, $hashedAnswer])) {
                return "success";
            }

            return "error";

        } catch (PDOException $e) {
            if ($e->getCode() == 1062) {
                return "existe";
            }
            return "error";
        }
    }

    public function obtenerPorUsuario($usuario) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM administrador WHERE Usuario = ? LIMIT 1");
            $stmt->execute([$usuario]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return false;
        }
    }

    public function actualizarContrasennaPorId($id, $nuevaContrasenna) {
        try {
            $hashed = password_hash($nuevaContrasenna, PASSWORD_DEFAULT);
            $stmt = $this->db->prepare("UPDATE administrador SET Contrasenna = ? WHERE id_admin = ?");
            return $stmt->execute([$hashed, $id]);
        } catch (PDOException $e) {
            return false;
        }
    }

    public function verificarCredenciales($usuario, $password) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM administrador WHERE Usuario = :user LIMIT 1");
            $stmt->bindParam(':user', $usuario);
            $stmt->execute();
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$admin) {
                return "no_existe";
            }
            
            if (password_verify($password, $admin['Contrasenna'])) {
                return "correcto";
            }
            
            return "password_incorrecta";
            
        } catch (PDOException $e) {
            return "error";
        }
    }

    public function obtenerTodos() {
        $sql = "SELECT id_admin, Usuario FROM administrador ORDER BY id_admin DESC";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    public function eliminar($id) {
        $sql = "DELETE FROM administrador WHERE id_admin = ?";
        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            return false;
        }
    }
}
?>
