<?php
// php/login.php - Autenticación con Validación de Rol (Estudiante vs Tutor)
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

$correo = trim($input['correo'] ?? $input['username'] ?? $input['login-correo'] ?? '');
$clave  = trim($input['clave'] ?? $input['contrasena'] ?? $input['login-clave'] ?? '');
$rolSeleccionado = strtolower(trim($input['rol'] ?? ''));

if (empty($correo) || empty($clave)) {
    echo json_encode(['exito' => false, 'mensaje' => 'Por favor ingresa tu correo y contraseña.']);
    exit;
}

// Extraer usuario si ingresan correo completo (@umg.edu.gt)
$userLimpio = (strpos($correo, '@') !== false) ? explode('@', $correo)[0] : $correo;

try {
    // Consulta con 3 parámetros posicionales (?)
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
    
    $stmt->execute([$userLimpio, $correo, $userLimpio]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    // 1. Validar si el usuario existe y la contraseña es correcta
    $esValida = false;
    if ($usuario) {
        if (password_verify($clave, $usuario['contrasena']) || $clave === $usuario['contrasena']) {
            $esValida = true;
        }
    }

    if (!$esValida) {
        echo json_encode(['exito' => false, 'mensaje' => 'Correo o contraseña incorrectos.']);
        exit;
    }

    // 2. Validar que el rol seleccionado en el formulario coincida con el rol real en la base de datos
    $rolRealDB = strtolower($usuario['nombreRol']); // 'estudiante', 'tutor', 'administrador'

    if (!empty($rolSeleccionado)) {
        if ($rolSeleccionado === 'estudiante' && $rolRealDB !== 'estudiante') {
            echo json_encode([
                'exito' => false, 
                'mensaje' => '❌ Acceso denegado: Tu cuenta no es de perfil Estudiante. Selecciona el perfil "' . ucfirst($rolRealDB) . '" para ingresar.'
            ]);
            exit;
        }

        if ($rolSeleccionado === 'tutor' && $rolRealDB !== 'tutor' && $rolRealDB !== 'administrador') {
            echo json_encode([
                'exito' => false, 
                'mensaje' => '❌ Acceso denegado: Tu cuenta no tiene permisos de Tutor. Selecciona el perfil "' . ucfirst($rolRealDB) . '" para ingresar.'
            ]);
            exit;
        }
    }

    // 3. Inicio de sesión exitoso
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
