<?php
/** Cambio de contraseña de la cuenta autenticada. No recibe ni confía en idUsuario del cliente. */
require_once __DIR__ . '/../php/auth_middleware.php';
require_once __DIR__ . '/../php/csrf.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function responderCambio(int $codigo, bool $exito, string $mensaje): void {
    http_response_code($codigo);
    echo json_encode(['exito' => $exito, 'mensaje' => $mensaje], JSON_UNESCAPED_UNICODE);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    responderCambio(405, false, 'Método no permitido.');
}
verificarAutenticacion();
verificarCsrf();
$entrada = json_decode(file_get_contents('php://input'), true);
if (!is_array($entrada)) responderCambio(400, false, 'Formulario inválido.');
$actual = (string)($entrada['contrasenaActual'] ?? '');
$nueva = (string)($entrada['contrasenaNueva'] ?? '');
$confirmar = (string)($entrada['confirmarContrasena'] ?? '');
if ($actual === '' || $nueva === '' || $confirmar === '') responderCambio(400, false, 'Completa todos los campos.');
if ($nueva !== $confirmar) responderCambio(400, false, 'Las contraseñas nuevas no coinciden.');
if (!preg_match('//u', $nueva) || preg_match_all('/./us', $nueva) < 8 || strlen($nueva) > 72) {
    responderCambio(400, false, 'La nueva contraseña debe tener al menos 8 caracteres y un máximo de 72 bytes.');
}
if (strlen($actual) > 1024) responderCambio(400, false, 'Contraseña actual inválida.');
if (hash_equals($actual, $nueva)) responderCambio(400, false, 'La nueva contraseña debe ser diferente de la actual.');

require_once __DIR__ . '/conexion.php';
try {
    $id = (int)$_SESSION['idUsuario'];
    $pdo->beginTransaction();
    $stmt = $pdo->prepare('SELECT contrasena, estado FROM tblUsuarios WHERE idUsuario = ? FOR UPDATE');
    $stmt->execute([$id]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$usuario || $usuario['estado'] !== 'Activo') {
        $pdo->rollBack();
        responderCambio(403, false, 'La cuenta no está habilitada.');
    }
    if (!password_verify($actual, (string)$usuario['contrasena'])) {
        $pdo->rollBack();
        responderCambio(401, false, 'La contraseña actual es incorrecta.');
    }
    $hash = password_hash($nueva, PASSWORD_DEFAULT);
    $actualizar = $pdo->prepare('UPDATE tblUsuarios SET contrasena = ? WHERE idUsuario = ?');
    $actualizar->execute([$hash, $id]);
    $pdo->commit();
    require_once __DIR__ . '/../php/auditoria.php';
    registrarAuditoria($pdo, 'CAMBIO_CONTRASENA', 'usuario', (string)$id, $id);
    destruirSesionSegura();
    responderCambio(200, true, 'Contraseña actualizada. Inicia sesión nuevamente.');
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('Error al cambiar contraseña: ' . $e->getMessage());
    responderCambio(500, false, 'No fue posible cambiar la contraseña.');
}
