<?php
// session_check.php - Nills Berducido Gómez
session_start();
header('Content-Type: application/json');

if (isset($_SESSION['usuario_id'])) {
    echo json_encode([
        'autenticado' => true,
        'usuario' => [
            'id'     => $_SESSION['usuario_id'],
            'nombre' => $_SESSION['usuario_nombre'],
            'correo' => $_SESSION['usuario_correo'],
            'rol'    => $_SESSION['usuario_rol']
        ]
    ]);
} else {
    echo json_encode(['autenticado' => false]);
}
?>
