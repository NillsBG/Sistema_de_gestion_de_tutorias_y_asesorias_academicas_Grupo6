<?php
// Control de acceso por sesión y roles para vistas y API.
require_once __DIR__ . '/seguridad_sesion.php';
iniciarSesionSegura();

function rolActual(): string {
    $rol = strtolower(trim((string)($_SESSION['rol'] ?? '')));
    return $rol === 'admin' ? 'administrador' : $rol;
}

function errorAcceso(int $codigo, string $mensaje): void {
    http_response_code($codigo);
    // Las API siempre devuelven JSON; las vistas conservan mensajes HTML.
    $esApi = str_contains(str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? ''), '/api/');
    if ($esApi) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['exito' => false, 'valido' => false, 'mensaje' => $mensaje], JSON_UNESCAPED_UNICODE);
    } elseif ($codigo === 401) {
        header('Location: index.php');
    } else {
        header('Content-Type: text/html; charset=utf-8');
        echo '<h2 style="text-align:center;margin-top:50px">403 - Acceso denegado</h2>';
    }
    exit;
}

function verificarAutenticacion(): void {
    if (empty($_SESSION['idUsuario']) || rolActual() === '') {
        errorAcceso(401, 'Debes iniciar sesión para continuar.');
    }
}

function verificarRol(array $rolesPermitidos = []): void {
    verificarAutenticacion();
    $roles = array_map(static function ($rol) {
        $valor = strtolower(trim((string)$rol));
        return $valor === 'admin' ? 'administrador' : $valor;
    }, $rolesPermitidos);
    if (!in_array(rolActual(), $roles, true)) {
        errorAcceso(403, 'No tienes permisos para realizar esta operación.');
    }
}

function carnetSesion(): string {
    verificarRol(['Estudiante']);
    $carnet = trim((string)($_SESSION['noCarnetCompleto'] ?? ''));
    if ($carnet === '') errorAcceso(403, 'Tu perfil de estudiante no está configurado.');
    return $carnet;
}

function tutorSesion(): int {
    verificarRol(['Tutor']);
    $id = (int)($_SESSION['idTutor'] ?? 0);
    if ($id <= 0) errorAcceso(403, 'Tu perfil de tutor no está configurado.');
    return $id;
}
