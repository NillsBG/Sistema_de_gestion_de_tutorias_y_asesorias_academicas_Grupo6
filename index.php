<?php
// index.php - Enrutador Principal con Control de Sesiones e Inactividad
require_once __DIR__ . '/php/seguridad_sesion.php';
require_once __DIR__ . '/php/csrf.php';

iniciarSesionSegura();

$idUsuario = $_SESSION['idUsuario'] ?? null;
$rol       = strtolower(trim((string)($_SESSION['rol'] ?? '')));

if ($rol === 'admin') $rol = 'administrador';

if ($idUsuario && !empty($rol)) {
    if ($rol === 'administrador') {
        include __DIR__ . '/vistas/admin.php';
        exit;
    } elseif ($rol === 'tutor') {
        include __DIR__ . '/vistas/tutor.php';
        exit;
    } elseif ($rol === 'estudiante') {
        include __DIR__ . '/vistas/estudiante.php';
        exit;
    }
}

// Si no hay sesión, muestra el Login
include __DIR__ . '/vistas/login.php';
?>
