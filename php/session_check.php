<?php
// php/session_check.php - Consulta de Sesión Activa
session_start();
header('Content-Type: application/json; charset=utf-8');

if (isset($_SESSION['idUsuario'])) {
    echo json_encode([
        'autenticado' => true,
        'usuario' => [
            'idUsuario'        => $_SESSION['idUsuario'],
            'username'         => $_SESSION['username'],
            'rol'              => $_SESSION['rol'],
            'nombre'           => $_SESSION['nombre'],
            'noCarnetCompleto' => $_SESSION['noCarnetCompleto'] ?? null,
            'idTutor'          => $_SESSION['idTutor'] ?? null
        ]
    ], JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode(['autenticado' => false], JSON_UNESCAPED_UNICODE);
}
?>
