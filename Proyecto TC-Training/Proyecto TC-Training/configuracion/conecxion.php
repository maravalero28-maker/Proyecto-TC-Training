<?php
class Conexion {
    private $servername = "localhost";
    private $dbname = "tc_control";
    private $username = "root";
    private $password = "";
    protected $connect;

    public function __construct() {
        try {
            $this->connect = new PDO(
                "mysql:host={$this->servername};dbname={$this->dbname};charset=utf8mb4",
                $this->username,
                $this->password,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
        } catch (PDOException $e) {
            if ($e->getCode() == 1049) {
                header("Location: ../instalar.php");
                exit();
            }
            die("❌ Error de conexión: " . $e->getMessage());
        }
    }

    public function getConexion() {
        return $this->connect;
    }
}
?>
