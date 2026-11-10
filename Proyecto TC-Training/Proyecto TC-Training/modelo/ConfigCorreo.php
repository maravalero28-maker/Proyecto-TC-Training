<?php
// Guarda y lee la configuración del correo emisor (SMTP) desde la base de datos,
// para poder cambiarla desde la interfaz web del módulo de Reportes.
//
// La contraseña de aplicación se guarda CIFRADA (AES-256). La clave de cifrado se
// genera sola en configuracion/clave_correo.key la primera vez que se guarda.
class ConfigCorreo {
    private $db;

    public function __construct() {
        require_once(__DIR__ . '/../configuracion/conecxion.php');
        $this->db = (new Conexion())->getConexion();
        $this->asegurarEsquema();
    }

    private function asegurarEsquema() {
        $this->db->exec("CREATE TABLE IF NOT EXISTS configuracion_correo (
            id TINYINT UNSIGNED NOT NULL PRIMARY KEY,
            host VARCHAR(150) NOT NULL,
            puerto SMALLINT UNSIGNED NOT NULL,
            seguridad VARCHAR(3) NOT NULL,
            usuario VARCHAR(190) NOT NULL,
            clave_cifrada TEXT NOT NULL,
            remitente_nombre VARCHAR(100) NOT NULL
        )");
    }

    private function fila() {
        $st = $this->db->query("SELECT * FROM configuracion_correo WHERE id = 1");
        $f = $st ? $st->fetch(PDO::FETCH_ASSOC) : false;
        return $f ?: null;
    }

    /** Datos para mostrar en pantalla (nunca incluye la contraseña). */
    public function obtenerPublico() {
        $f = $this->fila();
        return [
            'host'             => $f['host'] ?? 'smtp.gmail.com',
            'puerto'           => (int) ($f['puerto'] ?? 587),
            'seguridad'        => $f['seguridad'] ?? 'tls',
            'usuario'          => $f['usuario'] ?? '',
            'remitente_nombre' => $f['remitente_nombre'] ?? 'TC-Control',
            'configurado'      => $f !== null && !empty($this->descifrar($f['clave_cifrada'])),
        ];
    }

    /** Datos completos (con la contraseña descifrada) para enviar correos. */
    public function obtenerParaEnvio() {
        $f = $this->fila();
        if (!$f) {
            return null;
        }
        $clave = $this->descifrar($f['clave_cifrada']);
        if (empty($clave)) {
            return null;
        }
        $f['clave'] = $clave;
        return $f;
    }

    /** Si $claveNueva viene vacía se conserva la contraseña ya guardada. */
    public function guardar($host, $puerto, $seguridad, $usuario, $nombre, $claveNueva = '') {
        if ($claveNueva !== '') {
            $cifrada = $this->cifrar($claveNueva);
        } else {
            $f = $this->fila();
            if (!$f || empty($this->descifrar($f['clave_cifrada']))) {
                throw new Exception('Debes escribir la contraseña de aplicación.');
            }
            $cifrada = $f['clave_cifrada'];
        }

        $st = $this->db->prepare(
            "INSERT INTO configuracion_correo (id, host, puerto, seguridad, usuario, clave_cifrada, remitente_nombre)
             VALUES (1, :host, :puerto, :seg, :usuario, :clave, :nombre)
             ON DUPLICATE KEY UPDATE host = VALUES(host), puerto = VALUES(puerto), seguridad = VALUES(seguridad),
                 usuario = VALUES(usuario), clave_cifrada = VALUES(clave_cifrada), remitente_nombre = VALUES(remitente_nombre)"
        );
        $st->execute([
            ':host'    => $host,
            ':puerto'  => $puerto,
            ':seg'     => $seguridad,
            ':usuario' => $usuario,
            ':clave'   => $cifrada,
            ':nombre'  => $nombre,
        ]);
    }

    // ---------- Cifrado de la contraseña ----------

    private function clave($crear = false) {
        $ruta = __DIR__ . '/../configuracion/clave_correo.key';
        if (!file_exists($ruta)) {
            if (!$crear) {
                return null;
            }
            if (@file_put_contents($ruta, base64_encode(random_bytes(32))) === false) {
                throw new Exception('No se pudo crear el archivo de clave en la carpeta "configuracion" (revisa los permisos).');
            }
        }
        return base64_decode(trim((string) file_get_contents($ruta)));
    }

    private function cifrar($texto) {
        $iv = random_bytes(16);
        $cifrado = openssl_encrypt($texto, 'aes-256-cbc', $this->clave(true), OPENSSL_RAW_DATA, $iv);
        return base64_encode($iv . $cifrado);
    }

    private function descifrar($dato) {
        $clave = $this->clave(false);
        $bin = base64_decode((string) $dato, true);
        if ($clave === null || $bin === false || strlen($bin) < 17) {
            return null;
        }
        $texto = openssl_decrypt(substr($bin, 16), 'aes-256-cbc', $clave, OPENSSL_RAW_DATA, substr($bin, 0, 16));
        return $texto === false ? null : $texto;
    }
}
