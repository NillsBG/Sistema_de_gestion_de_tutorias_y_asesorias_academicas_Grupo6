<?php
// Protección CSRF basada en token sincronizado con la sesión PHP.
require_once __DIR__ . '/seguridad_sesion.php';
iniciarSesionSegura();

function tokenCsrf(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return (string) $_SESSION['csrf_token'];
}

function verificarCsrf(): void {
    $metodo = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));
    if (!in_array($metodo, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) return;

    $recibido = (string)($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if ($recibido === '' || !hash_equals(tokenCsrf(), $recibido)) {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        echo json_encode(['exito' => false, 'valido' => false,
            'mensaje' => 'Solicitud rechazada por seguridad (CSRF). Actualiza la página e inténtalo de nuevo.'], JSON_UNESCAPED_UNICODE);
        exit;
    }
}
