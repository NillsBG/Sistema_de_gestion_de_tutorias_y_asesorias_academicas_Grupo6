<?php
// Sesiones seguras. Se incluye antes de generar cualquier salida HTML/JSON.
const TIEMPO_INACTIVIDAD = 900; // 15 minutos, en segundos.
date_default_timezone_set('America/Guatemala');

function iniciarSesionSegura(): void {
    if (session_status() === PHP_SESSION_ACTIVE) {
        verificarTiempoInactividad();
        return;
    }
    if (headers_sent()) {
        throw new RuntimeException('La sesión debe iniciarse antes de imprimir contenido.');
    }
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_trans_sid', '0');
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (int)($_SERVER['SERVER_PORT'] ?? 0) === 443;
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $https,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
    header('Cache-Control: no-store, private');
    verificarTiempoInactividad();
}

function destruirSesionSegura(): void {
    if (session_status() !== PHP_SESSION_ACTIVE) return;
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires' => time() - 3600,
            'path' => $params['path'],
            'domain' => $params['domain'],
            'secure' => $params['secure'],
            'httponly' => true,
            'samesite' => $params['samesite'] ?? 'Lax',
        ]);
    }
    session_destroy();
}

function verificarTiempoInactividad(): void {
    if (empty($_SESSION['idUsuario'])) return;
    $ahora = time();
    // Permite actualizar sin cerrar de golpe las sesiones iniciadas antes de instalarlo.
    $ultimo = (int)($_SESSION['ultimaActividadReal'] ?? $ahora);
    if (($ahora - $ultimo) >= TIEMPO_INACTIVIDAD) {
        destruirSesionSegura();
        return;
    }
    if (!isset($_SESSION['ultimaActividadReal'])) {
        $_SESSION['ultimaActividadReal'] = $ahora;
    }
}

function registrarActividadReal(): bool {
    if (empty($_SESSION['idUsuario'])) return false;
    $_SESSION['ultimaActividadReal'] = time();
    return true;
}
