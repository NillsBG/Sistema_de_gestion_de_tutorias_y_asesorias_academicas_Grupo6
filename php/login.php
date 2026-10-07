<?php
// login.php - Nills Berducido Gómez (Líder de Integración, Lógica de Negocio & QA)
session_start();
header('Content-Type: application/json');
require_once 'conexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['exito' => false, 'mensaje' => 'Método no permitido']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

$username   = trim($input['username'] ?? $input['correo'] ?? '');
$contrasena = trim($input['contrasena'] ?? $input['clave'] ?? '');

if (empty($username) || empty($contrasena)) {
    http_response_code(400);
    echo json_encode(['exito' => false, 'mensaje' => 'Por favor, ingresa tu usuario y contraseña']);
    exit;
}

try {
    // Consulta a tblUsuarios uniendo Roles, Estudiante y Tutor
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
        WHERE (u.username = :username OR e.noCarnetCompleto = :username)
          AND u.estado = 'Activo'
        LIMIT 1
    ");
    $stmt->execute([':username' => $username]);
    $usuario = $stmt->fetch();

    // Verificación de contraseña (Soporta Hash PHP y Texto Plano para datos de prueba)
    $esValida = false;
    if ($usuario) {
        if (password_verify($contrasena, $usuario['contrasena']) || $contrasena === $usuario['contrasena']) {
            $esValida = true;
        }
    }

    if ($esValida) {
        // Variables de sesión según el rol asignado en la base de datos
        $_SESSION['idUsuario'] = $usuario['idUsuario'];
        $_SESSION['username']  = $usuario['username'];
        $_SESSION['rol']       = $usuario['nombreRol'];

        $nombreCompleto = $usuario['nombreEstudiante'] ?? $usuario['nombreTutor'] ?? $usuario['username'];
        $_SESSION['nombre']    = $nombreCompleto;

        if (strtolower($usuario['nombreRol']) === 'estudiante') {
            $_SESSION['noCarnetCompleto'] = $usuario['noCarnetCompleto'];
        } elseif (strtolower($usuario['nombreRol']) === 'tutor') {
            $_SESSION['idTutor'] = $usuario['idTutor'];
        }

        echo json_encode([
            'exito' => true,
            'mensaje' => 'Inicio de sesión exitoso',
            'usuario' => [
                'idUsuario'        => $usuario['idUsuario'],
                'username'         => $usuario['username'],
                'rol'              => $usuario['nombreRol'],
                'nombre'           => $nombreCompleto,
                'noCarnetCompleto' => $usuario['noCarnetCompleto'] ?? null,
                'idTutor'          => $usuario['idTutor'] ?? null
            ]
        ]);
    } else {
        http_response_code(401);
        echo json_encode(['exito' => false, 'mensaje' => 'Usuario o contraseña incorrectos']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['exito' => false, 'mensaje' => 'Error interno en el servidor']);
}
?>