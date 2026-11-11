<?php
class PlantillaMensaje {
    private $db;

    const TEXTO_PREVENCION = "Hola {nombre}, te saludamos de Training Club. Mensaje preventivo: si ocurre un sismo, mantén la calma. Aléjate de ventanas, vidrios y objetos que puedan caer, protégete bajo una mesa resistente o junto a una pared interior, no uses ascensores y, cuando termine el movimiento, evacúa con orden por las rutas señaladas. Cuida de ti y de quienes te rodean.";

    public function __construct($db_conn) {
        $this->db = $db_conn;
        $this->asegurarTabla();
    }

    // Crea la tabla y el mensaje de prevención si todavía no existen.
    private function asegurarTabla() {
        try {
            $this->db->exec("CREATE TABLE IF NOT EXISTS plantilla_mensaje (
                id_plantilla INT(11) NOT NULL AUTO_INCREMENT,
                titulo VARCHAR(100) NOT NULL,
                tipo VARCHAR(30) NOT NULL DEFAULT 'otro',
                contenido TEXT NOT NULL,
                es_predeterminada TINYINT(1) NOT NULL DEFAULT 0,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id_plantilla)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

            $total = (int)$this->db->query("SELECT COUNT(*) FROM plantilla_mensaje")->fetchColumn();
            if ($total === 0) {
                $stmt = $this->db->prepare("INSERT INTO plantilla_mensaje (titulo, tipo, contenido, es_predeterminada) VALUES (?, 'prevencion', ?, 1)");
                $stmt->execute(['Prevención contra sismos', self::TEXTO_PREVENCION]);
            }
        } catch (PDOException $e) {
            // Si falla, las demás funciones devolverán vacío sin romper la pantalla.
        }
    }

    public function obtenerTodas() {
        try {
            $stmt = $this->db->query("SELECT id_plantilla, titulo, tipo, contenido, es_predeterminada
                                      FROM plantilla_mensaje
                                      ORDER BY es_predeterminada DESC, titulo ASC");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    public function obtenerPorId($id) {
        try {
            $stmt = $this->db->prepare("SELECT id_plantilla, titulo, tipo, contenido, es_predeterminada FROM plantilla_mensaje WHERE id_plantilla = ?");
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return false;
        }
    }

    public function guardar($titulo, $tipo, $contenido) {
        $tipos = unserialize(TIPOS_MENSAJE);
        $titulo = trim($titulo);
        $contenido = trim($contenido);

        if ($titulo === '' || $contenido === '') {
            return ['ok' => false, 'mensaje' => 'Escribe un título y el texto del mensaje.'];
        }
        if (mb_strlen($titulo) > 100) {
            return ['ok' => false, 'mensaje' => 'El título no puede pasar de 100 caracteres.'];
        }
        if (mb_strlen($contenido) > 1000) {
            return ['ok' => false, 'mensaje' => 'El mensaje no puede pasar de 1000 caracteres.'];
        }
        if (!isset($tipos[$tipo])) {
            $tipo = 'otro';
        }

        try {
            $stmt = $this->db->prepare("INSERT INTO plantilla_mensaje (titulo, tipo, contenido, es_predeterminada) VALUES (?, ?, ?, 0)");
            $stmt->execute([$titulo, $tipo, $contenido]);
            return ['ok' => true, 'id' => (int)$this->db->lastInsertId()];
        } catch (PDOException $e) {
            return ['ok' => false, 'mensaje' => 'No se pudo guardar el mensaje.'];
        }
    }

    public function eliminar($id) {
        $plantilla = $this->obtenerPorId($id);
        if (!$plantilla) {
            return ['ok' => false, 'mensaje' => 'El mensaje no existe.'];
        }
        if ((int)$plantilla['es_predeterminada'] === 1) {
            return ['ok' => false, 'mensaje' => 'No puedes eliminar el mensaje predeterminado. Marca otro como predeterminado primero.'];
        }
        try {
            $stmt = $this->db->prepare("DELETE FROM plantilla_mensaje WHERE id_plantilla = ?");
            $stmt->execute([$id]);
            return ['ok' => true];
        } catch (PDOException $e) {
            return ['ok' => false, 'mensaje' => 'No se pudo eliminar el mensaje.'];
        }
    }

    public function marcarPredeterminada($id) {
        if (!$this->obtenerPorId($id)) {
            return ['ok' => false, 'mensaje' => 'El mensaje no existe.'];
        }
        try {
            $this->db->beginTransaction();
            $this->db->exec("UPDATE plantilla_mensaje SET es_predeterminada = 0");
            $stmt = $this->db->prepare("UPDATE plantilla_mensaje SET es_predeterminada = 1 WHERE id_plantilla = ?");
            $stmt->execute([$id]);
            $this->db->commit();
            return ['ok' => true];
        } catch (PDOException $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return ['ok' => false, 'mensaje' => 'No se pudo cambiar el mensaje predeterminado.'];
        }
    }
}
?>
