<?php
require_once __DIR__ . '/seguridad_sesion.php';
iniciarSesionSegura();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$metodo = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($metodo !== 'GET' && $metodo !== 'POST') {
    header('Allow: GET, POST');
    http_response_code(405);
    echo json_encode(['autenticado' => false, 'mensaje' => 'Método no permitido.']);
    exit;
}
if (empty($_SESSION['idUsuario'])) {
    http_response_code(401);
    echo json_encode(['autenticado' => false, 'mensaje' => 'Sesión expirada o no iniciada.']);
    exit;
}
if ($metodo === 'POST') {
    require_once __DIR__ . '/csrf.php';
    verificarCsrf();
    // Solo interacciones humanas en js/actividad_sesion.js envían POST.
    // Comprobar origen cuando el navegador lo envíe.
    $origen = $_SERVER['HTTP_ORIGIN'] ?? '';
    $host = $_SERVER['HTTP_HOST'] ?? '';
    if ($origen !== '' && parse_url($origen, PHP_URL_HOST) !== parse_url('http://' . $host, PHP_URL_HOST)) {
        http_response_code(403);
        echo json_encode(['autenticado' => false, 'mensaje' => 'Origen no autorizado.']);
        exit;
    }
    registrarActividadReal();
}
echo json_encode([
    'autenticado' => true,
    'tiempoInactividadSegundos' => TIEMPO_INACTIVIDAD,
    'usuario' => [
        'idUsuario' => $_SESSION['idUsuario'],
        'username' => $_SESSION['username'] ?? '',
        'rol' => $_SESSION['rol'] ?? '',
        'nombre' => $_SESSION['nombre'] ?? '',
        'noCarnetCompleto' => $_SESSION['noCarnetCompleto'] ?? null,
        'codigoCarrera' => $_SESSION['codigoCarrera'] ?? '0901',
        'nombreCarrera' => $_SESSION['nombreCarrera'] ?? 'Ingeniería en Sistemas',
        'idTutor' => $_SESSION['idTutor'] ?? null,
    ],
], JSON_UNESCAPED_UNICODE);
