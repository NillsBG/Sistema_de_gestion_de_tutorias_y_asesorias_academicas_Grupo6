<?php
require_once __DIR__ . '/seguridad_sesion.php';
iniciarSesionSegura();
header('Content-Type: application/json; charset=utf-8');
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    echo json_encode(['exito' => false, 'mensaje' => 'Método no permitido.']);
    exit;
}
require_once __DIR__ . '/csrf.php';
verificarCsrf();
destruirSesionSegura();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
echo json_encode(['exito' => true, 'mensaje' => 'Sesión cerrada correctamente.'], JSON_UNESCAPED_UNICODE);
