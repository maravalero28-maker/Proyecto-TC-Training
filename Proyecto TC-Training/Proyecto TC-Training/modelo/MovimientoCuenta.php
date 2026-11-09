<?php
class MovimientoCuenta {
    public static function ajustarSaldo(PDO $db, $idCuenta, $delta) {
        if (empty($idCuenta) || abs((float) $delta) < 0.005) {
            return !empty($idCuenta);
        }

        $delta = round((float) $delta, 2);
        $sql = "UPDATE cuenta
                SET saldo = saldo + ?
                WHERE id_cuenta = ?
                  AND (saldo + ?) >= 0";
        $stmt = $db->prepare($sql);
        $stmt->execute([$delta, $idCuenta, $delta]);
        return $stmt->rowCount() === 1;
    }
}
?>
