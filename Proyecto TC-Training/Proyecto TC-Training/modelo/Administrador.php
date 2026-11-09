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
            $stmt = $this->db->prepare("SHOW COLUMNS FROM administrador LIKE 'id_rol'");
            $stmt->execute();
            if ($stmt->rowCount() === 0) {
                $this->db->exec("ALTER TABLE administrador ADD COLUMN id_rol INT(11) DEFAULT 2");
            }
        } catch (PDOException $e) {
            
        }
    }

    public function registrar($usuario, $password, $pregunta = null, $respuesta = null, $idRol = 2) {
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

            
            $stmt = $this->db->prepare("INSERT INTO administrador (Usuario, Contrasenna, PreguntaSecreta, RespuestaSecreta, id_rol) VALUES (?, ?, ?, ?, ?)");

            if ($stmt->execute([$usuario, $hashedPassword, $pregunta, $hashedAnswer, (int) $idRol])) {
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

            $passwordInfo = password_get_info($admin['Contrasenna']);
            if (empty($passwordInfo['algo']) && hash_equals($admin['Contrasenna'], $password)) {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $this->db->prepare("UPDATE administrador SET Contrasenna = ? WHERE id_admin = ?");
                if ($stmt->execute([$hash, $admin['id_admin']])) {
                    return "correcto";
                }
                return "error";
            }
            
            return "password_incorrecta";
            
        } catch (PDOException $e) {
            return "error";
        }
    }

    public function obtenerTodos() {
        $sql = "SELECT a.id_admin, a.Usuario, a.id_rol, r.nombre AS rol FROM administrador a LEFT JOIN rol_usuario r ON r.id_rol = a.id_rol ORDER BY a.id_admin DESC";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    public function contar() {
        try {
            return (int) $this->db->query("SELECT COUNT(*) FROM administrador")->fetchColumn();
        } catch (PDOException $e) {
            return 0;
        }
    }

    private function contarAdministradores() {
        return (int) $this->db->query("SELECT COUNT(*) FROM administrador WHERE id_rol = 1")->fetchColumn();
    }

    public function cambiarRol($id, $idRol) {
        try {
            $stmt = $this->db->prepare("SELECT id_rol FROM administrador WHERE id_admin = ?");
            $stmt->execute([$id]);
            $fila = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$fila) {
                return false;
            }
            if ((int) $fila['id_rol'] === 1 && (int) $idRol !== 1 && $this->contarAdministradores() <= 1) {
                return false;
            }
            $stmt = $this->db->prepare("UPDATE administrador SET id_rol = ? WHERE id_admin = ?");
            return $stmt->execute([$idRol, $id]);
        } catch (PDOException $e) {
            return false;
        }
    }

    public function actualizar($id, $usuario, $password = '') {
        try {
            $stmt = $this->db->prepare("SELECT id_admin FROM administrador WHERE Usuario = ? AND id_admin <> ?");
            $stmt->execute([$usuario, $id]);
            if ($stmt->rowCount() > 0) {
                return "existe";
            }
            if ($password !== '') {
                $stmt = $this->db->prepare("UPDATE administrador SET Usuario = ?, Contrasenna = ? WHERE id_admin = ?");
                $ok = $stmt->execute([$usuario, password_hash($password, PASSWORD_DEFAULT), $id]);
            } else {
                $stmt = $this->db->prepare("UPDATE administrador SET Usuario = ? WHERE id_admin = ?");
                $ok = $stmt->execute([$usuario, $id]);
            }
            return $ok ? "success" : "error";
        } catch (PDOException $e) {
            return "error";
        }
    }

    public function eliminar($id) {
        try {
            $stmt = $this->db->prepare("SELECT id_rol FROM administrador WHERE id_admin = ?");
            $stmt->execute([$id]);
            $fila = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$fila) {
                return false;
            }
            if ((int) $fila['id_rol'] === 1 && $this->contarAdministradores() <= 1) {
                return false;
            }
            $stmt = $this->db->prepare("DELETE FROM administrador WHERE id_admin = ?");
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            return false;
        }
    }
}
?>
