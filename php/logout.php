<?php
// logout.php - Nills Berducido Gómez
session_start();
session_unset();
session_destroy();
header('Content-Type: application/json');

echo json_encode(['exito' => true, 'mensaje' => 'Sesión cerrada correctamente']);
?>