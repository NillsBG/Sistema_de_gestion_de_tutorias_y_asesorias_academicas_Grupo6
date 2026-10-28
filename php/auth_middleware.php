<?php
// php/auth_middleware.php - Control de Acceso basado en Roles (RBAC)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function verificarAutenticacion() {
    if (!isset($_SESSION['idUsuario'])) {
        header('Location: index.php');
        exit;
    }
}

function verificarRol($rolesPermitidos = []) {
    verificarAutenticacion();
    $rolActual = strtolower($_SESSION['rol'] ?? '');
    $rolesLower = array_map('strtolower', $rolesPermitidos);

    if (!in_array($rolActual, $rolesLower)) {
        http_response_code(403);
        echo "<h2 style='color:red; text-align:center; margin-top:50px;'>403 - Acceso Denegado: No tienes permisos para ver esta sección.</h2>";
        exit;
    }
}
?>