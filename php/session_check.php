<?php
// php/session_check.php - Verificación de Estado de Sesión Server-Side
session_start();
header('Content-Type: application/json; charset=utf-8');

if (isset($_SESSION['idUsuario']) && !empty($_SESSION['idUsuario'])) {
    echo json_encode([
        'autenticado' => true,
        'usuario' => [
            'idUsuario'        => $_SESSION['idUsuario'],
            'username'         => $_SESSION['username'],
            'rol'              => $_SESSION['rol'],
            'nombre'           => $_SESSION['nombre'],
            'noCarnetCompleto' => $_SESSION['noCarnetCompleto'] ?? null,
            'codigoCarrera'    => $_SESSION['codigoCarrera'] ?? '0901',
            'nombreCarrera'    => $_SESSION['nombreCarrera'] ?? 'Ingeniería en Sistemas',
            'idTutor'          => $_SESSION['idTutor'] ?? null
        ]
    ], JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode(['autenticado' => false]);
}
?>
