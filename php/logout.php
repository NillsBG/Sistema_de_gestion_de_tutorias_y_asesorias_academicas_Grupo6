<?php
require_once __DIR__ . '/seguridad_sesion.php';
iniciarSesionSegura();
destruirSesionSegura();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
echo json_encode(['exito' => true, 'mensaje' => 'Sesión cerrada correctamente.'], JSON_UNESCAPED_UNICODE);
