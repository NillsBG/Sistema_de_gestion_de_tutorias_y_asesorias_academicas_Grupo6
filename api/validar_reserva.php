<?php
require_once __DIR__ . '/../php/auth_middleware.php';
verificarRol(['Estudiante']);
require_once __DIR__ . '/../php/csrf.php';
verificarCsrf();
header('Content-Type: application/json; charset=utf-8');
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    echo json_encode(['valido' => false, 'mensaje' => 'Método no permitido.']); exit;
}
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/reglas_reserva.php';
$pdo->exec("SET time_zone = '-06:00'");
$entrada = json_decode(file_get_contents('php://input'), true);
if (!is_array($entrada)) $entrada = $_POST;
try {
    [$valido, $mensaje] = evaluarReserva($pdo, carnetSesion(), (int)($entrada['idDisponibilidad'] ?? 0));
    echo json_encode(['valido'=>$valido,'mensaje'=>$mensaje], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    error_log('Error prevalidación reserva: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['valido'=>false,'mensaje'=>'No fue posible validar la reserva.']);
}
