<?php
// php/login.php - Autenticación por Usuario y Detección Automática de Rol
session_start();
header('Content-Type: application/json; charset=utf-8');

if (file_exists('../api/conexion.php')) {
    require_once '../api/conexion.php';
} elseif (file_exists('api/conexion.php')) {
    require_once 'api/conexion.php';
} else {
    require_once 'conexion.php';
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['exito' => false, 'mensaje' => 'Método no permitido']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

$usuarioIngresado = trim($input['usuario'] ?? $input['username'] ?? $input['login-usuario'] ?? $input['login-correo'] ?? '');
$clave             = trim($input['clave'] ?? $input['contrasena'] ?? $input['login-clave'] ?? '');

if (empty($usuarioIngresado) || empty($clave)) {
    echo json_encode(['exito' => false, 'mensaje' => 'Por favor ingresa tu usuario y contraseña.']);
    exit;
}

$userLimpio = (strpos($usuarioIngresado, '@') !== false) ? explode('@', $usuarioIngresado)[0] : $usuarioIngresado;

try {
    $stmt = $pdo->prepare("
        SELECT 
            u.idUsuario, 
            u.username, 
            u.contrasena, 
            u.estado,
            r.nombreRol,
            e.noCarnetCompleto,
            e.nombreEstudiante,
            t.idTutor,
            t.nombreTutor
        FROM tblUsuarios u
        INNER JOIN tblAsignacionRoles ar ON u.idUsuario = ar.idUsuario
        INNER JOIN tblRoles r ON ar.idRol = r.idRol
        LEFT JOIN tblEstudiantes e ON u.idUsuario = e.idUsuario
        LEFT JOIN tblTutores t ON u.idUsuario = t.idUsuario
        WHERE (u.username = ? OR u.username = ? OR e.noCarnetCompleto = ?)
          AND u.estado = 'Activo'
        LIMIT 1
    ");
    
    $stmt->execute([$userLimpio, $usuarioIngresado, $userLimpio]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    $esValida = false;
    if ($usuario) {
        if (password_verify($clave, $usuario['contrasena']) || $clave === $usuario['contrasena']) {
            $esValida = true;
        }
    }

    if (!$esValida) {
        echo json_encode(['exito' => false, 'mensaje' => 'Usuario o contraseña incorrectos.']);
        exit;
    }

    $rolRealDB = strtolower($usuario['nombreRol']);

    $_SESSION['idUsuario'] = $usuario['idUsuario'];
    $_SESSION['username']  = $usuario['username'];
    $_SESSION['rol']       = $usuario['nombreRol'];

    $nombreCompleto = $usuario['nombreEstudiante'] ?? $usuario['nombreTutor'] ?? $usuario['username'];
    $_SESSION['nombre']    = $nombreCompleto;

    if ($rolRealDB === 'estudiante') {
        $_SESSION['noCarnetCompleto'] = $usuario['noCarnetCompleto'];
    } elseif ($rolRealDB === 'tutor' || $rolRealDB === 'administrador') {
        $_SESSION['idTutor'] = $usuario['idTutor'];
    }

    echo json_encode([
        'exito' => true,
        'mensaje' => '¡Inicio de sesión exitoso!',
        'usuario' => [
            'idUsuario'        => $usuario['idUsuario'],
            'username'         => $usuario['username'],
            'rol'              => $usuario['nombreRol'],
            'nombre'           => $nombreCompleto,
            'noCarnetCompleto' => $usuario['noCarnetCompleto'] ?? null,
            'idTutor'          => $usuario['idTutor'] ?? null
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode(['exito' => false, 'mensaje' => 'Error en el servidor: ' . $e->getMessage()]);
}
?>