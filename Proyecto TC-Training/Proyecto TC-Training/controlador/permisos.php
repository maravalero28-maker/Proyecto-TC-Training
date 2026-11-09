<?php
// Funciones de permisos. Se incluye DESPUÉS de session_start().

function esAdmin() {
    return isset($_SESSION['id_rol']) && (int) $_SESSION['id_rol'] === 1;
}

function puedeAcceder($modulo) {
    if (esAdmin()) {
        return true;
    }
    $permisos = $_SESSION['permisos'] ?? [];
    return in_array($modulo, $permisos, true);
}

function requerirModulo($modulo) {
    if (!puedeAcceder($modulo)) {
        header("Location: ../controlador/InicioController.php?error=sin_permiso");
        exit();
    }
}

function requerirAdmin() {
    if (!esAdmin()) {
        header("Location: ../controlador/InicioController.php?error=sin_permiso");
        exit();
    }
}
?>
