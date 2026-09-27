<?php
class Configuracion {
    private $conexion;

    public function __construct($conexion) {
        $this->conexion = $conexion;
        $this->asegurarColumnas();
    }

    private function asegurarseDeIngresosPeriodo() {
        try {
            $stmt = $this->conexion->prepare("SHOW COLUMNS FROM configuracion LIKE 'ingresos_periodo'");
            $stmt->execute();
            if ($stmt->rowCount() === 0) {
                $this->conexion->exec("ALTER TABLE configuracion ADD COLUMN ingresos_periodo VARCHAR(50) NOT NULL DEFAULT 'semanal'");
            }
        } catch (PDOException $e) {
            
        }
    }

    private function asegurarColumnas() {
        $this->asegurarseDeIngresosPeriodo();
    }

    public function obtenerConfiguracion() {
        $stmt = $this->conexion->prepare("SELECT * FROM configuracion WHERE id = 1");
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row === false) return null;
        if (!isset($row['vencimiento_dias'])) {
            $row['vencimiento_dias'] = 7;
        }
        if (!isset($row['ingresos_periodo'])) {
            $row['ingresos_periodo'] = 'semanal';
        }
        return $row;
    }

    public function actualizarConfiguracion($nombreEmpresa, $tarifaBase, $moneda, $vencimientoDias = 7, $ingresosPeriodo = 'semanal') {
        $stmt = $this->conexion->prepare("UPDATE configuracion SET nombre_empresa = ?, tarifa_base = ?, moneda = ?, vencimiento_dias = ?, ingresos_periodo = ? WHERE id = 1");
        return $stmt->execute([$nombreEmpresa, $tarifaBase, $moneda, $vencimientoDias, $ingresosPeriodo]);
    }
}
?>