<?php
// session_check.php - Nills Berducido Gómez
session_start();
header('Content-Type: application/json');

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
    ]);
} else {
    echo json_encode(['autenticado' => false]);
}
?>
