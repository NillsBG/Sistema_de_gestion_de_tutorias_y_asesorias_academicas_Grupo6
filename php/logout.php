<?php
// php/logout.php - Cierre de Sesión
session_start();
session_unset();
session_destroy();
header('Content-Type: application/json; charset=utf-8');

echo json_encode(['exito' => true, 'mensaje' => 'Sesión cerrada correctamente'], JSON_UNESCAPED_UNICODE);
?>