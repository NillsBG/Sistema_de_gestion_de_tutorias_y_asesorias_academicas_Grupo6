<?php
// index.php - Enrutador Principal Basado en Roles (Server-Side Architecture)
session_start();

$rol = strtolower($_SESSION['rol'] ?? '');

if (!isset($_SESSION['idUsuario']) || empty($rol)) {
    include 'vistas/login.php';
    exit;
}

switch ($rol) {
    case 'estudiante':
        include 'vistas/estudiante.php';
        break;
    case 'tutor':
        include 'vistas/tutor.php';
        break;
    case 'administrador':
    case 'admin':
        include 'vistas/admin.php';
        break;
    default:
        session_destroy();
        include 'vistas/login.php';
        break;
}
?>